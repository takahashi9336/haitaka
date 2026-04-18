-- ============================================
-- Health: 食材ストック（キッチン在庫）
-- hl_kitchen_stock_items に item_group（食材/調味料/その他）を追加
-- - food / seasoning / other
-- - 既存データは food で埋める
-- ============================================

ALTER TABLE `hl_kitchen_stock_items`
  ADD COLUMN `item_group` varchar(32) NOT NULL DEFAULT 'food' COMMENT 'グループ: food/seasoning/other' AFTER `name`;

-- 既存行（過去データ）を明示的に埋める
UPDATE `hl_kitchen_stock_items`
   SET `item_group` = 'food'
 WHERE `item_group` IS NULL OR `item_group` = '';

-- フィルタ/並び替え用（必要最小限）
ALTER TABLE `hl_kitchen_stock_items`
  ADD KEY `idx_user_group_purchased_date` (`user_id`, `item_group`, `purchased_date`);

