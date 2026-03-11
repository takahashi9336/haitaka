<?php
/**
 * アニメ作品詳細
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();
$auth = new Auth();
$auth->requireLogin();

$user = $_SESSION['user'];
$allowedIds = isset($_ENV['ANIME_BETA_ID_NAMES']) ? array_map('trim', explode(',', $_ENV['ANIME_BETA_ID_NAMES'])) : [];
if (empty($allowedIds) || !in_array($user['id_name'] ?? '', $allowedIds, true)) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: /');
    exit;
}

$controller = new \App\Anime\Controller\AnimeController();
$controller->detail();
