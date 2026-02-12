<?php
/**
 * メモ更新 API
 * 物理パス: haitaka/www/note/api/update.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Note\Controller\NoteController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$controller = new NoteController();
$controller->update();
