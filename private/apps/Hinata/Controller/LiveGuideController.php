<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\EventModel;
use App\Hinata\Model\ReleaseModel;
use Core\Auth;

/**
 * 初参戦ライブガイド コントローラ
 */
class LiveGuideController {

    /**
     * 初参戦ガイド一覧（一般ユーザー向け）
     */
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $eventModel = new EventModel();
        $events = $eventModel->getUpcomingLiveEventsForGuide();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/live_guide.php';
    }

    /**
     * ライブガイド楽曲管理（管理者専用）
     */
    public function admin(): void {
        $auth = new Auth();
        $auth->requireHinataAdmin('/hinata/');

        $eventModel = new EventModel();
        $releaseModel = new ReleaseModel();

        $events = $eventModel->getUpcomingLiveEventsForGuide();
        $releases = $releaseModel->getAllReleases();
        $releasesWithSongs = [];
        foreach ($releases as $r) {
            $full = $releaseModel->getReleaseWithSongs((int)$r['id']);
            if ($full && !empty($full['songs'])) {
                $releasesWithSongs[] = $full;
            }
        }
        $likelihoodLabels = \App\Hinata\Model\EventGuideSongModel::LIKELIHOOD_LABELS;
        $trackTypesDisplay = \App\Hinata\Model\SongModel::TRACK_TYPES_DISPLAY;

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/live_guide_admin.php';
    }
}
