<?php
/**
 * TikTok クライアント連携インポートバッチ
 *
 * Windowsクライアントアプリから送られてきたTikTok動画URL一覧を受け取り、
 * 既存のメディア登録ロジック（MediaAssetModel）を使ってDB登録する。
 *
 * HTTP:
 *   POST /hinata/batch/tiktok_client_import
 *   Header:
 *     Content-Type: application/json
 *     X-Hinata-Tiktok-Token: {secret}  または JSON ボディ内 token フィールド
 *   Body:
 *   {
 *     "token": "(任意、ヘッダ優先)",
 *     "account": "hinatazakanews",
 *     "urls": [
 *       "https://www.tiktok.com/@hinatazakanews/video/123...",
 *       "https://www.tiktok.com/@hinatazakanews/video/456..."
 *     ],
 *     "category": "Special"  // 任意、省略時は未設定(null)
 *   }
 *
 * CLI:
 *   php tiktok_client_import.php urls.txt [category]
 *   - 1行1URLのテキストファイルを読み込み登録する簡易モード
 */

$isCli = (php_sapi_name() === 'cli');

require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Database;
use Core\Logger;
use App\Hinata\Model\MediaAssetModel;

/**
 * 共有トークンを取得する
 * 優先順位: 環境変数 HINATA_TIKTOK_CLIENT_TOKEN -> .env ファイル
 */
function get_client_token(): ?string
{
    $token = $_ENV['HINATA_TIKTOK_CLIENT_TOKEN'] ?? null;
    if (!empty($token)) {
        return trim($token);
    }

    $envPath = __DIR__ . '/../../../private/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                if (trim($key) === 'HINATA_TIKTOK_CLIENT_TOKEN') {
                    return trim($val);
                }
            }
        }
    }
    return null;
}

/**
 * トークン認証チェック（HTTP 用）
 */
function require_valid_token(): void
{
    $expected = get_client_token();
    if ($expected === null || $expected === '') {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'サーバ側トークンが未設定です']);
        exit;
    }

    $headerToken = $_SERVER['HTTP_X_HINATA_TIKTOK_TOKEN'] ?? '';
    $raw = file_get_contents('php://input');
    $jsonToken = null;
    if ($raw !== false && $raw !== '') {
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['token'])) {
            $jsonToken = (string)$data['token'];
        }
    }

    $provided = $headerToken !== '' ? $headerToken : ($jsonToken ?? '');

    if (!hash_equals($expected, (string)$provided)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'トークンが不正です']);
        exit;
    }

    // 認証済みなので、入力JSONは後で再パースする
    if ($raw !== false) {
        $GLOBALS['__tiktok_client_import_raw'] = $raw;
    }
}

/**
 * 実処理本体
 *
 * @param string[] $urls
 * @param string|null $defaultCategory
 * @return array{created:int,skipped:int,errors:int}
 */
