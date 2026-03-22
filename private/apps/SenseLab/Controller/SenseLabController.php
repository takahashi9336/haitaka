<?php

namespace App\SenseLab\Controller;

use Core\Auth;
use App\SenseLab\Model\SenseEntryModel;
use App\SenseLab\Model\SenseQuickEntryModel;

class SenseLabController
{
    public function index(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];

        $model = new SenseEntryModel();
        $quickModel = new SenseQuickEntryModel();
        $category = $_GET['category'] ?? null;
        $category = $category !== '' ? $category : null;
        $entries = $model->getList($user['id'], $category);
        $stats = $model->getStats($user['id']);
        $quickEntries = $quickModel->getListByUser($user['id']);

        require_once __DIR__ . '/../Views/index.php';
    }

    public function new(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $errors = $_SESSION['sense_lab_errors'] ?? [];
        unset($_SESSION['sense_lab_errors']);

        require_once __DIR__ . '/../Views/new.php';
    }

    public function create(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $errors = [];

        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $reason1 = trim($_POST['reason_1'] ?? '');
        $reason2 = trim($_POST['reason_2'] ?? '');
        $reason3 = trim($_POST['reason_3'] ?? '');

        if ($title === '') {
            $errors[] = 'タイトルは必須です。';
        }
        if ($category === '') {
            $category = 'other';
        }

        $hasReason = ($reason1 !== '' || $reason2 !== '' || $reason3 !== '');
        if (!$hasReason) {
            $errors[] = '理由はいずれか1つ以上入力してください。';
        }

        $uploadDir = dirname(__DIR__, 4) . '/www/uploads/sense_lab';
        $uploadUrlBase = '/uploads/sense_lab';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $imagePath = null;
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = '画像アップロードに失敗しました。';
            } else {
                $maxSize = 2 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    $errors[] = '画像サイズは2MB以内にしてください。';
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                ];
                if (!isset($allowed[$mime])) {
                    $errors[] = '許可されている画像形式は JPG/PNG/GIF のみです。';
                }
                if (!$errors) {
                    $ext = $allowed[$mime];
                    $basename = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
                    $filename = $basename . '.' . $ext;
                    $destPath = $uploadDir . '/' . $filename;
                    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                        $errors[] = '画像ファイルの保存に失敗しました。';
                    } else {
                        $imagePath = $uploadUrlBase . '/' . $filename;
                    }
                }
            }
        }

        if ($errors) {
            $_SESSION['sense_lab_errors'] = $errors;
            header('Location: /sense_lab/new.php');
            exit;
        }

        $model = new SenseEntryModel();
        $model->create([
            'user_id' => $user['id'],
            'title' => $title,
            'category' => $category,
            'image_path' => $imagePath,
            'reason_1' => $reason1 !== '' ? $reason1 : null,
            'reason_2' => $reason2 !== '' ? $reason2 : null,
            'reason_3' => $reason3 !== '' ? $reason3 : null,
        ]);

        header('Location: /sense_lab/');
        exit;
    }

    public function show(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $id = (int)($_GET['id'] ?? 0);

        $model = new SenseEntryModel();
        $entry = $model->findByIdAndUser($id, $user['id']);
        if (!$entry) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        require_once __DIR__ . '/../Views/show.php';
    }

    public function edit(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $id = (int)($_GET['id'] ?? 0);

        $model = new SenseEntryModel();
        $entry = $model->findByIdAndUser($id, $user['id']);
        if (!$entry) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $errors = $_SESSION['sense_lab_errors'] ?? [];
        unset($_SESSION['sense_lab_errors']);

        require_once __DIR__ . '/../Views/edit.php';
    }

    public function update(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $id = (int)($_POST['id'] ?? 0);

        $model = new SenseEntryModel();
        $entry = $model->findByIdAndUser($id, $user['id']);
        if (!$entry) {
            http_response_code(404);
            echo 'Not found';
            return;
        }

        $errors = [];
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $reason1 = trim($_POST['reason_1'] ?? '');
        $reason2 = trim($_POST['reason_2'] ?? '');
        $reason3 = trim($_POST['reason_3'] ?? '');

        if ($title === '') {
            $errors[] = 'タイトルは必須です。';
        }
        if ($category === '') {
            $category = $entry['category'] ?? 'other';
        }

        $hasReason = ($reason1 !== '' || $reason2 !== '' || $reason3 !== '');
        if (!$hasReason) {
            $errors[] = '理由はいずれか1つ以上入力してください。';
        }

        $imagePath = $entry['image_path'];
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $maxSize = 2 * 1024 * 1024;
                if ($file['size'] > $maxSize) {
                    $errors[] = '画像サイズは2MB以内にしてください。';
                } else {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                    ];
                    if (!isset($allowed[$mime])) {
                        $errors[] = '許可されている画像形式は JPG/PNG/GIF のみです。';
                    } else {
                        $uploadDir = dirname(__DIR__, 4) . '/www/uploads/sense_lab';
                        $uploadUrlBase = '/uploads/sense_lab';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0775, true);
                        }
                        $ext = $allowed[$mime];
                        $basename = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
                        $filename = $basename . '.' . $ext;
                        $destPath = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                            $errors[] = '画像ファイルの保存に失敗しました。';
                        } else {
                            $imagePath = $uploadUrlBase . '/' . $filename;
                        }
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = '画像アップロードに失敗しました。';
            }
        }

        if ($errors) {
            $_SESSION['sense_lab_errors'] = $errors;
            header('Location: /sense_lab/edit.php?id=' . $id);
            exit;
        }

        $model->update($id, $user['id'], [
            'title' => $title,
            'category' => $category,
            'image_path' => $imagePath,
            'reason_1' => $reason1 !== '' ? $reason1 : null,
            'reason_2' => $reason2 !== '' ? $reason2 : null,
            'reason_3' => $reason3 !== '' ? $reason3 : null,
        ]);

        header('Location: /sense_lab/show.php?id=' . $id);
        exit;
    }

    public function delete(): void
    {
        $auth = new Auth();
        $auth->requireAdmin();

        $user = $_SESSION['user'];
        $id = (int)($_POST['id'] ?? 0);

        if ($id) {
            $model = new SenseEntryModel();
            $entry = $model->findByIdAndUser($id, $user['id']);
            if ($entry) {
                $model->delete($id, $user['id']);
                if (!empty($entry['image_path'])) {
                    $path = dirname(__DIR__, 4) . '/www' . $entry['image_path'];
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            }
        }

        header('Location: /sense_lab/');
        exit;
    }
}

