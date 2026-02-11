<?php
/**
 * メンバーメンテナンス画面 エントリ
 * 物理パス: haitaka/www/hinata/member_admin.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MemberController;

$controller = new MemberController();
$controller->admin();