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
        'id', 'platform', 'media_key', 'sub_key',
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

        // TODO: TikTok / Instagram は今後の拡張で対応

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
        ?string $uploadDate = null
    ): ?int {
        $platform = strtolower($platform);

        $sql = "SELECT id FROM com_media_assets WHERE platform = :platform AND media_key = :media_key";
        $params = ['platform' => $platform, 'media_key' => $mediaKey];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $assetId = $stmt->fetchColumn();

        if ($assetId) {
            return (int)$assetId;
        }

        // thumbnail_url は任意。YouTube は media_key から表示時に生成するため、DBには無理に保存しない。
        $this->create([
            'platform'      => $platform,
            'media_key'     => $mediaKey,
            'sub_key'       => $subKey,
            'title'         => $title,
            'thumbnail_url' => $thumbnailUrl,
            'upload_date'   => $uploadDate ?: date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $newId = $this->lastInsertId();
        return $newId ? (int)$newId : null;
    }

    /**
     * asset_id で hn_media_metadata を取得 or 作成し、meta_id を返す
     * 
     * ※ asset_id は UNIQUE KEY のため、1動画に1メタデータが原則
     * カテゴリは後から更新可能
     */
    public function findOrCreateMetadata(int $assetId, string $category): ?int {
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
     * メタデータIDでカテゴリを更新
     */
    public function updateMetadataCategory(int $metaId, string $category): bool {
        $stmt = $this->pdo->prepare("
            UPDATE hn_media_metadata 
            SET category = :category 
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $metaId, 'category' => $category]);
    }
}

