<?php
/**
 * 人物別：未登録映画ピックアップ API
 * 物理パス: haitaka/www/movie/api/person_pickup.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Movie\Controller\MovieController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$controller = new MovieController();
$controller->personPickupApi();

