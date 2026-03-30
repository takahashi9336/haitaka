<?php

namespace App\FriendsActivity\Controller;

use Core\Auth;
use App\FriendsActivity\Service\FriendsActivityService;

class FriendsActivityController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function activity(): void {
        $this->auth->requireLogin();

        $items = [];
        $viewableUsers = [];
        $hasViewable = false;

        try {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $service = new FriendsActivityService();

            if (!$service->hasViewableUsers($userId)) {
                // 友達/グループ未設定の場合はそのまま空で表示
            } else {
                $filter = $_GET['filter'] ?? null;
                if ($filter !== null && !in_array($filter, ['anime', 'movie', 'drama'], true)) {
                    $filter = null;
                }
                $filterUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
                if ($filterUserId !== null && $filterUserId <= 0) {
                    $filterUserId = null;
                }
                $items = $service->getFriendsWatchedItems($userId, null, $filter, $filterUserId);
                $viewableUsers = $service->getViewableUsersWithNames($userId);
                $hasViewable = true;
            }
        } catch (\Throwable $e) {
            \Core\Logger::errorWithContext('Friends activity page error', $e);
        }

        $user = $_SESSION['user'];
        $appKey = 'dashboard';
        require_once __DIR__ . '/../../../components/theme_from_session.php';
        require_once __DIR__ . '/../Views/activity.php';
    }
}
