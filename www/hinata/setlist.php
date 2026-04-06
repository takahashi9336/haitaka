<?php
/**
 * LIVEセットリスト表示 エントリ
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\SetlistController;

$controller = new SetlistController();
$controller->show();

