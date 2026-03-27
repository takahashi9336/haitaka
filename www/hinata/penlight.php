<?php
/**
 * ペンライトカラー表（初心者向け）エントリ
 * 物理パス: haitaka/www/hinata/penlight.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\PenlightController;

$controller = new PenlightController();
$controller->index();

