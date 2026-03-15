<?php

namespace App\Drama\Controller;

use App\Drama\Model\DramaModel;
use App\Drama\Model\UserDramaModel;
use App\Drama\Model\TmdbTvApiClient;
use Core\Auth;
use Core\Logger;

class DramaController {
    public function dashboard(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];

        try {
            $userDramaModel = new UserDramaModel();
            $wannaWatchCount = $userDramaModel->countByStatus('wanna_watch');
            $watchingCount = $userDramaModel->countByStatus('watching');
            $watchedCount = $userDramaModel->countByStatus('watched');
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
     * 一括登録画面
     */
    public function import(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $user = $_SESSION['user'];
        $tmdb = new TmdbTvApiClient();
        $tmdbConfigured = $tmdb->isConfigured();

        require_once __DIR__ . '/../Views/drama_import.php';
    }

    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

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
            $wannaWatchCount = $userDramaModel->countByStatus('wanna_watch');
            $watchingCount = $userDramaModel->countByStatus('watching');
            $watchedCount = $userDramaModel->countByStatus('watched');
            $series = $userDramaModel->getListByStatus($tab, $sort, $order, $filter);
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
        $auth = new Auth();
        $auth->requireLogin();

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
        $auth = new Auth();
        $auth->requireLogin();

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
     * 一括登録 API（プレビュー結果を受け取って一括でDB登録）
     */
    public function bulkAdd(): void {
        header('Content-Type: application/json');

        try {
            $auth = new Auth();
            $auth->requireLogin();

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

