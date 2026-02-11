<?php
require_once __DIR__ . '/../../../private/vendor/autoload.php';
use Core\Auth;
use App\Hinata\Controller\TalkController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

(new TalkController())->delete();