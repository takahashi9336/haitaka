<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Admin\Controller\AdminController;

(new AdminController())->index();
