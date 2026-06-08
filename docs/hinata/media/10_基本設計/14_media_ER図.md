# Media（メディア管理） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    com_media_assets ||--o| hn_media_metadata : "1:1 拡張"
    hn_media_metadata ||--o{ hn_media_members : "has many"
    hn_media_metadata ||--o{ hn_media_hashtags : "has many"
    hn_media_metadata ||--o{ hn_song_media_links : "has many"
    hn_members ||--o{ hn_media_members : "紐付け"
    hn_songs ||--o{ hn_song_media_links : "紐付け"
    hn_media_categories ||..o{ hn_media_metadata : "カテゴリ参照"

    hn_blog_posts }o--|| hn_members : "執筆者"
    hn_news ||--o{ hn_news_members : "has many"
    hn_members ||--o{ hn_news_members : "紐付け"
    hn_schedule ||--o{ hn_schedule_members : "has many"
    hn_members ||--o{ hn_schedule_members : "紐付け"

    com_media_assets {
        bigint id PK
        varchar platform "youtube/tiktok/instagram"
        varchar media_key "UNIQUE(platform,media_key)"
        varchar sub_key "TikTok:@username等"
        varchar media_type "video/short/live"
        varchar title
        text thumbnail_url
        text description
        datetime upload_date
        datetime created_at
    }

    hn_media_metadata {
        bigint id PK
        bigint asset_id FK "UNIQUE -> com_media_assets.id"
        varchar category "MV/Trailer/Live等"
        date release_date "レガシー（非推奨）"
        text memo
    }

    hn_media_members {
        bigint id PK
        bigint media_meta_id FK "-> hn_media_metadata.id CASCADE"
        int member_id FK "-> hn_members.id CASCADE"
        varchar update_user
    }

    hn_media_categories {
        int id PK
        varchar name "UNIQUE"
        int sort_order
        datetime created_at
    }

    hn_media_hashtags {
        bigint id PK
        bigint media_meta_id FK "-> hn_media_metadata.id CASCADE"
        varchar hashtag "UNIQUE(media_meta_id,hashtag)"
        datetime created_at
    }

    hn_blog_posts {
        bigint id PK
        int member_id FK "-> hn_members.id"
        int article_id "UNIQUE 公式サイト記事ID"
        varchar title
        mediumtext body_html
        text body_text
        varchar thumbnail_url
        datetime published_at
        varchar detail_url
        datetime created_at
        datetime updated_at
    }

    hn_news {
        bigint id PK
        varchar article_code "UNIQUE 公式サイト記事コード"
        date published_date
        varchar category
        varchar title
        varchar detail_url
        datetime created_at
        datetime updated_at
    }

    hn_news_members {
        bigint news_id PK_FK "-> hn_news.id"
        int member_id PK_FK "-> hn_members.id"
    }

    hn_schedule {
        bigint id PK
        varchar schedule_code "UNIQUE 公式サイトID_日付"
        date schedule_date
        varchar category
        varchar time_text
        varchar title
        varchar detail_url
        datetime created_at
        datetime updated_at
    }

    hn_schedule_members {
        bigint schedule_id PK_FK "-> hn_schedule.id"
        int member_id PK_FK "-> hn_members.id"
    }

    hn_members {
        int id PK
        varchar name
        varchar kana
        int generation
        tinyint is_active
        smallint official_blog_ct
    }

    hn_songs {
        bigint id PK
        int release_id FK
        varchar title
    }

    hn_song_media_links {
        bigint id PK
        bigint song_id FK "-> hn_songs.id CASCADE"
        bigint media_meta_id FK "UNIQUE -> hn_media_metadata.id CASCADE"
        datetime created_at
    }
```

## 2. 補足

- `com_media_assets` は共通テーブル（`com_` プレフィクス）であり、日向坂以外の機能（イベント動画等）からも参照される。platform + media_key の UNIQUE KEY で同一メディアの重複登録を防止。
- `hn_media_metadata` は com_media_assets に対する 1:1 拡張テーブル。asset_id が UNIQUE KEY であり、1 つの com_media_assets に対して最大 1 つの日向坂メタデータが存在する。
- `hn_media_metadata.release_date` はレガシーカラムであり、現在は `com_media_assets.upload_date` を使用する。将来的に廃止予定。
- `hn_media_members` は ON DELETE CASCADE により、hn_media_metadata 削除時に自動で紐付けも削除される。
- `hn_media_categories.name` は hn_media_metadata.category に格納する文字列値と一致する。カテゴリ名変更時は両テーブルを同期更新。
- `hn_news_members` / `hn_schedule_members` は複合主キー (news_id/schedule_id, member_id) で重複防止。外部キー制約は設定されていない（INSERT IGNORE で運用）。
- `hn_blog_posts.member_id` は hn_members.official_blog_ct を介して公式サイトの ct パラメータからマッピングされる。
- `hn_schedule.schedule_code` は公式サイトの detail ID に日付を付与した形式（例: `12345_2026-06-07`）で、同一番組が複数日にまたがる場合も別レコードとして管理。
