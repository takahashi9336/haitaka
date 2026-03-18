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

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
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
    $model->create([
        'user_id' => (int)$user['id'],
        'app_key' => $appKey,
        'page_title' => $pageTitle,
        'source_url' => $sourceUrl,
        'category_hint' => $categoryHint,
        'note' => $note,
    ]);

    echo json_encode(['status' => 'success']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}

