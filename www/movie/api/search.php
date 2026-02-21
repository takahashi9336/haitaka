<?php
/**
 * TMDB映画検索 API
 * 物理パス: haitaka/www/movie/api/search.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Movie\Controller\MovieController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$controller = new MovieController();
$controller->search();
