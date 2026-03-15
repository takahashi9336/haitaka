<?php
/**
 * エンタメダッシュボード フロントコントローラ
 * 物理パス: haitaka/www/entame/index.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Entame\Controller\EntameController;

$controller = new EntameController();
$controller->dashboard();

