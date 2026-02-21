-- mv_movies に配信サービス情報を保存するカラムを追加
ALTER TABLE mv_movies
    ADD COLUMN watch_providers JSON DEFAULT NULL COMMENT '配信サービス情報(JustWatch経由TMDBデータ)',
    ADD COLUMN watch_providers_updated_at DATETIME DEFAULT NULL COMMENT '配信情報の最終取得日時';
