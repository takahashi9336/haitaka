<?php
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Admin\Controller\GuideController;

$controller = new GuideController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $controller->delete();
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if ($id !== null) {
    $controller->edit($id);
} elseif (isset($_GET['new'])) {
    $controller->edit(null);
} else {
    $controller->index();
}
