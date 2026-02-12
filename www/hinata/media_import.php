<?php
/**
 * メディア一括登録画面 エントリポイント
 * 物理パス: haitaka/www/hinata/media_import.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->import();
