<?php
/**
 * ネタのステータス(完了/未完了)のみを更新するAPI
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\TalkController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$controller = new TalkController();
// Controllerのステータス更新メソッドを実行
$controller->updateStatus();