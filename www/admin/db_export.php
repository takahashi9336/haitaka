<?php
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Admin\Controller\DbExportController;

(new DbExportController())->index();
