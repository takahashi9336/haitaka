<?php
/**
 * Annict OAuth コールバック
 * 物理パス: haitaka/www/anime/oauth_callback.php
 */
require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

Database::connect();
use App\Anime\Service\AnnictOAuthService;

$auth = new Auth();
$auth->requireLogin();
$user = $_SESSION['user'];
$allowedIds = isset($_ENV['ANIME_BETA_ID_NAMES']) ? array_map('trim', explode(',', $_ENV['ANIME_BETA_ID_NAMES'])) : [];
if (!empty($allowedIds) && !in_array($user['id_name'] ?? '', $allowedIds, true)) {
    header('HTTP/1.1 403 Forbidden');
    header('Location: /');
    exit;
}

$code = trim($_GET['code'] ?? '');
$state = trim($_GET['state'] ?? '');
$error = trim($_GET['error'] ?? '');

if ($error !== '') {
    $msg = $_GET['error_description'] ?? $error;
    $_SESSION['anime_oauth_error'] = $msg;
    header('Location: /anime/?oauth_error=1');
    exit;
}

if ($code === '') {
    $_SESSION['anime_oauth_error'] = '認可コードがありません';
    header('Location: /anime/?oauth_error=1');
    exit;
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    header('Location: /login.php');
    exit;
}

$service = new AnnictOAuthService();
$result = $service->exchangeCodeAndSave($userId, $code);

if ($result['success']) {
    unset($_SESSION['anime_oauth_error']);
    header('Location: /anime/');
} else {
    $_SESSION['anime_oauth_error'] = $result['message'] ?? 'Annict 連携に失敗しました';
    header('Location: /anime/?oauth_error=1');
}
exit;
