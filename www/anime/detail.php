<?php
/**
 * アニメ作品詳細
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();
$auth = new Auth();
$auth->requireLogin();

$controller = new \App\Anime\Controller\AnimeController();
$controller->detail();
