<?php
/**
 * Google Maps API 動作確認スクリプト
 *
 * 使い方（CLI）:
 *   cd d:\02_MyPlatform\home\haitaka
 *   php private/scripts/test_maps_api.php
 *
 * 使い方（ブラウザ・開発環境）:
 *   https://your-domain/live_trip/test_maps_api.php
 *   ※ルーティング設定により要確認
 */
(function () {
    $isCli = php_sapi_name() === 'cli';

    if (!$isCli) {
        header('Content-Type: text/plain; charset=utf-8');
        // 本番環境では削除または認証を追加すること
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            echo "ローカルホストからのみ実行可能です。\n";
            exit(1);
        }
    }

    $out = function (string $s) use ($isCli) {
        echo $s . "\n";
    };

    $out('=== Google Maps API 動作確認 ===');
    $out('');

    // Bootstrap
    $root = dirname(__DIR__);
    require_once $root . '/vendor/autoload.php';
    \Core\Bootstrap::registerErrorHandlers();

    // 1. .env / APIキー
    try {
        $pdo = \Core\Database::connect();
        $out('[1] DB接続: OK');
    } catch (\Throwable $e) {
        $out('[1] DB接続: NG - ' . $e->getMessage());
        exit(1);
    }

    $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
    if ($apiKey === '') {
        $out('[2] APIキー: 未設定 (.env の GOOGLE_MAPS_API_KEY を確認)');
        exit(1);
    }
    $out('[2] APIキー: 設定済 (' . substr($apiKey, 0, 8) . '...)');

    // 2. 利用量テーブル（テスト前）
    $usageModel = new \App\LiveTrip\Model\MapsApiUsageModel();
    $ym = date('Y-m');
    $before = $usageModel->getCurrentCount('geocoding');
    $out('[3] lt_maps_api_usage (geocoding ' . $ym . '): ' . $before . ' 件');

    // 3. Geocoding API 実行
    $geo = (new \App\LiveTrip\Service\MapsGeocodeService())->geocode('東京駅');
    if ($geo === null) {
        $out('[4] Geocoding API: 失敗（結果なし）');
        $out('    → APIキー・リファラー制限・ネットワーク・制限超過を確認');
    } else {
        $out('[4] Geocoding API: OK');
        $out('    緯度: ' . $geo['latitude'] . ', 経度: ' . $geo['longitude']);
    }

    // 4. 利用量テーブル（テスト後）
    $after = $usageModel->getCurrentCount('geocoding');
    $out('[5] lt_maps_api_usage (geocoding ' . $ym . '): ' . $after . ' 件');
    if ($geo !== null && $after > $before) {
        $out('    → DBへの記録: OK (+' . ($after - $before) . ')');
    } elseif ($geo !== null && $after === $before) {
        $out('    → DBへの記録: 変化なし（increment 未実行の可能性）');
    }

    // 5. 全SKUの利用状況
    $out('');
    $out('--- 月間利用状況 (lt_maps_api_usage) ---');
    try {
        $stmt = $pdo->prepare("SELECT sku, `year_month`, `count` FROM lt_maps_api_usage WHERE `year_month` = :ym ORDER BY sku");
        $stmt->execute(['ym' => $ym]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            $out('(データなし)');
        } else {
            foreach ($rows as $r) {
                $out("  {$r['sku']}: {$r['count']}");
            }
        }
    } catch (\Throwable $e) {
        $out('テーブル取得エラー: ' . $e->getMessage());
    }

    $out('');
    $out('=== 完了 ===');
})();
