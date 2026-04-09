<?php

namespace App\Note\Model;

use Core\BaseModel;

/**
 * リスト種別エントリ管理モデル
 * 物理パス: haitaka/private/apps/Note/Model/NoteListEntryModel.php
 */
class NoteListEntryModel extends BaseModel {
    protected string $table = 'nt_list_entries';
    protected bool $isUserIsolated = true;

    public const LIST_KINDS = [
        'todo' => 'やること',
        'question' => '疑問・仮説',
        'first_time' => 'はじめて',
        'fun' => 'おもろかったこと',
        'book' => '書籍メモ',
        'generic_list' => '汎用リスト',
    ];

    protected array $fields = [
        'id', 'user_id', 'list_kind', 'payload',
        'bg_color', 'is_pinned', 'status', 'created_at', 'updated_at',
    ];

    public function isValidListKind(string $kind): bool {
        return array_key_exists($kind, self::LIST_KINDS);
    }

    public function normalizePayload(string $kind, array $payload): array {
        if (!$this->isValidListKind($kind)) {
            return [];
        }

        if ($kind === 'todo') {
            $items = $payload['items'] ?? [];
            if (!is_array($items)) $items = [];
            $norm = [];
            foreach ($items as $it) {
                if (!is_array($it)) continue;
                $text = trim((string)($it['text'] ?? ''));
                if ($text === '') continue;
                $done = !empty($it['done']) ? 1 : 0;
                $id = $it['id'] ?? null;
                $id = ($id === null || $id === '') ? null : (string)$id;
                $norm[] = ['id' => $id, 'text' => $text, 'done' => $done];
            }
            return ['items' => $norm];
        }

        if ($kind === 'generic_list') {
            $items = $payload['items'] ?? [];
            if (!is_array($items)) $items = [];
            $norm = [];
            foreach ($items as $it) {
                if (!is_array($it)) continue;
                $text = trim((string)($it['text'] ?? ''));
                if ($text === '') continue;
                $id = $it['id'] ?? null;
                $id = ($id === null || $id === '') ? null : (string)$id;
                $norm[] = ['id' => $id, 'text' => $text];
            }
            return ['items' => $norm];
        }

        if ($kind === 'question') {
            $q = trim((string)($payload['question'] ?? ''));
            $h = trim((string)($payload['hypothesis'] ?? ''));
            $gap = trim((string)($payload['gap'] ?? ''));
            $a = trim((string)($payload['answer'] ?? ''));
            $transfer = trim((string)($payload['transfer'] ?? ''));
            return [
                'question' => $q,
                'hypothesis' => $h,
                'gap' => $gap,
                'answer' => $a,
                'transfer' => $transfer,
            ];
        }

        if ($kind === 'first_time') {
            $occurredAt = trim((string)($payload['occurred_at'] ?? ''));
            $what = trim((string)($payload['what'] ?? ''));
            $memo = trim((string)($payload['memo'] ?? ''));
            return [
                'occurred_at' => $occurredAt,
                'what' => $what,
                'memo' => $memo,
            ];
        }

        if ($kind === 'fun') {
            $hook = trim((string)($payload['hook'] ?? ''));
            $detail = trim((string)($payload['detail'] ?? ''));
            return [
                'hook' => $hook,
                'detail' => $detail,
            ];
        }

        if ($kind === 'book') {
            $title = trim((string)($payload['title'] ?? ''));
            $why = trim((string)($payload['why_read'] ?? ''));
            $notes = trim((string)($payload['notes'] ?? ''));
            return [
                'title' => $title,
                'why_read' => $why,
                'notes' => $notes,
            ];
        }

        return [];
    }

    private function decodeRow(array $row): array {
        $p = $row['payload'] ?? null;
        if (is_string($p)) {
            $decoded = json_decode($p, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $row['payload'] = $decoded;
            } else {
                $row['payload'] = [];
            }
        } elseif (!is_array($p)) {
            $row['payload'] = [];
        }
        return $row;
    }

    public function getActiveByKind(string $kind): array {
        if (!$this->isValidListKind($kind)) return [];
        $sql = "SELECT " . implode(', ', $this->fields) . "
                FROM {$this->table}
                WHERE user_id = :uid AND list_kind = :k AND status = 'active'
                ORDER BY is_pinned DESC, created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'k' => $kind]);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => $this->decodeRow($r), $rows);
    }

    public function getArchivedByKind(string $kind): array {
        if (!$this->isValidListKind($kind)) return [];
        $sql = "SELECT " . implode(', ', $this->fields) . "
                FROM {$this->table}
                WHERE user_id = :uid AND list_kind = :k AND status = 'archived'
                ORDER BY is_pinned DESC, created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'k' => $kind]);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => $this->decodeRow($r), $rows);
    }

    public function createEntry(array $data): bool {
        $kind = (string)($data['list_kind'] ?? '');
        $payload = $data['payload'] ?? [];
        if (!is_array($payload)) $payload = [];

        $payload = $this->normalizePayload($kind, $payload);
        if ($payload === []) return false;

        $row = [
            'list_kind' => $kind,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'bg_color' => $data['bg_color'] ?? '#ffffff',
            'is_pinned' => $data['is_pinned'] ?? 0,
            'status' => $data['status'] ?? 'active',
        ];

        return $this->create($row);
    }

    public function updateEntry(int $id, array $data): bool {
        $existing = $this->find($id);
        if (!$existing) return false;
        $kind = (string)($existing['list_kind'] ?? '');

        $update = [];
        if (array_key_exists('payload', $data)) {
            $payload = $data['payload'];
            if (!is_array($payload)) $payload = [];
            $payload = $this->normalizePayload($kind, $payload);
            if ($payload === []) return false;
            $update['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }
        foreach (['bg_color', 'is_pinned', 'status'] as $k) {
            if (array_key_exists($k, $data)) $update[$k] = $data[$k];
        }
        if ($update === []) return false;
        return $this->update($id, $update);
    }

    public function togglePin(int $id): bool {
        $row = $this->find($id);
        if (!$row) return false;
        $newPin = !empty($row['is_pinned']) ? 0 : 1;
        return $this->update($id, ['is_pinned' => $newPin]);
    }
}

