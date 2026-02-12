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
        'title', 'thumbnail_url', 'created_at'
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
    public function findOrCreateAsset(string $platform, string $mediaKey, ?string $subKey, string $title, ?string $thumbnailUrl = null): ?int {
        $platform = strtolower($platform);

        $sql = "SELECT id FROM com_media_assets WHERE platform = :platform AND media_key = :media_key";
        $params = ['platform' => $platform, 'media_key' => $mediaKey];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $assetId = $stmt->fetchColumn();

        if ($assetId) {
            return (int)$assetId;
        }

        // サムネイルが未指定の場合、YouTubeのみデフォルトを補完
        if ($thumbnailUrl === null && $platform === 'youtube') {
            $thumbnailUrl = "https://img.youtube.com/vi/{$mediaKey}/mqdefault.jpg";
        }

        $this->create([
            'platform'      => $platform,
            'media_key'     => $mediaKey,
            'sub_key'       => $subKey,
            'title'         => $title,
            'thumbnail_url' => $thumbnailUrl,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $newId = $this->lastInsertId();
        return $newId ? (int)$newId : null;
    }

    /**
     * asset_id + category で hn_media_metadata を取得 or 作成し、meta_id を返す
     */
    public function findOrCreateMetadata(int $assetId, string $category, ?string $releaseDate = null): ?int {
        $sql = "SELECT id FROM hn_media_metadata WHERE asset_id = :asset_id AND category = :category";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['asset_id' => $assetId, 'category' => $category]);
        $metaId = $stmt->fetchColumn();

        if ($metaId) {
            return (int)$metaId;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO hn_media_metadata (asset_id, category, release_date)
            VALUES (:asset_id, :category, :release_date)
        ");
        $stmt->execute([
            'asset_id'     => $assetId,
            'category'     => $category,
            'release_date' => $releaseDate,
        ]);

        $newId = $this->lastInsertId();
        return $newId ? (int)$newId : null;
    }
}

