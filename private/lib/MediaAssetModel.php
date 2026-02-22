<?php

namespace Core;

/**
 * 汎用メディアアセット管理モデル
 * - com_media_assets / hn_media_metadata をまとめて扱う
 * - 将来の TikTok / Instagram 拡張もここに集約する想定
 *
 * 物理パス: haitaka/private/lib/MediaAssetModel.php
 */
class MediaAssetModel extends BaseModel {
    protected string $table = 'com_media_assets';
    protected array $fields = [
        'id', 'platform', 'media_key', 'sub_key', 'media_type',
        'title', 'thumbnail_url', 'description',
        'upload_date', 'created_at'
    ];

    /**
     * メディア管理は共通テーブルのため、ユーザー隔離を無効化する
     */
    protected bool $isUserIsolated = false;

    /**
     * メディアURLを解析し、プラットフォームとキー情報を返す
     *
     * 戻り値: ['platform' => string, 'media_key' => string, 'sub_key' => ?string] もしくは null
     */
    public function parseUrl(string $url): ?array {
        $url = trim($url);
        if ($url === '') return null;

        // YouTube
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
            return [
                'platform'  => 'youtube',
                'media_key' => $match[1],
                'sub_key'   => null,
            ];
        }

        // TikTok: https://www.tiktok.com/@username/video/1234567890
        //         https://vm.tiktok.com/XXXXXXXX/
        if (preg_match('%tiktok\.com/@([^/]+)/video/(\d+)%i', $url, $match)) {
            return [
                'platform'  => 'tiktok',
                'media_key' => $match[2],
                'sub_key'   => '@' . $match[1],
            ];
        }
        if (preg_match('%(?:vm|vt)\.tiktok\.com/([A-Za-z0-9_-]+)%i', $url, $match)) {
            return [
                'platform'  => 'tiktok',
                'media_key' => $match[1],
                'sub_key'   => null,
            ];
        }

        // Instagram: https://www.instagram.com/reel/XXXXXXXXXXX/
        //            https://www.instagram.com/p/XXXXXXXXXXX/
        //            https://www.instagram.com/username/reel/XXXXXXXXXXX/
        if (preg_match('%instagram\.com/(?:[A-Za-z0-9_.]+/)?(?:reel|p|reels)/([A-Za-z0-9_-]+)%i', $url, $match)) {
            return [
                'platform'  => 'instagram',
                'media_key' => $match[1],
                'sub_key'   => null,
            ];
        }

        return null;
    }

    /**
     * platform + media_key (+sub_key) で com_media_assets を取得 or 作成し、asset_id を返す
     */
    public function findOrCreateAsset(
        string $platform,
        string $mediaKey,
        ?string $subKey,
        string $title,
        ?string $thumbnailUrl = null,
        ?string $uploadDate = null,
        ?string $description = null,
        ?string $mediaType = null
    ): ?int {
        $platform = strtolower($platform);

        $sql = "SELECT id FROM com_media_assets WHERE platform = :platform AND media_key = :media_key";
        $params = ['platform' => $platform, 'media_key' => $mediaKey];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $assetId = $stmt->fetchColumn();

        if ($assetId) {
            $updates = [];
            $updateParams = ['id' => $assetId];
            if ($title !== '') {
                $updates[] = 'title = :title';
                $updateParams['title'] = $title;
            }
            if ($thumbnailUrl !== null && $thumbnailUrl !== '') {
                $updates[] = 'thumbnail_url = :thumbnail_url';
                $updateParams['thumbnail_url'] = $thumbnailUrl;
            }
            if ($description !== null && $description !== '') {
                $updates[] = 'description = :description';
                $updateParams['description'] = $description;
            }
            if ($uploadDate !== null) {
                $updates[] = 'upload_date = :upload_date';
                $updateParams['upload_date'] = $uploadDate;
            }
            if ($mediaType !== null) {
                $updates[] = 'media_type = :media_type';
                $updateParams['media_type'] = $mediaType;
            }
            if ($subKey !== null) {
                $updates[] = 'sub_key = :sub_key';
                $updateParams['sub_key'] = $subKey;
            }
            if (!empty($updates)) {
                $sql = "UPDATE com_media_assets SET " . implode(', ', $updates) . " WHERE id = :id";
                $this->pdo->prepare($sql)->execute($updateParams);
            }
            return (int)$assetId;
        }

        $data = [
            'platform'      => $platform,
            'media_key'     => $mediaKey,
            'sub_key'       => $subKey,
            'title'         => $title,
            'thumbnail_url' => $thumbnailUrl,
            'description'   => $description,
            'upload_date'   => $uploadDate ?: date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        if ($mediaType) {
            $data['media_type'] = $mediaType;
        }
        $this->create($data);

        $newId = $this->lastInsertId();
        return $newId ? (int)$newId : null;
    }

    /**
     * asset_id で hn_media_metadata を取得 or 作成し、meta_id を返す
     * 
     * ※ asset_id は UNIQUE KEY のため、1動画に1メタデータが原則
     * カテゴリは後から更新可能
     */
    public function findOrCreateMetadata(int $assetId, ?string $category): ?int {
        $sql = "SELECT id FROM hn_media_metadata WHERE asset_id = :asset_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['asset_id' => $assetId]);
        $metaId = $stmt->fetchColumn();

        if ($metaId) {
            // 既存のメタデータが存在する場合、カテゴリを更新（必要に応じて）
            return (int)$metaId;
        }

        // 新規作成（release_date は今後利用しないため登録しない）
        $stmt = $this->pdo->prepare("
            INSERT INTO hn_media_metadata (asset_id, category)
            VALUES (:asset_id, :category)
        ");
        $stmt->execute([
            'asset_id' => $assetId,
            'category' => $category,
        ]);

        $newId = $this->lastInsertId();
        return $newId ? (int)$newId : null;
    }

    /**
     * メタデータIDでカテゴリを更新（null の場合は未設定に）
     */
    public function updateMetadataCategory(int $metaId, ?string $category): bool {
        $stmt = $this->pdo->prepare("
            UPDATE hn_media_metadata 
            SET category = :category 
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $metaId, 'category' => $category]);
    }

    /**
     * asset_id で com_media_assets.upload_date を更新
     */
    public function updateAssetUploadDate(int $assetId, ?string $uploadDate): bool {
        $stmt = $this->pdo->prepare("
            UPDATE com_media_assets
            SET upload_date = :upload_date
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $assetId, 'upload_date' => $uploadDate]);
    }
}

