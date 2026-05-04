<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\Database;
use Core\Utils\StringUtil;

/**
 * DB一括抽出（管理画面の子画面）
 * 全CREATE文・スキーマ概要（Markdown）・JSON・全テーブルデータ（CSV/ZIP）のダウンロードを提供する。
 */
class DbExportController {
    use DbSchemaTrait;
    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function index(): void {
        $this->auth->requireAdmin();

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
        if ($download === 'all_data_csv_zip') {
            $this->downloadAllDataCsvZip();
            return;
        }

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/db_export.php';
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

    /**
     * 全テーブルの行データをテーブルごとに CSV にし、1 つの ZIP でダウンロード
     * （PHP zip 拡張が必要）
     */
    public function downloadAllDataCsvZip(): void {
        if (!class_exists(\ZipArchive::class)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'ZipArchive が利用できません。php.ini で zip 拡張を有効にしてください。';
            exit;
        }

        $pdo = Database::connect();
        $tables = $this->getTableList($pdo);
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'db_data_' . bin2hex(random_bytes(8)) . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'ZIP ファイルの作成に失敗しました。';
            exit;
        }

        $tempCsvPaths = [];
        try {
            foreach ($tables as $table) {
                $csvPath = $this->writeTableDataCsvToTemp($pdo, $table);
                if ($csvPath === null) {
                    continue;
                }
                $tempCsvPaths[] = $csvPath;
                $entryName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $table) . '.csv';
                $zip->addFile($csvPath, $entryName);
            }
            $zip->close();

            if ($tempCsvPaths === [] && $tables !== []) {
                @unlink($zipPath);
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'どのテーブルも CSV 化できませんでした。権限または DB の状態を確認してください。';
                exit;
            }

            $filename = 'db_all_tables_data_' . date('Ymd_His') . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . (string) filesize($zipPath));
            readfile($zipPath);
        } finally {
            foreach ($tempCsvPaths as $p) {
                @unlink($p);
            }
            @unlink($zipPath);
        }
        exit;
    }

    /**
     * 1 テーブル分を UTF-8（BOM 付き）CSV として一時ファイルに書き出す
     */
    private function writeTableDataCsvToTemp(\PDO $pdo, string $table): ?string {
        $san = StringUtil::sanitizeIdentifier($table);
        if ($san === '') {
            return null;
        }
        $safeTable = '`' . str_replace('`', '``', $san) . '`';
        $struct = $this->getTableStructure($pdo, $san);
        $headers = [];
        foreach ($struct as $col) {
            $name = $col['COLUMN_NAME'] ?? '';
            if ($name !== '') {
                $headers[] = $name;
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'dbcsv_');
        if ($tmp === false) {
            return null;
        }
        $fh = fopen($tmp, 'wb');
        if ($fh === false) {
            @unlink($tmp);
            return null;
        }

        fprintf($fh, "\xEF\xBB\xBF");
        if ($headers !== []) {
            fputcsv($fh, $headers);
        }

        try {
            $stmt = $pdo->query('SELECT * FROM ' . $safeTable);
            if ($stmt !== false) {
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if ($headers === []) {
                        $headers = array_keys($row);
                        fputcsv($fh, $headers);
                    }
                    $line = [];
                    foreach ($headers as $h) {
                        $line[] = $row[$h] ?? null;
                    }
                    fputcsv($fh, $line);
                }
            }
        } catch (\Throwable $e) {
            fclose($fh);
            @unlink($tmp);
            return null;
        }

        fclose($fh);
        return $tmp;
    }
}
