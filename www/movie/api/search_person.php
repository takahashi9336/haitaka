<?php
/**
 * TMDB人物検索 API
 * 物理パス: haitaka/www/movie/api/search_person.php
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use App\Movie\Model\TmdbApiClient;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $query = (string)($_GET['q'] ?? '');
    $page = (int)($_GET['page'] ?? 1);
    if (trim($query) === '') throw new \Exception('検索キーワードを入力してください');

    $tmdb = new TmdbApiClient();
    if (!$tmdb->isConfigured()) throw new \Exception('TMDB APIキーが設定されていません。.envにTMDB_API_KEYを追加してください。');

    $results = $tmdb->searchPersons($query, $page);
    if ($results === null) throw new \Exception('TMDB APIの通信に失敗しました');

    echo json_encode(['status' => 'success', 'data' => $results], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

