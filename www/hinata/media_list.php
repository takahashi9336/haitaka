<?php
/**
 * 動画一覧エントリポイント
 * 物理パス: haitaka/www/hinata/media_list.php
 */

require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\MediaController;

$controller = new MediaController();
$controller->list();
