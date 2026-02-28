<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\MemberActivityModel;
use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;
use Core\Logger;

/**
 * メンバーメンテナンス コントローラ (DB完全同期 & 画像アップロード対応)
 * 物理パス: haitaka/private/apps/Hinata/Controller/MemberController.php
 */
class MemberController {

    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();
        
        try {
            $model = new MemberModel();
            $members = $model->getMembersForBook();
            $user = $_SESSION['user'];
            require_once __DIR__ . '/../Views/members.php';
        } catch (\Exception $e) {
            die("SQL Error: " . $e->getMessage());
        }
    }

    public function admin(): void {
        $auth = new Auth();
        $auth->requireHinataAdmin('/hinata/');
        
        $model = new MemberModel();
        $members = $model->getAllWithColors();
        $colors = $model->getColorMaster();

        $activityModel = new MemberActivityModel();
        $activitiesByMember = $activityModel->getAllGroupedByMember(false);
        $activityCategories = MemberActivityModel::CATEGORIES;

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/member_admin.php';
    }

    public function detail(): void {
        header('Content-Type: application/json');
        try {
            // セッションおよび認証を必ず初期化しておく（favorite_level取得にuser_idが必要なため）
            $auth = new Auth();
            if (!$auth->check()) {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'unauthorized']);
                return;
            }

            $id = $_GET['id'] ?? null;
            if (!$id) throw new \Exception('ID missing');
            $model = new MemberModel();
            $detail = $model->getMemberDetail((int)$id);
            echo json_encode(['status' => 'success', 'data' => $detail]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 保存処理
     */
    public function save(): void {
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'] ?? null;
            if (!$id) throw new \Exception('ID missing');

            $model = new MemberModel();
            $currentMember = $model->find($id);
            if (!$currentMember) throw new \Exception('Member not found');

            $pdo = Database::connect();
            $pdo->beginTransaction();

            // 1. 複数画像アップロード（最大5枚）
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
            $uploadDir = ($docRoot !== '' ? $docRoot . '/' : __DIR__ . '/../../../../www/') . 'assets/img/members/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $existingRaw = $_POST['image_existing'] ?? [];
            $existing = is_array($existingRaw) ? array_values($existingRaw) : [];
            $savedImages = [];
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            for ($i = 0; $i < 5; $i++) {
                $key = 'image_file_' . $i;
                if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$key];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        throw new \Exception('対応形式は jpg, png, gif, webp です（スロット' . ($i + 1) . '）');
                    }
                    $newFileName = "member_{$id}_{$i}.{$ext}";
                    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                        throw new \Exception('画像の保存に失敗しました（スロット' . ($i + 1) . '）');
                    }
                    $savedImages[] = $newFileName;
                } elseif (!empty($existing[$i]) && is_string($existing[$i]) && preg_match('/^[\w\-\.]+$/', $existing[$i])) {
                    $savedImages[] = $existing[$i];
                }
            }

            $oldImages = array_column($model->getMemberImages((int)$id), 'image_url');
            $model->saveMemberImages((int)$id, $savedImages);
            $imageUrl = $savedImages[0] ?? null;
            foreach ($oldImages as $old) {
                if ($old && !in_array($old, $savedImages, true) && file_exists($uploadDir . $old)) {
                    @unlink($uploadDir . $old);
                }
            }

            // 2. YouTube 紹介動画
            $pvMovieId = $currentMember['pv_movie_id'] ?? null;
            if (!empty($_POST['pv_youtube_url'])) {
                $mediaModel = new MediaAssetModel();
                $parsed = $mediaModel->parseUrl($_POST['pv_youtube_url']);
                if ($parsed && $parsed['platform'] === 'youtube') {
                    $title = ($_POST['name'] ?? '') . ' 紹介動画';
                    $assetId = $mediaModel->findOrCreateAsset(
                        $parsed['platform'],
                        $parsed['media_key'],
                        $parsed['sub_key'],
                        $title
                    );

                    if ($assetId) {
                        $metaId = $mediaModel->findOrCreateMetadata(
                            $assetId,
                            'SoloPV',
                            null
                        );

                        if ($metaId) {
                            // hn_media_members の紐付けを INSERT IGNORE で作成
                            $stmt = $pdo->prepare("
                                INSERT IGNORE INTO hn_media_members (media_meta_id, member_id, update_user)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$metaId, (int)$id, $_SESSION['user']['id_name'] ?? '']);
                        }
                    }
                }
            }

            // 3. データ更新 (DBカラム定義と完全同期)
            $data = [
                'name'         => $_POST['name'],
                'kana'         => $_POST['kana'],
                'generation'   => (int)$_POST['generation'],
                'birth_date'   => !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
                'blood_type'   => $_POST['blood_type'] ?? '',
                'height'       => !empty($_POST['height']) ? (float)$_POST['height'] : null,
                'birth_place'  => $_POST['birth_place'] ?? '',
                'color_id1'    => !empty($_POST['color_id1']) ? (int)$_POST['color_id1'] : null,
                'color_id2'    => !empty($_POST['color_id2']) ? (int)$_POST['color_id2'] : null,
                'blog_url'     => $_POST['blog_url'] ?? '',
                'insta_url'    => $_POST['insta_url'] ?? '',
                'twitter_url'  => $_POST['twitter_url'] ?? '',
                'member_info'  => $_POST['member_info'] ?? '',
                'image_url'    => $imageUrl,
                // 旧構成カラムは後方互換のため残すが、新メディア構成では未使用
                'pv_movie_id'  => $pvMovieId,
                'is_active'    => (int)$_POST['is_active'],
                'update_user'  => $_SESSION['user']['id_name'] ?? '',
            ];

            $model->update((int)$id, $data);
            $pdo->commit();
            Logger::info("hn_members update id={$id} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            $pdo = Database::connect();
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 一覧編集用の簡易保存（画像・PV以外の主要項目）
     */
    public function saveBasic(): void {
        header('Content-Type: application/json');
        try {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID missing');
            }

            $model = new MemberModel();
            $currentMember = $model->find((int)$id);
            if (!$currentMember) {
                throw new \Exception('Member not found');
            }

            $data = [
                'name'        => $_POST['name'] ?? $currentMember['name'],
                'kana'        => $_POST['kana'] ?? $currentMember['kana'],
                'generation'  => isset($_POST['generation']) ? (int)$_POST['generation'] : $currentMember['generation'],
                'color_id1'   => ($_POST['color_id1'] ?? '') !== '' ? (int)$_POST['color_id1'] : null,
                'color_id2'   => ($_POST['color_id2'] ?? '') !== '' ? (int)$_POST['color_id2'] : null,
                'blog_url'    => $_POST['blog_url'] ?? $currentMember['blog_url'],
                'insta_url'   => $_POST['insta_url'] ?? $currentMember['insta_url'],
                'twitter_url' => $_POST['twitter_url'] ?? $currentMember['twitter_url'],
                'member_info' => $_POST['member_info'] ?? $currentMember['member_info'],
                'is_active'   => isset($_POST['is_active']) ? (int)$_POST['is_active'] : $currentMember['is_active'],
                'update_user' => $_SESSION['user']['id_name'] ?? '',
            ];

            $model->update((int)$id, $data);
            Logger::info("hn_members update_basic id={$id} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 一覧編集用：複数メンバーを一括保存
     */
    public function saveBasicBulk(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['items']) || !is_array($input['items'])) {
                throw new \Exception('No items provided');
            }

            $model = new MemberModel();
            foreach ($input['items'] as $row) {
                if (empty($row['id'])) {
                    continue;
                }
                $currentMember = $model->find((int)$row['id']);
                if (!$currentMember) {
                    continue;
                }

                $data = [
                    'name'        => $row['name'] ?? $currentMember['name'],
                    'kana'        => $row['kana'] ?? $currentMember['kana'],
                    'generation'  => isset($row['generation']) ? (int)$row['generation'] : $currentMember['generation'],
                    'color_id1'   => ($row['color_id1'] ?? '') !== '' ? (int)$row['color_id1'] : null,
                    'color_id2'   => ($row['color_id2'] ?? '') !== '' ? (int)$row['color_id2'] : null,
                    'blog_url'    => $row['blog_url'] ?? $currentMember['blog_url'],
                    'insta_url'   => $row['insta_url'] ?? $currentMember['insta_url'],
                    'twitter_url' => $row['twitter_url'] ?? $currentMember['twitter_url'],
                    'member_info' => $row['member_info'] ?? $currentMember['member_info'],
                    'is_active'   => isset($row['is_active']) ? (int)$row['is_active'] : $currentMember['is_active'],
                    'update_user' => $_SESSION['user']['id_name'] ?? '',
                ];

                $model->update((int)$row['id'], $data);
            }
            Logger::info("hn_members update_basic_bulk count=" . count($input['items']) . " by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * メンバー個人活動の保存（新規/更新）
     */
    public function saveActivity(): void {
        header('Content-Type: application/json');
        try {
            $memberId = (int)($_POST['member_id'] ?? 0);
            if (!$memberId) throw new \Exception('member_id missing');

            $model = new MemberActivityModel();
            $activityId = (int)($_POST['activity_id'] ?? 0);

            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
            $uploadDir = ($docRoot !== '' ? $docRoot . '/' : __DIR__ . '/../../../../www/') . 'assets/img/activities/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $imageUrl = $_POST['image_existing'] ?? null;
            if (!empty($_FILES['activity_image']['name']) && $_FILES['activity_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['activity_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowedExt, true)) {
                    throw new \Exception('対応形式は jpg, png, gif, webp です');
                }
                $newFileName = "activity_{$memberId}_" . time() . ".{$ext}";
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                    throw new \Exception('画像の保存に失敗しました');
                }
                if ($imageUrl && $imageUrl !== $newFileName && file_exists($uploadDir . $imageUrl)) {
                    @unlink($uploadDir . $imageUrl);
                }
                $imageUrl = $newFileName;
            }

            $data = [
                'member_id'   => $memberId,
                'category'    => $_POST['category'] ?? 'other',
                'title'       => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'url'         => $_POST['url'] ?? '',
                'url_label'   => $_POST['url_label'] ?? '',
                'image_url'   => $imageUrl,
                'is_active'   => (int)($_POST['is_active'] ?? 1),
                'sort_order'  => (int)($_POST['sort_order'] ?? 0),
                'start_date'  => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                'end_date'    => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            ];

            if ($activityId > 0) {
                $data['id'] = $activityId;
            }

            $savedId = $model->saveActivity($data);
            Logger::info("hn_member_activities save id={$savedId} member={$memberId} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success', 'id' => $savedId]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * メンバー個人活動の削除
     */
    public function deleteActivity(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new \Exception('id missing');

            $model = new MemberActivityModel();
            $activity = $model->find($id);

            if ($activity && !empty($activity['image_url'])) {
                $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
                $uploadDir = ($docRoot !== '' ? $docRoot . '/' : __DIR__ . '/../../../../www/') . 'assets/img/activities/';
                $imgPath = $uploadDir . $activity['image_url'];
                if (file_exists($imgPath)) {
                    @unlink($imgPath);
                }
            }

            $model->delete($id);
            Logger::info("hn_member_activities delete id={$id} by=" . ($_SESSION['user']['id_name'] ?? 'guest'));
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}