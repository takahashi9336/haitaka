<?php
/**
 * メンバー図鑑 エントリ
 * 物理パス: haitaka/www/hinata/members.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MemberController;

$controller = new MemberController();

// アクション分岐（詳細取得APIか、画面表示か）
if (isset($_GET['action']) && $_GET['action'] === 'detail') {
    $controller->detail();
} else {
    $controller->index();
}