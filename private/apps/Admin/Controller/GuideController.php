<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\GuideModel;

class GuideController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireAdmin();

        $model = new GuideModel();
        $guides = $model->getAll();

        require_once __DIR__ . '/../Views/guides.php';
    }

    public function edit(?int $id = null): void {
        $this->auth->requireAdmin();

        $model = new GuideModel();
        $guide = $id ? $model->find($id) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $guideKey = trim($_POST['guide_key'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $appKey = trim($_POST['app_key'] ?? '') ?: null;
            $showOnFirstVisit = isset($_POST['show_on_first_visit']) ? 1 : 0;
            $blocksJson = $_POST['blocks_json'] ?? '[]';
            $blocks = json_decode($blocksJson, true);
            if (!is_array($blocks)) {
                $blocks = [];
            }

            if (!$guideKey || !$title) {
                $_SESSION['guide_error'] = 'guide_key と title は必須です';
            } else {
                if ($id && $guide && !empty($guide['id'])) {
                    $model->updateGuide($id, [
                        'guide_key' => $guideKey,
                        'title' => $title,
                        'app_key' => $appKey,
                        'show_on_first_visit' => $showOnFirstVisit,
                        'blocks' => $blocks,
                    ]);
                    $_SESSION['guide_success'] = 'ガイドを更新しました';
                } else {
                    $model->createGuide([
                        'guide_key' => $guideKey,
                        'title' => $title,
                        'app_key' => $appKey,
                        'show_on_first_visit' => $showOnFirstVisit,
                        'blocks' => $blocks,
                    ]);
                    $_SESSION['guide_success'] = 'ガイドを作成しました';
                }
                header('Location: /admin/guides.php');
                exit;
            }

            $guide = [
                'id' => $id,
                'guide_key' => $guideKey,
                'title' => $title,
                'app_key' => $appKey ?? '',
                'show_on_first_visit' => $showOnFirstVisit,
                'blocks' => $blocks,
            ];
        }

        require_once __DIR__ . '/../Views/guide_edit.php';
    }

    public function delete(): void {
        $this->auth->requireAdmin();

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $model = new GuideModel();
            $model->delete($id);
            $_SESSION['guide_success'] = 'ガイドを削除しました';
        }
        header('Location: /admin/guides.php');
        exit;
    }
}
