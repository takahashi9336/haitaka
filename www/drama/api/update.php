<?php
/**
 * ドラマエントリ更新 API
 * 物理パス: haitaka/www/drama/api/update.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Drama\Controller\DramaController;

$auth = new Auth();
$auth->requireLogin();

$controller = new DramaController();
$controller->updateEntry();

