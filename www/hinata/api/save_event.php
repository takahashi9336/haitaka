<?php
/**
 * イベント情報保存 API
 * 物理パス: haitaka/www/hinata/api/save_event.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Controller\EventController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    exit;
}

$controller = new EventController();
$controller->save();