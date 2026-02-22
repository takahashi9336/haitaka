<?php
/**
 * サムネイル画像アップロードAPI
 * POST (multipart/form-data): file, asset_id(任意)
 * asset_id がある場合は com_media_assets.thumbnail_url も更新
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}
if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルのアップロードに失敗しました']);
    exit;
}

$file = $_FILES['file'];
$mime = mime_content_type($file['tmp_name']);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!isset($allowed[$mime])) {
    echo json_encode(['status' => 'error', 'message' => '対応していない画像形式です (JPEG/PNG/WebP/GIF)']);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルサイズが5MBを超えています']);
    exit;
}

$assetId = (int)($_POST['asset_id'] ?? 0);
$ext = $allowed[$mime];
$prefix = $assetId ? ('thumb_' . $assetId) : ('thumb_new_' . bin2hex(random_bytes(4)));
$filename = $prefix . '_' . time() . '.' . $ext;
$uploadDir = __DIR__ . '/../../uploads/thumbnails/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルの保存に失敗しました']);
    exit;
}

$publicUrl = '/uploads/thumbnails/' . $filename;

try {
    if ($assetId) {
        $mediaModel = new \Core\MediaAssetModel();
        $mediaModel->update($assetId, ['thumbnail_url' => $publicUrl]);
    }
    echo json_encode(['status' => 'success', 'thumbnail_url' => $publicUrl]);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
