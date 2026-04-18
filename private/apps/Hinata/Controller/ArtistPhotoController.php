<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\ReleaseMemberImageModel;
use App\Hinata\Model\ReleaseModel;
use Core\Auth;

/**
 * アー写一覧（閲覧）コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/ArtistPhotoController.php
 */
class ArtistPhotoController {
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireLogin();

        $tab = ($_GET['tab'] ?? '') === 'members' ? 'members' : 'releases';
        $releaseIdFilter = isset($_GET['release_id']) ? (int)$_GET['release_id'] : null;
        $memberIdFilter = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;

        $releaseModel = new ReleaseModel();
        $memberModel = new MemberModel();
        $rmiModel = new ReleaseMemberImageModel();

        $releases = $releaseModel->getAllReleasesWithSummary();
        $members = $memberModel->getAllWithColors();

        $releaseIds = array_map('intval', array_column($releases, 'id'));
        $memberIds = array_map('intval', array_column($members, 'id'));

        $rowsByRelease = $rmiModel->getRowsByReleaseIds($releaseIds);
        $rowsByMember = $rmiModel->getRowsByMemberIds($memberIds);

        $user = $_SESSION['user'] ?? [];
        $releaseTypes = ReleaseModel::RELEASE_TYPES;

        require_once __DIR__ . '/../Views/artist_photos_index.php';
    }
}

