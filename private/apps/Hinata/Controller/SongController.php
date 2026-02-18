<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\SongModel;
use App\Hinata\Model\ReleaseModel;
use App\Hinata\Model\ReleaseEditionModel;
use App\Hinata\Model\ReleaseMemberImageModel;
use App\Hinata\Model\SongMemberModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;

/**
 * 楽曲ページ用コントローラ（公開：リリース一覧・全曲一覧・楽曲個別紹介）
 * 物理パス: haitaka/private/apps/Hinata/Controller/SongController.php
 */
class SongController {

    /**
     * 楽曲トップ：リリース一覧／全曲一覧タブ
     */
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $releaseModel = new ReleaseModel();
        $songModel = new SongModel();

        $releases = $releaseModel->getAllReleasesWithSummary();
        foreach ($releases as &$r) {
            $r['title_center'] = $songModel->getTitleTrackCenterNames((int)$r['id']);
        }
        unset($r);

        $releaseIdFilter = isset($_GET['release_id']) ? (int)$_GET['release_id'] : null;

        // 全曲一覧用：リリース単位でグルーピングし、各リリースに type_a ジャケットURL と曲リストを付与
        $songsByRelease = [];
        foreach ($releases as $r) {
            if ($releaseIdFilter !== null && (int)$r['id'] !== $releaseIdFilter) {
                continue;
            }
            $songsList = $songModel->getAllSongsWithRelease((int)$r['id']);
            $jacketUrl = null;
            foreach ($r['editions'] ?? [] as $ed) {
                if (($ed['edition'] ?? '') === 'type_a' && !empty($ed['jacket_image_url'])) {
                    $jacketUrl = $ed['jacket_image_url'];
                    break;
                }
            }
            if (!$jacketUrl && !empty($r['editions'])) {
                $jacketUrl = $r['editions'][0]['jacket_image_url'] ?? null;
            }
            $songsByRelease[] = [
                'id' => $r['id'],
                'title' => $r['title'],
                'release_number' => $r['release_number'],
                'release_type' => $r['release_type'],
                'release_date' => $r['release_date'],
                'jacket_url' => $jacketUrl,
                'songs' => $songsList,
            ];
        }

        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $editionLabels = ReleaseEditionModel::EDITIONS;
        $trackTypesDisplay = SongModel::TRACK_TYPES_DISPLAY;
        $user = $_SESSION['user'];

