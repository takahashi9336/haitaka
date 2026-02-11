<?php
/**
 * ログイン検証用デバッグスクリプト
 * エラーが発生した場合、その内容を画面に表示します。
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../private/vendor/autoload.php';

use Core\Database;

echo "<h1>Login Debug Tool</h1>";

try {
    echo "<li>PHP Version: " . PHP_VERSION . "</li>";
    echo "<li>Database Connection Attempting...</li>";
    
    $db = Database::connect();
    echo "<p style='color:green;'>✅ DB Connection: OK</p>";

    // sys_usersの中身を確認
    $stmt = $db->query("SELECT id, id_name, role FROM sys_users");
    $users = $stmt->fetchAll();
    
    echo "<h2>Registered Users:</h2>";
    if (empty($users)) {
        echo "<p style='color:orange;'>⚠️ sys_usersテーブルにデータがありません。</p>";
    } else {
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }

    // パスワードハッシュの検証テスト（takahashiさんでテスト）
    $testId = 'takahashi';
    $testPass = 'password'; // SQLで登録した想定のパスワード
    
    $stmt = $db->prepare("SELECT password FROM sys_users WHERE id_name = :id");
    $stmt->execute(['id' => $testId]);
    $hash = $stmt->fetchColumn();

    echo "<h2>Password Hash Verification:</h2>";
    if ($hash) {
        if (password_verify($testPass, $hash)) {
            echo "<p style='color:green;'>✅ Password Match: SUCCESS (ID: $testId)</p>";
        } else {
            echo "<p style='color:red;'>❌ Password Match: FAILED</p>";
            echo "Hash in DB: " . htmlspecialchars($hash) . "<br>";
            echo "Verify against: " . htmlspecialchars($testPass);
        }
    } else {
        echo "<p style='color:red;'>User '$testId' not found in DB.</p>";
    }

} catch (\Throwable $e) {
    echo "<h2>❌ Fatal Error Captured:</h2>";
    echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre style='font-size:11px; background:#eee; padding:10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}