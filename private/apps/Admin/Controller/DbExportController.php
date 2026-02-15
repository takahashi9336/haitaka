<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\Database;
use Core\Utils\StringUtil;

/**
 * DB一括抽出（管理画面の子画面）
 * 全CREATE文・スキーマ概要（Markdown）・JSON の3形式でダウンロードを提供する。
 */
class DbExportController {

    public function index(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $download = $_GET['download'] ?? '';
        if ($download === 'all_create') {
            $this->downloadAllCreate();
            return;
        }
        if ($download === 'schema_md') {
            $this->downloadSchemaMarkdown();
            return;
        }
        if ($download === 'schema_json') {
            $this->downloadSchemaJson();
            return;
        }

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/db_export.php';
    }

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

    /**
     * 全テーブルの CREATE TABLE 文を1つの .sql でダウンロード
     */
    public function downloadAllCreate(): void {
        $pdo = Database::connect();
        $tables = $this->getTableList($pdo);
        $lines = [
            '-- ' . str_repeat('=', 60),
            '-- 全テーブル CREATE 文（構造のみ・データなし）',
            '-- 取得日時: ' . date('Y-m-d H:i:s'),
            '-- テーブル数: ' . count($tables),
            '-- AI共有・スキーマ確認用',
            '-- ' . str_repeat('=', 60),
            '',
        ];
        foreach ($tables as $table) {
            $create = $this->getCreateTable($pdo, $table);
            if ($create !== null && $create !== '') {
                $lines[] = '-- --- "'.$table.'" ---';
                $lines[] = $create . ';';
                $lines[] = '';
            }
        }
        $body = implode("\n", $lines);
        $filename = 'schema_all_tables_' . date('Ymd_His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    /**
     * スキーマ概要を Markdown でダウンロード
     */
    public function downloadSchemaMarkdown(): void {
        $pdo = Database::connect();
        $tables = $this->getTableList($pdo);
        $lines = [
            '# スキーマ概要',
            '',
            '- **取得日時**: ' . date('Y-m-d H:i:s'),
            '- **テーブル数**: ' . count($tables),
            '',
            '---',
            '',
        ];
        foreach ($tables as $table) {
            $struct = $this->getTableStructure($pdo, $table);
            $lines[] = '## ' . $table;
            $lines[] = '';
            if (empty($struct)) {
                $lines[] = '（カラム情報なし）';
                $lines[] = '';
                continue;
            }
            $lines[] = '| カラム名 | 型 | NULL | キー | デフォルト | EXTRA |';
            $lines[] = '| --- | --- | --- | --- | --- | --- |';
            foreach ($struct as $col) {
                $lines[] = '| ' . ($col['COLUMN_NAME'] ?? '')
                    . ' | ' . ($col['COLUMN_TYPE'] ?? '')
                    . ' | ' . ($col['IS_NULLABLE'] ?? '')
                    . ' | ' . ($col['COLUMN_KEY'] ?? '')
                    . ' | ' . (($col['COLUMN_DEFAULT'] ?? '') !== '' ? $col['COLUMN_DEFAULT'] : '—')
                    . ' | ' . ($col['EXTRA'] ?? '')
                    . ' |';
            }
            $lines[] = '';
        }
        $body = implode("\n", $lines);
        $filename = 'schema_overview_' . date('Ymd_His') . '.md';
        header('Content-Type: text/markdown; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    /**
     * スキーマを JSON でダウンロード（テーブル名・カラム情報・CREATE文を含む）
     */
    public function downloadSchemaJson(): void {
        $pdo = Database::connect();
        $tables = $this->getTableList($pdo);
        $dbName = null;
        try {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        } catch (\Throwable $e) {
            // ignore
        }
        $payload = [
            'exported_at' => date('Y-m-d H:i:s'),
            'database' => $dbName,
            'tables_count' => count($tables),
            'tables' => [],
        ];
        foreach ($tables as $table) {
            $struct = $this->getTableStructure($pdo, $table);
            $create = $this->getCreateTable($pdo, $table);
            $columns = [];
            foreach ($struct as $col) {
                $columns[] = [
                    'name' => $col['COLUMN_NAME'] ?? null,
                    'type' => $col['COLUMN_TYPE'] ?? null,
                    'nullable' => ($col['IS_NULLABLE'] ?? '') === 'YES',
                    'key' => $col['COLUMN_KEY'] ?? null,
                    'default' => $col['COLUMN_DEFAULT'] ?? null,
                    'extra' => $col['EXTRA'] ?? null,
                ];
            }
            $payload['tables'][] = [
                'name' => $table,
                'create_sql' => $create,
                'columns' => $columns,
            ];
        }
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'schema_' . date('Ymd_His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }
}
