<?php
require_once __DIR__ . '/../../../private/bootstrap.php';

use Core\Auth;
use Core\Database;

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$articleUrl = trim((string)($input['article_url'] ?? ''));
$articleTitle = trim((string)($input['article_title'] ?? ''));

if ($articleUrl === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'article_url is required']);
    exit;
}

// 各コメントは500文字以内に制限（多すぎる場合は切り捨て）
function norm(?string $v): string {
    $v = trim((string)($v ?? ''));
    if ($v === '') return '';
    if (mb_strlen($v) > 500) {
        $v = mb_substr($v, 0, 500);
    }
    return $v;
}

$data = [
    'praise_1'   => norm($input['praise_1'] ?? null),
    'praise_2'   => norm($input['praise_2'] ?? null),
    'praise_3'   => norm($input['praise_3'] ?? null),
    'tsukkomi_1' => norm($input['tsukkomi_1'] ?? null),
    'tsukkomi_2' => norm($input['tsukkomi_2'] ?? null),
    'tsukkomi_3' => norm($input['tsukkomi_3'] ?? null),
];

$nonEmptyCount = 0;
foreach ($data as $v) {
    if ($v !== '') $nonEmptyCount++;
}

if ($nonEmptyCount === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => '少なくとも1つはコメントを入力してください']);
    exit;
}

if ($articleTitle === '') {
    $articleTitle = $articleUrl;
}

try {
    $pdo = Database::connect();

    $sql = 'INSERT INTO dashboard_article_training
                (user_id, article_url, article_title,
                 praise_1, praise_2, praise_3,
                 tsukkomi_1, tsukkomi_2, tsukkomi_3,
                 created_at, updated_at)
            VALUES
                (:user_id, :article_url, :article_title,
                 :praise_1, :praise_2, :praise_3,
                 :tsukkomi_1, :tsukkomi_2, :tsukkomi_3,
                 NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                article_title = VALUES(article_title),
                praise_1 = VALUES(praise_1),
                praise_2 = VALUES(praise_2),
                praise_3 = VALUES(praise_3),
                tsukkomi_1 = VALUES(tsukkomi_1),
                tsukkomi_2 = VALUES(tsukkomi_2),
                tsukkomi_3 = VALUES(tsukkomi_3),
                updated_at = NOW()';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'       => $user['id'],
        ':article_url'   => $articleUrl,
        ':article_title' => $articleTitle,
        ':praise_1'      => $data['praise_1'],
        ':praise_2'      => $data['praise_2'],
        ':praise_3'      => $data['praise_3'],
        ':tsukkomi_1'    => $data['tsukkomi_1'],
        ':tsukkomi_2'    => $data['tsukkomi_2'],
        ':tsukkomi_3'    => $data['tsukkomi_3'],
    ]);

    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    \Core\Logger::errorWithContext('save_article_training failed', $e);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'サーバーエラーが発生しました']);
}

