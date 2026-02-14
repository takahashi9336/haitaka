-- ============================================
-- リリース版別情報（hn_release_editions）の追加
-- 設計: docs/design_リリース版別ジャケット画像.md
-- ============================================

-- 1. 版ごとの情報管理テーブル
CREATE TABLE IF NOT EXISTS `hn_release_editions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `release_id` int(11) NOT NULL COMMENT 'リリースID',
  `edition` enum('type_a','type_b','type_c','type_d','normal') NOT NULL COMMENT '版（初回限定TYPE-A～D、通常版）',
  `jacket_image_url` varchar(255) DEFAULT NULL COMMENT '当該版のジャケット画像URL',
  `sort_order` tinyint(4) DEFAULT 0 COMMENT '表示順',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `release_edition` (`release_id`,`edition`),
  KEY `release_id` (`release_id`),
  CONSTRAINT `hn_release_editions_ibfk_1` FOREIGN KEY (`release_id`) REFERENCES `hn_releases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='リリース版別情報（1行=1リリースの1版、ジャケット画像はその1要素）';

-- 2. hn_releases から jacket_image_url を削除（現状データなしのため DROP のみ）
ALTER TABLE `hn_releases` DROP COLUMN `jacket_image_url`;

-- ============================================
-- DML 例（アプリの save で登録する想定。確認用）
-- ============================================
-- INSERT INTO hn_release_editions (release_id, edition, jacket_image_url, sort_order) VALUES
-- (1, 'type_a', 'https://example.com/jacket-a.jpg', 0),
-- (1, 'type_b', 'https://example.com/jacket-b.jpg', 1),
-- (1, 'normal', 'https://example.com/jacket-normal.jpg', 2);
