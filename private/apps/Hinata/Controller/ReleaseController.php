<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\ReleaseModel;
use App\Hinata\Model\ReleaseEditionModel;
use App\Hinata\Model\ReleaseMemberImageModel;
use App\Hinata\Model\SongModel;
use App\Hinata\Model\SongMemberModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Database;
use Core\Logger;

/**
 * リリース・楽曲管理コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/ReleaseController.php
 */
class ReleaseController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    /**
     * リリース管理画面の表示（管理者専用）
     */
    public function admin(): void {
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        $releaseModel = new ReleaseModel();
        $editionModel = new ReleaseEditionModel();
        $memberModel = new MemberModel();

        $releases = $releaseModel->getAllReleases();
        $releaseIds = array_column($releases, 'id');
        $editionsByRelease = $editionModel->getEditionsByReleaseIds($releaseIds ?: [0]);

        $members = $memberModel->getAllWithColors();
        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $trackTypesDisplay = SongModel::TRACK_TYPES_DISPLAY;
        $editionLabels = ReleaseEditionModel::EDITIONS;

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/release_admin.php';
    }

    /**
     * リリース詳細（公開）：収録曲一覧を表示
     */
    public function show(): void {
        $this->auth->requireLogin();

        $releaseId = (int)($_GET['id'] ?? 0);
        if ($releaseId === 0) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $releaseModel = new ReleaseModel();
        $release = $releaseModel->getReleaseWithSongs($releaseId);
        if (!$release) {
            header('Location: /hinata/songs.php');
            exit;
        }

        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $trackTypesDisplay = SongModel::TRACK_TYPES_DISPLAY;
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/release_show.php';
    }

    /**
     * リリース別アーティスト写真の登録画面（管理者専用）
     */
    public function artistPhotos(): void {
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        $releaseId = (int)($_GET['release_id'] ?? 0);
        if ($releaseId === 0) {
            header('Location: /hinata/release_admin.php');
            exit;
        }

        $releaseModel = new ReleaseModel();
        $release = $releaseModel->getReleaseWithSongs($releaseId);
        if (!$release) {
            header('Location: /hinata/release_admin.php');
            exit;
        }

        $memberModel = new MemberModel();
        $releaseMemberImageModel = new ReleaseMemberImageModel();
        $members = $memberModel->getAllWithColors();
        $imageMap = $releaseMemberImageModel->getMapByReleaseId($releaseId);
        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $user = $_SESSION['user'];
        $appKey = 'hinata';
        require_once __DIR__ . '/../Views/release_artist_photos.php';
    }

    /**
     * リリース別アーティスト写真の保存API（管理者専用）
     * POST: { release_id, members: [{ member_id, image_url }, ...] }
     */
    public function saveReleaseMemberImages(): void {
        header('Content-Type: application/json');
        // 日向坂ポータル管理者（admin / hinata_admin）のみ
        (new HinataAuth($this->auth))->requireHinataAdmin('/hinata/');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $releaseId = (int)($input['release_id'] ?? 0);
            if ($releaseId === 0) {
                throw new \Exception('release_id が必要です');
            }
            $members = $input['members'] ?? [];
            $rows = [];
            foreach ($members as $row) {
                $memberId = (int)($row['member_id'] ?? 0);
                $imageUrl = trim((string)($row['image_url'] ?? ''));
                if ($memberId <= 0) {
                    continue;
                }
                $rows[] = ['member_id' => $memberId, 'image_url' => $imageUrl];
            }
            // 空の image_url は「登録しない」扱い（フォーメーションではメンバー既定画像を使用）
            $rows = array_filter($rows, fn($r) => $r['image_url'] !== '');
            $releaseMemberImageModel = new ReleaseMemberImageModel();
            $releaseMemberImageModel->saveForRelease($releaseId, array_values($rows));
            Logger::info("hn_release_member_images save release_id={$releaseId} count=" . count($rows) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success', 'message' => 'アーティスト写真を保存しました']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * リリース保存API
     * POST: { id?, release_type, release_number, title, title_kana, release_date, description, songs: [...] }
     */
    public function save(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['title'])) {
                throw new \Exception('タイトルは必須です');
            }

            $pdo = Database::connect();
            $pdo->beginTransaction();

            $releaseModel = new ReleaseModel();
            $editionModel = new ReleaseEditionModel();
            $songModel = new SongModel();
            $songMemberModel = new SongMemberModel();

            // リリース情報の保存（ジャケットは hn_release_editions で管理）
            $releaseData = [
                'release_type' => $input['release_type'] ?? 'single',
                'release_number' => $input['release_number'] ?? null,
                'title' => $input['title'],
                'title_kana' => $input['title_kana'] ?? null,
                'release_date' => $input['release_date'] ?? null,
                'description' => $input['description'] ?? null,
            ];

            if (!empty($input['id'])) {
                // 更新
                $releaseId = (int)$input['id'];
                $releaseModel->update($releaseId, $releaseData + [
                    'update_user' => $_SESSION['user']['id_name'] ?? '',
                ]);
            } else {
                // 新規作成
                $releaseModel->create($releaseData + [
                    'update_user' => $_SESSION['user']['id_name'] ?? '',
                ]);
                $releaseId = (int)$pdo->lastInsertId();
            }

            // 版別情報の保存（type_a はメインジャケットのため必須チェックは画面側で実施）
            if (isset($input['editions']) && is_array($input['editions'])) {
                $editionModel->saveForRelease($releaseId, $input['editions']);
            }

            // 収録曲の保存（キー `songs` があるときのみ同期。未送信の従来クライアントは楽曲を変更しない）
            $isEditRelease = !empty($input['id']);
            if (array_key_exists('songs', $input) && is_array($input['songs'])) {
                $allowedTrackTypes = array_keys(SongModel::TRACK_TYPES_DISPLAY);
                $keptSongIds = [];

                foreach ($input['songs'] as $songData) {
                    $title = trim((string)($songData['title'] ?? ''));
                    if ($title === '') {
                        continue;
                    }

                    $tt = (string)($songData['track_type'] ?? 'other');
                    if (!in_array($tt, $allowedTrackTypes, true)) {
                        $tt = 'other';
                    }

                    $rawTn = $songData['track_number'] ?? null;
                    $trackNumber = ($rawTn === null || $rawTn === '') ? null : (int)$rawTn;

                    $mediaMetaId = $songData['media_meta_id'] ?? null;
                    if ($mediaMetaId === null || $mediaMetaId === '') {
                        $mediaMetaId = null;
                    } else {
                        $mediaMetaId = (int)$mediaMetaId;
                    }

                    $songRecord = [
                        'release_id' => $releaseId,
                        'media_meta_id' => $mediaMetaId,
                        'title' => $title,
                        'title_kana' => isset($songData['title_kana']) && trim((string)$songData['title_kana']) !== ''
                            ? trim((string)$songData['title_kana']) : null,
                        'track_type' => $tt,
                        'track_number' => $trackNumber,
                        'lyricist' => isset($songData['lyricist']) && trim((string)$songData['lyricist']) !== ''
                            ? trim((string)$songData['lyricist']) : null,
                        'composer' => isset($songData['composer']) && trim((string)$songData['composer']) !== ''
                            ? trim((string)$songData['composer']) : null,
                        'duration' => isset($songData['duration']) && trim((string)$songData['duration']) !== ''
                            ? trim((string)$songData['duration']) : null,
                        'memo' => isset($songData['memo']) && trim((string)$songData['memo']) !== ''
                            ? trim((string)$songData['memo']) : null,
                        'apple_music_url' => isset($songData['apple_music_url']) && trim((string)$songData['apple_music_url']) !== ''
                            ? trim((string)$songData['apple_music_url']) : null,
                        'spotify_url' => isset($songData['spotify_url']) && trim((string)$songData['spotify_url']) !== ''
                            ? trim((string)$songData['spotify_url']) : null,
                    ];

                    if (!empty($songData['id'])) {
                        $songId = (int)$songData['id'];
                        $existing = $songModel->find($songId);
                        if (!$existing || (int)$existing['release_id'] !== $releaseId) {
                            throw new \Exception('楽曲IDが不正です（別リリースの楽曲は更新できません）');
                        }
                        $songModel->update($songId, $songRecord);
                    } else {
                        $songModel->create($songRecord);
                        $songId = (int)$pdo->lastInsertId();
                    }
                    $keptSongIds[] = $songId;

                    if (!empty($songData['members']) && is_array($songData['members'])) {
                        $songMemberModel->bulkInsertMembers($songId, $songData['members']);
                    }
                }

                if ($isEditRelease) {
                    if ($keptSongIds === []) {
                        $stmt = $pdo->prepare('DELETE FROM hn_songs WHERE release_id = ?');
                        $stmt->execute([$releaseId]);
                    } else {
                        $placeholders = implode(',', array_fill(0, count($keptSongIds), '?'));
                        $sql = "DELETE FROM hn_songs WHERE release_id = ? AND id NOT IN ({$placeholders})";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(array_merge([$releaseId], $keptSongIds));
                    }
                }
            }

            $pdo->commit();

            echo json_encode([
                'status' => 'success',
                'release_id' => $releaseId,
                'message' => 'リリース情報を保存しました'
            ]);
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * リリース削除API
     */
    public function delete(): void {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                throw new \Exception('IDが必要です');
            }

            $releaseModel = new ReleaseModel();
            $releaseId = (int)$input['id'];
            $releaseModel->delete($releaseId);
            Logger::info("hn_releases delete id={$releaseId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));

            echo json_encode(['status' => 'success', 'message' => '削除しました']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * リリース詳細取得API
     */
    public function detail(): void {
        header('Content-Type: application/json');
        
        try {
            $releaseId = (int)($_GET['id'] ?? 0);
            if ($releaseId === 0) {
                throw new \Exception('IDが必要です');
            }

            $releaseModel = new ReleaseModel();
            $release = $releaseModel->getReleaseWithSongs($releaseId);

            if (!$release) {
                throw new \Exception('リリースが見つかりません');
            }

            echo json_encode(['status' => 'success', 'data' => $release]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