function import_tiktok_urls(array $urls, ?string $defaultCategory = null): array
{
    $pdo = Database::connect();
    $model = new MediaAssetModel();

    // メンバー自動紐付けのために全メンバー一覧を取得
    $allMembers = $pdo->query("SELECT id, name, kana FROM hn_members ORDER BY (id = 99) ASC, id ASC")
        ->fetchAll(\PDO::FETCH_ASSOC);

    $created = 0;
    $skipped = 0;
    $errors  = 0;

    $normalizedCategory = null;
    if ($defaultCategory !== null) {
        $trimmed = trim($defaultCategory);
        $normalizedCategory = ($trimmed === '') ? null : $trimmed;
    }

    foreach ($urls as $url) {
        $url = trim((string)$url);
        if ($url === '') {
            $skipped++;
            continue;
        }

        try {
            $parsed = $model->parseUrl($url);
            if (!$parsed) {
                // 対応外URL
                $skipped++;
                continue;
            }

            // TikTok 以外のURLはスキップ（将来拡張余地あり）
            if (($parsed['platform'] ?? '') !== 'tiktok') {
                $skipped++;
                continue;
            }

            // すでに日向坂メタデータとして登録済みであれば、何も更新せずスキップ
            if (is_already_registered($pdo, $parsed)) {
                $skipped++;
                continue;
            }

            // TikTok oEmbed からタイトル・公開日時・サムネイルURLを取得
            $oembed = fetch_tiktok_oembed($url);
            $title = $url;
            $uploadDate = null;
            $thumbnailUrl = null;
            if ($oembed) {
                if (!empty($oembed['title'])) {
                    $title = (string)$oembed['title'];
                }
                if (!empty($oembed['published_at'])) {
                    $ts = strtotime((string)$oembed['published_at']);
                    if ($ts !== false) {
                        $uploadDate = date('Y-m-d H:i:s', $ts);
                    }
                }
                if (!empty($oembed['thumbnail_url'])) {
                    $thumbnailUrl = download_and_save_thumbnail(
                        (string)$oembed['thumbnail_url'],
                        'tiktok',
                        (string)($parsed['media_key'] ?? '')
                    );
                }
            }

            // 既存ロジックと同様に findOrCreateAsset / findOrCreateMetadata を使う
            $assetId = $model->findOrCreateAsset(
                $parsed['platform'],
                $parsed['media_key'],
                $parsed['sub_key'],
                $title,        // oEmbed から取得したタイトル（なければURL）
                $thumbnailUrl, // oEmbed から取得したサムネイル（サーバ保存済みURL）
                $uploadDate,   // oEmbed から取得した公開日時（あれば）
                null,          // description
                'short'        // TikTokはショート動画扱い
            );

            if (!$assetId) {
                $errors++;
                continue;
            }

            $metaId = $model->findOrCreateMetadata($assetId, $normalizedCategory);
            if (!$metaId) {
                $errors++;
                continue;
            }

            // oEmbed からタイトル・説明を取得して、メンバー自動紐付けを試みる
            $text = '';
            if ($oembed) {
                $title = (string)($oembed['title'] ?? '');
                $desc  = (string)($oembed['description'] ?? '');
                $text  = trim($title . ' ' . $desc);
            }
            if ($text !== '') {
                $matchedIds = detect_members_from_text($text, $allMembers);
                if (!empty($matchedIds)) {
                    auto_link_members($pdo, $metaId, $matchedIds);
                }
            }

            $created++;
        } catch (\Throwable $e) {
            Logger::errorWithContext('tiktok_client_import: ' . $e->getMessage(), $e);
            $errors++;
        }
    }

    return [
        'created' => $created,
        'skipped' => $skipped,
        'errors'  => $errors,
    ];
}

/**
 * すでに日向坂メタデータとして登録済みかどうかを判定する
 * MediaController::checkDuplicateStatus の Registered 判定相当。
 *
 * @param \PDO  $pdo
 * @param array $parsed ['platform','media_key',...]
 */
function is_already_registered(\PDO $pdo, array $parsed): bool
{
    // 1. com_media_assets にあるか
    $stmt = $pdo->prepare("
        SELECT id FROM com_media_assets
        WHERE platform = :platform AND media_key = :media_key
    ");
    $stmt->execute([
        'platform' => $parsed['platform'] ?? '',
        'media_key' => $parsed['media_key'] ?? '',
    ]);
    $assetId = $stmt->fetchColumn();

    if (!$assetId) {
        // asset 自体がない → 未登録
        return false;
    }

    // 2. hn_media_metadata に登録済みか（asset_id で一意性保証）
    $stmt = $pdo->prepare("
        SELECT id FROM hn_media_metadata
        WHERE asset_id = :asset_id
        LIMIT 1
    ");
    $stmt->execute(['asset_id' => $assetId]);
    $metaId = $stmt->fetchColumn();

    // メタデータが1件でもあれば「登録済み」とみなしてスキップ対象
    return $metaId ? true : false;
}

/**
 * TikTok oEmbed APIを呼び出す（MediaController::fetchTikTokOembed 相当）
 */
function fetch_tiktok_oembed(string $url): ?array
{
    $oembedUrl = 'https://www.tiktok.com/oembed?url=' . urlencode($url);

    $ctx = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 10, 'ignore_errors' => true],
    ]);
    $response = @file_get_contents($oembedUrl, false, $ctx);
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!$data || !isset($data['title'])) return null;

    $publishedAt = null;
    if (preg_match('/\/video\/(\d+)/', $url, $m)) {
        $publishedAt = extract_tiktok_timestamp($m[1]);
    }

    return [
        'title'         => $data['title'],
        'description'   => $data['title'],
        'author_name'   => $data['author_name'] ?? '',
        'thumbnail_url' => $data['thumbnail_url'] ?? '',
        'published_at'  => $publishedAt,
    ];
}

/**
 * TikTok動画IDからアップロード日時を抽出（MediaController::extractTikTokTimestamp 相当）
 */
function extract_tiktok_timestamp(string $videoId): ?string
{
    if (!ctype_digit($videoId) || strlen($videoId) < 15) return null;
    $id = (int)$videoId;
    if ($id <= 0) return null;
    $timestamp = $id >> 32;
    if ($timestamp < 1420070400 || $timestamp > 2524608000) return null;
    return date('Y-m-d\TH:i:s\Z', $timestamp);
}

/**
 * 外部URLからサムネイル画像をダウンロードし、サーバに保存する。
 * MediaController::downloadAndSaveThumbnail 相当。
 *
 * @return string|null 保存した場合のローカルURL (/uploads/thumbnails/...)、失敗時は null
 */
