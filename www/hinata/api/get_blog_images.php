<?php
/**
 * ブログ記事の画像一覧取得API
 * body_html から <img> の src を抽出し、hinatazaka46.com ドメインのURLのみ返却。
 * 画像がない場合は thumbnail_url をフォールバック。
 *
 * GET ?article_id=68048 または GET ?id=123 （hn_blog_posts.id）
 */
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;
use Core\Logger;

try {
    $auth = new Auth();
    if (!$auth->check()) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
        exit;
    }

    $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
    $postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($articleId <= 0 && $postId <= 0) {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'article_id または id が必要です']);
        exit;
    }

    $pdo = Database::connect();
    if ($articleId > 0) {
        $stmt = $pdo->prepare("SELECT id, article_id, title, body_html, thumbnail_url FROM hn_blog_posts WHERE article_id = :aid LIMIT 1");
        $stmt->execute(['aid' => $articleId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, article_id, title, body_html, thumbnail_url FROM hn_blog_posts WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $postId]);
    }
    $post = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$post) {
        header('Content-Type: application/json', true, 404);
        echo json_encode(['status' => 'error', 'message' => '記事が見つかりません']);
        exit;
    }

    $urls = extractImageUrls($post['body_html'] ?? '', $post['thumbnail_url'] ?? '');

    echo json_encode([
        'status'  => 'success',
        'title'   => $post['title'] ?? '',
        'images'  => $urls,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    Logger::errorWithContext('get_blog_images: ' . $e->getMessage(), $e);
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * body_html から img src を抽出し、絶対URL化。hinatazaka46.com のみ許可。
 * 画像がなければ thumbnail_url をフォールバック。
 */
function extractImageUrls(string $bodyHtml, string $thumbnailUrl): array {
    $base = 'https://www.hinatazaka46.com';
    $urls = [];

    $bodyHtml = trim($bodyHtml);
    if ($bodyHtml !== '') {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($bodyHtml, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $imgs = $xpath->query('//img[@src]');

        if ($imgs !== false) {
            foreach ($imgs as $img) {
                $src = trim($img->getAttribute('src'));
                if ($src === '') continue;

                $abs = (str_starts_with($src, 'http')) ? $src : rtrim($base, '/') . '/' . ltrim($src, '/');
                $host = parse_url($abs, PHP_URL_HOST);
                if ($host && (strtolower($host) === 'hinatazaka46.com' || str_ends_with(strtolower($host), '.hinatazaka46.com'))) {
                    $urls[] = $abs;
                }
            }
        }
    }

    $urls = array_values(array_unique($urls));

    if (empty($urls) && $thumbnailUrl !== '') {
        $t = trim($thumbnailUrl);
        $host = parse_url($t, PHP_URL_HOST);
        if ($host && (strtolower($host) === 'hinatazaka46.com' || str_ends_with(strtolower($host), '.hinatazaka46.com'))) {
            $urls[] = $t;
        } elseif ($t !== '' && !str_starts_with($t, 'http')) {
            $abs = rtrim($base, '/') . '/' . ltrim($t, '/');
            $urls[] = $abs;
        }
    }

    return $urls;
}
