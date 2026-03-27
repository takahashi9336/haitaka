-- Sense Lab: 画像パスを /upload/sense_lab → /uploads/sense_lab に統一
-- ※ 実行前に www/upload/sense_lab/ 内のファイルを www/uploads/sense_lab/ へ移動してください

UPDATE sl_sense_entries
SET image_path = REPLACE(image_path, '/upload/sense_lab/', '/uploads/sense_lab/')
WHERE image_path LIKE '/upload/sense_lab/%';
