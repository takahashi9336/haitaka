-- 検証用: 既存 lt_user_places を com_user_places へリネーム
-- 目的:
--  - アプリ参照先を com_user_places に変更した後、
--    「テーブルリネーム方式」でも動作することを確認する。
--
-- 注意:
--  - これは "create+copy" 方式とは排他的です。
--    事前に com_user_places が存在する場合は失敗します。

RENAME TABLE lt_user_places TO com_user_places;

