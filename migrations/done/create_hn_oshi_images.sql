CREATE TABLE IF NOT EXISTS `hn_oshi_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL COMMENT 'uploads/oshi/{user_id}/{member_id}/xxx.jpg',
  `caption` varchar(200) DEFAULT NULL COMMENT '画像メモ（任意）',
  `sort_order` tinyint(3) UNSIGNED DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_member` (`user_id`, `member_id`),
  CONSTRAINT `fk_oshi_images_member` FOREIGN KEY (`member_id`) REFERENCES `hn_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
