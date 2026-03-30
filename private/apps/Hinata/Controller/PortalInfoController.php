<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\TopicModel;
use App\Hinata\Model\AnnouncementModel;
use App\Hinata\Model\EventApplicationModel;
use App\Hinata\Model\EventModel;
use Core\Auth;

/**
 * ポータル情報管理コントローラ（トピック・お知らせ・応募締め切り）
 */
class PortalInfoController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function admin(): void {
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        $topicModel = new TopicModel();
        $announcementModel = new AnnouncementModel();
        $eventModel = new EventModel();

        $topics = $topicModel->getAll();
        $announcements = $announcementModel->getAll();

        // 応募締め切り対象イベント（カテゴリ 1/2/3: ライブ・ミーグリ・リアルミーグリ）
        $events = $eventModel->getAllUpcomingEvents();
        $mgEvents = array_filter($events, fn($e) => in_array((int)$e['category'], [1, 2, 3], true));

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal_info_admin.php';
    }
}
