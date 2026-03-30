<?php

namespace App\Drama\Controller;

use App\Drama\Model\DramaModel;
use App\Drama\Model\UserDramaModel;
use App\Drama\Model\TmdbTvApiClient;
use App\Movie\Model\TmdbApiClient as MovieTmdbApiClient;
use Core\Auth;
use Core\Logger;

class DramaController {
    private const REC_CACHE_TTL = 21600; // 6時間
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    private function getRecCachePath(string $type, int $userId): string {
        $dir = __DIR__ . '/../../../cache/drama_rec';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if ($type === 'trending') {
            return $dir . '/trending.json';
        }
        return $dir . "/{$type}_{$userId}.json";
    }

    private function readRecCache(string $path): ?array {
        if (!file_exists($path)) return null;
        $mtime = filemtime($path);
        if (time() - $mtime > self::REC_CACHE_TTL) {
            @unlink($path);
            return null;
        }
        $data = @file_get_contents($path);
        if ($data === false) return null;
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function writeRecCache(string $path, array $data): void {
        @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function dashboard(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];

        try {
            $userDramaModel = new UserDramaModel();
            $wannaWatchCount = $userDramaModel->countByStatus('wanna_watch', null);
            $watchingCount = $userDramaModel->countByStatus('watching', null);
            $watchedCount = $userDramaModel->countByStatus('watched', null);
            $thisMonthCount = $userDramaModel->getThisMonthWatchedCount();
            $totalRuntime = $userDramaModel->getTotalWatchedRuntime();
            $monthlyWatchCounts = $userDramaModel->getMonthlyWatchCounts(12);
            $genreDistribution = $userDramaModel->getGenreDistribution();
            $ratingDistribution = $userDramaModel->getRatingDistribution();

            $avgRating = 0;
            $ratedCount = array_sum($ratingDistribution);
            if ($ratedCount > 0) {
                $sum = 0;
                foreach ($ratingDistribution as $score => $cnt) {
                    $sum += $score * $cnt;
                }
                $avgRating = round($sum / $ratedCount, 1);
            }
        } catch (\Exception $e) {
            Logger::errorWithContext('Drama dashboard error', $e);
            $wannaWatchCount = $watchingCount = $watchedCount = $thisMonthCount = $totalRuntime = $avgRating = $ratedCount = 0;
            $monthlyWatchCounts = [];
            $genreDistribution = $ratingDistribution = [];
        }

        $tmdb = new TmdbTvApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/drama_dashboard.php';
    }

    /**
     * ガチャAPI（ランダムに見たいドラマ1件返却）
     */
    public function gachaApi(): void {
        header('Content-Type: application/json');
        try {
            $userDramaModel = new UserDramaModel();
            $item = $userDramaModel->getRandomWannaWatchItem();
            if (!$item) {
                echo json_encode(['status' => 'empty', 'message' => '見たいリストが空です'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(['status' => 'success', 'data' => $item], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 一括登録画面
     */
    public function import(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        $tmdb = new TmdbTvApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/drama_import.php';
    }

    public function index(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        $tab = $_GET['tab'] ?? 'wanna_watch';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        $filter = $_GET['filter'] ?? '';

        if (!in_array($tab, ['wanna_watch', 'watching', 'watched'], true)) {
            $tab = 'wanna_watch';
        }

        try {
            $userDramaModel = new UserDramaModel();
            $wannaWatchCount = $userDramaModel->countByStatus('wanna_watch', null);
            $watchingCount = $userDramaModel->countByStatus('watching', null);
            $watchedCount = $userDramaModel->countByStatus('watched', null);
            $series = $userDramaModel->getListByStatus($tab, $sort, $order, $filter, null);
        } catch (\Exception $e) {
            Logger::errorWithContext('Drama list error', $e);
            $wannaWatchCount = $watchingCount = $watchedCount = 0;
            $series = [];
        }

        $tmdb = new TmdbTvApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/drama_list.php';
    }

    public function detail(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /drama/list.php');
            exit;
        }

        try {
            $userDramaModel = new UserDramaModel();
            $series = $userDramaModel->getDetailWithSeries($id);
            if (!$series) {
                header('Location: /drama/list.php');
                exit;
            }

            $tmdb = new TmdbTvApiClient();
            $dramaModel = new DramaModel();
            if ($tmdb->isConfigured() && !empty($series['tmdb_id'])) {
                $tmdbDetail = $tmdb->getSeriesDetail((int)$series['tmdb_id']);
                if ($tmdbDetail) {
                    $dramaModel->updateFromTmdb((int)$series['series_id'], $tmdbDetail);
                    $series['overview'] = $tmdbDetail['overview'] ?? $series['overview'];
                    if (!empty($tmdbDetail['genres'])) {
                        $series['genres'] = json_encode(
                            array_map(fn($g) => $g['name'], $tmdbDetail['genres']),
                            JSON_UNESCAPED_UNICODE
                        );
                    }
                    $series['credits'] = $tmdbDetail['credits'] ?? null;
                    $wpAll = $tmdbDetail['watch/providers']['results'] ?? [];
                    $wpJP = $wpAll['JP'] ?? null;
                    if ($wpJP) {
                        $dramaModel->updateWatchProviders((int)$series['series_id'], $wpJP);
                        $series['watch_providers'] = json_encode($wpJP, JSON_UNESCAPED_UNICODE);
                        $series['watch_providers_updated_at'] = date('Y-m-d H:i:s');
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::errorWithContext('Drama detail error', $e);
            header('Location: /drama/list.php');
            exit;
        }

        require_once __DIR__ . '/../Views/drama_detail.php';
    }

    public function searchPage(): void {
        $this->auth->requireLogin();

        $query = $_GET['q'] ?? '';

        $tmdb = new TmdbTvApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/drama_search.php';
    }

    public function search(): void {
        header('Content-Type: application/json');

        try {
            $query = $_GET['q'] ?? '';
            $page = (int)($_GET['page'] ?? 1);

            if (empty(trim($query))) {
                throw new \Exception('検索キーワードを入力してください');
            }

            $tmdb = new TmdbTvApiClient();
            if (!$tmdb->isConfigured()) {
                throw new \Exception('TMDB APIキーが設定されていません。.envにTMDB_API_KEYを追加してください。');
            }

            $results = $tmdb->searchSeries($query, $page);
            if ($results === null) {
                throw new \Exception('TMDB APIの通信に失敗しました');
            }

            $userDramaModel = new UserDramaModel();
            foreach ($results['results'] as &$series) {
                $userEntry = $userDramaModel->findByTmdbId((int)$series['id']);
                $series['user_status'] = $userEntry ? $userEntry['status'] : null;
                $series['user_series_id'] = $userEntry ? $userEntry['id'] : null;
            }

            echo json_encode([
                'status' => 'success',
                'data' => $results,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * おすすめドラマ API（6時間キャッシュ付き）
     */
    public function recommendationsApi(): void {
        header('Content-Type: application/json');
        try {
            $type = $_GET['type'] ?? '';
            if (!in_array($type, ['personal', 'genre', 'trending'], true)) {
                echo json_encode(['status' => 'error', 'message' => '不正なtypeパラメータ'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $userDramaModel = new UserDramaModel();
            $tmdb = new TmdbTvApiClient();
            if (!$tmdb->isConfigured()) {
                echo json_encode(['status' => 'error', 'message' => 'TMDB未設定'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $cachePath = $this->getRecCachePath($type, $userId);
            $cached = $this->readRecCache($cachePath);

            if ($cached !== null) {
                $this->applyRegisteredFlags($cached, $userDramaModel);
                echo json_encode($cached, JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = $this->fetchRecommendations($type, $userDramaModel, $tmdb);
            if ($result !== null) {
                $this->writeRecCache($cachePath, $result);
            }

            echo json_encode($result ?? ['status' => 'empty'], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Logger::errorWithContext('Drama recommendations API error', $e);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function applyRegisteredFlags(array &$response, UserDramaModel $userDramaModel): void {
        $existingSet = array_flip($userDramaModel->getAllTmdbIds());
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as &$s) {
                $id = $s['id'] ?? null;
                if ($id === null) continue;
                $s['_registered'] = isset($existingSet[$id]);
            }
        }
    }

    private function fetchRecommendations(string $type, UserDramaModel $userDramaModel, TmdbTvApiClient $tmdb): ?array {
        $existingSet = array_flip($userDramaModel->getAllTmdbIds());
        $seriesList = [];

        switch ($type) {
            case 'personal':
                $topIds = $userDramaModel->getTopRatedTmdbIds(5);
                if (empty($topIds)) {
                    return ['status' => 'empty', 'message' => '高評価のドラマがありません'];
                }

                $seen = [];
                foreach ($topIds as $tmdbId) {
                    $result = $tmdb->getRecommendations((int)$tmdbId);
                    if (!$result || empty($result['results'])) continue;
                    foreach ($result['results'] as $s) {
                        $id = $s['id'] ?? null;
                        if ($id === null || isset($seen[$id])) continue;
                        $seen[$id] = true;
                        $s['_registered'] = isset($existingSet[$id]);
                        $seriesList[] = $s;
                    }
                }
                usort($seriesList, fn($a, $b) => ($b['vote_average'] ?? 0) <=> ($a['vote_average'] ?? 0));
                $seriesList = array_filter($seriesList, fn($s) => empty($s['_registered']));
                $seriesList = array_slice(array_values($seriesList), 0, 20);
                return ['status' => 'success', 'data' => $seriesList];

            case 'genre':
                $genreNames = $userDramaModel->getTopGenreNames(3);
                if (empty($genreNames)) {
                    return ['status' => 'empty', 'message' => 'ジャンルデータがありません'];
                }

                $genreIds = [];
                foreach ($genreNames as $name) {
                    $gid = MovieTmdbApiClient::genreNameToId($name);
                    if ($gid) {
                        $genreIds[] = $gid;
                    }
                }
                if (empty($genreIds)) {
                    return ['status' => 'empty', 'message' => 'ジャンルマッピングなし'];
                }

                $result = $tmdb->discoverByGenres($genreIds);
                if ($result && !empty($result['results'])) {
                    foreach ($result['results'] as $s) {
                        $id = $s['id'] ?? null;
                        if ($id === null) continue;
                        $s['_registered'] = isset($existingSet[$id]);
                        if (!$s['_registered']) {
                            $seriesList[] = $s;
                        }
                    }
                }
                $seriesList = array_slice($seriesList, 0, 20);
                return ['status' => 'success', 'genres' => $genreNames, 'data' => $seriesList];

            case 'trending':
                $result = $tmdb->getTrending('week');
                if ($result && !empty($result['results'])) {
                    foreach ($result['results'] as $s) {
                        $id = $s['id'] ?? null;
                        if ($id === null) continue;
                        $s['_registered'] = isset($existingSet[$id]);
                        $seriesList[] = $s;
                    }
                }
                $seriesList = array_slice($seriesList, 0, 20);
                return ['status' => 'success', 'data' => $seriesList];
        }

        return null;
    }

    /**
     * 一括登録 API（プレビュー結果を受け取って一括でDB登録）
     */
    public function bulkAdd(): void {
        header('Content-Type: application/json');

        try {
            $this->auth->requireLogin();

            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $items = $input['items'] ?? [];
            if (empty($items)) {
                throw new \Exception('登録するドラマがありません');
            }

            $dramaModel = new DramaModel();
            $userDramaModel = new UserDramaModel();
            $tmdb = new TmdbTvApiClient();
            $added = 0;
            $skipped = 0;
            $errors = [];

            foreach ($items as $item) {
                try {
                    $status = $item['status'] ?? 'wanna_watch';
                    if (!in_array($status, ['wanna_watch', 'watching', 'watched'], true)) {
                        $status = 'wanna_watch';
                    }

                    $series = null;
                    $tmdbId = $item['tmdb_id'] ?? null;

                    if ($tmdbId && $tmdbId > 0) {
                        $existing = $dramaModel->findByTmdbId((int)$tmdbId);
                        if ($existing) {
                            $series = $existing;
                        } else {
                            $tmdbData = null;
                            if ($tmdb->isConfigured()) {
                                $tmdbData = $tmdb->getSeriesDetail((int)$tmdbId);
                            }
                            if ($tmdbData) {
                                $series = $dramaModel->findOrCreateByTmdbId($tmdbData);
                            }
                        }
                    }

                    if (!$series) {
                        $title = trim($item['title'] ?? '');
                        if ($title === '') {
                            continue;
                        }
                        $existing = $dramaModel->findPlaceholderByTitle($title);
                        $series = $existing ?: $dramaModel->createPlaceholder($title);
                    }

                    $existingEntry = $userDramaModel->findBySeriesId((int)$series['id']);
                    if ($existingEntry) {
                        $skipped++;
                        continue;
                    }

                    $userDramaModel->addSeries((int)$series['id'], $status);
                    $added++;
                } catch (\Exception $e) {
                    $errors[] = ($item['title'] ?? '不明') . ': ' . $e->getMessage();
                }
            }

            $msg = "{$added}件を登録しました";
            if ($skipped > 0) {
                $msg .= "（{$skipped}件は登録済のためスキップ）";
            }

            echo json_encode([
                'status' => 'success',
                'message' => $msg,
                'data' => ['added' => $added, 'skipped' => $skipped, 'errors' => $errors],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function tmdbDetailApi(): void {
        header('Content-Type: application/json');
        try {
            $tmdbId = (int)($_GET['tmdb_id'] ?? 0);
            if ($tmdbId <= 0) throw new \Exception('tmdb_id is required');

            $tmdb = new TmdbTvApiClient();
            if (!$tmdb->isConfigured()) throw new \Exception('TMDB API not configured');

            $detail = $tmdb->getSeriesDetail($tmdbId);
            if (!$detail) throw new \Exception('TMDB API error');

            $jp = $detail['watch/providers']['results']['JP'] ?? null;
            $providers = null;
            if ($jp) {
                $providers = [
                    'flatrate' => $jp['flatrate'] ?? [],
                    'rent'     => $jp['rent'] ?? [],
                    'buy'      => $jp['buy'] ?? [],
                    'link'     => $jp['link'] ?? null,
                ];
            }

            $userDramaModel = new UserDramaModel();
            $userEntry = $userDramaModel->findByTmdbId($tmdbId);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'providers'  => $providers,
                    'user_status'   => $userEntry ? $userEntry['status'] : null,
                    'user_series_id' => $userEntry ? $userEntry['id'] : null,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    public function add(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $tmdbId = (int)($input['tmdb_id'] ?? 0);
            $status = $input['status'] ?? 'wanna_watch';

            if ($tmdbId <= 0) {
                throw new \Exception('作品IDが不正です');
            }
            if (!in_array($status, ['wanna_watch', 'watching', 'watched'], true)) {
                throw new \Exception('ステータスが不正です');
            }

            $tmdb = new TmdbTvApiClient();
            $tmdbData = $input['tmdb_data'] ?? null;

            if (!$tmdbData && $tmdb->isConfigured()) {
                $tmdbData = $tmdb->getSeriesDetail($tmdbId);
            }
            if (!$tmdbData) {
                throw new \Exception('TMDBから作品情報を取得できませんでした');
            }

            $dramaModel = new DramaModel();
            $series = $dramaModel->findOrCreateByTmdbId($tmdbData);

            $userDramaModel = new UserDramaModel();
            $result = $userDramaModel->addSeries((int)$series['id'], $status);

            if ($result) {
                $userEntry = $userDramaModel->findBySeriesId((int)$series['id']);
                echo json_encode([
                    'status' => 'success',
                    'message' => $status === 'wanna_watch' ? '見たいリストに追加しました' : '登録しました',
                    'data' => ['id' => $userEntry['id'] ?? null, 'status' => $status],
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('追加に失敗しました');
            }
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateEntry(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('IDが指定されていません');
            }

            $userDramaModel = new UserDramaModel();

            if (isset($input['status'])) {
                $status = $input['status'];
                if ($status === 'watched') {
                    $result = $userDramaModel->markAsWatched(
                        $id,
                        $input['watched_date'] ?? null,
                        isset($input['rating']) ? (int)$input['rating'] : null,
                        $input['memo'] ?? null
                    );
                } elseif ($status === 'wanna_watch') {
                    $result = $userDramaModel->moveToWannaWatch($id);
                } elseif ($status === 'watching') {
                    $result = $userDramaModel->update($id, ['status' => 'watching']);
                } else {
                    throw new \Exception('不正なステータスです');
                }
            } else {
                $clearDate = array_key_exists('watched_date', $input) && empty($input['watched_date']);
                $result = $userDramaModel->updateReview(
                    $id,
                    isset($input['rating']) ? (int)$input['rating'] : null,
                    $input['memo'] ?? null,
                    $input['watched_date'] ?? null,
                    $clearDate
                );
            }

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => '更新しました',
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('更新に失敗しました');
            }
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function remove(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('IDが指定されていません');
            }

            $userDramaModel = new UserDramaModel();
            $result = $userDramaModel->delete($id);

            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'リストから削除しました',
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new \Exception('削除に失敗しました');
            }
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function updateTags(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('IDが指定されていません');
            }

            $tags = $input['tags'] ?? [];
            if (!is_array($tags)) {
                $tags = [];
            }
            $tags = array_values(array_unique(array_filter(array_map('trim', $tags))));

            $userDramaModel = new UserDramaModel();
            $userDramaModel->update($id, [
                'tags' => !empty($tags) ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null,
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'タグを更新しました',
                'data' => ['tags' => $tags],
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}

