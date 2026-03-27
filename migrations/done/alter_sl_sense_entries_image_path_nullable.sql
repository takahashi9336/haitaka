-- Sense Lab: 画像を任意にする（既存テーブル用）
-- create_sl_sense_lab.sql を既に実行済みで image_path が NOT NULL の場合に実行

ALTER TABLE sl_sense_entries
  MODIFY COLUMN image_path VARCHAR(500) NULL COMMENT '画像の相対パス（任意）';