        require_once __DIR__ . '/../Views/song_index.php';
    }

    /**
     * 楽曲個別紹介
     */
    public function detail(): void {
        $auth = new Auth();
        $auth->requireLogin();

        $songId = (int)($_GET['id'] ?? 0);
        if ($songId === 0) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $songModel = new SongModel();
        $releaseModel = new ReleaseModel();

        $song = $songModel->getSongWithMembers($songId);
        if (!$song) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $release = $releaseModel->find((int)$song['release_id']);
        $formation = $songModel->getFormation($songId);
        $centerMembers = $songModel->getCenterMembers($songId);

        // リリース単位のアーティスト写真（フォーメーション表示で優先）
        $releaseMemberImageModel = new ReleaseMemberImageModel();
        $releaseMemberImageMap = $releaseMemberImageModel->getMapByReleaseId((int)$song['release_id']);

        // いずれかのメンバーの position が NULL の場合はフォーメーション非表示
        $showFormation = false;
        if (!empty($song['members'])) {
            $showFormation = true;
            foreach ($song['members'] as $m) {
                if (!array_key_exists('position', $m) || $m['position'] === null || $m['position'] === '') {
                    $showFormation = false;
                    break;
                }
            }
        }

        // 楽曲に紐づく動画一覧（カテゴリ別表示・MV優先）
        $mediaLinks = $songModel->getMediaLinksBySongId($songId);
        $videosByCategory = [];
        foreach ($mediaLinks as $v) {
            $cat = $v['category'] ?? '';
            if (!isset($videosByCategory[$cat])) {
                $videosByCategory[$cat] = [];
            }
            $videosByCategory[$cat][] = $v;
        }
        // MV を最優先、その後カテゴリ名順
        $categoryOrder = array_keys($videosByCategory);
        usort($categoryOrder, function ($a, $b) {
            if ($a === 'MV' && $b !== 'MV') return -1;
            if ($b === 'MV' && $a !== 'MV') return 1;
            return strcmp((string)$a, (string)$b);
        });

        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $trackTypesDisplay = SongModel::TRACK_TYPES_DISPLAY;
        $formationTypesDisplay = SongModel::FORMATION_TYPES_DISPLAY;
        // 戻り先: リリースの楽曲一覧から来た場合はそのリリース詳細へ、全曲タブからは全曲タブへ、それ以外はリリース一覧
        if (isset($_GET['from']) && $_GET['from'] === 'release' && !empty($_GET['release_id'])) {
            $backUrl = 'release.php?id=' . (int)$_GET['release_id'];
        } elseif (isset($_GET['from']) && $_GET['from'] === 'songs') {
            $backUrl = 'songs.php?tab=songs';
        } else {
            $backUrl = 'songs.php';
        }
        $user = $_SESSION['user'];

        require_once __DIR__ . '/../Views/song_detail.php';
    }

    /**
     * 楽曲の参加メンバー編集画面（管理者専用）
     */
    public function memberEdit(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $songId = (int)($_GET['song_id'] ?? 0);
        if ($songId === 0) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $songModel = new SongModel();
        $releaseModel = new ReleaseModel();
        $songMemberModel = new SongMemberModel();
        $memberModel = new MemberModel();

        $song = $songModel->find($songId);
        if (!$song) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $release = $releaseModel->find((int)$song['release_id']);
        $members = $songMemberModel->getBySongIdWithNames($songId);
        $allMembers = $memberModel->getAllWithColors();

        // フォーメーション編集画面では、アー写は hn_release_member_images.image_url のみを使用し、
        // 登録がないメンバーは人アイコン表示（image_url は null）とする。
        $releaseMemberImageMap = [];
        if (!empty($song['release_id'])) {
            $releaseMemberImageModel = new ReleaseMemberImageModel();
            $releaseMemberImageMap = $releaseMemberImageModel->getMapByReleaseId((int)$song['release_id']);
        }
        foreach ($allMembers as &$m) {
            $mid = (int)($m['id'] ?? 0);
            $m['image_url'] = ($mid > 0 && isset($releaseMemberImageMap[$mid]) && $releaseMemberImageMap[$mid] !== '')
                ? $releaseMemberImageMap[$mid]
                : null;
        }
        unset($m);

        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $rowNames = SongMemberModel::ROW_NAMES;
        $user = $_SESSION['user'];

        require_once __DIR__ . '/../Views/song_member_edit.php';
    }

    /**
     * 楽曲参加メンバー一括保存API（管理者専用）POST JSON: { song_id, members: [{ member_id, is_center, row_number, position, part_description? }] }
     */
    public function saveMembers(): void {
        header('Content-Type: application/json');
        $auth = new Auth();
        $auth->requireAdmin();

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['song_id'])) {
                throw new \Exception('song_id が必要です');
            }
            $songId = (int)$input['song_id'];

            $songModel = new SongModel();
            if (!$songModel->find($songId)) {
                throw new \Exception('楽曲が見つかりません');
            }

            $members = [];
            if (!empty($input['members']) && is_array($input['members'])) {
                foreach ($input['members'] as $row) {
                    $memberId = (int)($row['member_id'] ?? 0);
                    if ($memberId <= 0) {
                        continue;
                    }
                    $members[] = [
                        'member_id' => $memberId,
                        'is_center' => !empty($row['is_center']),
                        'row_number' => isset($row['row_number']) && $row['row_number'] !== '' ? (int)$row['row_number'] : null,
                        'position' => isset($row['position']) && $row['position'] !== '' ? (int)$row['position'] : null,
                        'part_description' => $row['part_description'] ?? null,
                    ];
                }
            }

            $songMemberModel = new SongMemberModel();
            $songMemberModel->bulkInsertMembers($songId, $members);

            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
