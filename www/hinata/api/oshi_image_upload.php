<?php
/**
 * 推し画像アップロードAPI
 * POST multipart/form-data: image (file), member_id (int), caption (optional)
 * 物理パス: haitaka/www/hinata/api/oshi_image_upload.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Model\OshiImageModel;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$memberId = (int)($_POST['member_id'] ?? 0);
$caption = $_POST['caption'] ?? null;

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

$model = new OshiImageModel();

if ($model->countByMember($memberId) >= OshiImageModel::MAX_IMAGES_PER_MEMBER) {
    echo json_encode(['status' => 'error', 'message' => '1メンバーあたり最大' . OshiImageModel::MAX_IMAGES_PER_MEMBER . '枚です']);
    exit;
}

$uploadDir = $model->getUploadDir($memberId);
$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    default => 'jpg',
};
$filename = uniqid('oshi_', true) . '.' . $ext;
$relPath = $uploadDir . '/' . $filename;

$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? (dirname(__DIR__, 3));
$absPath = $docRoot . '/' . $relPath;

if (!OshiImageModel::resizeImage($file['tmp_name'], $absPath, $mime)) {
    echo json_encode(['status' => 'error', 'message' => '画像の処理に失敗しました']);
    exit;
}

$imageId = $model->saveImage($memberId, $relPath, $caption);

echo json_encode([
    'status' => 'success',
    'id' => $imageId,
    'image_path' => $relPath,
], JSON_UNESCAPED_UNICODE);
