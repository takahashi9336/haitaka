<?php

namespace App\Hinata\Model;

use Core\BaseModel;

class MeetGreetReportAvatarModel extends BaseModel {
    protected string $table = 'hn_meetgreet_report_avatars';
    protected array $fields = ['id', 'user_id', 'member_id', 'image_path', 'created_at', 'updated_at'];
    protected bool $isUserIsolated = true;

    public const MAX_DIMENSION = 400;
    public const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    public const UPLOAD_BASE = 'uploads/mg_avatar';

    /**
     * メンバーIDからアバター画像パスを取得
     */
    public function getByMemberId(int $memberId): ?string {
        $sql = "SELECT image_path FROM {$this->table}
                WHERE user_id = :uid AND member_id = :mid LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['image_path'] : null;
    }

    /**
     * アバター画像を保存（UPSERT）
     */
    public function saveAvatar(int $memberId, string $imagePath): void {
        $sql = "INSERT INTO {$this->table} (user_id, member_id, image_path, created_at)
                VALUES (:uid, :mid, :path, NOW())
                ON DUPLICATE KEY UPDATE image_path = VALUES(image_path), updated_at = NOW()";
        $this->pdo->prepare($sql)->execute([
            'uid'  => $this->userId,
            'mid'  => $memberId,
            'path' => $imagePath,
        ]);
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
     * 画像をリサイズ（OshiImageModelと同じロジック、小さめサイズ）
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

    /**
     * メンバーのアバター画像を優先順位で解決
     * 1. レポ用アバター (hn_meetgreet_report_avatars)
     * 2. 推し設定画像 (hn_user_member_profiles)
     * 3. メンバーマスタ画像 (hn_members.image_url)
     * 4. デフォルト画像 (/assets/img/members/member_{id}.jpg)
     */
    public function resolveAvatar(int $memberId, ?array $memberDetail = null): ?string {
        $path = $this->getByMemberId($memberId);
        if ($path) return '/' . $path;

        $stmt = $this->pdo->prepare(
            "SELECT image_path FROM hn_user_member_profiles
             WHERE user_id = :uid AND member_id = :mid LIMIT 1"
        );
        $stmt->execute(['uid' => $this->userId, 'mid' => $memberId]);
        $profile = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($profile && $profile['image_path']) return '/' . $profile['image_path'];

        if (!empty($memberDetail['image_url'])) {
            $url = $memberDetail['image_url'];
            return str_starts_with($url, '/') ? $url : '/assets/img/members/' . $url;
        }

        return '/assets/img/members/member_' . $memberId . '.jpg';
    }
}
