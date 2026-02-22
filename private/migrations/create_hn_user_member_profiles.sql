-- ユーザごとのメンバープロフィール画像
-- ユーザが独自に設定したメンバー画像を保存（未設定時は管理者プリセットを使用）
CREATE TABLE IF NOT EXISTS hn_user_member_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    member_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_member (user_id, member_id),
    INDEX idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
