-- 暗号化対象のvarcharカラムをTEXTに変更
-- 暗号化後の文字列はbase64(iv+tag+ciphertext)となり、元の文字列より長くなるため

ALTER TABLE `hn_meetgreet_reports`
  MODIFY COLUMN `my_nickname` TEXT DEFAULT NULL COMMENT 'レポ内での自分の表示名（暗号化）';
