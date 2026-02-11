<?php
/**
 * イベント削除 API
 * 物理パス: haitaka/www/hinata/api/delete_event.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\EventController;

$auth = new Auth();
if (!$auth->check() || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
    exit;
}

$controller = new EventController();
$controller->delete();