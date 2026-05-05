<?php

namespace App\Admin\Model;

final class TextFileAdminStorage {
    private const ALLOWED_EXT = ['txt' => true, 'md' => true, 'html' => true];
    private const MAX_CONTENT_BYTES = 524288; // 512KB

    private string $dir;
    private string $indexPath;

    public function __construct(?string $baseDir = null) {
        // private/apps/Admin/Model -> private/storage/...
        $this->dir = $baseDir ?: (dirname(__DIR__, 3) . '/storage/admin_text_files');
        $this->indexPath = $this->dir . '/index.json';
        $this->ensureDir();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array {
        $data = $this->readIndex();
        usort($data, static function ($a, $b) {
            $au = (string)($a['updated_at'] ?? '');
            $bu = (string)($b['updated_at'] ?? '');
            if ($au === $bu) {
                return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
            }
            return strcmp($bu, $au);
        });
        return $data;
    }

    public function get(string $id): ?array {
        $index = $this->readIndex();
        foreach ($index as $row) {
            if (($row['id'] ?? '') === $id) {
                $path = $this->dir . '/' . ($row['filename'] ?? '');
                if (!is_file($path)) return null;
                $content = file_get_contents($path);
                if ($content === false) return null;
                $row['content'] = $content;
                return $row;
            }
        }
        return null;
    }

    public function save(array $data, int $createdBy): array {
        $id = trim((string)($data['id'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $ext = strtolower(trim((string)($data['ext'] ?? '')));
        $content = (string)($data['content'] ?? '');

        if ($title === '') {
            throw new \InvalidArgumentException('タイトルは必須です');
        }
        if (mb_strlen($title) > 120) {
            throw new \InvalidArgumentException('タイトルが長すぎます（120文字以内）');
        }
        if (!isset(self::ALLOWED_EXT[$ext])) {
            throw new \InvalidArgumentException('拡張子が不正です（txt / md / html）');
        }
        if (strlen($content) > self::MAX_CONTENT_BYTES) {
            throw new \InvalidArgumentException('本文が大きすぎます（最大 512KB）');
        }

        $now = date('Y-m-d H:i:s');
        $isNew = ($id === '');
        if ($isNew) {
            $id = $this->newId();
        }

        $safeBase = $this->slug($title);
        $filename = $id . '_' . $safeBase . '.' . $ext;
        $path = $this->dir . '/' . $filename;

        // index を排他して、ファイル名衝突を避けつつ更新
        $fp = $this->openIndexFp();
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException('ロックに失敗しました');
            }
            $index = $this->readIndexFromFp($fp);

            $existingIdx = null;
            $existing = null;
            foreach ($index as $i => $row) {
                if (($row['id'] ?? '') === $id) {
                    $existingIdx = $i;
                    $existing = $row;
                    break;
                }
            }

            if ($existing && !empty($existing['filename']) && $existing['filename'] !== $filename) {
                $oldPath = $this->dir . '/' . $existing['filename'];
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            if (file_put_contents($path, $content, LOCK_EX) === false) {
                throw new \RuntimeException('ファイル保存に失敗しました');
            }

            $row = [
                'id' => $id,
                'title' => $title,
                'ext' => $ext,
                'filename' => $filename,
                'size' => filesize($path) ?: strlen($content),
                'created_by' => $existing['created_by'] ?? $createdBy,
                'created_at' => $existing['created_at'] ?? $now,
                'updated_at' => $now,
            ];

            if ($existingIdx !== null) {
                $index[$existingIdx] = $row;
            } else {
                $index[] = $row;
            }

            $this->writeIndexToFp($fp, $index);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }

        return ['id' => $id, 'filename' => $filename];
    }

    public function delete(string $id): bool {
        $id = trim($id);
        if ($id === '') return false;

        $fp = $this->openIndexFp();
        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException('ロックに失敗しました');
            }
            $index = $this->readIndexFromFp($fp);
            $new = [];
            $deletedRow = null;
            foreach ($index as $row) {
                if (($row['id'] ?? '') === $id) {
                    $deletedRow = $row;
                    continue;
                }
                $new[] = $row;
            }
            if (!$deletedRow) {
                flock($fp, LOCK_UN);
                return false;
            }
            $this->writeIndexToFp($fp, $new);
            flock($fp, LOCK_UN);
        } finally {
            fclose($fp);
        }

        $path = $this->dir . '/' . ($deletedRow['filename'] ?? '');
        if (is_file($path)) {
            @unlink($path);
        }
        return true;
    }

    private function ensureDir(): void {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
        if (!is_file($this->indexPath)) {
            file_put_contents($this->indexPath, '[]', LOCK_EX);
        }
    }

    private function newId(): string {
        return date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    private function slug(string $s): string {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^\p{L}\p{N}]+/u', '_', $s) ?? '';
        $s = trim($s, '_');
        if ($s === '') $s = 'text';
        if (mb_strlen($s) > 50) $s = mb_substr($s, 0, 50);
        return $s;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readIndex(): array {
        $raw = @file_get_contents($this->indexPath);
        if ($raw === false || trim($raw) === '') return [];
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    private function openIndexFp() {
        $fp = fopen($this->indexPath, 'c+');
        if ($fp === false) {
            throw new \RuntimeException('index のオープンに失敗しました');
        }
        return $fp;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readIndexFromFp($fp): array {
        rewind($fp);
        $raw = stream_get_contents($fp);
        if ($raw === false || trim($raw) === '') return [];
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    /**
     * @param array<int, array<string, mixed>> $index
     */
    private function writeIndexToFp($fp, array $index): void {
        $payload = json_encode(array_values($index), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($payload === false) {
            throw new \RuntimeException('index の JSON 化に失敗しました');
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $payload);
        fflush($fp);
    }
}

