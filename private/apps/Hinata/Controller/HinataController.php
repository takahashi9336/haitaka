<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\NetaModel;
use App\Hinata\Model\EventModel;
use Core\Auth;

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

        // 統計情報の取得
        $groupedNeta = $netaModel->getGroupedNeta();
        $netaCount = 0;
        foreach ($groupedNeta as $group) {
            $netaCount += count($group['items']);
        }

        // 次回イベントの取得
        $nextEvent = $eventModel->getNextEvent();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/portal.php';
    }
}