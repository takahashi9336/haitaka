<?php
/**
 * リリース版別ジャケット画像アップロードAPI
 * POST: multipart/form-data, フィールド名 "file"
 * 返却: { status: 'success', url: '/assets/img/releases/xxx.jpg' } または error
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}
if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

$field = 'file';
if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'ファイルが選択されていないか、アップロードに失敗しました']);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['status' => 'error', 'message' => '対応形式は jpg, png, gif, webp です']);
    exit;
}

$baseDir = dirname(__DIR__, 2) . '/assets/img/releases/';
if (!is_dir($baseDir)) {
    if (!@mkdir($baseDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => '保存先ディレクトリを作成できませんでした']);
        exit;
    }
}

$fileName = 'jacket_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
$path = $baseDir . $fileName;

if (!move_uploaded_file($_FILES[$field]['tmp_name'], $path)) {
    echo json_encode(['status' => 'error', 'message' => '画像の保存に失敗しました']);
    exit;
}

// 相対URL（同一オリジンで配信される想定）
$url = '/assets/img/releases/' . $fileName;
echo json_encode(['status' => 'success', 'url' => $url]);
