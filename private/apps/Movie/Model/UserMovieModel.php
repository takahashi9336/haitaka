<?php

namespace App\Movie\Model;

use Core\BaseModel;

/**
 * ユーザー映画リストモデル
 * 物理パス: haitaka/private/apps/Movie/Model/UserMovieModel.php
 */
class UserMovieModel extends BaseModel {
    protected string $table = 'mv_user_movies';
    protected bool $isUserIsolated = true;

    protected array $fields = [
        'id', 'user_id', 'movie_id', 'status', 'rating',
        'memo', 'watched_date', 'tags', 'created_at', 'updated_at'
    ];

    /**
     * ステータス別の映画一覧を取得（映画情報JOIN）
     */
    public function getListByStatus(
        string $status,
        string $sort = 'created_at',
        string $order = 'DESC',
        string $filter = '',
        ?string $creditRole = null,
        ?int $creditPersonTmdbId = null,
        ?array $creditPersonTmdbIds = null
    ): array {
        $allowedSort = ['created_at', 'updated_at', 'watched_date', 'rating', 'release_date', 'title'];
        $sortCol = in_array($sort, $allowedSort) ? $sort : 'created_at';

        if ($sortCol === 'release_date') {
            $sortCol = 'm.release_date';
        } elseif ($sortCol === 'title') {
            $sortCol = 'm.title';
        } else {
            $sortCol = "um.{$sortCol}";
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $where = "um.user_id = :uid AND um.status = :status";
        $params = ['uid' => $this->userId, 'status' => $status];
        $joinCredits = '';

        // credits フィルタ（人物×役割）
        $ids = [];
        if (is_array($creditPersonTmdbIds)) {
            foreach ($creditPersonTmdbIds as $v) {
                $iv = (int)$v;
                if ($iv > 0) $ids[] = $iv;
            }
            $ids = array_values(array_unique($ids));
        } elseif ($creditPersonTmdbId !== null && $creditPersonTmdbId > 0) {
            $ids = [(int)$creditPersonTmdbId];
        }

        if ($creditRole !== null && !empty($ids)) {
            $allowedRoles = ['cast', 'director', 'writer'];
            if (in_array($creditRole, $allowedRoles, true)) {
                $joinCredits = " JOIN mv_movie_credits c ON c.movie_id = um.movie_id ";
                $where .= " AND c.role_kind = :credit_role";
                $params['credit_role'] = $creditRole;

                if (count($ids) === 1) {
                    $where .= " AND c.person_tmdb_id = :credit_person_id";
                    $params['credit_person_id'] = (int)$ids[0];
                } else {
                    $ph = [];
                    foreach ($ids as $idx => $pid) {
                        $k = "credit_person_id_{$idx}";
                        $ph[] = ':' . $k;
                        $params[$k] = (int)$pid;
                    }
                    $where .= " AND c.person_tmdb_id IN (" . implode(',', $ph) . ")";
                }
            }
        }

        if ($filter === 'this_month') {
            $where .= " AND um.watched_date >= :month_start AND um.watched_date < :month_end";
            $params['month_start'] = date('Y-m-01');
            $params['month_end'] = date('Y-m-01', strtotime('+1 month'));
        }

        $selectMatchedNames = '';
        $groupBy = '';
        if ($joinCredits !== '') {
            // 作品ごとに「一致した人名だけ」を表示できるよう集計
            // ※ 同一人物が複数行にならないよう DISTINCT
            $selectMatchedNames = ",
                GROUP_CONCAT(DISTINCT c.person_name ORDER BY c.rank_no SEPARATOR ' / ') AS matched_person_names
            ";
            $groupBy = " GROUP BY um.id ";
        }

        $sql = "SELECT um.*, m.tmdb_id, m.title, m.original_title, m.overview,
                       m.poster_path, m.backdrop_path, m.release_date,
                       m.vote_average, m.vote_count, m.genres, m.runtime,
                       m.watch_providers
                       {$selectMatchedNames}
                FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                {$joinCredits}
                WHERE {$where}
                {$groupBy}
                ORDER BY {$sortCol} {$order}, um.id {$order}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * ユーザーの映画エントリを取得
     */
    public function findByMovieId(int $movieId): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :uid AND movie_id = :mid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'mid' => $movieId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 映画をリストに追加
     */
    public function addMovie(int $movieId, string $status = 'watchlist'): bool {
        $existing = $this->findByMovieId($movieId);
        if ($existing) {
            return $this->update((int)$existing['id'], ['status' => $status]);
        }
        return $this->create([
            'movie_id' => $movieId,
            'status' => $status,
        ]);
    }

    /**
     * ステータスを「見た」に変更
     */
    public function markAsWatched(int $id, ?string $watchedDate = null, ?int $rating = null, ?string $memo = null): bool {
        $data = ['status' => 'watched'];
        $data['watched_date'] = (!empty($watchedDate)) ? $watchedDate : null;
        if ($rating !== null) $data['rating'] = $rating;
        if ($memo !== null) $data['memo'] = $memo;
        return $this->update($id, $data);
    }

    /**
     * ステータスを「見たい」に戻す
     */
    public function moveToWatchlist(int $id): bool {
        return $this->update($id, [
            'status' => 'watchlist',
            'watched_date' => null,
            'rating' => null,
        ]);
    }

    /**
     * 映画の評価・メモを更新
     */
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

    /**
     * カウント取得
     */
    public function countByStatus(string $status): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :uid AND status = :status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * 映画詳細をJOIN付きで取得
     */
    public function getDetailWithMovie(int $id): ?array {
        $sql = "SELECT um.*, m.tmdb_id, m.title, m.original_title, m.overview,
                       m.poster_path, m.backdrop_path, m.release_date,
                       m.vote_average, m.vote_count, m.genres, m.runtime
                FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.id = :id AND um.user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $this->userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 月別鑑賞本数（過去N ヶ月）
     */
    public function getMonthlyWatchCounts(int $months = 12): array {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $result[$ym] = 0;
        }
        $start = date('Y-m-01', strtotime("-" . ($months - 1) . " months"));
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

    /**
     * 評価スコア分布 (1-10)
     */
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

    /**
     * 総視聴時間（分）を返す
     */
    public function getTotalWatchedRuntime(): int {
        $sql = "SELECT COALESCE(SUM(m.runtime), 0) AS total
                FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND um.status = 'watched' AND m.runtime > 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 今月の鑑賞数
     */
    public function getThisMonthWatchedCount(): int {
        $start = date('Y-m-01');
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE user_id = :uid AND status = 'watched' AND watched_date >= :start";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'start' => $start]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * ランダムにwatchlistから1件取得
     */
    public function getRandomWatchlistItem(): ?array {
        $sql = "SELECT um.*, m.tmdb_id, m.title, m.original_title, m.overview,
                       m.poster_path, m.backdrop_path, m.release_date,
                       m.vote_average, m.genres, m.runtime
                FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND um.status = 'watchlist'
                ORDER BY RAND() LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * watched映画のジャンル分布を取得
     */
    public function getGenreDistribution(): array {
        $sql = "SELECT m.genres FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND um.status = 'watched' AND m.genres IS NOT NULL";
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

    /**
     * TMDB IDでユーザーの登録状況を確認
     */
    public function findByTmdbId(int $tmdbId): ?array {
        $sql = "SELECT um.* FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND m.tmdb_id = :tmdb_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'tmdb_id' => $tmdbId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 高評価映画のTMDB IDを取得（レコメンド用）
     */
    public function getTopRatedTmdbIds(int $limit = 5): array {
        $sql = "SELECT m.tmdb_id FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND um.status = 'watched'
                  AND um.rating >= 7 AND m.tmdb_id IS NOT NULL AND m.tmdb_id > 0
                ORDER BY um.rating DESC, um.updated_at DESC
                LIMIT :lim";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('uid', $this->userId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'tmdb_id');
    }

    /**
     * ユーザーが登録済みの全TMDB IDリスト（除外フィルタ用）
     */
    public function getAllTmdbIds(): array {
        $sql = "SELECT m.tmdb_id FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND m.tmdb_id IS NOT NULL AND m.tmdb_id > 0";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return array_column($stmt->fetchAll(), 'tmdb_id');
    }

    /**
     * ユーザーの上位ジャンル名を取得（Discover用）
     */
    public function getTopGenreNames(int $limit = 3): array {
        $distribution = $this->getGenreDistribution();
        return array_slice(array_keys($distribution), 0, $limit);
    }

    /**
     * credits（俳優/監督/脚本）のランキングを取得（見た映画本数ベース）
     * @return array<int, array{person_tmdb_id:int, person_name:string, movie_count:int}>
     */
    public function getCreditsRankingByRole(string $roleKind, int $limit = 5): array {
        $allowed = ['cast', 'director', 'writer'];
        if (!in_array($roleKind, $allowed, true)) {
            return [];
        }
        $limit = max(1, min(20, $limit));

        $sql = "
            SELECT
                c.person_tmdb_id,
                MIN(c.person_name) AS person_name,
                COUNT(DISTINCT um.movie_id) AS movie_count
            FROM mv_user_movies um
            JOIN mv_movie_credits c ON c.movie_id = um.movie_id
            WHERE um.user_id = :uid
              AND um.status = 'watched'
              AND c.role_kind = :role_kind
            GROUP BY c.person_tmdb_id
            ORDER BY movie_count DESC, person_name ASC
            LIMIT {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid' => $this->userId,
            'role_kind' => $roleKind,
        ]);
        $rows = $stmt->fetchAll() ?: [];
        return array_map(function($r) {
            return [
                'person_tmdb_id' => (int)($r['person_tmdb_id'] ?? 0),
                'person_name' => (string)($r['person_name'] ?? ''),
                'movie_count' => (int)($r['movie_count'] ?? 0),
            ];
        }, $rows);
    }
}
