<?php

namespace App\FriendsActivity\Service;

use Core\Database;
use App\FriendsActivity\Model\FriendGroupModel;

/**
 * 友人の視聴履歴を3アプリ（アニメ・映画・ドラマ）横断で取得するサービス
 */
class FriendsActivityService {

    private \PDO $pdo;
    private FriendGroupModel $friendGroupModel;

    public function __construct() {
        $this->pdo = Database::connect();
        $this->friendGroupModel = new FriendGroupModel();
    }

    /**
     * 閲覧可能なユーザー一覧（id_name 付き）を取得
     *
     * @return array 各要素: user_id, id_name
     */
    public function getViewableUsersWithNames(int $currentUserId): array {
        try {
            $viewableIds = $this->friendGroupModel->getViewableUserIds($currentUserId);
        } catch (\Throwable $e) {
            return [];
        }
        if (empty($viewableIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($viewableIds), '?'));
        $sql = "SELECT id AS user_id, id_name FROM sys_users WHERE id IN ($placeholders) ORDER BY id_name ASC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($viewableIds);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 友人の視聴済み一覧を取得（ソート済み・統合形式）
     *
     * @param int $currentUserId ログインユーザーID
     * @param int|null $limit 取得件数（null=全件）
     * @param string|null $filter フィルタ: anime, movie, drama, または null=全て
     * @param int|null $filterUserId 特定ユーザーで絞り込み（null=全員）
     * @return array 各要素: type, title, detail_url, item_id, user_id, id_name, watched_date, image_url
     */
    public function getFriendsWatchedItems(int $currentUserId, ?int $limit = null, ?string $filter = null, ?int $filterUserId = null): array {
        try {
            $viewableIds = $this->friendGroupModel->getViewableUserIds($currentUserId);
        } catch (\Throwable $e) {
            return [];
        }
        if (empty($viewableIds)) {
            return [];
        }
        if ($filterUserId !== null) {
            if (!in_array($filterUserId, $viewableIds, true)) {
                return [];
            }
            $viewableIds = [$filterUserId];
        }

        $placeholders = implode(',', array_fill(0, count($viewableIds), '?'));
        $items = [];

        if ($filter === null || $filter === 'anime') {
            $items = array_merge($items, $this->fetchAnimeWatched($viewableIds, $placeholders));
        }
        if ($filter === null || $filter === 'movie') {
            $items = array_merge($items, $this->fetchMovieWatched($viewableIds, $placeholders));
        }
        if ($filter === null || $filter === 'drama') {
            $items = array_merge($items, $this->fetchDramaWatched($viewableIds, $placeholders));
        }

        usort($items, function ($a, $b) {
            $da = $a['sort_date'] ?? '';
            $db = $b['sort_date'] ?? '';
            return strcmp($db, $da);
        });

        if ($limit !== null && $limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        $items = $this->enrichWithRegistered($items, $currentUserId);

        return array_map(function ($row) {
            unset($row['sort_date']);
            return $row;
        }, $items);
    }

    /**
     * 各アイテムに現在ユーザーの登録済みフラグ _registered と user_status/user_movie_id/user_series_id を付与
     */
    private function enrichWithRegistered(array $items, int $currentUserId): array {
        $animeIds = $this->getMyAnimeIds($currentUserId);
        $movieStatusMap = $this->getMyMovieStatusMap($currentUserId);
        $dramaStatusMap = $this->getMyDramaStatusMap($currentUserId);

        foreach ($items as &$item) {
            if ($item['type'] === 'anime') {
                $item['_registered'] = in_array((int)($item['item_id'] ?? 0), $animeIds, true);
            } elseif ($item['type'] === 'movie' && !empty($item['tmdb_id'])) {
                $tid = (int)$item['tmdb_id'];
                $entry = $movieStatusMap[$tid] ?? null;
                $item['_registered'] = $entry !== null;
                $item['user_status'] = $entry['status'] ?? null;
                $item['user_movie_id'] = $entry['user_movie_id'] ?? null;
            } elseif ($item['type'] === 'drama' && !empty($item['tmdb_id'])) {
                $tid = (int)$item['tmdb_id'];
                $entry = $dramaStatusMap[$tid] ?? null;
                $item['_registered'] = $entry !== null;
                $item['user_status'] = $entry['status'] ?? null;
                $item['user_series_id'] = $entry['user_series_id'] ?? null;
            }
        }
        return $items;
    }

    private function getMyAnimeIds(int $userId): array {
        try {
            $sql = "SELECT DISTINCT annict_work_id FROM an_user_works WHERE user_id = ? AND annict_work_id IS NOT NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'annict_work_id'));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array tmdb_id => ['status'=>'watchlist'|'watched', 'user_movie_id'=>id] */
    private function getMyMovieStatusMap(int $userId): array {
        try {
            $sql = "SELECT m.tmdb_id, um.id AS user_movie_id, um.status FROM mv_user_movies um JOIN mv_movies m ON um.movie_id = m.id WHERE um.user_id = ? AND m.tmdb_id IS NOT NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $map = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $tid = (int)$r['tmdb_id'];
                $map[$tid] = ['status' => $r['status'], 'user_movie_id' => (int)$r['user_movie_id']];
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array tmdb_id => ['status'=>'wanna_watch'|'watching'|'watched', 'user_series_id'=>id] */
    private function getMyDramaStatusMap(int $userId): array {
        try {
            $sql = "SELECT s.tmdb_id, us.id AS user_series_id, us.status FROM dr_user_series us JOIN dr_series s ON us.series_id = s.id WHERE us.user_id = ? AND s.tmdb_id IS NOT NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $map = [];
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $tid = (int)$r['tmdb_id'];
                $map[$tid] = ['status' => $r['status'], 'user_series_id' => (int)$r['user_series_id']];
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 閲覧可能なユーザーがいるか
     */
    public function hasViewableUsers(int $currentUserId): bool {
        try {
            return count($this->friendGroupModel->getViewableUserIds($currentUserId)) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function fetchAnimeWatched(array $userIds, string $placeholders): array {
        $sql = "SELECT uw.user_id, uw.annict_work_id AS item_id, uw.watched_date, uw.updated_at,
                       w.title, w.image_url,
                       u.id_name
                FROM an_user_works uw
                JOIN an_works w ON uw.work_id = w.id
                JOIN sys_users u ON uw.user_id = u.id
                WHERE uw.user_id IN ($placeholders) AND uw.status = 'watched'
                ORDER BY uw.updated_at DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($userIds);
        } catch (\Throwable $e) {
            return [];
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($r) {
            return [
                'type' => 'anime',
                'title' => $r['title'] ?? '',
                'detail_url' => '/anime/detail.php?id=' . (int)($r['item_id'] ?? 0),
                'item_id' => (int)($r['item_id'] ?? 0),
                'user_id' => (int)($r['user_id'] ?? 0),
                'id_name' => $r['id_name'] ?? '',
                'watched_date' => $r['watched_date'] ?? null,
                'image_url' => $r['image_url'] ?? null,
                'sort_date' => $r['watched_date'] ?: ($r['updated_at'] ?? ''),
            ];
        }, $rows);
    }

    private function fetchMovieWatched(array $userIds, string $placeholders): array {
        $sql = "SELECT um.user_id, um.id AS item_id, um.watched_date, um.updated_at,
                       m.title, m.original_title, m.overview, m.poster_path, m.tmdb_id, m.release_date,
                       u.id_name
                FROM mv_user_movies um
                JOIN mv_movies m ON um.movie_id = m.id
                JOIN sys_users u ON um.user_id = u.id
                WHERE um.user_id IN ($placeholders) AND um.status = 'watched'
                ORDER BY um.updated_at DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($userIds);
        } catch (\Throwable $e) {
            return [];
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($r) {
            $posterPath = $r['poster_path'] ?? null;
            $imageUrl = $posterPath ? 'https://image.tmdb.org/t/p/w342' . $posterPath : null;
            return [
                'type' => 'movie',
                'title' => $r['title'] ?? '',
                'detail_url' => '/movie/detail.php?id=' . (int)($r['item_id'] ?? 0),
                'item_id' => (int)($r['item_id'] ?? 0),
                'tmdb_id' => !empty($r['tmdb_id']) ? (int)$r['tmdb_id'] : null,
                'poster_path' => $posterPath,
                'overview' => $r['overview'] ?? null,
                'release_date' => $r['release_date'] ?? null,
                'original_title' => $r['original_title'] ?? null,
                'user_id' => (int)($r['user_id'] ?? 0),
                'id_name' => $r['id_name'] ?? '',
                'watched_date' => $r['watched_date'] ?? null,
                'image_url' => $imageUrl,
                'sort_date' => $r['watched_date'] ?: ($r['updated_at'] ?? ''),
            ];
        }, $rows);
    }

    private function fetchDramaWatched(array $userIds, string $placeholders): array {
        $sql = "SELECT us.user_id, us.id AS item_id, us.watched_date, us.updated_at,
                       s.title, s.original_title, s.overview, s.poster_path, s.tmdb_id,
                       s.first_air_date, s.number_of_seasons, s.number_of_episodes,
                       u.id_name
                FROM dr_user_series us
                JOIN dr_series s ON us.series_id = s.id
                JOIN sys_users u ON us.user_id = u.id
                WHERE us.user_id IN ($placeholders) AND us.status = 'watched'
                ORDER BY us.updated_at DESC";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($userIds);
        } catch (\Throwable $e) {
            return [];
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(function ($r) {
            $posterPath = $r['poster_path'] ?? null;
            $imageUrl = $posterPath ? 'https://image.tmdb.org/t/p/w342' . $posterPath : null;
            return [
                'type' => 'drama',
                'title' => $r['title'] ?? '',
                'detail_url' => '/drama/detail.php?id=' . (int)($r['item_id'] ?? 0),
                'item_id' => (int)($r['item_id'] ?? 0),
                'tmdb_id' => !empty($r['tmdb_id']) ? (int)$r['tmdb_id'] : null,
                'poster_path' => $posterPath,
                'overview' => $r['overview'] ?? null,
                'first_air_date' => $r['first_air_date'] ?? null,
                'number_of_seasons' => isset($r['number_of_seasons']) ? (int)$r['number_of_seasons'] : null,
                'number_of_episodes' => isset($r['number_of_episodes']) ? (int)$r['number_of_episodes'] : null,
                'original_title' => $r['original_title'] ?? null,
                'user_id' => (int)($r['user_id'] ?? 0),
                'id_name' => $r['id_name'] ?? '',
                'watched_date' => $r['watched_date'] ?? null,
                'image_url' => $imageUrl,
                'sort_date' => $r['watched_date'] ?: ($r['updated_at'] ?? ''),
            ];
        }, $rows);
    }
}
