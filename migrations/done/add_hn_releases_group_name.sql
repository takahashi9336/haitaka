-- ひらがなけやき楽曲登録: hn_releases にグループ識別子を追加
-- 実行後: 既存行はすべて hinatazaka46（日向坂46改名後リリース）

ALTER TABLE `hn_releases`
  ADD COLUMN `group_name` VARCHAR(30) NOT NULL DEFAULT 'hinatazaka46'
  COMMENT 'hinatazaka46=日向坂46 / hiragana_keyaki=けやき坂46'
  AFTER `release_type`;
