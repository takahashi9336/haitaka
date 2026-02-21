<?php

namespace App\Movie\Model;

use Core\BaseModel;

/**
 * TMDB映画キャッシュモデル
 * 物理パス: haitaka/private/apps/Movie/Model/MovieModel.php
 */
class MovieModel extends BaseModel {
    protected string $table = 'mv_movies';
    protected bool $isUserIsolated = false;

    protected array $fields = [
        'id', 'tmdb_id', 'title', 'original_title', 'overview',
        'poster_path', 'backdrop_path', 'release_date',
        'vote_average', 'vote_count', 'genres', 'runtime',
        'watch_providers', 'watch_providers_updated_at',
        'created_at', 'updated_at'
    ];

    /**
     * TMDB IDで映画を検索し、なければ作成して返す
     */
    public function findOrCreateByTmdbId(array $tmdbData): array {
        $existing = $this->findByTmdbId($tmdbData['id']);
        if ($existing) {
            return $existing;
        }

        $genres = [];
        if (!empty($tmdbData['genres'])) {
            $genres = array_map(fn($g) => $g['name'], $tmdbData['genres']);
        } elseif (!empty($tmdbData['genre_ids'])) {
            $genres = $tmdbData['genre_ids'];
        }

        $data = [
            'tmdb_id' => $tmdbData['id'],
            'title' => $tmdbData['title'] ?? '',
            'original_title' => $tmdbData['original_title'] ?? null,
            'overview' => $tmdbData['overview'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'release_date' => !empty($tmdbData['release_date']) ? $tmdbData['release_date'] : null,
            'vote_average' => $tmdbData['vote_average'] ?? null,
            'vote_count' => $tmdbData['vote_count'] ?? null,
            'genres' => json_encode($genres, JSON_UNESCAPED_UNICODE),
            'runtime' => $tmdbData['runtime'] ?? null,
        ];

        $this->createMovie($data);
        return $this->findByTmdbId($tmdbData['id']);
    }

    public function findByTmdbId(int $tmdbId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id = :tmdb_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tmdb_id' => $tmdbId]);
        return $stmt->fetch() ?: null;
    }

    public function createMovie(array $data): bool {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        return $this->pdo->prepare($sql)->execute($data);
    }

    /**
     * 仮登録（TMDB未発見）の映画を作成
     */
    public function createPlaceholder(string $title): array {
        $data = [
            'title' => $title,
            'original_title' => null,
            'overview' => null,
            'poster_path' => null,
            'backdrop_path' => null,
            'release_date' => null,
            'vote_average' => null,
            'vote_count' => null,
            'genres' => null,
            'runtime' => null,
        ];

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->pdo->prepare($sql)->execute($data);

        $id = $this->pdo->lastInsertId();
        return $this->findById((int)$id);
    }

    /**
     * タイトルで仮登録映画を検索
     */
    public function findPlaceholderByTitle(string $title): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id IS NULL AND title = :title LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['title' => $title]);
        return $stmt->fetch() ?: null;
    }

    /**
     * ID指定で取得（ユーザー隔離なし）
     */
    public function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 仮登録映画にTMDB情報を紐付け
     */
    public function linkToTmdb(int $id, array $tmdbData): bool {
        $genres = [];
        if (!empty($tmdbData['genres'])) {
            $genres = array_map(fn($g) => $g['name'], $tmdbData['genres']);
        } elseif (!empty($tmdbData['genre_ids'])) {
            $genres = $tmdbData['genre_ids'];
        }

        $data = [
            'tmdb_id' => $tmdbData['id'],
            'title' => $tmdbData['title'] ?? '',
            'original_title' => $tmdbData['original_title'] ?? null,
            'overview' => $tmdbData['overview'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'release_date' => !empty($tmdbData['release_date']) ? $tmdbData['release_date'] : null,
            'vote_average' => $tmdbData['vote_average'] ?? null,
            'vote_count' => $tmdbData['vote_count'] ?? null,
            'genres' => json_encode($genres, JSON_UNESCAPED_UNICODE),
            'runtime' => $tmdbData['runtime'] ?? null,
        ];

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :target_id";
        $data['target_id'] = $id;
        return $this->pdo->prepare($sql)->execute($data);
    }

    /**
     * 全仮登録映画を取得
     */
    public function getPlaceholders(): array {
        $sql = "SELECT * FROM {$this->table} WHERE tmdb_id IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 配信サービス情報を保存
     */
    public function updateWatchProviders(int $id, ?array $providersJP): bool {
        $sql = "UPDATE {$this->table} SET watch_providers = :wp, watch_providers_updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'wp' => $providersJP ? json_encode($providersJP, JSON_UNESCAPED_UNICODE) : null,
            'id' => $id,
        ]);
    }

    /**
     * 映画キャッシュを更新（TMDB詳細取得時に呼ぶ）
     */
    public function updateFromTmdb(int $id, array $tmdbData): bool {
        $genres = [];
        if (!empty($tmdbData['genres'])) {
            $genres = array_map(fn($g) => $g['name'], $tmdbData['genres']);
        }

        $data = [
            'title' => $tmdbData['title'] ?? '',
            'original_title' => $tmdbData['original_title'] ?? null,
            'overview' => $tmdbData['overview'] ?? null,
            'poster_path' => $tmdbData['poster_path'] ?? null,
            'backdrop_path' => $tmdbData['backdrop_path'] ?? null,
            'release_date' => !empty($tmdbData['release_date']) ? $tmdbData['release_date'] : null,
            'vote_average' => $tmdbData['vote_average'] ?? null,
            'vote_count' => $tmdbData['vote_count'] ?? null,
            'genres' => json_encode($genres, JSON_UNESCAPED_UNICODE),
            'runtime' => $tmdbData['runtime'] ?? null,
        ];

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :target_id";
        $data['target_id'] = $id;
        return $this->pdo->prepare($sql)->execute($data);
    }
}
