<?php
/**
 * Focus Note マンスリーページ
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\FocusNote\Controller\FocusNoteController;

$controller = new FocusNoteController();
$controller->monthly();
