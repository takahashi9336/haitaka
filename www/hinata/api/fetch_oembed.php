<?php
/**
 * oEmbed情報取得API（YouTube / TikTok / Instagram URL解析）
 * 物理パス: haitaka/www/hinata/api/fetch_oembed.php
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use App\Hinata\Controller\MediaController;

$auth = new Auth();
if (!$auth->check()) {
    header('Content-Type: application/json', true, 401);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

if (!in_array(($_SESSION['user']['role'] ?? ''), ['admin', 'hinata_admin'], true)) {
    header('Content-Type: application/json', true, 403);
    echo json_encode(['status' => 'error', 'message' => '権限がありません']);
    exit;
}

$controller = new MediaController();
$controller->fetchOembed();
