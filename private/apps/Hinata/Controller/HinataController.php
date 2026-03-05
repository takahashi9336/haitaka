<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\NetaModel;
use App\Hinata\Model\EventModel;
use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\ReleaseModel;
use App\Hinata\Model\ReleaseEditionModel;
use App\Hinata\Model\BlogModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Database;

/**
 * 日向坂ポータル画面の制御
 * 物理パス: haitaka/private/apps/Hinata/Controller/HinataController.php
 */
class HinataController {
    public function portal(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $netaModel = new NetaModel();
        $eventModel = new EventModel();
        $favModel = new FavoriteModel();

        // 統計情報の取得
        $groupedNeta = $netaModel->getGroupedNeta();
        $netaCount = 0;
        foreach ($groupedNeta as $group) {
            $netaCount += count($group['items']);
        }

        // 次回イベントの取得
        $nextEvent = $eventModel->getNextEvent();

        // 推しサマリ（3名分、動画・イベント・楽曲数付き）
        $oshiSummary = $favModel->getOshiPortalSummary();

        // 推しの新着情報（メンバーごとに最新1件）
        $oshiLatestItemByMember = [];
        try {
            $oshiMemberIds = array_column($oshiSummary, 'member_id');
            if (!empty($oshiMemberIds)) {
                $oshiLatestItemByMember = $favModel->getOshiLatestItemPerMember($oshiMemberIds, 3);
            }
        } catch (\Exception $e) {
            // テーブル未作成時は空配列のまま
        }

        // 最新リリース情報
        $latestRelease = $this->getLatestRelease();

        // 本日のミーグリ予定
        $todayMeetGreetSlots = [];
        if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] === 0) {
            $mgModel = new MeetGreetModel();
            $today = date('Y-m-d');
            $todayMeetGreetSlots = $mgModel->getSlotsByDate($today);
        }

        // 最新ブログ（メンバー全員対象、ポカ含む）
        $latestBlogPosts = [];
        try {
            $blogModel = new BlogModel();
            $latestBlogPosts = $blogModel->getLatestAll(20);
        } catch (\Exception $e) {
            // テーブル未作成時は空配列のまま
        }

        // 次の誕生日メンバー
        $upcomingBirthdays = $this->getUpcomingBirthdays();

        // 今日は何の日（日向坂ヒストリー）
        $todayInHistory = $this->getTodayInHistory();

        // 推し情報をセッションにキャッシュ
        $favModel->cacheOshiToSession();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
    }

    /**
     * 次の誕生日メンバー（2週間以内）
     */
    private function getUpcomingBirthdays(): array {
        $pdo = Database::connect();
        $sql = "SELECT * FROM (
                    SELECT m.id, m.name, m.birth_date, m.generation, m.image_url,
                           c1.color_code as color1, c2.color_code as color2,
                           (SELECT mi.image_url FROM hn_member_images mi WHERE mi.member_id = m.id ORDER BY mi.sort_order ASC LIMIT 1) as first_image,
                           CASE
                               WHEN DATE_FORMAT(m.birth_date, '%m-%d') >= DATE_FORMAT(CURDATE(), '%m-%d')
                               THEN DATEDIFF(
                                   CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(m.birth_date, '%m-%d')),
                                   CURDATE()
                               )
                               ELSE DATEDIFF(
                                   CONCAT(YEAR(CURDATE()) + 1, '-', DATE_FORMAT(m.birth_date, '%m-%d')),
                                   CURDATE()
                               )
                           END AS days_until
                    FROM hn_members m
                    LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                    LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                    WHERE m.is_active = 1 AND m.birth_date IS NOT NULL
                ) t
                WHERE days_until <= 14
                ORDER BY days_until ASC";
        return $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 今日は何の日（過去のリリース・イベント）
     */
    private function getTodayInHistory(): array {
        $pdo = Database::connect();
        $items = [];

        $releaseSql = "SELECT 'release' as type, r.id, r.title, r.release_type, r.release_date,
                              YEAR(CURDATE()) - YEAR(r.release_date) as years_ago
                       FROM hn_releases r
                       WHERE DATE_FORMAT(r.release_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                         AND YEAR(r.release_date) < YEAR(CURDATE())
                       ORDER BY r.release_date DESC";
        $items = array_merge($items, $pdo->query($releaseSql)->fetchAll(\PDO::FETCH_ASSOC));

        $eventSql = "SELECT 'event' as type, e.id, e.event_name as title, e.category, e.event_date,
                            YEAR(CURDATE()) - YEAR(e.event_date) as years_ago
                     FROM hn_events e
                     WHERE DATE_FORMAT(e.event_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
                       AND YEAR(e.event_date) < YEAR(CURDATE())
                     ORDER BY e.event_date DESC";
        $items = array_merge($items, $pdo->query($eventSql)->fetchAll(\PDO::FETCH_ASSOC));

        usort($items, function ($a, $b) {
            return ((int)$a['years_ago']) - ((int)$b['years_ago']);
        });

        return $items;
    }

    private function getLatestRelease(): ?array {
        $pdo = Database::connect();
        $sql = "SELECT r.*, 
                       (SELECT COUNT(*) FROM hn_songs WHERE release_id = r.id) as song_count
                FROM hn_releases r 
                WHERE r.release_date IS NOT NULL 
                ORDER BY r.release_date DESC 
                LIMIT 1";
        $release = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
        if (!$release) return null;

        $editionModel = new ReleaseEditionModel();
        $release['jacket_url'] = $editionModel->getMainJacketUrl((int)$release['id']);
        $release['editions'] = $editionModel->getByReleaseId((int)$release['id']);
        $release['release_type_label'] = ReleaseModel::RELEASE_TYPES[$release['release_type']] ?? $release['release_type'];

        $mvSql = "SELECT DISTINCT s.title AS song_title, s.track_number,
                         ma.media_key, ma.thumbnail_url, ma.title AS video_title, ma.platform
                  FROM hn_songs s
                  JOIN hn_song_media_links l ON l.song_id = s.id
                  JOIN hn_media_metadata hmeta ON hmeta.id = l.media_meta_id
                  JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                  WHERE s.release_id = :rid
                    AND hmeta.category = 'MV'
                    AND ma.platform = 'youtube'
                  ORDER BY s.track_number ASC, ma.upload_date DESC";
        $mvStmt = $pdo->prepare($mvSql);
        $mvStmt->execute(['rid' => (int)$release['id']]);
        $release['mvs'] = $mvStmt->fetchAll(\PDO::FETCH_ASSOC);

        $songsSql = "SELECT s.id, s.title, s.track_type, s.track_number,
                            s.apple_music_url, s.spotify_url
                     FROM hn_songs s
                     WHERE s.release_id = :rid
                     ORDER BY s.track_number ASC, s.id ASC";
        $songsStmt = $pdo->prepare($songsSql);
        $songsStmt->execute(['rid' => (int)$release['id']]);
        $release['songs'] = $songsStmt->fetchAll(\PDO::FETCH_ASSOC);

        return $release;
    }
}
