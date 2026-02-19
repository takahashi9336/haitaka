-- ============================================
-- 動画カテゴリマスタ (hn_media_categories)
-- カテゴリの新規追加・名称変更を画面から行うため
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_media_categories` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT 'カテゴリ名（hn_media_metadata.category に格納する値）',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='動画カテゴリマスタ';

-- 既存カテゴリの初期投入
INSERT IGNORE INTO `hn_media_categories` (`name`, `sort_order`) VALUES
('CM', 1),
('Hinareha', 2),
('Live', 3),
('MV', 4),
('SelfIntro', 5),
('SoloPV', 6),
('Special', 7),
('Teaser', 8),
('Trailer', 9),
('Variety', 10);
