<?php

namespace App\Admin\Controller;

use Core\Auth;
use Core\Database;
use Core\Utils\StringUtil;

class DbViewerController {
    private const MAX_ROWS = 500;
    private const ROWS_PER_PAGE = 100;

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

        if ($selectedTable && in_array($selectedTable, $tables, true)) {
            $columns = $this->getColumns($pdo, $selectedTable);
            $totalCount = $this->getCount($pdo, $selectedTable);
            $offset = ($page - 1) * self::ROWS_PER_PAGE;
            $rows = $this->getRows($pdo, $selectedTable, $offset, self::ROWS_PER_PAGE);
        }

        $user = $_SESSION['user'];
        $rowsPerPage = self::ROWS_PER_PAGE;
        require_once __DIR__ . '/../Views/db_viewer.php';
    }

    private function getTableList(\PDO $pdo): array {
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
        $list = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $list[] = $row['TABLE_NAME'];
        }
        return $list;
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
