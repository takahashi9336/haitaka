-- 推しレベル体系の拡張
-- 旧: level 2 = 推し → 新: level 9 = 最推し
-- 新体系: 0=その他, 1=気になる, 7=3推し, 8=2推し, 9=最推し
UPDATE hn_favorites SET level = 9 WHERE level = 2;