function download_and_save_thumbnail(string $externalUrl, string $platform, string $mediaKey): ?string
{
    $url = trim($externalUrl);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return null;
    }
    if (str_starts_with($url, '/')) {
        return $url;
    }

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => 15,
            'ignore_errors' => true,
            'header'        => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nReferer: https://www.tiktok.com/\r\n",
        ],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 100) {
        return null;
    }

    $ext = 'jpg';
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($data);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (isset($allowed[$mime])) {
        $ext = $allowed[$mime];
    }

    $baseDir = dirname(__DIR__, 3) . '/www/uploads/thumbnails/';
    if (!is_dir($baseDir)) {
        if (!@mkdir($baseDir, 0755, true)) {
            return null;
        }
    }

    $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($mediaKey, 0, 64));
    $filename = 'thumb_' . $platform . '_' . $safeKey . '_' . time() . '.' . $ext;
    $path = $baseDir . $filename;

    if (!file_put_contents($path, $data)) {
        return null;
    }

    return '/uploads/thumbnails/' . $filename;
}

/**
 * テキスト内からメンバー名を検出し、マッチしたメンバーIDリストを返す
 * （MediaController::detectMembersFromText 相当のロジック）
 *
 * @param string $text
 * @param array<int,array{id:int,name:string,kana:string}> $allMembers
 * @return int[]
 */
function detect_members_from_text(string $text, array $allMembers): array
{
    if (trim($text) === '') return [];
    $matched = [];
    foreach ($allMembers as $m) {
        $name = $m['name'] ?? '';
        if ($name === '') continue;
        if (mb_strpos($text, $name) !== false) {
            $matched[] = (int)$m['id'];
            continue;
        }
        $parts = preg_split('/\s+/u', $name);
        if (count($parts) === 2) {
            $sei = $parts[0];
            $mei = $parts[1];
            if (mb_strlen($mei) >= 2 && mb_strpos($text, $mei) !== false) {
                $matched[] = (int)$m['id'];
            }
        }
    }
    return array_values(array_unique($matched));
}

/**
 * hn_media_members に自動紐付け（既存の紐付けがあればそれは尊重）
 * （MediaController::autoLinkMembers 相当のロジック）
 *
 * @param \PDO $pdo
 * @param int  $metaId
 * @param int[] $memberIds
 */
function auto_link_members(\PDO $pdo, int $metaId, array $memberIds): void
{
    $existing = $pdo->prepare("SELECT member_id FROM hn_media_members WHERE media_meta_id = ?");
    $existing->execute([$metaId]);
    $existingIds = array_map('intval', $existing->fetchAll(\PDO::FETCH_COLUMN));

    $insert = $pdo->prepare("INSERT INTO hn_media_members (media_meta_id, member_id, update_user) VALUES (?, ?, ?)");
    $user = $_SESSION['user']['id_name'] ?? 'auto';
    foreach ($memberIds as $mid) {
        $mid = (int)$mid;
        if ($mid > 0 && !in_array($mid, $existingIds, true)) {
            $insert->execute([$metaId, $mid, $user]);
        }
    }
}

if ($isCli) {
    // CLIモード: php tiktok_client_import.php urls.txt [category]
    $file = $argv[1] ?? null;
    if (!$file || !is_file($file)) {
        fwrite(STDERR, "Usage: php tiktok_client_import.php urls.txt [category]\n");
        exit(1);
    }
    $category = $argv[2] ?? null;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $stats = import_tiktok_urls($lines, $category);
    echo date('Y-m-d H:i:s') . " TikTok client import completed.\n";
    echo '  Created: ' . $stats['created'] . ', Skipped: ' . $stats['skipped'] . ', Errors: ' . $stats['errors'] . "\n";
    exit($stats['errors'] > 0 ? 1 : 0);
}

// HTTPモード
header('Content-Type: application/json; charset=utf-8');

try {
    require_valid_token();

    $raw = $GLOBALS['__tiktok_client_import_raw'] ?? file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true) ?: [];

    $urls = $input['urls'] ?? [];
    if (!is_array($urls) || empty($urls)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'urls が指定されていません'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $category = $input['category'] ?? null;
    if (is_string($category)) {
        $category = trim($category);
        if ($category === '') {
            $category = null;
        }
    }

    $stats = import_tiktok_urls($urls, $category);

    echo json_encode([
        'status'  => 'success',
        'created' => $stats['created'],
        'skipped' => $stats['skipped'],
        'errors'  => $stats['errors'],
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    Logger::errorWithContext('tiktok_client_import_http: ' . $e->getMessage(), $e);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

