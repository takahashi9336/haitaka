<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\NetaModel;
use App\Hinata\Model\EventModel;
use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\MeetGreetModel;
use App\Hinata\Model\ReleaseModel;
use App\Hinata\Model\ReleaseEditionModel;
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

        // 最新リリース情報
        $latestRelease = $this->getLatestRelease();

        // 本日のミーグリ予定
        $todayMeetGreetSlots = [];
        if (!empty($nextEvent) && isset($nextEvent['days_left']) && (int)$nextEvent['days_left'] === 0) {
            $mgModel = new MeetGreetModel();
            $today = date('Y-m-d');
            $todayMeetGreetSlots = $mgModel->getSlotsByDate($today);
        }

        // 推し情報をセッションにキャッシュ
        $favModel->cacheOshiToSession();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
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

        return $release;
    }
}
