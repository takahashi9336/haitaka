<?php
/**
 * ブログ画像ダウンロードプロキシAPI
 * 日向坂46公式ブログの画像URLを取得し、Content-Disposition でストリーム転送する。
 * サーバーには永続保存しない。
 *
 * GET ?url=... （URLエンコード済みの画像URL）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Logger;

try {
    $auth = new Auth();
    if (!$auth->check()) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
        exit;
    }

    $rawUrl = trim($_GET['url'] ?? '');
    if ($rawUrl === '') {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'url パラメータが必要です']);
        exit;
    }

    $parsed = parse_url($rawUrl);
    if (!$parsed || !isset($parsed['host'])) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => '無効なURLです']);
        exit;
    }

    $scheme = strtolower($parsed['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'http/https のURLのみ許可されています']);
        exit;
    }

    $host = strtolower($parsed['host']);
    $allowed = ($host === 'hinatazaka46.com' || str_ends_with($host, '.hinatazaka46.com'));
    if (!$allowed) {
        header('Content-Type: application/json', true, 403);
        echo json_encode(['status' => 'error', 'message' => '許可されていないドメインです']);
        exit;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (compatible; HaitakaPlatform/1.0)',
                'Accept: image/*',
                'Accept-Language: ja',
            ]),
            'timeout' => 15,
        ],
    ]);

    $data = @file_get_contents($rawUrl, false, $ctx);
    if ($data === false || strlen($data) < 100) {
        header('Content-Type: application/json', true, 502);
        echo json_encode(['status' => 'error', 'message' => '画像の取得に失敗しました']);
        exit;
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($data);
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext = $allowedMime[$mime] ?? 'jpg';

    $filename = 'blog_' . date('Ymd_His') . '.' . $ext;

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: no-store');

    echo $data;
} catch (\Throwable $e) {
    Logger::errorWithContext('download_blog_image: ' . $e->getMessage(), $e);
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
