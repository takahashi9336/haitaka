<?php
/**
 * Focus Note ウィークリーページ
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\FocusNote\Controller\FocusNoteController;

$controller = new FocusNoteController();
$controller->weekly();
