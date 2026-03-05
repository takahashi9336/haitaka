<?php
/**
 * 目標・行動目標設定フォーム
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\FocusNote\Controller\FocusNoteController;

$controller = new FocusNoteController();
$controller->goalSettingForm();
