<?php

namespace App\Drama\Model;

use Core\Logger;

class TmdbTvApiClient {
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

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

    public function searchSeries(string $query, int $page = 1): ?array {
        if (!$this->isConfigured()) return null;

        $params = [
            'api_key' => $this->apiKey,
            'query' => $query,
            'language' => 'ja-JP',
            'page' => $page,
            'include_adult' => 'false',
        ];

        return $this->request('/search/tv', $params);
    }

    public function getSeriesDetail(int $tmdbId): ?array {
        if (!$this->isConfigured()) return null;

        $params = [
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
            'append_to_response' => 'credits,watch/providers',
        ];

        return $this->request("/tv/{$tmdbId}", $params);
    }

    public function getTrending(string $timeWindow = 'week'): ?array {
        if (!$this->isConfigured()) return null;

        return $this->request("/trending/tv/{$timeWindow}", [
            'api_key' => $this->apiKey,
            'language' => 'ja-JP',
        ]);
    }

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

        return $this->request('/discover/tv', $params);
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
            Logger::error('TMDB TV API request failed: ' . $url);
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('TMDB TV API JSON parse error: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }
}

