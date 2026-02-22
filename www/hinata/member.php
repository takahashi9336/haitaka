<?php
/**
 * メンバー個別ページ
 * 物理パス: haitaka/www/hinata/member.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\OshiController;

$controller = new OshiController();
$controller->memberPage();
