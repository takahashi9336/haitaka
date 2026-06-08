# ポータル（Portal） ER図

## 1. ポータル所有テーブルのデータモデル関係図

```mermaid
erDiagram
    hn_topics {
        int id PK "AUTO_INCREMENT"
        varchar title "NOT NULL タイトル"
        text summary "概要"
        varchar url "リンクURL"
        varchar image_url "画像URL"
        varchar topic_type "NOT NULL DEFAULT other"
        date start_date "表示開始日"
        date end_date "表示終了日"
        tinyint sort_order "NOT NULL DEFAULT 0"
        tinyint is_active "NOT NULL DEFAULT 1"
        datetime created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
        datetime updated_at "ON UPDATE CURRENT_TIMESTAMP"
    }

    hn_announcements {
        int id PK "AUTO_INCREMENT"
        varchar title "NOT NULL タイトル"
        text body "本文"
        varchar url "リンクURL"
        varchar image_url "画像URL"
        varchar announcement_type "NOT NULL DEFAULT other"
        datetime published_at "公開日時"
        datetime expires_at "終了日時"
        tinyint sort_order "NOT NULL DEFAULT 0"
        tinyint is_active "NOT NULL DEFAULT 1"
        datetime created_at "NOT NULL DEFAULT CURRENT_TIMESTAMP"
        datetime updated_at "ON UPDATE CURRENT_TIMESTAMP"
    }
```

## 2. ポータルダッシュボードが参照する外部テーブルとの関係図

```mermaid
erDiagram
    PORTAL_DASHBOARD ||--o{ hn_topics : "表示(R)"
    PORTAL_DASHBOARD ||--o{ hn_announcements : "表示(R)"
    PORTAL_DASHBOARD ||--o{ hn_event_applications : "締切表示(R)"

    PORTAL_DASHBOARD ||--|| hn_events : "次のイベント/今日は何の日(R)"
    PORTAL_DASHBOARD ||--o{ hn_favorites : "推しサマリ(R)"
    PORTAL_DASHBOARD ||--o{ hn_members : "推し情報/誕生日(R)"
    PORTAL_DASHBOARD ||--o{ hn_neta : "ネタ統計(R)"
    PORTAL_DASHBOARD ||--o{ hn_meetgreet_slots : "本日のミーグリ(R)"
    PORTAL_DASHBOARD ||--|| hn_releases : "最新リリース/今日は何の日(R)"
    PORTAL_DASHBOARD ||--o{ hn_release_editions : "ジャケット(R)"
    PORTAL_DASHBOARD ||--o{ hn_songs : "収録曲(R)"
    PORTAL_DASHBOARD ||--o{ hn_blogs : "最新ブログ(R)"
    PORTAL_DASHBOARD ||--o{ hn_news : "推しニュース(R)"
    PORTAL_DASHBOARD ||--o{ com_media_assets : "推し新着動画(R)"

    hn_event_applications }o--|| hn_events : "event_id"
    hn_favorites }o--|| hn_members : "member_id"
    hn_members ||--o{ hn_member_images : "member_id"
    hn_members ||--o{ hn_colors : "color_id1/color_id2"
    hn_songs }o--|| hn_releases : "release_id"
    hn_release_editions }o--|| hn_releases : "release_id"
    hn_songs ||--o{ hn_song_media_links : "song_id"
    hn_song_media_links }o--|| hn_media_metadata : "media_meta_id"
    hn_media_metadata }o--|| com_media_assets : "asset_id"

    hn_event_applications {
        int id PK
        bigint event_id FK "hn_events.id"
        varchar round_name "ラウンド名"
        datetime application_start "応募開始"
        datetime application_deadline "応募締切"
        datetime announcement_date "当選発表"
        varchar application_url "応募URL"
        text memo "メモ"
        tinyint sort_order "並び順"
    }

    hn_events {
        bigint id PK
        varchar event_name "イベント名"
        date event_date "開催日"
        int category "カテゴリ"
    }

    hn_favorites {
        bigint id PK
        bigint member_id FK
        int level "推しレベル 7-9"
    }

    hn_members {
        bigint id PK
        varchar name "名前"
        date birth_date "生年月日"
        int generation "期"
        int is_active "在籍中"
    }

    hn_releases {
        bigint id PK
        varchar title "タイトル"
        varchar release_type "single/album等"
        date release_date "発売日"
    }
```

## 3. ポータル情報管理のCRUD範囲

ポータル情報管理画面（`PortalInfoController::admin`）は以下のテーブルに対してCRUD操作を行う。

| テーブル | C (作成) | R (参照) | U (更新) | D (削除) |
| :--- | :--- | :--- | :--- | :--- |
| hn_topics | save_topic API | admin画面 / ポータル | save_topic API | - |
| hn_announcements | save_announcement API | admin画面 / ポータル | save_announcement API | - |
| hn_event_applications | save_event_applications API | admin画面 / ポータル | save_event_applications API | - |
