<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 推し（お気に入り）管理モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/FavoriteModel.php
 *
 * レベル定義:
 *   0 = その他, 1 = 気になる, 7 = 3推し, 8 = 2推し, 9 = 最推し
 *   level 7-9 はユーザーにつき各1名のみ（排他）
 */
class FavoriteModel extends BaseModel {
    protected string $table = 'hn_favorites';
    protected array $fields = ['id', 'user_id', 'member_id', 'level', 'created_at'];
    protected bool $isUserIsolated = true;

    public const LEVEL_NONE      = 0;
    public const LEVEL_KINNINARU = 1;
    public const LEVEL_OSHI_3    = 7;
    public const LEVEL_OSHI_2    = 8;
    public const LEVEL_OSHI_TOP  = 9;

    public const OSHI_LEVELS = [self::LEVEL_OSHI_3, self::LEVEL_OSHI_2, self::LEVEL_OSHI_TOP];

    public const LEVEL_LABELS = [
        self::LEVEL_OSHI_TOP  => '最推し',
        self::LEVEL_OSHI_2    => '2推し',
        self::LEVEL_OSHI_3    => '3推し',
        self::LEVEL_KINNINARU => '気になる',
    ];

    /**
     * ユーザーの推し3名を取得（level 7-9、メンバー情報付き）
     */
    public function getOshiMembers(): array {
        $sql = "SELECT f.level, f.member_id, f.created_at as fav_created_at,
                       m.name, m.kana, m.generation, m.image_url, m.blog_url, m.insta_url,
                       c1.color_code as color1, c1.color_name as color1_name,
                       c2.color_code as color2, c2.color_name as color2_name
                FROM {$this->table} f
                JOIN hn_members m ON f.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE f.user_id = :uid AND f.level IN (7, 8, 9)
                ORDER BY f.level DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $memberIds = array_column($rows, 'member_id');
        $imagesMap = (new MemberModel())->getMemberImagesMap($memberIds);

        // ユーザ設定のプロフィール画像を優先
        $userProfileMap = [];
        if (!empty($memberIds)) {
            $ph = implode(',', array_fill(0, count($memberIds), '?'));
            $upStmt = $this->pdo->prepare(
                "SELECT member_id, image_path FROM hn_user_member_profiles WHERE user_id = ? AND member_id IN ($ph)"
            );
            $upStmt->execute(array_merge([$this->userId], $memberIds));
            foreach ($upStmt->fetchAll(\PDO::FETCH_ASSOC) as $up) {
                $userProfileMap[(int)$up['member_id']] = $up['image_path'];
            }
        }

        foreach ($rows as &$r) {
            $mid = (int)$r['member_id'];
            if (isset($userProfileMap[$mid])) {
                $r['image_url'] = '/' . $userProfileMap[$mid];
                $r['is_custom_image'] = true;
            } else {
                $imgs = $imagesMap[$mid] ?? [];
                $r['image_url'] = $imgs[0] ?? $r['image_url'] ?? null;
                $r['is_custom_image'] = false;
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * ポータル用サマリ: 推し3名分の最新出演動画・次イベント・参加楽曲数を一括取得
     */
    public function getOshiPortalSummary(): array {
        $oshiMembers = $this->getOshiMembers();
        if (empty($oshiMembers)) {
            return [];
        }

        $memberIds = array_column($oshiMembers, 'member_id');
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));

        // 最新出演動画（メンバーごとに1件）
        $latestVideos = $this->getLatestVideosByMembers($memberIds, $placeholders);

        // 次イベント（メンバーごとに1件）
        $nextEvents = $this->getNextEventsByMembers($memberIds, $placeholders);

        // 参加楽曲数
        $songCounts = $this->getSongCountsByMembers($memberIds, $placeholders);

        foreach ($oshiMembers as &$m) {
            $mid = $m['member_id'];
            $m['latest_video'] = $latestVideos[$mid] ?? null;
            $m['next_event'] = $nextEvents[$mid] ?? null;
            $m['song_count'] = $songCounts[$mid] ?? 0;
        }
        unset($m);

        return $oshiMembers;
    }

    private function getLatestVideosByMembers(array $memberIds, string $placeholders): array {
        $sql = "SELECT mm.member_id,
                       ma.media_key, ma.title as video_title,
                       ma.thumbnail_url, ma.platform, ma.upload_date
                FROM hn_media_members mm
                JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                WHERE mm.member_id IN ($placeholders)
                  AND ma.platform = 'youtube'
                ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($memberIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $mid = $row['member_id'];
            if (!isset($result[$mid])) {
                $result[$mid] = $row;
            }
        }
        return $result;
    }

    private function getNextEventsByMembers(array $memberIds, string $placeholders): array {
        $sql = "SELECT em.member_id, e.event_name, e.event_date, e.category,
                       DATEDIFF(e.event_date, CURDATE()) as days_left
                FROM hn_event_members em
                JOIN hn_events e ON e.id = em.event_id
                WHERE em.member_id IN ($placeholders)
                  AND e.event_date >= CURDATE()
                ORDER BY e.event_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($memberIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $mid = $row['member_id'];
            if (!isset($result[$mid])) {
                $result[$mid] = $row;
            }
        }
        return $result;
    }

    private function getSongCountsByMembers(array $memberIds, string $placeholders): array {
        $sql = "SELECT member_id, COUNT(*) as cnt
                FROM hn_song_members
                WHERE member_id IN ($placeholders)
                GROUP BY member_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($memberIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['member_id']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * 推しメンバーごとの最新N件を取得（タイムラインと同じロジックで同期）
     * ブログ・ニュース・スケジュール(翌日以前)・イベント(翌日以前)・動画
     * @param int $limitPerMember メンバーあたりの件数
     * @return array [member_id => [[type, title, event_date, url, member_name], ...], ...]
     */
    public function getOshiLatestItemPerMember(array $memberIds, int $limitPerMember = 3): array {
        if (empty($memberIds)) return [];

        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $unions = [];
        $unions[] = "SELECT 'blog' as type, bp.title, bp.published_at as event_date, bp.detail_url as url, bp.member_id, m.name as member_name
                     FROM hn_blog_posts bp
                     JOIN hn_members m ON m.id = bp.member_id
                     WHERE bp.member_id IN ($placeholders)";
        $unions[] = "SELECT 'news' as type, n.title, n.published_date as event_date, n.detail_url as url, nm.member_id, m.name as member_name
                     FROM hn_news n
                     JOIN hn_news_members nm ON nm.news_id = n.id
                     JOIN hn_members m ON m.id = nm.member_id
                     WHERE nm.member_id IN ($placeholders)";
        $unions[] = "SELECT 'schedule' as type, s.title, s.schedule_date as event_date, s.detail_url as url, sm.member_id, m.name as member_name
                     FROM hn_schedule s
                     JOIN hn_schedule_members sm ON sm.schedule_id = s.id
                     JOIN hn_members m ON m.id = sm.member_id
                     WHERE sm.member_id IN ($placeholders) AND s.schedule_date <= CURDATE()";
        $unions[] = "SELECT 'event' as type, e.event_name as title, e.event_date as event_date, NULL as url, em.member_id, m.name as member_name
                     FROM hn_events e
                     JOIN hn_event_members em ON em.event_id = e.id
                     JOIN hn_members m ON m.id = em.member_id
                     WHERE em.member_id IN ($placeholders) AND e.event_date <= CURDATE()";
        $unions[] = "SELECT 'video' as type, COALESCE(ma.title, '動画') as title, COALESCE(ma.upload_date, ma.created_at) as event_date,
                            CASE ma.platform
                                WHEN 'tiktok' THEN CONCAT('https://www.tiktok.com/', COALESCE(NULLIF(TRIM(ma.sub_key), ''), '@user'), '/video/', ma.media_key)
                                WHEN 'instagram' THEN CONCAT('https://www.instagram.com/reel/', ma.media_key, '/')
                                ELSE CONCAT('https://www.youtube.com/watch?v=', ma.media_key)
                            END as url,
                            mm.member_id, m.name as member_name
                     FROM hn_media_members mm
                     JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                     JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                     JOIN hn_members m ON m.id = mm.member_id
                     WHERE mm.member_id IN ($placeholders)";

        $params = array_merge($memberIds, $memberIds, $memberIds, $memberIds, $memberIds);
        $sql = "SELECT * FROM (" . implode(" UNION ALL ", $unions) . ") combined
                ORDER BY event_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $mid = (int)$row['member_id'];
            if (!isset($result[$mid])) {
                $result[$mid] = [];
            }
            if (count($result[$mid]) < $limitPerMember) {
                $result[$mid][] = $row;
            }
        }
        return $result;
    }

    /**
     * 推しレベル設定（level 7-9 は排他制御付き）
     * @return array ['status', 'level', 'swapped_member_id'?, 'swapped_member_name'?]
     */
    public function setLevel(int $memberId, int $level): array {
        $swapped = null;

        // level 7-9 は排他: 同レベルの既存レコードを解除
        if (in_array($level, self::OSHI_LEVELS, true)) {
            $stmt = $this->pdo->prepare(
                "SELECT f.id, f.member_id, m.name
                 FROM {$this->table} f
                 JOIN hn_members m ON m.id = f.member_id
                 WHERE f.user_id = :uid AND f.level = :lv AND f.member_id != :mid"
            );
            $stmt->execute(['uid' => $this->userId, 'lv' => $level, 'mid' => $memberId]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($existing) {
                $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")
                    ->execute([$existing['id']]);
                $swapped = [
                    'member_id' => $existing['member_id'],
                    'member_name' => $existing['name'],
                ];
            }
        }

        // 対象メンバーの現在の状態を確認
        $stmt = $this->pdo->prepare(
            "SELECT id, level FROM {$this->table} WHERE user_id = :uid AND member_id = :mid"
        );
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        $fav = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($level <= 0) {
            if ($fav) {
                $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")
                    ->execute([$fav['id']]);
            }
            $result = ['status' => 'success', 'level' => 0];
        } elseif ($fav) {
            $this->pdo->prepare("UPDATE {$this->table} SET level = ? WHERE id = ?")
                ->execute([$level, $fav['id']]);
            $result = ['status' => 'success', 'level' => $level];
        } else {
            $this->pdo->prepare(
                "INSERT INTO {$this->table} (user_id, member_id, level, created_at) VALUES (?, ?, ?, NOW())"
            )->execute([$this->userId, $memberId, $level]);
            $result = ['status' => 'success', 'level' => $level];
        }

        if ($swapped) {
            $result['swapped_member_id'] = $swapped['member_id'];
            $result['swapped_member_name'] = $swapped['member_name'];
        }

        $this->cacheOshiToSession();

        return $result;
    }

    /**
     * ユーザーの全お気に入り取得
     */
    public function getUserFavorites(): array {
        $sql = "SELECT f.*, m.name, m.kana, m.generation, m.image_url
                FROM {$this->table} f
                JOIN hn_members m ON f.member_id = m.id
                WHERE f.user_id = :uid
                ORDER BY f.level DESC, m.kana ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 推し情報をセッションにキャッシュ
     */
    public function cacheOshiToSession(): void {
        $stmt = $this->pdo->prepare(
            "SELECT f.level, f.member_id, m.name, m.image_url
             FROM {$this->table} f
             JOIN hn_members m ON f.member_id = m.id
             WHERE f.user_id = :uid AND f.level IN (7, 8, 9)
             ORDER BY f.level DESC"
        );
        $stmt->execute(['uid' => $this->userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $memberIds = array_column($rows, 'member_id');
        $imagesMap = empty($memberIds) ? [] : (new MemberModel())->getMemberImagesMap($memberIds);

        $oshi = [];
        foreach ($rows as $r) {
            $imgs = $imagesMap[$r['member_id']] ?? [];
            $oshi[(int)$r['level']] = [
                'id'        => (int)$r['member_id'],
                'name'      => $r['name'],
                'image_url' => $imgs[0] ?? $r['image_url'] ?? null,
            ];
        }
        $_SESSION['oshi'] = $oshi;
    }

    /**
     * 指定メンバーの現在レベルを取得
     */
    public function getMemberLevel(int $memberId): int {
        $stmt = $this->pdo->prepare(
            "SELECT level FROM {$this->table} WHERE user_id = :uid AND member_id = :mid"
        );
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['level'] : 0;
    }
}
