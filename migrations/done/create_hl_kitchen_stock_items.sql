-- ============================================
-- Health: 食材ストック（キッチン在庫）
-- hl_kitchen_stock_items
-- - ユーザーごとに在庫を保持（端末間同期/バックアップ用）
-- ============================================

CREATE TABLE IF NOT EXISTS `hl_kitchen_stock_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'BaseModelでの隔離に必須',
  `name` varchar(255) NOT NULL COMMENT '食材名',
  `qty` varchar(100) DEFAULT NULL COMMENT '数量（自由入力）',
  `purchased_date` date DEFAULT NULL COMMENT '購入日',
  `is_frozen` tinyint(1) NOT NULL DEFAULT 0 COMMENT '冷凍フラグ',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_purchased_date` (`user_id`, `purchased_date`),
  KEY `idx_user_created_at` (`user_id`, `created_at`),
  CONSTRAINT `fk_hl_kitchen_stock_items_user` FOREIGN KEY (`user_id`) REFERENCES `sys_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Health: 食材ストック';

