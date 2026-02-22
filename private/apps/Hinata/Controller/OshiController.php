<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\MemberModel;
use App\Hinata\Model\NetaModel;
use App\Hinata\Model\SongModel;
use App\Hinata\Model\EventModel;
use Core\Auth;
use Core\Database;

/**
 * 推し管理コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/OshiController.php
 */
class OshiController {

    /**
     * 推し設定ページ
     */
    public function settings(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $memberModel = new MemberModel();
        $favModel = new FavoriteModel();

        $members = $memberModel->getActiveMembersWithColors();
        $favorites = $favModel->getUserFavorites();
        $oshiMembers = $favModel->getOshiMembers();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/oshi_settings.php';
    }

    /**
     * 推しデータAPI（ポータル用サマリ）
     */
    public function oshiData(): void {
        header('Content-Type: application/json');
        try {
            $favModel = new FavoriteModel();
            $summary = $favModel->getOshiPortalSummary();
            echo json_encode(['status' => 'success', 'data' => $summary], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 推し個別ページ
     */
    public function memberPage(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $memberId = (int)($_GET['id'] ?? 0);
        if (!$memberId) {
            header('Location: /hinata/');
            exit;
        }

        $memberModel = new MemberModel();
        $member = $memberModel->getMemberDetail($memberId);
        if (!$member) {
            header('Location: /hinata/');
            exit;
        }

        $favModel = new FavoriteModel();
        $oshiLevel = $favModel->getMemberLevel($memberId);

        // 参加楽曲
        $memberSongs = $this->getMemberSongs($memberId);

        // ソロ出演動画（YouTube の SoloPV / 単独紐づけ動画）
        $soloVideos = $this->getMemberSoloVideos($memberId);
        // YouTube参加動画（他メンバーと共演）
        $youtubeGroupVideos = $this->getMemberYoutubeGroupVideos($memberId);
        // Instagram動画（全件）
        $instagramVideos = $this->getMemberVideosByPlatform($memberId, 'instagram');
        // TikTok動画（全件）
        $tiktokVideos = $this->getMemberVideosByPlatform($memberId, 'tiktok');

        // 参加イベント（今後 + 過去5件）
        $memberEvents = $this->getMemberEvents($memberId);

        // マイフォト（oshi_images）
        $oshiImages = $this->getOshiImages($memberId);

        // ミーグリネタ
        $memberNeta = $this->getMemberNeta($memberId);

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/oshi_member.php';
    }

    private function getMemberSongs(int $memberId): array {
        $pdo = Database::connect();
        $sql = "SELECT s.id, s.title, s.track_type, s.track_number,
                       r.title as release_title, r.release_date, r.release_type,
                       sm.is_center, sm.row_number, sm.position
                FROM hn_song_members sm
                JOIN hn_songs s ON sm.song_id = s.id
                JOIN hn_releases r ON s.release_id = r.id
                WHERE sm.member_id = :mid
                ORDER BY r.release_date DESC, s.track_number ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * ソロ出演動画: 全プラットフォーム横断で SoloPV カテゴリ、または紐づけメンバーが1名のみの動画
     */
    private function getMemberSoloVideos(int $memberId): array {
        $pdo = Database::connect();
        $sql = "SELECT ma.media_key, ma.title, ma.thumbnail_url, ma.platform,
                       ma.upload_date, ma.description, hmeta.category, ma.sub_key
                FROM hn_media_members mm
                JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                WHERE mm.member_id = :mid
                  AND (
                    hmeta.category = 'SoloPV'
                    OR (SELECT COUNT(*) FROM hn_media_members mm2 WHERE mm2.media_meta_id = mm.media_meta_id) = 1
                  )
                ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * YouTube参加動画: このメンバーが出演する全 YouTube 動画（ソロと重複あり）
     */
    private function getMemberYoutubeGroupVideos(int $memberId): array {
        $pdo = Database::connect();
        $sql = "SELECT ma.media_key, ma.title, ma.thumbnail_url, ma.platform,
                       ma.upload_date, ma.description, hmeta.category, ma.sub_key
                FROM hn_media_members mm
                JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                WHERE mm.member_id = :mid
                  AND ma.platform = 'youtube'
                ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 指定プラットフォームの全動画（Instagram / TikTok 用）
     */
    private function getMemberVideosByPlatform(int $memberId, string $platform): array {
        $pdo = Database::connect();
        $sql = "SELECT ma.media_key, ma.title, ma.thumbnail_url, ma.platform,
                       ma.upload_date, ma.description, hmeta.category, ma.sub_key
                FROM hn_media_members mm
                JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                WHERE mm.member_id = :mid
                  AND ma.platform = :plat
                ORDER BY COALESCE(ma.upload_date, ma.created_at) DESC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId, 'plat' => $platform]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getMemberEvents(int $memberId): array {
        $pdo = Database::connect();
        $sql = "SELECT e.id, e.event_name, e.event_date, e.category, e.event_place,
                       DATEDIFF(e.event_date, CURDATE()) as days_left
                FROM hn_event_members em
                JOIN hn_events e ON e.id = em.event_id
                WHERE em.member_id = :mid
                ORDER BY e.event_date DESC
                LIMIT 20";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getOshiImages(int $memberId): array {
        $pdo = Database::connect();
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "SELECT * FROM hn_oshi_images
                WHERE user_id = :uid AND member_id = :mid
                ORDER BY sort_order ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getMemberNeta(int $memberId): array {
        $pdo = Database::connect();
        $userId = $_SESSION['user']['id'] ?? 0;
        $sql = "SELECT * FROM hn_neta
                WHERE user_id = :uid AND member_id = :mid AND status != 'delete'
                ORDER BY FIELD(status, 'stock', 'done'), created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId, 'mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
