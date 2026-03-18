<?php
/**
 * 今期アニメ一覧 API
 * 物理パス: haitaka/www/anime/api/current_season.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Anime\Controller\AnimeController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$controller = new AnimeController();
$controller->currentSeasonApi();

