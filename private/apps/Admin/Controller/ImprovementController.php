<?php

namespace App\Admin\Controller;

use Core\Auth;
use App\Admin\Model\ImprovementItemModel;

class ImprovementController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireAdmin();

        $model = new ImprovementItemModel();
        $statusFilter = trim($_GET['status'] ?? '') ?: null;
        $screenNameFilter = trim($_GET['screen_name'] ?? '') ?: null;
        $items = $model->getList($statusFilter, $screenNameFilter);

        $success = $_SESSION['improvement_success'] ?? null;
        $error = $_SESSION['improvement_error'] ?? null;
        unset($_SESSION['improvement_success'], $_SESSION['improvement_error']);

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/improvement_list.php';
    }

    public function update(): void {
        $this->auth->requireAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $model = new ImprovementItemModel();

        if ($id && $action === 'status') {
            $status = $_POST['status'] ?? '';
            if (in_array($status, [ImprovementItemModel::STATUS_PENDING, ImprovementItemModel::STATUS_DONE, ImprovementItemModel::STATUS_CANCELLED], true)) {
                $model->updateStatus($id, $status);
                $_SESSION['improvement_success'] = 'ステータスを更新しました';
            }
        } elseif ($id && $action === 'update') {
            $screenName = trim($_POST['screen_name'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $status = $_POST['status'] ?? ImprovementItemModel::STATUS_PENDING;
            $priority = $_POST['priority'] !== '' ? (int)$_POST['priority'] : null;
            $memo = trim($_POST['memo'] ?? '') ?: null;
            if ($screenName && $content && in_array($status, [ImprovementItemModel::STATUS_PENDING, ImprovementItemModel::STATUS_DONE, ImprovementItemModel::STATUS_CANCELLED], true)) {
                $data = [
                    'screen_name' => $screenName,
                    'content' => $content,
                    'status' => $status,
                    'priority' => $priority,
                    'memo' => $memo,
                ];
                if ($status === ImprovementItemModel::STATUS_DONE) {
                    $data['resolved_at'] = date('Y-m-d H:i:s');
                } else {
                    $data['resolved_at'] = null;
                }
                $model->update($id, $data);
                $_SESSION['improvement_success'] = '更新しました';
            } else {
                $_SESSION['improvement_error'] = '画面名と改善事項は必須です';
            }
        }

        $q = [];
        if (!empty($_POST['_filter_status'])) {
            $q[] = 'status=' . urlencode($_POST['_filter_status']);
        }
        if (!empty($_POST['_filter_screen_name'])) {
            $q[] = 'screen_name=' . urlencode($_POST['_filter_screen_name']);
        }
        header('Location: /admin/improvement_list.php' . ($q ? '?' . implode('&', $q) : ''));
        exit;
    }

    public function create(): void {
        $this->auth->requireAdmin();

        $screenName = trim($_POST['screen_name'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $sourceUrl = trim($_POST['source_url'] ?? '') ?: null;
        $priority = $_POST['priority'] !== '' ? (int)$_POST['priority'] : null;

        if (!$screenName || !$content) {
            $_SESSION['improvement_error'] = '画面名と改善事項は必須です';
        } else {
            $model = new ImprovementItemModel();
            $model->createItem([
                'screen_name' => $screenName,
                'content' => $content,
                'status' => ImprovementItemModel::STATUS_PENDING,
                'priority' => $priority,
                'source_url' => $sourceUrl,
            ]);
            $_SESSION['improvement_success'] = '改善事項を追加しました';
        }
        header('Location: /admin/improvement_list.php');
        exit;
    }

    public function delete(): void {
        $this->auth->requireAdmin();

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $model = new ImprovementItemModel();
            $model->delete($id);
            $_SESSION['improvement_success'] = '削除しました';
        }
        header('Location: /admin/improvement_list.php');
        exit;
    }
}
