<?php
/**
 * メモ一覧画面
 * 物理パス: haitaka/www/note/index.php
 */
require_once __DIR__ . '/../../private/vendor/autoload.php';

use App\Note\Controller\NoteController;

$controller = new NoteController();
$controller->index();
