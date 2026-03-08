<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Admin\Controller\ImprovementController;

$controller = new ImprovementController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'delete') {
        $controller->delete();
        exit;
    }
    if ($action === 'create') {
        $controller->create();
        exit;
    }
    if ($action === 'update') {
        $controller->update();
        exit;
    }
}

$controller->index();
