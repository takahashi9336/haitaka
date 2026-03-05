<?php
/**
 * お知らせ保存 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\AnnouncementModel;

$auth = new Auth();
if (!$auth->check() || !$auth->isHinataAdmin()) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = !empty($input['id']) ? (int)$input['id'] : 0;

    $data = [
        'title'            => trim($input['title'] ?? ''),
        'body'             => trim($input['body'] ?? ''),
        'url'              => trim($input['url'] ?? '') ?: null,
        'image_url'        => trim($input['image_url'] ?? '') ?: null,
        'announcement_type'=> $input['announcement_type'] ?? 'other',
        'published_at'     => !empty($input['published_at']) ? str_replace('T', ' ', substr($input['published_at'], 0, 16)) . ':00' : null,
        'expires_at'       => !empty($input['expires_at']) ? str_replace('T', ' ', substr($input['expires_at'], 0, 16)) . ':00' : null,
        'sort_order'       => (int)($input['sort_order'] ?? 0),
        'is_active'        => !empty($input['is_active']) ? 1 : 0,
    ];

    if (empty($data['title'])) {
        throw new \Exception('タイトルは必須です');
    }

    $model = new AnnouncementModel();
    if ($id > 0) {
        $model->update($id, $data);
    } else {
        $model->create($data);
    }
    echo json_encode(['status' => 'success']);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
