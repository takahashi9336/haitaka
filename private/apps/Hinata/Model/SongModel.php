<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 楽曲管理モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/SongModel.php
 */
class SongModel extends BaseModel {
    protected string $table = 'hn_songs';
    protected array $fields = [
        'id', 'release_id', 'media_meta_id', 'title', 'title_kana',
        'track_type', 'track_number',
        'lyricist', 'composer', 'duration', 'memo', 'created_at'
    ];

    /**
     * 楽曲は全ユーザー共通データのため、隔離を無効化
     */
    protected bool $isUserIsolated = false;

    /**
     * 楽曲詳細と参加メンバーを取得
     */
    public function getSongWithMembers(int $songId): ?array {
        // 楽曲情報
        $song = $this->find($songId);
        if (!$song) {
            return null;
        }

        // 参加メンバーを取得
        $sql = "SELECT sm.*, m.name, m.image_url, 
                       c1.color_code as color1, c2.color_code as color2
                FROM hn_song_members sm
                JOIN hn_members m ON sm.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE sm.song_id = :sid
                ORDER BY 
                    FIELD(sm.role, 'center', 'fukujin', 'under', 'other'),
                    sm.position ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        $song['members'] = $stmt->fetchAll();

        return $song;
    }

    /**
     * メンバーが主役級で参加している楽曲を取得
     */
    public function getFeaturedSongsByMember(int $memberId, int $limit = 5): array {
        $sql = "SELECT 
                    r.title as release_title,
                    r.release_date,
                    s.title as song_title,
                    sm.role,
                    sm.position,
                    ma.media_key,
                    ma.thumbnail_url
                FROM hn_song_members sm
                JOIN hn_songs s ON sm.song_id = s.id
                JOIN hn_releases r ON s.release_id = r.id
                LEFT JOIN hn_media_metadata hmeta ON s.media_meta_id = hmeta.id
                LEFT JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                WHERE sm.member_id = :mid
                  AND sm.is_featured = 1
                  AND s.media_meta_id IS NOT NULL
                ORDER BY r.release_date DESC
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':mid', $memberId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * フォーメーション別にメンバーを取得
     * 
     * @param int $songId 楽曲ID
     * @return array ['row_1' => [...], 'row_2' => [...], 'row_3' => [...], 'other' => [...]]
     */
    public function getFormation(int $songId): array {
        $sql = "SELECT 
                    sm.row_number,
                    sm.position,
                    sm.role,
                    sm.is_featured,
                    m.id as member_id,
                    m.name,
                    m.image_url,
                    c1.color_code as color1,
                    c2.color_code as color2
                FROM hn_song_members sm
                JOIN hn_members m ON sm.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE sm.song_id = :sid
                ORDER BY sm.row_number ASC, sm.position ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        $members = $stmt->fetchAll();

        // 列ごとにグループ化
        $formation = [
            'row_1' => [],  // フロント
            'row_2' => [],  // 2列目
            'row_3' => [],  // 3列目
            'other' => [],  // その他（アンダー等）
        ];

        foreach ($members as $member) {
            $rowNum = $member['row_number'];
            if ($rowNum === null || $rowNum === 0) {
                $formation['other'][] = $member;
            } else {
                $key = 'row_' . $rowNum;
                if (isset($formation[$key])) {
                    $formation[$key][] = $member;
                } else {
                    $formation['other'][] = $member;
                }
            }
        }

        return $formation;
    }

    /**
     * センターメンバーを取得
     * 
     * @param int $songId 楽曲ID
     * @return array センターメンバーのリスト（ダブルセンター対応）
     */
    public function getCenterMembers(int $songId): array {
        $sql = "SELECT 
                    m.id,
                    m.name,
                    m.image_url,
                    sm.position
                FROM hn_song_members sm
                JOIN hn_members m ON sm.member_id = m.id
                WHERE sm.song_id = :sid
                  AND sm.role = 'center'
                ORDER BY sm.position ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        return $stmt->fetchAll();
    }

    /**
     * 楽曲種別の定数
     */
    public const TRACK_TYPES = [
        'title' => '表題曲',
        'coupling' => 'カップリング',
        'album_only' => 'アルバム収録曲',
        'bonus' => 'ボーナストラック',
        'kisei' => '期別曲',
        'unit' => 'ユニット曲',
        'solo' => 'ソロ曲',
        'other' => 'その他',
    ];
}
