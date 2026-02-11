<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * メンバー情報管理モデル (DB定義完全同期版)
 * 物理パス: haitaka/private/apps/Hinata/Model/MemberModel.php
 */
class MemberModel extends BaseModel {
    protected string $table = 'hn_members';
    
    // 画像(image_1d913a.jpg)の定義と完全に一致させました
    protected array $fields = [
        'id', 'name', 'kana', 'generation', 'birth_date', 'blood_type', 
        'height', 'birth_place', 'color_id1', 'color_id2', 'is_active', 
        'image_url', 'blog_url', 'insta_url', 'pv_movie_id', 'twitter_url', 'member_info'
    ];

    /**
     * 共通マスタのためユーザー隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * メンバー詳細情報の取得
     */
    public function getMemberDetail(int $memberId): ?array {
        $sql = "SELECT m.*,
                       c1.color_code as color1, c1.color_name as color1_name,
                       c2.color_code as color2, c2.color_name as color2_name,
                       v.video_key as pv_video_key, v.title as pv_title
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                LEFT JOIN com_youtube_embed_data v ON m.pv_movie_id = v.id
                WHERE m.id = :mid";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * 現役メンバー一覧の取得
     */
    public function getActiveMembersWithColors(): array {
        $sql = "SELECT m.*, c1.color_code as color1, c2.color_code as color2
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE m.is_active = 1
                ORDER BY m.generation ASC, m.kana ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * 管理用：全メンバー取得
     */
    public function getAllWithColors(): array {
        $sql = "SELECT m.*, c1.color_code as color1, c2.color_code as color2
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                ORDER BY m.is_active DESC, m.generation ASC, m.kana ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getColorMaster(): array {
        return $this->pdo->query("SELECT * FROM hn_colors ORDER BY id ASC")->fetchAll();
    }
}