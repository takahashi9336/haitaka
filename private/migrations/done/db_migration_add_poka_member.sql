-- ============================================
-- ポカ（日向坂46マスコットキャラクター）をメンバーとして登録
-- id=99, generation=0（期別なし）
-- ============================================

INSERT IGNORE INTO hn_members (id, name, kana, generation, is_active, blood_type, birth_place, blog_url, insta_url, twitter_url, member_info, update_user)
VALUES (99, 'ポカ', 'ぽか', 0, 1, '', '', '', '', '', '日向坂46 マスコットキャラクター', 'migration');
