<?php

require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\SenseLab\Model\SenseQuickEntryModel;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = new Auth();
    $auth->requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        exit;
    }

    $user = $_SESSION['user'] ?? null;
    if (!$user || empty($user['id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $data = [];
    $hasFile = false;
    if (!empty($_FILES)) {
        $data = $_POST;
        $hasFile = isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    } else {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }
    }

    $note = trim($data['note'] ?? '');
    if ($note === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'note is required']);
        exit;
    }

    $appKey = isset($data['app_key']) ? trim((string)$data['app_key']) : null;
    $pageTitle = isset($data['page_title']) ? trim((string)$data['page_title']) : null;
    $sourceUrl = isset($data['source_url']) ? trim((string)$data['source_url']) : null;
    $categoryHint = isset($data['category_hint']) ? trim((string)$data['category_hint']) : null;

    if ($appKey === '') {
        $appKey = null;
    }
    if ($pageTitle === '') {
        $pageTitle = null;
    }
    if ($sourceUrl === '') {
        $sourceUrl = null;
    }
    if ($categoryHint === '') {
        $categoryHint = null;
    }

    $model = new SenseQuickEntryModel();
    $imagePath = null;
    if ($hasFile) {
        $file = $_FILES['image'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => '画像アップロードに失敗しました。']);
            exit;
        }

        $maxSize = 2 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => '画像サイズは2MB以内にしてください。']);
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        if (!isset($allowed[$mime])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => '許可されている画像形式は JPG/PNG/GIF のみです。']);
            exit;
        }

        $uploadDir = dirname(__DIR__, 4) . '/www/uploads/sense_lab';
        $uploadUrlBase = '/uploads/sense_lab';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $ext = $allowed[$mime];
        $basename = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $filename = $basename . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => '画像ファイルの保存に失敗しました。']);
            exit;
        }
        $imagePath = $uploadUrlBase . '/' . $filename;
    }

    $quickEntryId = $model->create([
        'user_id' => (int)$user['id'],
        'app_key' => $appKey,
        'page_title' => $pageTitle,
        'source_url' => $sourceUrl,
        'category_hint' => $categoryHint,
        'note' => $note,
        'image_path' => $imagePath,
    ]);

    echo json_encode(['status' => 'success', 'quick_entry_id' => $quickEntryId]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}

