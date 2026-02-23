<?php
/**
 * TaskManager Entry Point
 */
require_once __DIR__ . '/../../private/bootstrap.php';


use App\TaskManager\Controller\TaskController;

$controller = new TaskController();
$controller->index();