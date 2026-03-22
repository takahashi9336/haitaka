<?php
/**
 * 友人の視聴一覧（もっと見る）
 */
require_once __DIR__ . '/../private/bootstrap.php';

use App\FriendsActivity\Controller\FriendsActivityController;

$controller = new FriendsActivityController();
$controller->activity();
