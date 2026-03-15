<?php
/**
 * ドラマ削除 API
 * 物理パス: haitaka/www/drama/api/remove.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Drama\Controller\DramaController;

$auth = new Auth();
$auth->requireLogin();

$controller = new DramaController();
$controller->remove();

