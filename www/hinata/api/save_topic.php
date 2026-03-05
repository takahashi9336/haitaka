<?php
/**
 * トピック保存 API
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Model\TopicModel;

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
        'title'     => trim($input['title'] ?? ''),
        'summary'   => trim($input['summary'] ?? ''),
        'url'       => trim($input['url'] ?? '') ?: null,
        'image_url' => trim($input['image_url'] ?? '') ?: null,
        'topic_type'=> $input['topic_type'] ?? 'other',
        'start_date'=> !empty($input['start_date']) ? $input['start_date'] : null,
        'end_date'  => !empty($input['end_date']) ? $input['end_date'] : null,
        'sort_order'=> (int)($input['sort_order'] ?? 0),
        'is_active' => !empty($input['is_active']) ? 1 : 0,
    ];

    if (empty($data['title'])) {
        throw new \Exception('タイトルは必須です');
    }

    $model = new TopicModel();
    if ($id > 0) {
        $model->update($id, $data);
    } else {
        $model->create($data);
    }
    echo json_encode(['status' => 'success']);
} catch (\Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
