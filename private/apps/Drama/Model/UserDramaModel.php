<?php

namespace App\Drama\Model;

use Core\BaseModel;

class UserDramaModel extends BaseModel {
    protected string $table = 'dr_user_series';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'series_id', 'status', 'rating',
        'memo', 'watched_date', 'current_season', 'current_episode',
        'tags', 'created_at', 'updated_at',
    ];

    public function getListByStatus(string $status, string $sort = 'created_at', string $order = 'DESC', string $filter = ''): array {
        $allowedSort = ['created_at', 'updated_at', 'watched_date', 'rating', 'first_air_date', 'title'];
        $sortCol = in_array($sort, $allowedSort, true) ? $sort : 'created_at';

        if ($sortCol === 'first_air_date') {
            $sortCol = 's.first_air_date';
        } elseif ($sortCol === 'title') {
            $sortCol = 's.title';
        } else {
            $sortCol = "us.{$sortCol}";
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $where = "us.user_id = :uid AND us.status = :status";
        $params = ['uid' => $this->userId, 'status' => $status];

        if ($filter === 'this_month') {
            $where .= " AND us.watched_date >= :month_start AND us.watched_date < :month_end";
            $params['month_start'] = date('Y-m-01');
            $params['month_end'] = date('Y-m-01', strtotime('+1 month'));
        }

        $sql = "SELECT us.*, s.tmdb_id, s.title, s.original_title, s.overview,
                       s.poster_path, s.backdrop_path,
                       s.first_air_date, s.last_air_date,
                       s.number_of_seasons, s.number_of_episodes,
                       s.vote_average, s.vote_count, s.genres, s.runtime_avg,
                       s.watch_providers
                FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE {$where}
                ORDER BY {$sortCol} {$order}, us.id {$order}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findBySeriesId(int $seriesId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND series_id = :sid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'sid' => $seriesId]);
        return $stmt->fetch() ?: null;
    }

    public function addSeries(int $seriesId, string $status = 'wanna_watch'): bool {
        $existing = $this->findBySeriesId($seriesId);
        if ($existing) {
            return $this->update((int)$existing['id'], ['status' => $status]);
        }
        return $this->create([
            'series_id' => $seriesId,
            'status' => $status,
        ]);
    }

    public function markAsWatched(int $id, ?string $watchedDate = null, ?int $rating = null, ?string $memo = null): bool {
        $data = ['status' => 'watched'];
        $data['watched_date'] = !empty($watchedDate) ? $watchedDate : null;
        if ($rating !== null) $data['rating'] = $rating;
        if ($memo !== null) $data['memo'] = $memo;
        return $this->update($id, $data);
    }

    public function moveToWannaWatch(int $id): bool {
        return $this->update($id, [
            'status' => 'wanna_watch',
            'watched_date' => null,
            'rating' => null,
        ]);
    }

    public function updateReview(int $id, ?int $rating, ?string $memo, ?string $watchedDate = null, bool $clearDate = false): bool {
        $data = [];
        if ($rating !== null) $data['rating'] = $rating;
        if ($memo !== null) $data['memo'] = $memo;
        if (!empty($watchedDate)) {
            $data['watched_date'] = $watchedDate;
        } elseif ($clearDate) {
            $data['watched_date'] = null;
        }
        if (empty($data)) return false;
        return $this->update($id, $data);
    }

    public function countByStatus(string $status): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :uid AND status = :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function getDetailWithSeries(int $id): ?array {
        $sql = "SELECT us.*, s.tmdb_id, s.title, s.original_title, s.overview,
                       s.poster_path, s.backdrop_path,
                       s.first_air_date, s.last_air_date,
                       s.number_of_seasons, s.number_of_episodes,
                       s.vote_average, s.vote_count, s.genres, s.runtime_avg,
                       s.watch_providers, s.watch_providers_updated_at
                FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.id = :id AND us.user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $this->userId]);
        return $stmt->fetch() ?: null;
    }

    public function getMonthlyWatchCounts(int $months = 12): array {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $result[$ym] = 0;
        }
        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $sql = "SELECT DATE_FORMAT(watched_date, '%Y-%m') AS ym, COUNT(*) AS cnt
                FROM {$this->table}
                WHERE user_id = :uid AND status = 'watched' AND watched_date >= :start
                GROUP BY ym ORDER BY ym";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'start' => $start]);
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['ym']])) $result[$row['ym']] = (int)$row['cnt'];
        }
        return $result;
    }

    public function getRatingDistribution(): array {
        $dist = array_fill(1, 10, 0);
        $sql = "SELECT rating, COUNT(*) AS cnt FROM {$this->table}
                WHERE user_id = :uid AND status = 'watched' AND rating IS NOT NULL AND rating > 0
                GROUP BY rating ORDER BY rating";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        foreach ($stmt->fetchAll() as $row) {
            $dist[(int)$row['rating']] = (int)$row['cnt'];
        }
        return $dist;
    }

    public function getTotalWatchedRuntime(): int {
        $sql = "SELECT COALESCE(SUM(s.runtime_avg * COALESCE(s.number_of_episodes, 1)), 0) AS total
                FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.user_id = :uid AND us.status = 'watched' AND s.runtime_avg > 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getThisMonthWatchedCount(): int {
        $start = date('Y-m-01');
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE user_id = :uid AND status = 'watched' AND watched_date >= :start";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'start' => $start]);
        return (int)$stmt->fetchColumn();
    }

    public function getGenreDistribution(): array {
        $sql = "SELECT s.genres FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.user_id = :uid AND us.status = 'watched' AND s.genres IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $genres = json_decode($row['genres'], true);
            if (!is_array($genres)) continue;
            foreach ($genres as $g) {
                if (!is_string($g)) continue;
                $counts[$g] = ($counts[$g] ?? 0) + 1;
            }
        }
        arsort($counts);
        return $counts;
    }

    public function findByTmdbId(int $tmdbId): ?array {
        $sql = "SELECT us.* FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.user_id = :uid AND s.tmdb_id = :tmdb_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'tmdb_id' => $tmdbId]);
        return $stmt->fetch() ?: null;
    }

    public function getTopRatedTmdbIds(int $limit = 5): array {
        $sql = "SELECT s.tmdb_id FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.user_id = :uid AND us.status = 'watched'
                  AND us.rating >= 7 AND s.tmdb_id IS NOT NULL AND s.tmdb_id > 0
                ORDER BY us.rating DESC, us.updated_at DESC
                LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('uid', $this->userId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'tmdb_id');
    }

    public function getAllTmdbIds(): array {
        $sql = "SELECT s.tmdb_id FROM {$this->table} us
                JOIN dr_series s ON us.series_id = s.id
                WHERE us.user_id = :uid AND s.tmdb_id IS NOT NULL AND s.tmdb_id > 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return array_column($stmt->fetchAll(), 'tmdb_id');
    }

    public function getTopGenreNames(int $limit = 3): array {
        $distribution = $this->getGenreDistribution();
        return array_slice(array_keys($distribution), 0, $limit);
    }
}

