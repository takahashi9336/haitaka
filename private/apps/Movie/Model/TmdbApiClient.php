<?php

namespace App\Movie\Model;

/**
 * TMDB API クライアント
 * 物理パス: haitaka/private/apps/Movie/Model/TmdbApiClient.php
 */
class TmdbApiClient {
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';
    private string $imageBaseUrl = 'https://image.tmdb.org/t/p/';

    public const GENRE_MAP = [
        'アクション' => 28, 'アドベンチャー' => 12, 'アニメーション' => 16,
        'コメディ' => 35, 'クライム' => 80, 'ドキュメンタリー' => 99,
        'ドラマ' => 18, 'ファミリー' => 10751, 'ファンタジー' => 14,
        'ヒストリー' => 36, 'ホラー' => 27, 'ミュージック' => 10402,
        'ミステリー' => 9648, 'ロマンス' => 10749, 'サイエンスフィクション' => 878,
        'テレビ映画' => 10770, 'スリラー' => 53, '戦争' => 10752, '西部劇' => 37,
    ];

    public function __construct() {
        $this->apiKey = $_ENV['TMDB_API_KEY'] ?? '';
        if (empty($this->apiKey)) {
            $envPath = __DIR__ . '/../../../.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) continue;
                    if (str_contains($line, '=')) {
                        [$key, $val] = explode('=', $line, 2);
                        if (trim($key) === 'TMDB_API_KEY') {
                            $this->apiKey = trim($val);
                            break;
                        }
                    }
                }
            }
        }
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }

    /**
     * 映画を検索
     */
    public function searchMovies(string $query, int $page = 1): ?array {
        if (!$this->isConfigured()) return null;

        $params = [
            'api_key' => $this->apiKey,
            'query' => $query,
            'language' => 'ja-JP',
            'page' => $page,
            'include_adult' => 'false',
        ];

        return $this->request('/search/movie', $params);
    }

    /**
     * 映画詳細を取得
     */
    public function getMovieDetail(int $tmdbId): ?array {
        if (!$this->isConfigured()) return null;

        $params = [
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
            'append_to_response' => 'credits,watch/providers',
        ];

        return $this->request("/movie/{$tmdbId}", $params);
    }

    /**
     * 指定映画に基づくレコメンド取得
     */
    public function getRecommendations(int $tmdbId, int $page = 1): ?array {
        if (!$this->isConfigured()) return null;

        return $this->request("/movie/{$tmdbId}/recommendations", [
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
            'page' => $page,
        ]);
    }

    /**
     * ジャンルIDベースのDiscover検索
     */
    public function discoverByGenres(array $genreIds, array $options = []): ?array {
        if (!$this->isConfigured() || empty($genreIds)) return null;

        $params = array_merge([
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
            'sort_by' => 'popularity.desc',
            'with_genres' => implode('|', $genreIds),
            'vote_average.gte' => 6.0,
            'vote_count.gte' => 100,
            'include_adult' => 'false',
            'page' => 1,
        ], $options);

        return $this->request('/discover/movie', $params);
    }

    /**
     * トレンド映画取得
     */
    public function getTrending(string $timeWindow = 'week'): ?array {
        if (!$this->isConfigured()) return null;

        return $this->request("/trending/movie/{$timeWindow}", [
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
        ]);
    }

    /**
     * ジャンル名からTMDB Genre IDを取得
     */
    public static function genreNameToId(string $name): ?int {
        return self::GENRE_MAP[$name] ?? null;
    }

    /**
     * ポスター画像URLを生成
     */
    public static function posterUrl(?string $path, string $size = 'w342'): string {
        if (empty($path)) return '';
        return 'https://image.tmdb.org/t/p/' . $size . $path;
    }

    /**
     * 背景画像URLを生成
     */
    public static function backdropUrl(?string $path, string $size = 'w780'): string {
        if (empty($path)) return '';
        return 'https://image.tmdb.org/t/p/' . $size . $path;
    }

    private function request(string $endpoint, array $params): ?array {
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            error_log('TMDB API request failed: ' . $url);
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('TMDB API JSON parse error: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }
}
