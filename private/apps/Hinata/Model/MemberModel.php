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
                       (
                           SELECT ma.media_key
                           FROM hn_media_members hmm
                           JOIN hn_media_metadata hmeta ON hmeta.id = hmm.media_meta_id
                           JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                           WHERE hmm.member_id = m.id
                             AND hmeta.category = 'member_kojin_pv'
                             AND ma.platform = 'youtube'
                           LIMIT 1
                       ) as pv_video_key,
                       (
                           SELECT ma.title
                           FROM hn_media_members hmm
                           JOIN hn_media_metadata hmeta ON hmeta.id = hmm.media_meta_id
                           JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                           WHERE hmm.member_id = m.id
                             AND hmeta.category = 'member_kojin_pv'
                             AND ma.platform = 'youtube'
                           LIMIT 1
                       ) as pv_title,
                       (
                           SELECT COALESCE(MAX(level), 0)
                           FROM hn_favorites f
                           WHERE f.member_id = m.id AND f.user_id = :uid_fav
                       ) as favorite_level
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE m.id = :mid";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mid' => $memberId, 'uid_fav' => $this->userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * メンバー帳用：現役＋卒業メンバー一覧（カラー・PVキー付き）
     */
    public function getMembersForBook(): array {
        $sql = "SELECT m.*,
                       c1.color_code as color1, c1.color_name as color1_name,
                       c2.color_code as color2, c2.color_name as color2_name,
                       (
                           SELECT ma.media_key
                           FROM hn_media_members hmm
                           JOIN hn_media_metadata hmeta ON hmeta.id = hmm.media_meta_id
                           JOIN com_media_assets ma ON ma.id = hmeta.asset_id
                           WHERE hmm.member_id = m.id
                             AND hmeta.category = 'member_kojin_pv'
                             AND ma.platform = 'youtube'
                           LIMIT 1
                       ) as pv_video_key
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                ORDER BY m.is_active DESC, m.generation ASC, m.kana ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * ミーグリネタ帳用：現役メンバー一覧（カラー付き）
     */
    public function getActiveMembersWithColors(): array {
        $sql = "SELECT m.*,
                       c1.color_code as color1, c1.color_name as color1_name,
                       c2.color_code as color2, c2.color_name as color2_name,
                       COALESCE(f.level, 0) as favorite_level
                FROM {$this->table} m
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                LEFT JOIN hn_favorites f ON f.member_id = m.id AND f.user_id = :uid_fav
                WHERE m.is_active = 1
                ORDER BY favorite_level DESC, m.generation ASC, m.kana ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid_fav' => $this->userId]);
        return $stmt->fetchAll();
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