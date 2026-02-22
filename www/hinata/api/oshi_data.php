<?php
/**
 * 推しデータAPI（ポータル用サマリ）
 * 物理パス: haitaka/www/hinata/api/oshi_data.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\OshiController;

$auth = new Auth();
$auth->requireLogin();

$controller = new OshiController();
$controller->oshiData();
