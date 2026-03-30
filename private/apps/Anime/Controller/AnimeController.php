<?php

namespace App\Anime\Controller;

use App\Anime\Model\AnnictApiClient;
use App\Anime\Model\UserWorkModel;
use App\Anime\Model\WorkModel;
use App\Anime\Service\AnnictOAuthService;
use Core\Auth;
use Core\Logger;

/**
 * アニメ管理コントローラ
 * 物理パス: haitaka/private/apps/Anime/Controller/AnimeController.php
 */
class AnimeController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    /**
     * 現在の年月から Annict の season 文字列を生成する（例: 2025-spring）
     */
    private function getCurrentSeasonString(): string {
        $year = (int)date('Y');
        $month = (int)date('n');

        if ($month >= 1 && $month <= 3) {
            $season = 'winter';
        } elseif ($month >= 4 && $month <= 6) {
            $season = 'spring';
        } elseif ($month >= 7 && $month <= 9) {
            $season = 'summer';
        } else {
            $season = 'autumn';
        }

        return sprintf('%d-%s', $year, $season);
    }

    /**
     * ダッシュボード（ローカル DB から取得）
     */
    public function dashboard(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        $userId = (int)($user['id'] ?? 0);

        $oauthService = new AnnictOAuthService();
        $oauthConfigured = $oauthService->isConfigured();
        $hasToken = $userId > 0 && AnnictOAuthService::getTokenForUser($userId) !== null;

        $oauthError = $_SESSION['anime_oauth_error'] ?? null;
        if (isset($_SESSION['anime_oauth_error'])) {
            unset($_SESSION['anime_oauth_error']);
        }

        $works = [];
        $stats = [
            'wanna_watch' => 0,
            'watching' => 0,
            'watched' => 0,
        ];
        $mediaDistribution = [];
        $seasonDistribution = [];
        $thisSeasonWorks = [];
        $watchingWorks = [];

        if ($userId > 0) {
            $userWorkModel = new UserWorkModel();
            $works = $userWorkModel->getAllByUser($userId);
            $stats = $userWorkModel->getStatsByUser($userId);
            foreach ($works as $w) {
                $media = $w['media_text'] ?? $w['media'] ?? 'その他';
                $mediaDistribution[$media] = ($mediaDistribution[$media] ?? 0) + 1;
                $sn = $w['season_name_text'] ?? $w['season_name'] ?? '';
                if ($sn) $seasonDistribution[$sn] = ($seasonDistribution[$sn] ?? 0) + 1;
            }
            $currentSeason = $this->getCurrentSeasonString();
            $thisSeasonWorks = array_filter($works, fn($w) => ($w['season_name'] ?? '') === $currentSeason);
            $watchingWorks = array_filter($works, fn($w) => ($w['status']['kind'] ?? '') === 'watching');
        }

        $authorizeUrl = $oauthConfigured ? $oauthService->getAuthorizeUrl() : '';

        require_once __DIR__ . '/../Views/anime_dashboard.php';
    }

    /**
     * リスト画面（ローカル DB から取得）
     */
    public function list(): void {
        $this->auth->requireLogin();

        $tab = $_GET['tab'] ?? 'watching';
        $allowedTabs = ['wanna_watch', 'watching', 'watched'];
        if (!in_array($tab, $allowedTabs, true)) $tab = 'watching';

        $user = $_SESSION['user'];
        $userId = (int)($user['id'] ?? 0);

        $works = [];
        if ($userId > 0) {
            $userWorkModel = new UserWorkModel();
            $works = $userWorkModel->getByUserAndStatus($userId, $tab);
        }

        $oauthService = new AnnictOAuthService();
        $oauthConfigured = $oauthService->isConfigured();

        $tabLabels = [
            'wanna_watch' => '見たい',
            'watching' => '見てる',
            'watched' => '見た',
        ];
        require_once __DIR__ . '/../Views/anime_list.php';
    }

    /**
     * 作品詳細（ローカル DB 優先、なければ Annict から取得）
     */
    public function detail(): void {
        $this->auth->requireLogin();

        $annictWorkId = (int)($_GET['id'] ?? 0);
        if ($annictWorkId <= 0) {
            header('Location: /anime/');
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $workModel = new WorkModel();
        $userWorkModel = new UserWorkModel();
        $localWork = $workModel->findByAnnictId($annictWorkId);

        if ($localWork) {
            $userWork = $userWorkModel->findByUserAndAnnictWork($userId, $annictWorkId);
            $work = $userWorkModel->formatWorkForDetail($localWork, $userWork);
        } else {
            $client = new AnnictApiClient($userId);
            $annictWork = $client->getWork($annictWorkId);
            if (!$annictWork) {
                header('Location: /anime/');
                exit;
            }
            $localWork = $workModel->upsertFromAnnict($annictWork);
            $userWork = $userWorkModel->findByUserAndAnnictWork($userId, $annictWorkId);
            $work = $userWorkModel->formatWorkForDetail($localWork, $userWork);
        }

        require_once __DIR__ . '/../Views/anime_detail.php';
    }

    /**
     * ステータス変更 API（ローカル DB のみに保存、Annict には書き込まない）
     */
    public function setStatusApi(): void {
        header('Content-Type: application/json; charset=utf-8');

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $annictWorkId = (int)($input['work_id'] ?? 0);
        $kind = trim($input['kind'] ?? '');

        $allowed = ['wanna_watch', 'watching', 'watched', 'no_select'];
        if ($annictWorkId <= 0 || !in_array($kind, $allowed, true)) {
            echo json_encode(['status' => 'error', 'message' => '不正なパラメータ'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $workModel = new WorkModel();
        $work = $workModel->findByAnnictId($annictWorkId);
        if (!$work) {
            $client = new AnnictApiClient($userId);
            $annictWork = $client->getWork($annictWorkId);
            if (!$annictWork) {
                echo json_encode(['status' => 'error', 'message' => '作品情報の取得に失敗しました'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $work = $workModel->upsertFromAnnict($annictWork);
        }

        $userWorkModel = new UserWorkModel();
        $userWorkModel->upsert($userId, (int)$work['id'], $annictWorkId, $kind);

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 作品検索 API
     */
    public function searchApi(): void {
        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));

        if ($q === '') {
            echo json_encode(['status' => 'error', 'message' => 'q は必須です'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $client = new AnnictApiClient($userId);
        $res = $client->searchWorks(['filter_title' => $q, 'page' => $page, 'per_page' => 20]);

        if (!$res) {
            echo json_encode(['status' => 'error', 'message' => '検索に失敗しました'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $works = $res['works'] ?? [];
        $workModel = new WorkModel();
        $userWorkModel = new UserWorkModel();

        foreach ($works as &$w) {
            // Annict 作品をローカルキャッシュへ保存 / 更新
            $workModel->upsertFromAnnict($w);

            // すでにユーザーのリストにあるかどうかを付加
            $annictId = (int)($w['id'] ?? 0);
            if ($annictId > 0 && $userId > 0) {
                $uw = $userWorkModel->findByUserAndAnnictWork($userId, $annictId);
                if ($uw && !empty($uw['status'])) {
                    $w['user_status'] = $uw['status'];
                }
            }
        }
        unset($w);

        echo json_encode(
            [
                'status' => 'success',
                'data' => $works,
                'total_count' => (int)($res['total_count'] ?? 0),
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * ガチャ API（見たいリストからランダム1件、ローカル DB から取得）
     */
    public function gachaApi(): void {
        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $userWorkModel = new UserWorkModel();
        $works = $userWorkModel->getByUserAndStatus($userId, 'wanna_watch');
        if (empty($works)) {
            echo json_encode(['status' => 'empty', 'message' => '見たいリストが空です'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $idx = array_rand($works);
        echo json_encode(['status' => 'success', 'data' => $works[$idx]], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 今期アニメ一覧 API（Annict から取得、ユーザーの登録状況を付加）
     */
    public function currentSeasonApi(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            if ($userId <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $season = $this->getCurrentSeasonString();
            $client = new AnnictApiClient($userId);
            $res = $client->getWorksRaw([
                'filter_season' => $season,
                'page' => 1,
                'per_page' => 50,
            ]);

            if (!$res || empty($res['works'])) {
                echo json_encode(['status' => 'empty', 'data' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $works = $res['works'] ?? [];
            $workModel = new WorkModel();
            $userWorkModel = new UserWorkModel();

            foreach ($works as &$w) {
                $workModel->upsertFromAnnict($w);
                $annictId = (int)($w['id'] ?? 0);
                if ($annictId > 0) {
                    $uw = $userWorkModel->findByUserAndAnnictWork($userId, $annictId);
                    if ($uw && !empty($uw['status'])) {
                        $w['user_status'] = $uw['status'];
                    }
                }
            }
            unset($w);

            // 最大20件まで返却（フロント側のカード数を揃える）
            $works = array_slice($works, 0, 20);

            echo json_encode([
                'status' => 'success',
                'season' => $season,
                'data' => $works,
                'total_count' => (int)($res['total_count'] ?? count($works)),
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            Logger::errorWithContext('Anime current season API error', $e);
            echo json_encode(['status' => 'error', 'message' => '内部エラーが発生しました'], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 今期アニメ一覧画面
     */
    public function currentSeasonList(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'] ?? [];
        $userId = (int)($user['id'] ?? 0);

        $season = $this->getCurrentSeasonString();
        $works = [];
        $errorMessage = null;

        try {
            if ($userId > 0) {
                $client = new AnnictApiClient($userId);
                $res = $client->getWorksRaw([
                    'filter_season' => $season,
                    'page' => 1,
                    'per_page' => 50,
                ]);
                if ($res && !empty($res['works'])) {
                    $works = $res['works'];
                    $workModel = new WorkModel();
                    $userWorkModel = new UserWorkModel();
                    foreach ($works as &$w) {
                        $workModel->upsertFromAnnict($w);
                        $annictId = (int)($w['id'] ?? 0);
                        if ($annictId > 0) {
                            $uw = $userWorkModel->findByUserAndAnnictWork($userId, $annictId);
                            if ($uw && !empty($uw['status'])) {
                                $w['user_status'] = $uw['status'];
                            }

                            // APIの response に images が含まれない/ブロックされるケースがあるため、
                            // ローカルキャッシュの image_url を優先して表示に使う
                            $localWork = $workModel->findByAnnictId($annictId);
                            if ($localWork && !empty($localWork['image_url'])) {
                                $w['image_url'] = $localWork['image_url'];
                                if (!isset($w['images']) || !is_array($w['images'])) {
                                    $w['images'] = [];
                                }
                                $w['images']['recommended_url'] = $localWork['image_url'];
                            }
                        }
                    }
                    unset($w);
                }
            }
        } catch (\Exception $e) {
            Logger::errorWithContext('Anime current season list error', $e);
            $errorMessage = '今期のアニメ情報の取得に失敗しました。時間をおいて再度お試しください。';
        }

        require_once __DIR__ . '/../Views/anime_current_season.php';
    }

    /**
     * 一括登録画面
     */
    public function import(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'] ?? [];
        $userId = (int)($user['id'] ?? 0);

        $oauthService = new AnnictOAuthService();
        $oauthConfigured = $oauthService->isConfigured();
        $hasToken = $userId > 0 && AnnictOAuthService::getTokenForUser($userId) !== null;
        $authorizeUrl = $oauthConfigured ? $oauthService->getAuthorizeUrl() : '';

        require_once __DIR__ . '/../Views/anime_import.php';
    }

    /**
     * Annict 連携解除 API
     */
    public function revokeApi(): void {
        header('Content-Type: application/json; charset=utf-8');

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (AnnictOAuthService::revokeToken($userId)) {
            echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => '連携解除に失敗しました'], JSON_UNESCAPED_UNICODE);
        }
    }
}
