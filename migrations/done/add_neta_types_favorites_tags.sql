-- hn_neta: ネタ種類・お気に入り対応 + タグ正規化
-- 既存環境に対して後付けで適用する前提

-- 1) hn_neta に列追加
ALTER TABLE hn_neta
  ADD COLUMN neta_type VARCHAR(20) NULL AFTER memo,
  ADD COLUMN is_favorite TINYINT(1) NOT NULL DEFAULT 0 AFTER neta_type;

-- 2) タグマスタ（ユーザー別）
CREATE TABLE IF NOT EXISTS hn_tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_tag (user_id, name),
  KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) ネタ×タグ中間
CREATE TABLE IF NOT EXISTS hn_neta_tags (
  neta_id BIGINT NOT NULL,
  tag_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (neta_id, tag_id),
  KEY idx_tag_id (tag_id),
  CONSTRAINT fk_hn_neta_tags_neta FOREIGN KEY (neta_id) REFERENCES hn_neta(id) ON DELETE CASCADE,
  CONSTRAINT fk_hn_neta_tags_tag FOREIGN KEY (tag_id) REFERENCES hn_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

