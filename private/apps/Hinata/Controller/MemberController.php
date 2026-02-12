<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\Database;
use Core\MediaAssetModel;

/**
 * メンバーメンテナンス コントローラ (DB完全同期 & 画像アップロード対応)
 * 物理パス: haitaka/private/apps/Hinata/Controller/MemberController.php
 */
class MemberController {

    public function index(): void {
        $auth = new Auth();
        if (!$auth->check()) { header('Location: /login.php'); exit; }
        
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
        if (!$auth->check() || ($_SESSION['user']['role'] ?? '') !== 'admin') { header('Location: /hinata/'); exit; }
        
        $model = new MemberModel();
        $members = $model->getAllWithColors();
        $colors = $model->getColorMaster();
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

            // 1. 画像アップロード (image_url)
            $imageUrl = $currentMember['image_url'] ?? null;
            if (!empty($_FILES['image_file']['name'])) {
                $file = $_FILES['image_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newFileName = "member_{$id}.{$ext}";
                $uploadDir = __DIR__ . '/../../../../www/assets/img/members/';
                
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if ($imageUrl && file_exists($uploadDir . $imageUrl)) {
                    @unlink($uploadDir . $imageUrl);
                }
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFileName)) {
                    $imageUrl = $newFileName;
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
                            'member_kojin_pv',
                            null
                        );

                        if ($metaId) {
                            // hn_media_members の紐付けを INSERT IGNORE で作成
                            $stmt = $pdo->prepare("
                                INSERT IGNORE INTO hn_media_members (media_meta_id, member_id)
                                VALUES (?, ?)
                            ");
                            $stmt->execute([$metaId, (int)$id]);
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
                'is_active'    => (int)$_POST['is_active']
            ];

            $model->update((int)$id, $data);
            $pdo->commit();
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
            ];

            $model->update((int)$id, $data);
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
                ];

                $model->update((int)$row['id'], $data);
            }
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}