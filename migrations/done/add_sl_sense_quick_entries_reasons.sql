-- Sense Lab: クイックスクラップに理由1〜3を追加

ALTER TABLE sl_sense_quick_entries
    ADD COLUMN reason_1 TEXT NULL COMMENT 'なぜ良いと思ったか 1つ目（任意）' AFTER image_path,
    ADD COLUMN reason_2 TEXT NULL COMMENT 'なぜ良いと思ったか 2つ目（任意）' AFTER reason_1,
    ADD COLUMN reason_3 TEXT NULL COMMENT 'なぜ良いと思ったか 3つ目（任意）' AFTER reason_2;

