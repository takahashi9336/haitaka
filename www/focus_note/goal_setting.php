<?php
/**
 * Focus Note 目標設定の考え方
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\FocusNote\Controller\FocusNoteController;

$controller = new FocusNoteController();
$controller->goalSetting();
