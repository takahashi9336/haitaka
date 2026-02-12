<?php
/**
 * 動画一覧追加ロードAPI
 * 物理パス: haitaka/www/hinata/api/load_more_media.php
 */

require_once __DIR__ . '/../../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->loadMore();
