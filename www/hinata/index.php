<?php
/**
 * 日向坂ポータルへの入り口 (全体ポータルから遷移)
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Hinata\Controller\HinataController;

// ポータル画面を制御するコントローラを呼び出し
$controller = new HinataController();
$controller->portal();
