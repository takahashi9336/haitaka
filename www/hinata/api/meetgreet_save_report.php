<?php
/**
 * ミーグリ レポ保存API
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\MeetGreetController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$controller = new MeetGreetController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->saveReport();
}
