<?php
/**
 * 既存の平文データを一括暗号化する移行スクリプト
 *
 * 使い方:
 *   php encrypt_existing_data.php
 *
 * 冪等: 既に暗号化済みの行はスキップする。
 * 安全のため、実行前にDBのバックアップを取ること。
 */

require_once __DIR__ . '/../../bootstrap.php';

use Core\Database;
use Core\Encryption;

$pdo = Database::connect();

$targets = [
    [
        'table'  => 'hn_neta',
        'fields' => ['content', 'memo'],
    ],
    [
        'table'  => 'hn_meetgreet_slots',
        'fields' => ['report'],
    ],
    [
        'table'  => 'hn_meetgreet_reports',
        'fields' => ['my_nickname'],
    ],
    [
        'table'  => 'hn_meetgreet_report_messages',
        'fields' => ['content'],
    ],
];

echo "=== 既存データ暗号化スクリプト ===\n\n";

foreach ($targets as $target) {
    $table = $target['table'];
    $fields = $target['fields'];
    $fieldList = implode(', ', $fields);

    echo "[{$table}] 対象フィールド: {$fieldList}\n";

    $rows = $pdo->query("SELECT id, {$fieldList} FROM {$table}")->fetchAll();
    $total = count($rows);
    $updated = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $sets = [];
        $params = ['id' => $row['id']];
        $needsUpdate = false;

        foreach ($fields as $field) {
            $value = $row[$field];
            if ($value === null || $value === '') {
                $skipped++;
                continue;
            }

            if (Encryption::isEncrypted($value)) {
                continue;
            }

            $encrypted = Encryption::encrypt($value);
            $sets[] = "{$field} = :{$field}";
            $params[$field] = $encrypted;
            $needsUpdate = true;
        }

        if ($needsUpdate && !empty($sets)) {
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
            $pdo->prepare($sql)->execute($params);
            $updated++;
        }
    }

    echo "  全 {$total} 行 => 暗号化: {$updated} 行, スキップ(空/暗号化済み): " . ($total - $updated) . " 行\n\n";
}

echo "=== 完了 ===\n";
