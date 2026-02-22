<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\Database;
use Core\Utils\StringUtil;

class DbViewerController {
    use DbSchemaTrait;
    /** 一覧表示数の選択肢（数値＝件数、'all'＝すべて） */
    private const LIMIT_OPTIONS = [50, 100, 250, 500, 'all'];
    private const LIMIT_ALL_MAX = 10000; // 「すべて」の上限（負荷対策）

    public function index(): void {
        $auth = new Auth();
        $auth->requireAdmin();

        $pdo = Database::connect();
        $tables = $this->getTableList($pdo);
        $selectedTable = isset($_GET['table']) ? $this->sanitizeTableName($_GET['table']) : null;
        $columns = [];
        $rows = [];
        $totalCount = null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $limitParam = $_GET['limit'] ?? '100';
        if (!in_array($limitParam, array_map('strval', self::LIMIT_OPTIONS), true) && $limitParam !== 'all') {
            $limitParam = '100';
        }
        $rowsPerPage = $limitParam === 'all' ? self::LIMIT_ALL_MAX : (int)$limitParam;

        $tableStructure = [];
        $createSql = null;

        if ($selectedTable && in_array($selectedTable, $tables, true)) {
            $columns = $this->getColumns($pdo, $selectedTable);
            $tableStructure = $this->getTableStructure($pdo, $selectedTable);
            $createSql = $this->getCreateTable($pdo, $selectedTable);
            $totalCount = $this->getCount($pdo, $selectedTable);
            $offset = ($page - 1) * $rowsPerPage;
            $rows = $this->getRows($pdo, $selectedTable, $offset, $rowsPerPage);
        }

        $user = $_SESSION['user'];
        $limitOption = $limitParam;
        require_once __DIR__ . '/../Views/db_viewer.php';
    }

    private function sanitizeTableName(string $name): string {
        return StringUtil::sanitizeIdentifier($name);
    }

    private function getColumns(\PDO $pdo, string $table): array {
        $table = StringUtil::sanitizeIdentifier($table);
        $stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table) . " ORDER BY ORDINAL_POSITION");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getCount(\PDO $pdo, string $table): int {
        $table = StringUtil::sanitizeIdentifier($table);
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `" . str_replace('`', '``', $table) . "`");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    private function getRows(\PDO $pdo, string $table, int $offset, int $limit): array {
        $table = StringUtil::sanitizeIdentifier($table);
        $safeTable = '`' . str_replace('`', '``', $table) . '`';
        $stmt = $pdo->query("SELECT * FROM $safeTable LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
