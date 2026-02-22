-- 既存データの media_type を一括設定
-- YouTube動画 → 'video', TikTok/Instagram → 'short'

UPDATE com_media_assets SET media_type = 'video' WHERE platform = 'youtube' AND (media_type IS NULL OR media_type = '');
UPDATE com_media_assets SET media_type = 'short' WHERE platform = 'tiktok' AND (media_type IS NULL OR media_type = '');
UPDATE com_media_assets SET media_type = 'short' WHERE platform = 'instagram' AND (media_type IS NULL OR media_type = '');
