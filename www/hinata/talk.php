<?php
/**
 * ミーグリ・ネタ帳機能への入り口
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\TalkController;

// TalkController内の index() メソッドを呼び出す
$controller = new TalkController();
$controller->index();