-- けやき坂46（ひらがなけやき）楽曲データ投入
-- 事前に add_hn_releases_group_name.sql を実行し、hn_releases.group_name 列が存在すること。
-- 重複実行時は INSERT 選別により二重登録を避ける（同一タイトル・同一 release_id が無い場合のみ挿入）。
-- hn_song_members（フォーメーション）は初期未登録。管理画面の参加メンバー編集で登録すること。

-- ---------------------------------------------------------------------------
-- 1) 長濱ねる（卒業メンバー）
-- ---------------------------------------------------------------------------
INSERT INTO hn_members (id, name, kana, generation, is_active, blood_type, birth_place, blog_url, insta_url, twitter_url, member_info, update_user)
SELECT 0, '長濱ねる', 'ながはまねる', 1, 0, '', '', '', '', '', 'けやき坂46 / 日向坂46（卒業）', 'migration'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM hn_members WHERE name = '長濱ねる' LIMIT 1);

-- ---------------------------------------------------------------------------
-- 2) リリース（アルバム・欅坂シングル収録コンピ）
-- ---------------------------------------------------------------------------
INSERT INTO hn_releases (release_type, group_name, release_number, title, title_kana, release_date, description, update_user)
SELECT 'album', 'hiragana_keyaki', '1st', '走り出す瞬間', 'はしりだすしゅんかん', '2018-06-20',
  'けやき坂46唯一のアルバム。TYPE-A/TYPE-B/通常盤の3形態', 'migration'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
);

INSERT INTO hn_releases (release_type, group_name, release_number, title, title_kana, release_date, description, update_user)
SELECT 'best', 'hiragana_keyaki', NULL, 'けやき坂46 欅坂46シングル収録曲',
  'けやきざかふぉーてぃーしっくす けやきざかふぉーてぃーしっくすしんぐるしゅうろくきょく', NULL,
  '欅坂46の2nd〜8thシングル及び1stアルバムに収録されたけやき坂46名義のカップリング曲', 'migration'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
);

-- ---------------------------------------------------------------------------
-- 3) 版別（走り出す瞬間）— ジャケットURLは後から管理画面で設定可
-- ---------------------------------------------------------------------------
INSERT INTO hn_release_editions (release_id, edition, jacket_image_url, sort_order, update_user)
SELECT r.id, 'type_a', NULL, 0, 'migration'
FROM hn_releases r
WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_release_editions e WHERE e.release_id = r.id AND e.edition = 'type_a');

INSERT INTO hn_release_editions (release_id, edition, jacket_image_url, sort_order, update_user)
SELECT r.id, 'type_b', NULL, 1, 'migration'
FROM hn_releases r
WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_release_editions e WHERE e.release_id = r.id AND e.edition = 'type_b');

INSERT INTO hn_release_editions (release_id, edition, jacket_image_url, sort_order, update_user)
SELECT r.id, 'normal', NULL, 2, 'migration'
FROM hn_releases r
WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_release_editions e WHERE e.release_id = r.id AND e.edition = 'normal');

-- ---------------------------------------------------------------------------
-- 4) 楽曲 — アルバム「走り出す瞬間」18曲
-- track_type: title=表題扱い1曲 / sub=その他アルバム収録（DB enum に合わせ album_only は未使用）
-- ---------------------------------------------------------------------------
INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '期待していない自分', 'きたいしていないじぶん', 'title', 1, '秋元康', 'kyota.', NULL, 'migration'
FROM hn_releases r
WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '期待していない自分');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '線香花火が消えるまで', NULL, 'sub', 2, '秋元康', '山本加津彦', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '線香花火が消えるまで');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '未熟な怒り', NULL, 'sub', 3, '秋元康', 'バグベア', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '未熟な怒り');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'わずかな光', NULL, 'sub', 4, '秋元康', '奥田もとい', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'わずかな光');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'ノックをするな!', NULL, 'sub', 5, '秋元康', 'APAZZI', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'ノックをするな!');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'ハロウィンのカボチャが割れた', NULL, 'sub', 6, '秋元康', '佐藤真吾', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'ハロウィンのカボチャが割れた');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '約束の卵', NULL, 'sub', 7, '秋元康', 'aokado', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '約束の卵');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'キレイになりたい', NULL, 'sub', 8, '秋元康', '石井健太郎', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'キレイになりたい');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '夏色のミュール', NULL, 'sub', 9, '秋元康', '井上トモノリ', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '夏色のミュール');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '男友達だから', NULL, 'sub', 10, '秋元康', '三谷秀甫', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '男友達だから');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '最前列へ', NULL, 'sub', 11, '秋元康', 'IKEZO', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '最前列へ');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'おいで夏の境界線', NULL, 'sub', 12, '秋元康', '中山聡, 足立優', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'おいで夏の境界線');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '車輪が軋むように君が泣く', NULL, 'sub', 13, '秋元康', '斉門', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '車輪が軋むように君が泣く');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '三輪車に乗りたい', NULL, 'sub', 14, '秋元康', 'Kaz Kuwamura 他', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '三輪車に乗りたい');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'こんな整列を誰がさせるのか?', NULL, 'sub', 15, '秋元康', '春行', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'こんな整列を誰がさせるのか?');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '居心地悪く、大人になった', NULL, 'sub', 16, '秋元康', '嶋田啓介', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '居心地悪く、大人になった');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '割れないシャボン玉', NULL, 'sub', 17, '秋元康', '渡辺翔, sasakure.UK 他', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '割れないシャボン玉');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'ひらがなで恋したい', NULL, 'sub', 18, '秋元康', 'ふるっぺ (ケラケラ)', NULL, 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = '走り出す瞬間' AND r.release_type = 'album'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'ひらがなで恋したい');

