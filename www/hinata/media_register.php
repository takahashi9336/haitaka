<?php
/**
 * メディア登録画面 エントリポイント
 * 物理パス: haitaka/www/hinata/media_register.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->registerPage();
