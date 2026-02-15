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
        'lyricist', 'composer', 'arranger', 'mv_director', 'choreographer',
        'duration', 'memo', 'created_at'
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

        // 参加メンバーを取得（期別一覧表示用に generation を含む）
        $sql = "SELECT sm.*, m.name, m.image_url, m.generation,
                       c1.color_code as color1, c2.color_code as color2
                FROM hn_song_members sm
                JOIN hn_members m ON sm.member_id = m.id
                LEFT JOIN hn_colors c1 ON m.color_id1 = c1.id
                LEFT JOIN hn_colors c2 ON m.color_id2 = c2.id
                WHERE sm.song_id = :sid
                ORDER BY sm.`row_number` ASC, sm.`position` ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        $song['members'] = $stmt->fetchAll();

        return $song;
    }

    /**
     * メンバーがセンターで参加している楽曲を取得
     */
    public function getFeaturedSongsByMember(int $memberId, int $limit = 5): array {
        $sql = "SELECT 
                    r.title as release_title,
                    r.release_date,
                    s.title as song_title,
                    sm.is_center,
                    sm.`position`,
                    ma.media_key,
                    ma.thumbnail_url
                FROM hn_song_members sm
                JOIN hn_songs s ON sm.song_id = s.id
                JOIN hn_releases r ON s.release_id = r.id
                LEFT JOIN hn_media_metadata hmeta ON s.media_meta_id = hmeta.id
                LEFT JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                WHERE sm.member_id = :mid
                  AND sm.is_center = 1
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
                    sm.`row_number`,
                    sm.`position`,
                    sm.is_center,
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
                ORDER BY sm.`row_number` ASC, sm.`position` ASC";
        
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
     * リリースの表題曲（track_type='title'）のセンター名一覧を取得
     * @return array メンバー名の配列（いない場合は空）
     */
    public function getTitleTrackCenterNames(int $releaseId): array {
        $sql = "SELECT m.name
                FROM hn_songs s
                JOIN hn_song_members sm ON sm.song_id = s.id AND sm.is_center = 1
                JOIN hn_members m ON sm.member_id = m.id
                WHERE s.release_id = :rid AND s.track_type = 'title'
                ORDER BY sm.`position` ASC
                LIMIT 10";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        return array_column($stmt->fetchAll(), 'name');
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
                    sm.`position`
                FROM hn_song_members sm
                JOIN hn_members m ON sm.member_id = m.id
                WHERE sm.song_id = :sid
                  AND sm.is_center = 1
                ORDER BY sm.`position` ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['sid' => $songId]);
        return $stmt->fetchAll();
    }

    /**
     * media_meta_id から YouTube 埋め込みURLを取得（無ければ null）
     */
    public function getYoutubeEmbedUrlByMediaMetaId(?int $mediaMetaId): ?string {
        if ($mediaMetaId === null || $mediaMetaId === 0) {
            return null;
        }
        $sql = "SELECT ma.media_key FROM hn_media_metadata hmeta
                JOIN com_media_assets ma ON hmeta.asset_id = ma.id
                WHERE hmeta.id = :mid AND ma.platform = 'youtube' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['mid' => $mediaMetaId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['media_key'])) {
            return 'https://www.youtube.com/embed/' . $row['media_key'];
        }
        return null;
    }

    /**
     * 全曲一覧用：リリース情報付きで全楽曲を取得（発売日降順・トラック順）
     * @param int|null $releaseId 指定時はそのリリースのみ
     * @return array
     */
    public function getAllSongsWithRelease(?int $releaseId = null): array {
        $sql = "SELECT s.id, s.release_id, s.title, s.title_kana, s.track_type, s.track_number,
                       r.title as release_title, r.release_type, r.release_date, r.release_number
                FROM hn_songs s
                JOIN hn_releases r ON s.release_id = r.id";
        $params = [];
        if ($releaseId !== null) {
            $sql .= " WHERE s.release_id = :rid";
            $params['rid'] = $releaseId;
        }
        $sql .= " ORDER BY r.release_date IS NULL, r.release_date DESC, s.track_number ASC, s.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * 指定リリースの楽曲一覧を取得（参加メンバー名を「、」区切りで付与）
     * @return array 各要素に member_names が付く
     */
    public function getSongsWithMembersByRelease(int $releaseId): array {
        $sql = "SELECT s.id, s.release_id, s.title, s.title_kana, s.track_type, s.track_number,
                       r.title as release_title, r.release_type, r.release_date, r.release_number,
                       GROUP_CONCAT(m.name ORDER BY sm.`row_number` ASC, sm.`position` ASC SEPARATOR '、') AS member_names
                FROM hn_songs s
                JOIN hn_releases r ON s.release_id = r.id
                LEFT JOIN hn_song_members sm ON sm.song_id = s.id
                LEFT JOIN hn_members m ON m.id = sm.member_id
                WHERE s.release_id = :rid
                GROUP BY s.id, s.release_id, s.title, s.title_kana, s.track_type, s.track_number,
                         r.title, r.release_type, r.release_date, r.release_number
                ORDER BY s.track_number ASC, s.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['rid' => $releaseId]);
        return $stmt->fetchAll();
    }

    /**
     * 楽曲種別の定数（管理画面等で使用）
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

    /** DB enum に合わせた表示用ラベル（楽曲ページ表示用） */
    public const TRACK_TYPES_DISPLAY = [
        'title'   => '表題曲',
        'read'    => '読み曲',
        'sub'     => 'カップリング',
        'type_a'  => 'TYPE-A',
        'type_b'  => 'TYPE-B',
        'type_c'  => 'TYPE-C',
        'type_d'  => 'TYPE-D',
        'normal'  => '通常',
        'other'   => 'その他',
    ];

    /** フォーメーション種別の表示用ラベル */
    public const FORMATION_TYPES_DISPLAY = [
        'all'      => '全員',
        'kibetsu'  => '期別',
        'senbatsu' => '選抜',
        'solo'     => 'ソロ',
        'under'    => 'アンダー',
        'unit'     => 'ユニット',
        'other'    => 'その他',
    ];
}
