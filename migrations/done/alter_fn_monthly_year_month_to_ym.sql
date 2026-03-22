-- 既に create_fn_focus_note.sql で year_month カラムで作成済みの場合に実行
-- year_month を ym にリネーム（予約語競合回避）

ALTER TABLE fn_monthly_pages
    CHANGE COLUMN `year_month` ym DATE NOT NULL COMMENT '月の初日 例: 2026-02-01',
    DROP INDEX uk_user_year_month,
    ADD UNIQUE KEY uk_user_ym (user_id, ym);
