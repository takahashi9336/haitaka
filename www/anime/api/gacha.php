<?php
/**
 * アニメガチャ API（見たいリストからランダム1件）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();
$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = $_SESSION['user'];
$allowedIds = isset($_ENV['ANIME_BETA_ID_NAMES']) ? array_map('trim', explode(',', $_ENV['ANIME_BETA_ID_NAMES'])) : [];
if (!empty($allowedIds) && !in_array($user['id_name'] ?? '', $allowedIds, true)) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

$controller = new \App\Anime\Controller\AnimeController();
$controller->gachaApi();
