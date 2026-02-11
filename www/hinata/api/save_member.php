<?php
/**
 * メンバー情報保存 API
 * 物理パス: haitaka/www/hinata/api/save_member.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\MemberController;

$auth = new Auth();
if (!$auth->check() || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json', true, 403);
    exit;
}

$controller = new MemberController();
$controller->save();