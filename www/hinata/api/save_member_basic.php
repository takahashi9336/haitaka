<?php
/**
 * メンバー情報 一覧編集用簡易保存 API
 * 物理パス: haitaka/www/hinata/api/save_member_basic.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\MemberController;

$auth = new Auth();
if (
    !$auth->check()
    || !in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)
) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

$controller = new MemberController();
$controller->saveBasic();

