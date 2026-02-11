<?php

namespace Core;

/**
 * YouTube動画管理モデル
 * 物理パス: haitaka/private/lib/MovieModel.php
 */
class MovieModel extends BaseModel {
    protected string $table = 'com_youtube_embed_data';
    protected array $fields = ['id', 'video_key', 'title', 'category_tag', 'thumbnail_url', 'created_at'];

    /**
     * 動画管理は共通テーブルのため、ユーザー隔離を無効化する (user_idエラー防止)
     */
    protected bool $isUserIsolated = false;

    public function parseAndSave(string $url, string $title, string $tag = 'General'): bool {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        $key = $match[1] ?? null;
        if (!$key) return false;

        $stmt = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE video_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetch()) return true;

        return $this->create([
            'video_key' => $key,
            'title' => $title,
            'category_tag' => $tag,
            'thumbnail_url' => "https://img.youtube.com/vi/{$key}/mqdefault.jpg",
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}