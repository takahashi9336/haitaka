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
        'memo', 'watched_date', 'created_at', 'updated_at'
    ];

    /**
     * ステータス別の映画一覧を取得（映画情報JOIN）
     */
    public function getListByStatus(string $status, string $sort = 'created_at', string $order = 'DESC'): array {
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

        $sql = "SELECT um.*, m.tmdb_id, m.title, m.original_title, m.overview,
                       m.poster_path, m.backdrop_path, m.release_date,
                       m.vote_average, m.vote_count, m.genres, m.runtime
                FROM {$this->table} um
                JOIN mv_movies m ON um.movie_id = m.id
                WHERE um.user_id = :uid AND um.status = :status
                ORDER BY {$sortCol} {$order}, um.id {$order}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'status' => $status]);
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
}
