<?php

namespace App\Admin\Controller;

use Core\Utils\StringUtil;

/**
 * DBスキーマ取得の共通ロジック
 * DbViewerController / DbExportController で共用する
 */
trait DbSchemaTrait {

    private function getTableList(\PDO $pdo): array {
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
        $list = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $list[] = $row['TABLE_NAME'];
        }
        return $list;
    }

    private function getTableStructure(\PDO $pdo, string $table): array {
        $table = StringUtil::sanitizeIdentifier($table);
        $stmt = $pdo->query("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table) . "
            ORDER BY ORDINAL_POSITION
        ");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getCreateTable(\PDO $pdo, string $table): ?string {
        $table = StringUtil::sanitizeIdentifier($table);
        $safeTable = '`' . str_replace('`', '``', $table) . '`';
        $stmt = $pdo->query("SHOW CREATE TABLE $safeTable");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['Create Table'] ?? null;
    }
}
