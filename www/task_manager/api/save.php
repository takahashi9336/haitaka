<?php
/**
 * TaskManager API: Save Task
 * * フロントエンドの fetch() から呼び出されるエントリポイントです。
 */

// オートローダーの読み込み
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\TaskManager\Controller\TaskController;

// セッション開始と認証チェック
$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode([
        'status' => 'error',
        'message' => 'セッションが終了しました。再度ログインしてください。'
    ]);
    exit;
}

// コントローラの実行
$controller = new TaskController();

// POSTリクエストのみ許可
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->store();
} else {
    header('Content-Type: application/json', true, 405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
}