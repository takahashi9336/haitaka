<?php
/**
 * 推し画像削除API
 * POST JSON: { id: int }
 * 物理パス: haitaka/www/hinata/api/oshi_image_delete.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Model\OshiImageModel;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$imageId = (int)($input['id'] ?? 0);

if (!$imageId) {
    echo json_encode(['status' => 'error', 'message' => 'id が必要です']);
    exit;
}

$model = new OshiImageModel();
$deleted = $model->deleteImage($imageId);

if ($deleted) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => '削除に失敗しました']);
}
