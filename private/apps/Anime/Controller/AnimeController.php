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

    /**
     * ダッシュボード（ローカル DB から取得）
     */
    public function dashboard(): void {
        $auth = new Auth();
        $auth->requireLogin();

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
            $currentSeason = $this->getCurrentSeason();
            $thisSeasonWorks = array_filter($works, fn($w) => ($w['season_name'] ?? '') === $currentSeason);
            $watchingWorks = array_filter($works, fn($w) => ($w['status']['kind'] ?? '') === 'watching');
        }

        $authorizeUrl = $oauthConfigured ? $oauthService->getAuthorizeUrl() : '';

        require_once __DIR__ . '/../Views/anime_dashboard.php';
    }

    private function getCurrentSeason(): string {
        $m = (int)date('n');
        $y = date('Y');
        if ($m >= 1 && $m <= 3) return $y . '-winter';
        if ($m >= 4 && $m <= 6) return $y . '-spring';
        if ($m >= 7 && $m <= 9) return $y . '-summer';
        return $y . '-autumn';
    }

    /**
     * リスト画面（ローカル DB から取得）
     */
    public function list(): void {
        $auth = new Auth();
        $auth->requireLogin();

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
        $auth = new Auth();
        $auth->requireLogin();

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
