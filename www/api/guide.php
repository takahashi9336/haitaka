<?php
/**
 * ガイド取得API（表示用）
 * GET ?guide_key=xxx
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\GuideModel;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

$guideKey = trim($_GET['guide_key'] ?? '');
if (!$guideKey) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'guide_key を指定してください']);
    exit;
}

$model = new GuideModel();
$guide = $model->findByKey($guideKey);
if (!$guide) {
    echo json_encode(['status' => 'success', 'guide' => null]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'guide' => [
        'title' => $guide['title'],
        'blocks' => $guide['blocks'] ?? [],
        'show_on_first_visit' => (bool)($guide['show_on_first_visit'] ?? false),
    ],
]);
