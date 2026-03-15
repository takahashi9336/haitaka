<?php
/**
 * ドラマ一括登録 API
 * 物理パス: haitaka/www/drama/api/bulk_add.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use App\Drama\Controller\DramaController;

$controller = new DramaController();
$controller->bulkAdd();

