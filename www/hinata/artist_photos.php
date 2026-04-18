<?php
/**
 * アー写一覧（閲覧） エントリ
 * 物理パス: haitaka/www/hinata/artist_photos.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use App\Hinata\Controller\ArtistPhotoController;

$controller = new ArtistPhotoController();
$controller->index();

