<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\ReleaseMemberImageModel;
use App\Hinata\Model\ReleaseModel;
use App\Hinata\Helper\MemberGroupHelper;
use Core\Auth;

/**
 * ペンライトカラー表（初心者向け）
 */
class PenlightController {
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireLogin();

        $model = new MemberModel();
        $members = $model->getMembersForBook(); // 現役+卒業（カラー付き）
        // ポカは本表では表示しない
        $members = array_values(array_filter($members, static function ($m) {
            return (int)($m['id'] ?? 0) !== MemberGroupHelper::POKA_MEMBER_ID;
        }));

        // 最新シングルのメンバーアー写を付与
        $latestSingleReleaseId = 0;
        $releaseModel = new ReleaseModel();
        foreach ($releaseModel->getAllReleases() as $r) {
            if (($r['release_type'] ?? '') === 'single') {
                $latestSingleReleaseId = (int)($r['id'] ?? 0);
                break;
            }
        }
        $artistImageMap = [];
        if ($latestSingleReleaseId > 0) {
            $releaseMemberImageModel = new ReleaseMemberImageModel();
            $artistImageMap = $releaseMemberImageModel->getMapByReleaseId($latestSingleReleaseId);
        }
        foreach ($members as &$m) {
            $mid = (int)($m['id'] ?? 0);
            $m['latest_single_artist_image'] = $artistImageMap[$mid] ?? null;
        }
        unset($m);

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/penlight.php';
    }
}

