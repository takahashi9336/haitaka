<?php
/**
 * メンバー個人活動 削除API
 * 物理パス: haitaka/www/hinata/api/delete_member_activity.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Hinata\Controller\MemberController;

$auth = new Auth();
if (
    !$auth->check()
    || !in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)
) {
    header('Content-Type: application/json', true, 403);
    exit;
}

$controller = new MemberController();
$controller->deleteActivity();
