-- ============================================
-- メンバー複数画像テーブル (hn_member_images)
-- 1メンバーあたり最大5枚の画像を登録可能
-- ============================================

CREATE TABLE IF NOT EXISTS `hn_member_images` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT 'hn_members.id',
  `image_url` varchar(255) NOT NULL COMMENT '画像ファイル名',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  FOREIGN KEY (`member_id`) REFERENCES `hn_members`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='メンバー画像（複数可・最大5枚）';

-- 既存の image_url を hn_member_images に移行
INSERT INTO `hn_member_images` (`member_id`, `image_url`, `sort_order`)
SELECT `id`, `image_url`, 0 FROM `hn_members` WHERE `image_url` IS NOT NULL AND `image_url` != '';
