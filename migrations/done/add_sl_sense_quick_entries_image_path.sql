-- Sense Lab: クイックスクラップに画像パスを追加

ALTER TABLE sl_sense_quick_entries
    ADD COLUMN image_path VARCHAR(500) NULL COMMENT '画像の相対パス（任意）' AFTER note;

