<?php
/**
 * ポータル情報管理 エントリポイント
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\PortalInfoController;

$controller = new PortalInfoController();
$controller->admin();
