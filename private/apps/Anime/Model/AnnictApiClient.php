<?php

namespace App\Anime\Model;

use Core\Logger;
use App\Anime\Service\AnnictOAuthService;

/**
 * Annict API クライアント
 * ユーザーごとの OAuth トークンを使用
 * 物理パス: haitaka/private/apps/Anime/Model/AnnictApiClient.php
 */
class AnnictApiClient {

    private const BASE_URL = 'https://api.annict.com/v1';

    private ?string $accessToken;
    private ?int $userId;

    public function __construct(?int $userId = null) {
        $this->userId = $userId ?? (int)($_SESSION['user']['id'] ?? 0);
        $this->accessToken = $this->userId > 0 ? AnnictOAuthService::getTokenForUser($this->userId) : null;
    }

    public function hasToken(): bool {
        return !empty($this->accessToken);
    }

    public function setToken(string $token): void {
        $this->accessToken = $token;
    }

    /**
     * 自分がステータス設定している作品一覧を取得
     * @param array{filter_status?: string, filter_season?: string, page?: int, per_page?: int} $params
     */
    public function getMeWorks(array $params = []): ?array {
        if (!$this->hasToken()) return null;

        $query = ['access_token' => $this->accessToken];
        if (!empty($params['filter_status'])) $query['filter_status'] = $params['filter_status'];
        if (!empty($params['filter_season'])) $query['filter_season'] = $params['filter_season'];
        if (isset($params['page'])) $query['page'] = (int)$params['page'];
        if (isset($params['per_page'])) $query['per_page'] = min(50, max(1, (int)$params['per_page']));

        return $this->request('GET', '/me/works', $query);
    }

    /**
     * 作品検索（認証不要のエンドポイント。read スコープで可）
     * 認証済みの場合、access_token を付与してレート制限緩和の可能性
     */
    public function searchWorks(array $params = []): ?array {
        $query = [];
        if ($this->hasToken()) {
            $query['access_token'] = $this->accessToken;
        }
        if (!empty($params['filter_title'])) $query['filter_title'] = $params['filter_title'];
        if (!empty($params['filter_season'])) $query['filter_season'] = $params['filter_season'];
        if (isset($params['page'])) $query['page'] = (int)$params['page'];
        if (isset($params['per_page'])) $query['per_page'] = min(50, max(1, (int)$params['per_page']));
        $query['sort_season'] = $params['sort_season'] ?? 'desc';

        return $this->request('GET', '/works', $query);
    }

    /**
     * ステータスを設定
     * @param int $workId Annict 作品ID
     * @param string $kind wanna_watch | watching | watched | no_select
     */
    public function setStatus(int $workId, string $kind): ?array {
        if (!$this->hasToken()) return null;

        $allowed = ['wanna_watch', 'watching', 'watched', 'no_select'];
        if (!in_array($kind, $allowed, true)) return null;

        $body = [
            'work_id' => $workId,
            'kind' => $kind,
        ];

        return $this->request('POST', '/me/statuses', $body, true);
    }

    /**
     * 作品詳細（ID指定）
     */
    public function getWork(int $annictId): ?array {
        $query = ['filter_ids' => (string)$annictId];
        if ($this->hasToken()) $query['access_token'] = $this->accessToken;

        $res = $this->request('GET', '/works', $query);
        if (!$res || empty($res['works']) || !isset($res['works'][0])) return null;
        return $res['works'][0];
    }

    /**
     * /works の生レスポンスを取得（検証用）
     * @param array{filter_ids?: string, filter_title?: string, filter_season?: string, page?: int, per_page?: int} $params
     */
    public function getWorksRaw(array $params): ?array {
        $query = [];
        if ($this->hasToken()) $query['access_token'] = $this->accessToken;
        if (isset($params['filter_ids']) && $params['filter_ids'] !== '') $query['filter_ids'] = $params['filter_ids'];
        if (!empty($params['filter_title'])) $query['filter_title'] = $params['filter_title'];
        if (!empty($params['filter_season'])) $query['filter_season'] = $params['filter_season'];
        if (isset($params['page'])) $query['page'] = (int)$params['page'];
        if (isset($params['per_page'])) $query['per_page'] = min(50, max(1, (int)$params['per_page']));
        $query['sort_season'] = $params['sort_season'] ?? 'desc';

        return $this->request('GET', '/works', $query);
    }

    private function request(string $method, string $path, array $params = [], bool $postAsForm = false): ?array {
        $url = self::BASE_URL . $path;

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Accept: application/json\r\n",
                    'timeout' => 15,
                ],
            ];
        } else {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($params),
                    'timeout' => 15,
                ],
            ];
        }

        $ctx = stream_context_create($opts);
        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) {
            Logger::error('Annict API request failed: ' . $method . ' ' . $path);
            return null;
        }

        if ($response === '' && $method === 'POST') {
            return []; // 204 No Content など
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Annict API JSON parse error: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }
}
