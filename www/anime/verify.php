<?php
/**
 * Annict API レスポンス検証画面
 * .env の ANIME_BETA_ID_NAMES に id_name が含まれるユーザーのみアクセス可能
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();

$auth = new Auth();
$auth->requireLogin();

$rawResponse = null;
$error = null;
$id = trim($_GET['id'] ?? '');
$q = trim($_GET['q'] ?? '');

if ($id !== '' || $q !== '') {
    $userId = (int)($user['id'] ?? 0);
    $client = new \App\Anime\Model\AnnictApiClient($userId);

    $params = ['per_page' => 20];
    if ($id !== '') {
        $params['filter_ids'] = $id;
    }
    if ($q !== '') {
        $params['filter_title'] = $q;
    }

    $rawResponse = $client->getWorksRaw($params);

    if ($rawResponse === null) {
        $error = 'Annict API の取得に失敗しました。';
    } elseif (empty($rawResponse['works'])) {
        $error = '該当する作品が見つかりませんでした。';
    }
}

require_once __DIR__ . '/../../private/apps/Anime/Views/anime_verify.php';
