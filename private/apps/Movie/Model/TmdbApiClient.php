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
            'append_to_response' => 'credits',
        ];

        return $this->request("/movie/{$tmdbId}", $params);
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
