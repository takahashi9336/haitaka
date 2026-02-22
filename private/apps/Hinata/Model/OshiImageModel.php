<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * 推し画像（マイフォト）管理モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/OshiImageModel.php
 */
class OshiImageModel extends BaseModel {
    protected string $table = 'hn_oshi_images';
    protected array $fields = ['id', 'user_id', 'member_id', 'image_path', 'caption', 'sort_order', 'created_at'];
    protected bool $isUserIsolated = true;

    public const MAX_IMAGES_PER_MEMBER = 10;
    public const MAX_DIMENSION = 1200;
    public const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    public const UPLOAD_BASE = 'uploads/oshi';

    /**
     * メンバーの画像一覧を取得
     */
    public function getByMember(int $memberId): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = :uid AND member_id = :mid
                ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * メンバーの画像数を取得
     */
    public function countByMember(int $memberId): int {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :uid AND member_id = :mid"
        );
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * 画像を保存
     */
    public function saveImage(int $memberId, string $imagePath, ?string $caption = null): int {
        $order = $this->countByMember($memberId);
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (user_id, member_id, image_path, caption, sort_order, created_at)
             VALUES (:uid, :mid, :path, :caption, :sort, NOW())"
        );
        $stmt->execute([
            'uid' => $this->userId,
            'mid' => $memberId,
            'path' => $imagePath,
            'caption' => $caption,
            'sort' => $order,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 画像を削除（ファイルも削除）
     */
    public function deleteImage(int $imageId): bool {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute(['id' => $imageId, 'uid' => $this->userId]);
        $image = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$image) return false;

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? (dirname(__DIR__, 4) . '/www');
        $filePath = $docRoot . '/' . $image['image_path'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?")
            ->execute([$imageId]);
        return true;
    }

    /**
     * アップロードディレクトリのパスを取得（なければ作成）
     */
    public function getUploadDir(int $memberId): string {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? (dirname(__DIR__, 4) . '/www');
        $relPath = self::UPLOAD_BASE . '/' . $this->userId . '/' . $memberId;
        $absPath = $docRoot . '/' . $relPath;
        if (!is_dir($absPath)) {
            @mkdir($absPath, 0755, true);
        }
        return $relPath;
    }

    /**
     * 画像をリサイズ（長辺がMAX_DIMENSIONを超える場合のみ）
     */
    public static function resizeImage(string $sourcePath, string $destPath, string $mimeType): bool {
        $image = null;
        switch ($mimeType) {
            case 'image/jpeg': $image = @imagecreatefromjpeg($sourcePath); break;
            case 'image/png':  $image = @imagecreatefrompng($sourcePath); break;
            case 'image/webp': $image = @imagecreatefromwebp($sourcePath); break;
        }
        if (!$image) return false;

        $w = imagesx($image);
        $h = imagesy($image);
        $max = self::MAX_DIMENSION;

        if ($w > $max || $h > $max) {
            if ($w >= $h) {
                $newW = $max;
                $newH = (int)round($h * ($max / $w));
            } else {
                $newH = $max;
                $newW = (int)round($w * ($max / $h));
            }
            $resized = imagecreatetruecolor($newW, $newH);
            if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($image);
            $image = $resized;
        }

        $result = false;
        switch ($mimeType) {
            case 'image/jpeg': $result = imagejpeg($image, $destPath, 85); break;
            case 'image/png':  $result = imagepng($image, $destPath, 6); break;
            case 'image/webp': $result = imagewebp($image, $destPath, 85); break;
        }
        imagedestroy($image);
        return $result;
    }
}
