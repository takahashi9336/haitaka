<?php
/**
 * アニメダッシュボード
 * 物理パス: haitaka/www/anime/index.php
 * .env の ANIME_BETA_ID_NAMES に id_name が含まれるユーザーのみアクセス可能（本展開前）
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();

$auth = new Auth();
$auth->requireLogin();

$controller = new \App\Anime\Controller\AnimeController();
$controller->dashboard();
