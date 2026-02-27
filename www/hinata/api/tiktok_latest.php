<?php
/**
 * TikTok最新動画取得API
 * GET (パラメータなし)
 * 物理パス: haitaka/www/hinata/api/tiktok_latest.php
 *
 * com_media_assets から platform='tiktok' の最新15件を返す
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

$auth = new Auth();
$auth->requireLogin();

header('Content-Type: application/json');

$pdo = Database::connect();

$limit = 15;
$sql = "SELECT media_key, sub_key, title, thumbnail_url, upload_date, description
        FROM com_media_assets
        WHERE platform = 'tiktok'
        ORDER BY upload_date DESC
        LIMIT :limit";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($videos)) {
    echo json_encode(['status' => 'success', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['status' => 'success', 'data' => $videos], JSON_UNESCAPED_UNICODE);
