<?php
/**
 * 推し活タイムラインAPI
 * GET: ?member_id=X&offset=0&limit=15
 */
require_once __DIR__ . '/../../../private/vendor/autoload.php';

use Core\Auth;
use Core\Database;

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->check()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => '認証が必要です']);
    exit;
}

try {
    $memberId = (int)($_GET['member_id'] ?? 0);
    if ($memberId === 0) throw new \Exception('member_id が必要です');
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 15)));

    $pdo = Database::connect();
    $userId = $_SESSION['user']['id'] ?? 0;

    $unions = [];
    $params = [];

    // ブログ
    $unions[] = "SELECT 'blog' as type, bp.id, bp.title, bp.thumbnail_url,
                        bp.published_at as event_date, bp.detail_url as url, NULL as extra
                 FROM hn_blog_posts bp
                 WHERE bp.member_id = :mid_blog";
    $params['mid_blog'] = $memberId;

    // ニュース
    $unions[] = "SELECT 'news' as type, n.id, n.title, NULL as thumbnail_url,
                        n.published_date as event_date, n.detail_url as url, n.category as extra
                 FROM hn_news n
                 JOIN hn_news_members nm ON nm.news_id = n.id
                 WHERE nm.member_id = :mid_news";
    $params['mid_news'] = $memberId;

    // スケジュール（翌日以前＝今日・過去のみ、予定で埋まらないよう）
    $unions[] = "SELECT 'schedule' as type, s.id, s.title, NULL as thumbnail_url,
                        s.schedule_date as event_date, s.detail_url as url, s.category as extra
                 FROM hn_schedule s
                 JOIN hn_schedule_members sm ON sm.schedule_id = s.id
                 WHERE sm.member_id = :mid_sched AND s.schedule_date <= CURDATE()";
    $params['mid_sched'] = $memberId;

    // イベント（翌日以前）
    $unions[] = "SELECT 'event' as type, e.id, e.event_name as title, NULL as thumbnail_url,
                        e.event_date as event_date, NULL as url, e.event_place as extra
                 FROM hn_events e
                 JOIN hn_event_members em ON em.event_id = e.id
                 WHERE em.member_id = :mid_event AND e.event_date <= CURDATE()";
    $params['mid_event'] = $memberId;

    // 動画（モーダル再生用に media_key, platform, sub_key, category, description を含む）
    $unions[] = "SELECT 'video' as type, hmeta.id, ma.title, ma.thumbnail_url,
                        COALESCE(ma.upload_date, ma.created_at) as event_date,
                        NULL as url,
                        JSON_OBJECT(
                            'media_key', ma.media_key,
                            'platform', ma.platform,
                            'sub_key', COALESCE(ma.sub_key, ''),
                            'category', COALESCE(hmeta.category, ''),
                            'description', COALESCE(ma.description, ''),
                            'upload_date', COALESCE(ma.upload_date, ma.created_at)
                        ) as extra
                 FROM hn_media_members mm
                 JOIN hn_media_metadata hmeta ON hmeta.id = mm.media_meta_id
                 JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                 WHERE mm.member_id = :mid_video";
    $params['mid_video'] = $memberId;

    $sql = "SELECT * FROM (\n" . implode("\nUNION ALL\n", $unions) . "\n) combined
            ORDER BY event_date DESC
            LIMIT :lim OFFSET :off";
    $params['lim'] = $limit;
    $params['off'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(':' . $key, $val, is_int($val) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $items], JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
