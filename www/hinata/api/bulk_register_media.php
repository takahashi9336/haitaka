<?php
/**
 * メディア一括登録API（URL貼り付け + YouTube検索結果から）
 * 物理パス: haitaka/www/hinata/api/bulk_register_media.php
 */
ob_start();
try {
    require_once __DIR__ . '/../../../private/vendor/autoload.php';
    $auth = new \Core\Auth();
    if (!$auth->check()) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode(['status' => 'error', 'message' => '認証が必要です'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8', true, 403);
        echo json_encode(['status' => 'error', 'message' => '権限がありません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $controller = new \App\Hinata\Controller\MediaController();
    $controller->bulkRegister();
} catch (\Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode([
        'status'  => 'error',
        'message' => '登録処理でエラーが発生しました: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
