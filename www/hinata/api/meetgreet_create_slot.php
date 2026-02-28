<?php
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Controller\MeetGreetController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    (new MeetGreetController())->createSlotApi();
}
