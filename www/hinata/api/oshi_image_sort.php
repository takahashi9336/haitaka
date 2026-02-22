<?php
/**
 * 推し画像並び順変更API
 * POST JSON: { order: [id1, id2, id3, ...] }
 * 物理パス: haitaka/www/hinata/api/oshi_image_sort.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use Core\Database;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['order'] ?? [];

if (empty($order) || !is_array($order)) {
    echo json_encode(['status' => 'error', 'message' => 'order 配列が必要です']);
    exit;
}

$pdo = Database::connect();
$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("UPDATE hn_oshi_images SET sort_order = :sort WHERE id = :id AND user_id = :uid");
foreach ($order as $sortOrder => $id) {
    $stmt->execute(['sort' => $sortOrder, 'id' => (int)$id, 'uid' => $userId]);
}

echo json_encode(['status' => 'success']);
