<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use Core\Auth;
use Core\MovieModel;
use Core\Database;

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
            $members = $model->getActiveMembersWithColors();
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
                $movieModel = new MovieModel();
                $movieModel->parseAndSave($_POST['pv_youtube_url'], $_POST['name'] . ' 紹介動画', 'Introduction');
                $newMovieId = (int)$pdo->lastInsertId();
                if ($newMovieId) {
                    $pvMovieId = $newMovieId;
                } else {
                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $_POST['pv_youtube_url'], $match);
                    $key = $match[1] ?? null;
                    if ($key) {
                        $stmt = $pdo->prepare("SELECT id FROM com_youtube_embed_data WHERE video_key = ?");
                        $stmt->execute([$key]);
                        $pvMovieId = $stmt->fetchColumn() ?: $pvMovieId;
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
}