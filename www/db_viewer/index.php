<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Admin\Controller\DbViewerController;

(new DbViewerController())->index();
