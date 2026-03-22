-- メンバー個人活動管理テーブル
-- ラジオ番組、ポッドキャスト、ドラマ出演等のメンバー個別活動を登録

CREATE TABLE IF NOT EXISTS `hn_member_activities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL COMMENT 'hn_members.id',
  `category` varchar(50) NOT NULL DEFAULT 'other' COMMENT 'カテゴリ(radio/podcast/drama/magazine/youtube_personal/cm/stage/other)',
  `title` varchar(200) NOT NULL COMMENT '活動名',
  `description` text DEFAULT NULL COMMENT '概要・説明文',
  `url` varchar(500) DEFAULT NULL COMMENT '誘導先URL',
  `url_label` varchar(100) DEFAULT NULL COMMENT 'リンクボタンラベル',
  `image_url` varchar(500) DEFAULT NULL COMMENT 'サムネイル画像パス',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=表示 0=非表示',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT '表示順',
  `start_date` date DEFAULT NULL COMMENT '活動開始日',
  `end_date` date DEFAULT NULL COMMENT '活動終了日',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_active` (`member_id`, `is_active`, `sort_order`),
  CONSTRAINT `fk_member_activities_member` FOREIGN KEY (`member_id`) REFERENCES `hn_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='メンバー個人活動';
