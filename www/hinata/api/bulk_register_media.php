<?php
/**
 * ????????API?URL???? + YouTube???????
 * ????: haitaka/www/hinata/api/bulk_register_media.php
 */
ob_start();
try {
    require_once __DIR__ . '/../../../private/bootstrap.php';
    $auth = new \Core\Auth();
    if (!$auth->check()) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode(['status' => 'error', 'message' => '???????'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8', true, 403);
        echo json_encode(['status' => 'error', 'message' => '????????'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $controller = new \App\Hinata\Controller\MediaController();
    $controller->bulkRegister();
} catch (\Throwable $e) {
    \Core\Logger::errorWithContext('bulk_register_media: ' . $e->getMessage(), $e);
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode([
        'status'  => 'error',
        'message' => '???????????????: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
