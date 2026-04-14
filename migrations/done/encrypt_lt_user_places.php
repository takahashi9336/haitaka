<?php
/**
 * lt_user_places の既存平文データを一括暗号化する移行スクリプト
 *
 * 使い方:
 *   php encrypt_lt_user_places.php
 *
 * 前提:
 * - .env に ENCRYPTION_KEY が設定されていること
 * - 実行前にDBバックアップを推奨
 */

require_once __DIR__ . '/../../private/bootstrap.php';

use Core\Database;
use Core\Encryption;

$pdo = Database::connect();

$table = 'lt_user_places';
$fields = ['address', 'place_id'];

echo "=== {$table} 暗号化スクリプト ===\n\n";

$rows = $pdo->query("SELECT id, " . implode(', ', $fields) . " FROM {$table}")->fetchAll();
$total = count($rows);
$updated = 0;

foreach ($rows as $row) {
    $sets = [];
    $params = ['id' => $row['id']];
    $needsUpdate = false;

    foreach ($fields as $field) {
        $value = $row[$field] ?? null;
        if ($value === null || $value === '') continue;
        if (Encryption::isEncrypted($value)) continue;

        $sets[] = "{$field} = :{$field}";
        $params[$field] = Encryption::encrypt((string) $value);
        $needsUpdate = true;
    }

    if ($needsUpdate) {
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
        $pdo->prepare($sql)->execute($params);
        $updated++;
    }
}

echo "  全 {$total} 行 => 暗号化: {$updated} 行, スキップ: " . ($total - $updated) . " 行\n\n";
echo "=== 完了 ===\n";

