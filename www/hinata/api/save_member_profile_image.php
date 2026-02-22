<?php
/**
 * メンバープロフィール画像の変更API（ユーザ個別）
 * POST multipart/form-data: image (file), member_id (int)
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use Core\Database;
use App\Hinata\Model\OshiImageModel;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$memberId = (int)($_POST['member_id'] ?? 0);
if (!$memberId) {
    echo json_encode(['status' => 'error', 'message' => 'member_id が必要です']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルのアップロードに失敗しました']);
    exit;
}

$file = $_FILES['image'];
$mime = mime_content_type($file['tmp_name']);

if (!in_array($mime, OshiImageModel::ALLOWED_TYPES, true)) {
    echo json_encode(['status' => 'error', 'message' => '対応形式: JPEG, PNG, WebP']);
    exit;
}

$userId = $_SESSION['user']['id'] ?? 0;
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
$relDir = 'uploads/member_profile/' . $userId;
$absDir = $docRoot . '/' . $relDir;
if (!is_dir($absDir)) {
    @mkdir($absDir, 0755, true);
}

$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    default => 'jpg',
};
$filename = 'member_' . $memberId . '_' . uniqid() . '.' . $ext;
$relPath = $relDir . '/' . $filename;
$absPath = $docRoot . '/' . $relPath;

if (!OshiImageModel::resizeImage($file['tmp_name'], $absPath, $mime)) {
    echo json_encode(['status' => 'error', 'message' => '画像の処理に失敗しました']);
    exit;
}

$pdo = Database::connect();

$existing = $pdo->prepare("SELECT image_path FROM hn_user_member_profiles WHERE user_id = ? AND member_id = ?");
$existing->execute([$userId, $memberId]);
$old = $existing->fetchColumn();
if ($old) {
    $oldAbs = $docRoot . '/' . $old;
    if (file_exists($oldAbs)) @unlink($oldAbs);
}

$stmt = $pdo->prepare(
    "INSERT INTO hn_user_member_profiles (user_id, member_id, image_path)
     VALUES (:uid, :mid, :path)
     ON DUPLICATE KEY UPDATE image_path = :path2, updated_at = NOW()"
);
$stmt->execute([
    'uid' => $userId,
    'mid' => $memberId,
    'path' => $relPath,
    'path2' => $relPath,
]);

echo json_encode([
    'status' => 'success',
    'image_path' => $relPath,
], JSON_UNESCAPED_UNICODE);
