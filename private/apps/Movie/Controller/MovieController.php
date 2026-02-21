<?php

namespace App\Movie\Controller;

use App\Movie\Model\MovieModel;
use App\Movie\Model\UserMovieModel;
use App\Movie\Model\TmdbApiClient;
use Core\Auth;

/**
 * 映画管理コントローラ
 * 物理パス: haitaka/private/apps/Movie/Controller/MovieController.php
 */
class MovieController {

    /**
     * ダッシュボード画面
     */
    public function dashboard(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];

        try {
            $userMovieModel = new UserMovieModel();
            $watchlistCount = $userMovieModel->countByStatus('watchlist');
            $watchedCount = $userMovieModel->countByStatus('watched');
            $thisMonthCount = $userMovieModel->getThisMonthWatchedCount();
            $totalRuntime = $userMovieModel->getTotalWatchedRuntime();
            $monthlyWatchCounts = $userMovieModel->getMonthlyWatchCounts(12);
            $genreDistribution = $userMovieModel->getGenreDistribution();
            $ratingDistribution = $userMovieModel->getRatingDistribution();

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
            error_log('Movie dashboard error: ' . $e->getMessage());
            $watchlistCount = $watchedCount = $thisMonthCount = $totalRuntime = $avgRating = $ratedCount = 0;
            $monthlyWatchCounts = [];
            $genreDistribution = $ratingDistribution = [];
        }

