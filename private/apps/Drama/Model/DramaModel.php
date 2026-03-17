<?php

namespace App\Drama\Model;

use Core\BaseModel;

class DramaModel extends BaseModel {
    protected string $table = 'dr_series';
    protected bool $isUserIsolated = false;

    protected array $fields = [
        'id', 'tmdb_id', 'category', 'title', 'original_title', 'overview',
        'poster_path', 'backdrop_path',
        'first_air_date', 'last_air_date',
        'number_of_seasons', 'number_of_episodes',
        'vote_average', 'vote_count', 'genres', 'runtime_avg',
        'watch_providers', 'watch_providers_updated_at',
        'created_at', 'updated_at',
    ];

    private function mapTmdbData(array $tmdbData, bool $includeTmdbId = false): array {
        $genres = [];
        if (!empty($tmdbData['genres'])) {
            $genres = array_map(fn($g) => $g['name'], $tmdbData['genres']);
        }

        $episodeRuntime = 0;
        if (!empty($tmdbData['episode_run_time']) && is_array($tmdbData['episode_run_time'])) {
            $episodeRuntime = (int)max($tmdbData['episode_run_time']);
        }

        $category = $this->detectCategoryFromTmdb($tmdbData);

        $data = [
            'category' => $category,
            'title' => $tmdbData['name'] ?? '',
            'original_title' => $tmdbData['original_name'] ?? null,
            'overview' => $tmdbData['overview'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'first_air_date' => !empty($tmdbData['first_air_date']) ? $tmdbData['first_air_date'] : null,
            'last_air_date' => !empty($tmdbData['last_air_date']) ? $tmdbData['last_air_date'] : null,
            'number_of_seasons' => $tmdbData['number_of_seasons'] ?? null,
            'number_of_episodes' => $tmdbData['number_of_episodes'] ?? null,
            'vote_average' => $tmdbData['vote_average'] ?? null,
            'vote_count' => $tmdbData['vote_count'] ?? null,
            'genres' => json_encode($genres, JSON_UNESCAPED_UNICODE),
            'runtime_avg' => $episodeRuntime ?: null,
        ];

        if ($includeTmdbId) {
            $data = ['tmdb_id' => $tmdbData['id']] + $data;
        }

        return $data;
    }

    /**
     * TMDBのTVシリーズ情報からアニメ/ドラマ種別を推定する
     * - 日本発 & Animationジャンルを持つものを anime、それ以外を drama とする
     */
    public function detectCategoryFromTmdb(array $tmdbData): string {
        $origin = $tmdbData['origin_country'] ?? [];
        $origin = is_array($origin) ? $origin : [];
        $originalLang = $tmdbData['original_language'] ?? '';

        // genres (詳細) / genre_ids（検索結果）どちらにも対応
        $genreIds = [];
        if (!empty($tmdbData['genres']) && is_array($tmdbData['genres'])) {
            foreach ($tmdbData['genres'] as $g) {
                if (isset($g['id'])) {
                    $genreIds[] = (int)$g['id'];
                }
            }
        } elseif (!empty($tmdbData['genre_ids']) && is_array($tmdbData['genre_ids'])) {
            $genreIds = array_map('intval', $tmdbData['genre_ids']);
        }

        $isJapanese = in_array('JP', $origin, true) || $originalLang === 'ja';
        $hasAnimation = in_array(16, $genreIds, true); // 16 = Animation

        if ($isJapanese && $hasAnimation) {
            return 'anime';
        }

        return 'drama';
    }

    private function updateColumnsById(int $id, array $data): bool {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :target_id";
        $data['target_id'] = $id;
        return $this->pdo->prepare($sql)->execute($data);
    }

    public function findOrCreateByTmdbId(array $tmdbData): array {
        $existing = $this->findByTmdbId((int)$tmdbData['id']);
        if ($existing) {
            return $existing;
        }
        $this->createSeries($this->mapTmdbData($tmdbData, true));
        return $this->findByTmdbId((int)$tmdbData['id']);
    }

    public function findByTmdbId(int $tmdbId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id = :tmdb_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tmdb_id' => $tmdbId]);
        return $stmt->fetch() ?: null;
    }

    public function createSeries(array $data): bool {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        return $this->pdo->prepare($sql)->execute($data);
    }

    public function createPlaceholder(string $title): array {
        $data = [
            'title' => $title,
            'original_title' => null,
            'overview' => null,
            'poster_path' => null,
            'backdrop_path' => null,
            'first_air_date' => null,
            'last_air_date' => null,
            'number_of_seasons' => null,
            'number_of_episodes' => null,
            'vote_average' => null,
            'vote_count' => null,
            'genres' => null,
            'runtime_avg' => null,
        ];

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->pdo->prepare($sql)->execute($data);

        $id = $this->pdo->lastInsertId();
        return $this->findById((int)$id);
    }

    public function findPlaceholderByTitle(string $title): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id IS NULL AND title = :title LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['title' => $title]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function linkToTmdb(int $id, array $tmdbData): bool {
        return $this->updateColumnsById($id, $this->mapTmdbData($tmdbData, true));
    }

    public function getPlaceholders(): array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateWatchProviders(int $id, ?array $providersJP): bool {
        $sql = "UPDATE {$this->table} SET watch_providers = :wp, watch_providers_updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'wp' => $providersJP ? json_encode($providersJP, JSON_UNESCAPED_UNICODE) : null,
            'id' => $id,
        ]);
    }

    public function updateFromTmdb(int $id, array $tmdbData): bool {
        return $this->updateColumnsById($id, $this->mapTmdbData($tmdbData));
    }
}

