<?php

namespace App\Hinata\Model;

use Core\BaseModel;

class MeetGreetReportMessageModel extends BaseModel {
    protected string $table = 'hn_meetgreet_report_messages';
    protected array $fields = [
        'id', 'user_id', 'report_id', 'sender_type', 'content',
        'sort_order', 'created_at', 'updated_at'
    ];
    protected array $encryptedFields = ['content'];

    /**
     * レポIDに紐づくメッセージ一覧を取得
     */
    public function getMessagesByReportId(int $reportId): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE report_id = :report_id AND user_id = :uid
                ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['report_id' => $reportId, 'uid' => $this->userId]);
        return $this->decryptRows($stmt->fetchAll());
    }

    /**
     * レポ内の全メッセージを一括保存（DELETE+INSERT）
     */
    public function bulkSave(int $reportId, array $messages): int {
        $delSql = "DELETE FROM {$this->table}
                   WHERE report_id = :report_id AND user_id = :uid";
        $this->pdo->prepare($delSql)->execute([
            'report_id' => $reportId,
            'uid'       => $this->userId,
        ]);

        if (empty($messages)) return 0;

        $insSql = "INSERT INTO {$this->table}
                   (user_id, report_id, sender_type, content, sort_order, created_at)
                   VALUES (:uid, :report_id, :sender_type, :content, :sort_order, NOW())";
        $stmt = $this->pdo->prepare($insSql);
        $count = 0;

        foreach ($messages as $i => $msg) {
            $stmt->execute([
                'uid'         => $this->userId,
                'report_id'   => $reportId,
                'sender_type' => $msg['sender_type'] ?? 'self',
                'content'     => \Core\Encryption::encrypt($msg['content'] ?? ''),
                'sort_order'  => $msg['sort_order'] ?? $i,
            ]);
            $count++;
        }
        return $count;
    }

    /**
     * 特定レポの全メッセージを一括取得（複数レポID対応）
     */
    public function getMessagesByReportIds(array $reportIds): array {
        if (empty($reportIds)) return [];
        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
        $sql = "SELECT * FROM {$this->table}
                WHERE report_id IN ({$placeholders}) AND user_id = ?
                ORDER BY report_id ASC, sort_order ASC, id ASC";
        $params = array_values($reportIds);
        $params[] = $this->userId;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $rows = $this->decryptRows($rows);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int)$row['report_id']][] = $row;
        }
        return $grouped;
    }
}
