<?php
/**
 * Focus Note ウィークリーページ
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\FocusNote\Controller\FocusNoteController;

$controller = new FocusNoteController();
$controller->weekly();
