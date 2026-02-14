<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\ReleaseModel;
use App\Hinata\Model\SongModel;
use App\Hinata\Model\SongMemberModel;
use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Database;

/**
 * リリース・楽曲管理コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/ReleaseController.php
 */
class ReleaseController {

    /**
     * リリース管理画面の表示（管理者専用）
     */
    public function admin(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $releaseModel = new ReleaseModel();
        $memberModel = new MemberModel();

        $releases = $releaseModel->getAllReleases();
        $members = $memberModel->getAllWithColors();
        $releaseTypes = ReleaseModel::RELEASE_TYPES;
        $trackTypes = SongModel::TRACK_TYPES;
        $roles = SongMemberModel::ROLES;

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/release_admin.php';
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
            $songModel = new SongModel();
            $songMemberModel = new SongMemberModel();

            // リリース情報の保存
            $releaseData = [
                'release_type' => $input['release_type'] ?? 'single',
                'release_number' => $input['release_number'] ?? null,
                'title' => $input['title'],
                'title_kana' => $input['title_kana'] ?? null,
                'release_date' => $input['release_date'] ?? null,
                'jacket_image_url' => $input['jacket_image_url'] ?? null,
                'description' => $input['description'] ?? null,
            ];

            if (!empty($input['id'])) {
                // 更新
                $releaseId = (int)$input['id'];
                $releaseModel->update($releaseId, $releaseData);
            } else {
                // 新規作成
                $releaseModel->create($releaseData);
                $releaseId = (int)$pdo->lastInsertId();
            }

            // 収録曲の保存（オプション）
            if (!empty($input['songs']) && is_array($input['songs'])) {
                foreach ($input['songs'] as $songData) {
                    $songRecord = [
                        'release_id' => $releaseId,
                        'media_meta_id' => $songData['media_meta_id'] ?? null,
                        'title' => $songData['title'] ?? '',
                        'title_kana' => $songData['title_kana'] ?? null,
                        'track_type' => $songData['track_type'] ?? 'other',
                        'track_number' => $songData['track_number'] ?? null,
                        'lyricist' => $songData['lyricist'] ?? null,
                        'composer' => $songData['composer'] ?? null,
                        'duration' => $songData['duration'] ?? null,
                        'memo' => $songData['memo'] ?? null,
                    ];

                    if (!empty($songData['id'])) {
                        // 楽曲更新
                        $songId = (int)$songData['id'];
                        $songModel->update($songId, $songRecord);
                    } else {
                        // 楽曲新規作成
                        $songModel->create($songRecord);
                        $songId = (int)$pdo->lastInsertId();
                    }

                    // 参加メンバーの保存
                    if (!empty($songData['members']) && is_array($songData['members'])) {
                        $songMemberModel->bulkInsertMembers($songId, $songData['members']);
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
            $releaseModel->delete((int)$input['id']);

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