        $tmdb = new TmdbApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/movie_dashboard.php';
    }

    /**
     * ガチャAPI（ランダム1件返却）
     */
    public function gachaApi(): void {
        header('Content-Type: application/json');
        try {
            $userMovieModel = new UserMovieModel();
            $movie = $userMovieModel->getRandomWatchlistItem();
            if (!$movie) {
                echo json_encode(['status' => 'empty', 'message' => '見たいリストが空です'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(['status' => 'success', 'data' => $movie], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private const REC_CACHE_TTL = 21600; // 6時間

    private function getRecCachePath(string $type, int $userId): string {
        $dir = __DIR__ . '/../../../cache/rec';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
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

    /**
     * おすすめ映画API（6時間キャッシュ付き）
     */
    public function recommendationsApi(): void {
        header('Content-Type: application/json');
        try {
            $type = $_GET['type'] ?? '';
            if (!in_array($type, ['personal', 'genre', 'trending'])) {
                echo json_encode(['status' => 'error', 'message' => '不正なtypeパラメータ'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $userMovieModel = new UserMovieModel();
            $tmdb = new TmdbApiClient();

            if (!$tmdb->isConfigured()) {
                echo json_encode(['status' => 'error', 'message' => 'TMDB未設定'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $cachePath = $this->getRecCachePath($type, $userId);
            $cached = $this->readRecCache($cachePath);

            if ($cached !== null) {
                $this->applyRegisteredFlags($cached, $userMovieModel);
                echo json_encode($cached, JSON_UNESCAPED_UNICODE);
                return;
            }

            $result = $this->fetchRecommendations($type, $userMovieModel, $tmdb);
            if ($result !== null) {
                $this->writeRecCache($cachePath, $result);
            }

            echo json_encode($result ?? ['status' => 'empty'], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            error_log('Recommendations API error: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function applyRegisteredFlags(array &$response, UserMovieModel $userMovieModel): void {
        $existingSet = array_flip($userMovieModel->getAllTmdbIds());
        if (isset($response['data'])) {
            foreach ($response['data'] as &$m) {
                $m['_registered'] = isset($existingSet[$m['id']]);
            }
        }
    }

    private function fetchRecommendations(string $type, UserMovieModel $userMovieModel, TmdbApiClient $tmdb): ?array {
        $existingSet = array_flip($userMovieModel->getAllTmdbIds());
        $movies = [];

        switch ($type) {
            case 'personal':
                $topIds = $userMovieModel->getTopRatedTmdbIds(5);
                if (empty($topIds)) return ['status' => 'empty', 'message' => '高評価の映画がありません'];

                $seen = [];
                foreach ($topIds as $tmdbId) {
                    $result = $tmdb->getRecommendations((int)$tmdbId);
                    if (!$result || empty($result['results'])) continue;
                    foreach ($result['results'] as $m) {
                        $id = $m['id'];
                        if (isset($seen[$id])) continue;
                        $seen[$id] = true;
                        $m['_registered'] = isset($existingSet[$id]);
                        $movies[] = $m;
                    }
                }
                usort($movies, fn($a, $b) => ($b['vote_average'] ?? 0) <=> ($a['vote_average'] ?? 0));
                $movies = array_filter($movies, fn($m) => !$m['_registered']);
                $movies = array_slice(array_values($movies), 0, 20);
                return ['status' => 'success', 'data' => $movies];

            case 'genre':
                $genreNames = $userMovieModel->getTopGenreNames(3);
                if (empty($genreNames)) return ['status' => 'empty', 'message' => 'ジャンルデータがありません'];

                $genreIds = [];
                foreach ($genreNames as $name) {
                    $gid = TmdbApiClient::genreNameToId($name);
                    if ($gid) $genreIds[] = $gid;
                }
                if (empty($genreIds)) return ['status' => 'empty', 'message' => 'ジャンルマッピングなし'];

                $result = $tmdb->discoverByGenres($genreIds);
                if ($result && !empty($result['results'])) {
                    foreach ($result['results'] as $m) {
                        $m['_registered'] = isset($existingSet[$m['id']]);
                        if (!$m['_registered']) $movies[] = $m;
                    }
                }
                $movies = array_slice($movies, 0, 20);
                return ['status' => 'success', 'genres' => $genreNames, 'data' => $movies];

            case 'trending':
                $result = $tmdb->getTrending('week');
                if ($result && !empty($result['results'])) {
                    foreach ($result['results'] as $m) {
                        $m['_registered'] = isset($existingSet[$m['id']]);
                        $movies[] = $m;
                    }
                }
                $movies = array_slice($movies, 0, 20);
                return ['status' => 'success', 'data' => $movies];
        }
        return null;
    }

    /**
     * 映画一覧画面
     */
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $tab = $_GET['tab'] ?? 'watchlist';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        $filter = $_GET['filter'] ?? '';

        try {
            $userMovieModel = new UserMovieModel();
            $watchlistCount = $userMovieModel->countByStatus('watchlist');
            $watchedCount = $userMovieModel->countByStatus('watched');
            $movies = $userMovieModel->getListByStatus($tab, $sort, $order, $filter);
        } catch (\Exception $e) {
            error_log('Movie list error: ' . $e->getMessage());
            $watchlistCount = 0;
            $watchedCount = 0;
            $movies = [];
        }

        $tmdb = new TmdbApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/movie_list.php';
    }

    /**
     * 映画詳細画面
     */
    public function detail(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: /movie/list.php');
            exit;
        }

        try {
            $userMovieModel = new UserMovieModel();
            $movie = $userMovieModel->getDetailWithMovie($id);

            if (!$movie) {
                header('Location: /movie/list.php');
                exit;
            }

            $tmdb = new TmdbApiClient();
            $movieModel = new MovieModel();
            if ($tmdb->isConfigured() && !empty($movie['tmdb_id'])) {
                $tmdbDetail = $tmdb->getMovieDetail((int)$movie['tmdb_id']);
                if ($tmdbDetail && !empty($tmdbDetail['runtime'])) {
                    $movieModel->updateFromTmdb((int)$movie['movie_id'], $tmdbDetail);
                    $movie['runtime'] = $tmdbDetail['runtime'];
                    $movie['overview'] = $tmdbDetail['overview'] ?? $movie['overview'];
                    if (!empty($tmdbDetail['genres'])) {
                        $movie['genres'] = json_encode(
                            array_map(fn($g) => $g['name'], $tmdbDetail['genres']),
                            JSON_UNESCAPED_UNICODE
                        );
                    }
                    $movie['credits'] = $tmdbDetail['credits'] ?? null;

                    $wpAll = $tmdbDetail['watch/providers']['results'] ?? [];
                    $wpJP = $wpAll['JP'] ?? null;
                    $movieModel->updateWatchProviders((int)$movie['movie_id'], $wpJP);
                    $movie['watch_providers'] = $wpJP ? json_encode($wpJP, JSON_UNESCAPED_UNICODE) : null;
                    $movie['watch_providers_updated_at'] = date('Y-m-d H:i:s');
                }
            }
        } catch (\Exception $e) {
            error_log('Movie detail error: ' . $e->getMessage());
            header('Location: /movie/list.php');
            exit;
        }

        require_once __DIR__ . '/../Views/movie_detail.php';
    }

    /**
     * 一括登録画面
     */
    public function import(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $tmdb = new TmdbApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/movie_import.php';
    }

    /**
     * 一括登録 API（プレビュー結果を受け取って一括でDB登録）
     */
    public function bulkAdd(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $items = $input['items'] ?? [];
            if (empty($items)) {
                throw new \Exception('登録する映画がありません');
            }

            $movieModel = new MovieModel();
            $userMovieModel = new UserMovieModel();
            $tmdb = new TmdbApiClient();
            $added = 0;
            $skipped = 0;
            $errors = [];

            foreach ($items as $item) {
                try {
                    $movieStatus = $item['status'] ?? 'watchlist';
                    if (!in_array($movieStatus, ['watchlist', 'watched'])) {
                        $movieStatus = 'watchlist';
                    }

                    $movie = null;
                    $tmdbId = $item['tmdb_id'] ?? null;

                    if ($tmdbId && $tmdbId > 0) {
                        $existing = $movieModel->findByTmdbId((int)$tmdbId);
                        if ($existing) {
                            $movie = $existing;
                        } else {
                            $tmdbData = $item['tmdb_data'] ?? null;
                            if (!$tmdbData && $tmdb->isConfigured()) {
                                $tmdbData = $tmdb->getMovieDetail((int)$tmdbId);
                            }
                            if ($tmdbData) {
                                $movie = $movieModel->findOrCreateByTmdbId($tmdbData);
                            }
                        }
                    }

                    if (!$movie) {
                        $title = trim($item['title'] ?? '');
                        if (empty($title)) continue;

                        $existing = $movieModel->findPlaceholderByTitle($title);
                        $movie = $existing ?: $movieModel->createPlaceholder($title);
                    }

                    $existingEntry = $userMovieModel->findByMovieId((int)$movie['id']);
                    if ($existingEntry) {
                        $skipped++;
                        continue;
                    }

                    $userMovieModel->addMovie((int)$movie['id'], $movieStatus);
                    $added++;

                } catch (\Exception $e) {
                    $errors[] = ($item['title'] ?? '不明') . ': ' . $e->getMessage();
                }
            }

            $msg = "{$added}件を登録しました";
            if ($skipped > 0) $msg .= "（{$skipped}件は登録済のためスキップ）";

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

    /**
     * TMDB映画検索 API
     */
    public function search(): void {
        header('Content-Type: application/json');

        try {
            $query = $_GET['q'] ?? '';
            $page = (int)($_GET['page'] ?? 1);

            if (empty(trim($query))) {
                throw new \Exception('検索キーワードを入力してください');
            }

            $tmdb = new TmdbApiClient();
            if (!$tmdb->isConfigured()) {
                throw new \Exception('TMDB APIキーが設定されていません。.envにTMDB_API_KEYを追加してください。');
            }

            $results = $tmdb->searchMovies($query, $page);
            if ($results === null) {
                throw new \Exception('TMDB APIの通信に失敗しました');
            }

            $userMovieModel = new UserMovieModel();
            foreach ($results['results'] as &$movie) {
                $userEntry = $userMovieModel->findByTmdbId((int)$movie['id']);
                $movie['user_status'] = $userEntry ? $userEntry['status'] : null;
                $movie['user_movie_id'] = $userEntry ? $userEntry['id'] : null;
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
     * 映画をリストに追加 API
     */
    public function add(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $tmdbId = (int)($input['tmdb_id'] ?? 0);
            $movieStatus = $input['status'] ?? 'watchlist';

            if ($tmdbId <= 0) {
                throw new \Exception('映画IDが不正です');
            }
            if (!in_array($movieStatus, ['watchlist', 'watched'])) {
                throw new \Exception('ステータスが不正です');
            }

            $tmdb = new TmdbApiClient();
            $tmdbData = $input['tmdb_data'] ?? null;

            if (!$tmdbData && $tmdb->isConfigured()) {
                $tmdbData = $tmdb->getMovieDetail($tmdbId);
            }
            if (!$tmdbData) {
                $tmdbData = [
                    'id' => $tmdbId,
                    'title' => $input['title'] ?? '不明',
                    'original_title' => $input['original_title'] ?? null,
                    'overview' => $input['overview'] ?? null,
                    'poster_path' => $input['poster_path'] ?? null,
                    'backdrop_path' => $input['backdrop_path'] ?? null,
                    'release_date' => $input['release_date'] ?? null,
                    'vote_average' => $input['vote_average'] ?? null,
                    'vote_count' => $input['vote_count'] ?? null,
                    'genre_ids' => $input['genre_ids'] ?? [],
                ];
            }

            $movieModel = new MovieModel();
            $movie = $movieModel->findOrCreateByTmdbId($tmdbData);

            $userMovieModel = new UserMovieModel();
            $result = $userMovieModel->addMovie((int)$movie['id'], $movieStatus);

            if ($result) {
                $userEntry = $userMovieModel->findByMovieId((int)$movie['id']);
                echo json_encode([
                    'status' => 'success',
                    'message' => $movieStatus === 'watchlist' ? '見たいリストに追加しました' : '見たリストに追加しました',
                    'data' => ['id' => $userEntry['id'] ?? null, 'movie_status' => $movieStatus],
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

    /**
     * 映画のステータス・評価・メモ更新 API
     */
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

            $userMovieModel = new UserMovieModel();

            if (isset($input['status'])) {
                if ($input['status'] === 'watched') {
                    $result = $userMovieModel->markAsWatched(
                        $id,
                        $input['watched_date'] ?? null,
                        isset($input['rating']) ? (int)$input['rating'] : null,
                        $input['memo'] ?? null
                    );
                } elseif ($input['status'] === 'watchlist') {
                    $result = $userMovieModel->moveToWatchlist($id);
                } else {
                    throw new \Exception('不正なステータスです');
                }
            } else {
                $clearDate = array_key_exists('watched_date', $input) && empty($input['watched_date']);
                $result = $userMovieModel->updateReview(
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

    /**
     * 映画をリストから削除 API
     */
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

            $userMovieModel = new UserMovieModel();
            $result = $userMovieModel->delete($id);

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

    /**
     * 仮登録映画にTMDB情報を紐付け API
     */
    public function linkTmdb(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $movieId = (int)($input['movie_id'] ?? 0);
            $tmdbId = (int)($input['tmdb_id'] ?? 0);

            if ($movieId <= 0 || $tmdbId <= 0) {
                throw new \Exception('パラメータが不正です');
            }

            $movieModel = new MovieModel();
            $movie = $movieModel->findById($movieId);
            if (!$movie) {
                throw new \Exception('映画が見つかりません');
            }

            if (!empty($movie['tmdb_id'])) {
                throw new \Exception('この映画は既にTMDB情報が紐付けられています');
            }

            $existingTmdb = $movieModel->findByTmdbId($tmdbId);
            if ($existingTmdb) {
                $userMovieModel = new UserMovieModel();
                $userEntry = $userMovieModel->findByMovieId($movieId);
                if ($userEntry) {
                    $existingEntry = $userMovieModel->findByMovieId((int)$existingTmdb['id']);
                    if ($existingEntry) {
                        throw new \Exception('このTMDB映画は既にリストに登録されています');
                    }
                    $sql = "UPDATE mv_user_movies SET movie_id = :new_mid WHERE id = :id AND user_id = :uid";
                    $pdo = \Core\Database::connect();
                    $pdo->prepare($sql)->execute([
                        'new_mid' => $existingTmdb['id'],
                        'id' => $userEntry['id'],
                        'uid' => $_SESSION['user']['id'],
                    ]);
                    $pdo->prepare("DELETE FROM mv_movies WHERE id = :id AND tmdb_id IS NULL")->execute(['id' => $movieId]);
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'TMDB情報を紐付けました',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $tmdb = new TmdbApiClient();
            $tmdbData = null;
            if ($tmdb->isConfigured()) {
                $tmdbData = $tmdb->getMovieDetail($tmdbId);
            }
            if (!$tmdbData) {
                $tmdbData = $input['tmdb_data'] ?? null;
            }
            if (!$tmdbData) {
                throw new \Exception('TMDB情報を取得できませんでした');
            }

            $movieModel->linkToTmdb($movieId, $tmdbData);

            echo json_encode([
                'status' => 'success',
                'message' => 'TMDB情報を紐付けました',
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * タイトルのみで映画追加 API
     */
    public function addManual(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $title = trim($input['title'] ?? '');
            $movieStatus = $input['status'] ?? 'watchlist';

            if (empty($title)) {
                throw new \Exception('タイトルを入力してください');
            }
            if (!in_array($movieStatus, ['watchlist', 'watched'])) {
                $movieStatus = 'watchlist';
            }

            $movieModel = new MovieModel();
            $movie = $movieModel->findPlaceholderByTitle($title);
            if (!$movie) {
                $movie = $movieModel->createPlaceholder($title);
            }

            $userMovieModel = new UserMovieModel();
            $existingEntry = $userMovieModel->findByMovieId((int)$movie['id']);
            if ($existingEntry) {
                throw new \Exception('この映画は既にリストに登録されています');
            }

            $userMovieModel->addMovie((int)$movie['id'], $movieStatus);

            echo json_encode([
                'status' => 'success',
                'message' => "「{$title}」を追加しました",
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * タグ更新 API
     */
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

            $userMovieModel = new UserMovieModel();
            $result = $userMovieModel->update($id, [
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

    /**
     * 一括編集画面
     */
    public function bulkEdit(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $tab = $_GET['tab'] ?? 'watchlist';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';

        try {
            $userMovieModel = new UserMovieModel();
            $watchlistCount = $userMovieModel->countByStatus('watchlist');
            $watchedCount = $userMovieModel->countByStatus('watched');
            $movies = $userMovieModel->getListByStatus($tab, $sort, $order);
        } catch (\Exception $e) {
            error_log('Bulk edit error: ' . $e->getMessage());
            $watchlistCount = 0;
            $watchedCount = 0;
            $movies = [];
        }

        require_once __DIR__ . '/../Views/movie_bulk_edit.php';
    }

    /**
     * 一括更新 API
     */
    public function bulkUpdate(): void {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON');
            }

            $userMovieModel = new UserMovieModel();
            $updates = $input['updates'] ?? [];
            $deletes = $input['deletes'] ?? [];
            $updatedCount = 0;
            $deletedCount = 0;

            foreach ($deletes as $id) {
                if ($userMovieModel->delete((int)$id)) {
                    $deletedCount++;
                }
            }

            foreach ($updates as $item) {
                $id = (int)($item['id'] ?? 0);
                if ($id <= 0) continue;

                $status = $item['status'] ?? null;
                $rating = isset($item['rating']) && $item['rating'] !== '' ? (int)$item['rating'] : null;
                $watchedDate = $item['watched_date'] ?? null;
                $memo = $item['memo'] ?? null;

                $data = [];
                if ($status && in_array($status, ['watchlist', 'watched'])) {
                    $data['status'] = $status;
                }
                if (array_key_exists('rating', $item)) {
                    $data['rating'] = $rating;
                }
                if (array_key_exists('watched_date', $item)) {
                    $data['watched_date'] = !empty($watchedDate) ? $watchedDate : null;
                }
                if (array_key_exists('memo', $item)) {
                    $data['memo'] = !empty($memo) ? $memo : null;
                }

                if (!empty($data)) {
                    $userMovieModel->update($id, $data);
                    $updatedCount++;
                }
            }

            $msg = '';
            if ($updatedCount > 0) $msg .= "{$updatedCount}件を更新";
            if ($deletedCount > 0) $msg .= ($msg ? '、' : '') . "{$deletedCount}件を削除";
            if (empty($msg)) $msg = '変更はありません';
            $msg .= 'しました';

            echo json_encode([
                'status' => 'success',
                'message' => $msg,
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
