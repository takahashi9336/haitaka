-- 日向坂ポータル：トピック・お知らせ・応募締め切り
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

-- 1. トピック（TOPICS）テーブル
CREATE TABLE IF NOT EXISTS `hn_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'タイトル',
  `summary` text DEFAULT NULL COMMENT '概要',
  `url` varchar(500) DEFAULT NULL COMMENT 'リンクURL',
  `image_url` varchar(500) DEFAULT NULL COMMENT '画像URL（任意、未登録時は通常ボックス）',
  `topic_type` varchar(50) NOT NULL DEFAULT 'other' COMMENT 'big_event/goods/news/other',
  `start_date` date DEFAULT NULL COMMENT '表示開始日',
  `end_date` date DEFAULT NULL COMMENT '表示終了日',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT '並び順',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=有効 0=無効',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_dates` (`is_active`, `start_date`, `end_date`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ポータルトピック（ひな誕祭・ひなたフェス等）';

-- 2. お知らせ（アナウンス）テーブル
CREATE TABLE IF NOT EXISTS `hn_announcements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'タイトル',
  `body` text DEFAULT NULL COMMENT '本文',
  `url` varchar(500) DEFAULT NULL COMMENT 'リンクURL',
  `image_url` varchar(500) DEFAULT NULL COMMENT '画像URL（任意、未登録時は通常ボックス）',
  `announcement_type` varchar(50) NOT NULL DEFAULT 'other' COMMENT 'goods/application_deadline/big_event/media/release/ticket/fanclub/meetgreet/audition/other',
  `published_at` datetime DEFAULT NULL COMMENT '公開日時',
  `expires_at` datetime DEFAULT NULL COMMENT '終了日時',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT '並び順',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=有効 0=無効',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_published` (`is_active`, `published_at`, `expires_at`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='お知らせ（グッズ・締切・メディア等）';

-- 3. 応募締め切りテーブル（1イベントに複数ラウンド）
CREATE TABLE IF NOT EXISTS `hn_event_applications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) UNSIGNED NOT NULL COMMENT 'hn_events.id',
  `round_name` varchar(100) NOT NULL DEFAULT '' COMMENT 'ラウンド名（第1次、第2次等）',
  `application_start` datetime DEFAULT NULL COMMENT '応募開始日時（任意）',
  `application_deadline` datetime NOT NULL COMMENT '応募締切日時',
  `announcement_date` datetime DEFAULT NULL COMMENT '当選発表日時（任意）',
  `application_url` varchar(500) DEFAULT NULL COMMENT '応募ページURL',
  `memo` text DEFAULT NULL COMMENT 'メモ',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0 COMMENT '並び順',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event` (`event_id`),
  KEY `idx_deadline` (`application_deadline`)
  -- FKは hn_events.id の型環境差のため未設定（JOINはidx_eventで運用）
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='イベント応募締め切り（複数ラウンド対応）';
