<?php
/**
 * LIVEセットリスト編集 エントリ（admin / hinata_admin）
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\SetlistController;

$controller = new SetlistController();
$controller->edit();