-- ---------------------------------------------------------------------------
-- 5) 楽曲 — 欅坂46シングル・アルバム収録（けやき坂46名義）15曲
-- ---------------------------------------------------------------------------
INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'ひらがなけやき', NULL, 'sub', 1, '秋元康', NULL, '収録: 欅坂46 2nd「世界には愛しかない」通常盤 / 発売日 2016-08-10', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'ひらがなけやき');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '誰よりも高く跳べ!', NULL, 'sub', 2, '秋元康', NULL, '収録: 欅坂46 3rd「二人セゾン」 / 発売日 2016-11-30', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '誰よりも高く跳べ!');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'W-KEYAKIZAKAの詩', NULL, 'sub', 3, '秋元康', NULL, '収録: 欅坂46 4th「不協和音」TYPE-A / 発売日 2017-04-05', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'W-KEYAKIZAKAの詩');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '僕たちは付き合っている', NULL, 'sub', 4, '秋元康', NULL, '収録: 欅坂46 4th「不協和音」 / 発売日 2017-04-05', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '僕たちは付き合っている');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '沈黙した恋人よ', NULL, 'sub', 5, '秋元康', NULL, '収録: 欅坂46 1stアルバム「真っ白なものは汚したくなる」 / 2017-07-19', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '沈黙した恋人よ');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '永遠の白線', NULL, 'sub', 6, '秋元康', NULL, '収録: 欅坂46 1stアルバム「真っ白なものは汚したくなる」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '永遠の白線');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '太陽は見上げる人を選ばない', NULL, 'sub', 7, '秋元康', NULL, '収録: 欅坂46 1stアルバム「真っ白なものは汚したくなる」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '太陽は見上げる人を選ばない');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '猫の名前', NULL, 'sub', 8, '秋元康', NULL, '収録: 欅坂46 1stアルバム「真っ白なものは汚したくなる」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '猫の名前');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'それでも歩いてる', NULL, 'sub', 9, '秋元康', NULL, '収録: 欅坂46 5th「風に吹かれても」 / 2017-10-25', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'それでも歩いてる');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'NO WAR in the future', NULL, 'sub', 10, '秋元康', NULL, '収録: 欅坂46 5th「風に吹かれても」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'NO WAR in the future');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'イマニミテイロ', NULL, 'sub', 11, '秋元康', NULL, '収録: 欅坂46 6th「ガラスを割れ!」 / 2018-03-07', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'イマニミテイロ');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '半分の記憶', NULL, 'sub', 12, '秋元康', NULL, '収録: 欅坂46 6th「ガラスを割れ!」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '半分の記憶');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, 'ハッピーオーラ', NULL, 'sub', 13, '秋元康', NULL, '収録: 欅坂46 7th「アンビバレント」TYPE-B / 2018-08-15', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = 'ハッピーオーラ');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '君に話しておきたいこと', NULL, 'sub', 14, '秋元康', NULL, '収録: 欅坂46 8th「黒い羊」 / 2019-02-27', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '君に話しておきたいこと');

INSERT INTO hn_songs (release_id, title, title_kana, track_type, track_number, lyricist, composer, memo, update_user)
SELECT r.id, '抱きしめてやる', NULL, 'sub', 15, '秋元康', NULL, '収録: 欅坂46 8th「黒い羊」', 'migration'
FROM hn_releases r WHERE r.group_name = 'hiragana_keyaki' AND r.title = 'けやき坂46 欅坂46シングル収録曲'
  AND NOT EXISTS (SELECT 1 FROM hn_songs s WHERE s.release_id = r.id AND s.title = '抱きしめてやる');

-- 6) hn_song_members … 未実施（管理画面の楽曲参加メンバー編集で登録）
