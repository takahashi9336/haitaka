<?php
/**
 * リリース詳細（公開）エントリ
 * 物理パス: haitaka/www/hinata/release.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\ReleaseController;

$controller = new ReleaseController();
$controller->show();
