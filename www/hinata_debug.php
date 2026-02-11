<?php
// エラーを画面に強制表示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Hinata App Debugger</h1>";

// 1. オートローダーの確認
$autoloadPath = __DIR__ . '/../private/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("<h2 style='color:red'>❌ Autoload file not found: $autoloadPath</h2>");
}
require_once $autoloadPath;
echo "<p style='color:green'>✅ Autoloader loaded.</p>";

use Core\Database;
use App\Hinata\Model\NetaModel;

try {
    // 2. クラスの読み込み確認
    if (!class_exists(NetaModel::class)) {
        throw new Exception("❌ Class App\Hinata\Model\NetaModel not found. Check folder names (Case Sensitive!).");
    }
    echo "<p style='color:green'>✅ NetaModel class found.</p>";

    // 3. DB接続確認
    $model = new NetaModel();
    echo "<p style='color:green'>✅ DB Connection successful.</p>";

    // 4. テーブル存在確認
    $db = Database::connect();
    $tables = ['hn_members', 'hn_colors', 'hn_neta', 'hn_favorites'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ Table '$table' exists.</p>";
            // カラム確認
            $cols = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            echo "<small style='color:gray'>Columns: " . implode(', ', $cols) . "</small><br>";
        } else {
            echo "<p style='color:red; font-weight:bold'>❌ Table '$table' DOES NOT EXIST!</p>";
        }
    }

    // 5. 問題のSQL実行テスト
    echo "<h3>Testing SQL Query...</h3>";
    // ログインユーザーIDの仮定（デバッグ用）
    $dummyUserId = 1; 

    // NetaModel内のSQLを直接テスト
    $sql = "SELECT n.*, m.name as member_name, c1.color_code as color1, 
                   IF(f.id IS NULL, 0, 1) as is_favorite
            FROM hn_members m
            JOIN hn_neta n ON n.member_id = m.id
            LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
            LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
            LEFT JOIN hn_favorites f ON m.id = f.member_id AND f.user_id = :uid
            WHERE n.user_id = :uid AND n.status != 'delete'
            ORDER BY is_favorite DESC, m.generation ASC, m.kana ASC, n.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute(['uid' => $dummyUserId]);
    $rows = $stmt->fetchAll();
    
    echo "<p style='color:green'>✅ SQL Query Executed Successfully.</p>";
    echo "<pre>Result Count: " . count($rows) . "</pre>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Database Error (SQL)</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background:#eee; padding:10px'>" . htmlspecialchars($sql ?? 'No SQL') . "</pre>";
} catch (Throwable $e) {
    echo "<h2 style='color:red'>❌ Fatal Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
}