# イベント管理・セットリスト・ライブガイド (event) ER図

## 1. データモデル関係図

```mermaid
erDiagram
    hn_event_series ||--o{ hn_events : "has many"
    hn_events ||--o{ hn_event_members : "has many"
    hn_events ||--o{ hn_event_movies : "has many"
    hn_events ||--o{ hn_event_applications : "has many"
    hn_events ||--o{ hn_event_guide_songs : "has many"
    hn_events ||--o| hn_event_shadow_narrations : "has one"
    hn_events ||--o{ hn_user_events_status : "has many"
    hn_events ||--o{ hn_event_attendance : "has many"
    hn_events ||--o{ hn_setlists : "has many"
    hn_event_shadow_narrations ||--o{ hn_event_shadow_narration_members : "has many"
    hn_setlists ||--o{ hn_setlist_centers : "has many"
    hn_members ||--o{ hn_event_members : "referenced by"
    hn_members ||--o{ hn_event_shadow_narration_members : "referenced by"
    hn_members ||--o{ hn_setlist_centers : "referenced by"
    hn_songs ||--o{ hn_setlists : "referenced by"
    hn_songs ||--o{ hn_event_guide_songs : "referenced by"
    hn_releases ||--o{ hn_songs : "has many"
    com_media_assets ||--o{ hn_event_movies : "referenced by"

    hn_event_series {
        bigint id PK
        varchar name UK "系列表示名"
        datetime created_at
    }

    hn_events {
        bigint id PK
        varchar event_name "イベント名"
        date event_date "開催日"
        int category "カテゴリ(1-6,99)"
        bigint series_id FK "hn_event_series.id"
        tinyint mg_rounds "ミーグリ部数"
        varchar event_place "会場名"
        varchar event_place_address "Maps用住所"
        decimal latitude "緯度"
        decimal longitude "経度"
        varchar place_id "Google Places ID"
        text event_info "詳細メモ"
        varchar event_url "特設サイトURL"
        varchar event_hashtag "ハッシュタグ"
        json collaboration_urls "コラボURL配列"
        text related_links "関連リンクJSON"
        datetime updated_at
        varchar update_user
    }

    hn_event_members {
        bigint event_id FK "hn_events.id"
        int member_id FK "hn_members.id"
    }

    hn_event_movies {
        bigint event_id FK "hn_events.id"
        bigint movie_id FK "com_media_assets.id"
    }

    hn_event_applications {
        int id PK
        bigint event_id FK "hn_events.id"
        varchar round_name "ラウンド名"
        datetime application_start "応募開始日時"
        datetime application_deadline "応募締切日時"
        datetime announcement_date "当選発表日時"
        varchar application_url "応募ページURL"
        text memo
        tinyint sort_order
        datetime created_at
        datetime updated_at
    }

    hn_user_events_status {
        int user_id FK "sys_users.id"
        bigint event_id FK "hn_events.id"
        int status "ステータス(1-5)"
        varchar seat_info "座席情報"
        text impression "感想"
    }

    hn_event_attendance {
        bigint id PK
        int user_id FK "sys_users.id"
        bigint event_id FK "hn_events.id"
        text memo
        datetime created_at
    }

    hn_setlists {
        bigint id PK
        bigint event_id FK "hn_events.id"
        bigint song_id FK "hn_songs.id (NULL許可)"
        varchar entry_type "song/mc/block"
        int sort_order
        tinyint encore "0=本編,1=EN,2=WEN"
        varchar label "MC/ブロックラベル"
        varchar block_kind "ブロック種別"
        int center_member_id "旧: 単一センター"
        varchar memo
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_setlist_centers {
        bigint id PK
        bigint setlist_id FK "hn_setlists.id"
        int member_id FK "hn_members.id"
    }

    hn_event_guide_songs {
        bigint id PK
        int event_id FK "hn_events.id"
        bigint song_id FK "hn_songs.id"
        enum likelihood "certain/high/possible"
        int sort_order
        datetime created_at
    }

    hn_event_shadow_narrations {
        bigint event_id PK "hn_events.id"
        varchar memo
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_event_shadow_narration_members {
        bigint event_id FK "hn_event_shadow_narrations.event_id"
        int member_id FK "hn_members.id"
    }

    hn_members {
        int id PK
        varchar name
        int generation
        varchar kana
    }

    hn_songs {
        bigint id PK
        varchar title
        bigint release_id FK
        varchar track_type
        varchar apple_music_url
        varchar spotify_url
    }

    hn_releases {
        bigint id PK
        varchar title
        varchar release_type
        int release_number
        date release_date
    }

    com_media_assets {
        bigint id PK
        varchar platform
        varchar media_key
        varchar sub_key
        varchar media_type
        varchar title
        varchar thumbnail_url
    }
```

## 2. テーブル間の補足事項

- `hn_events` と `hn_event_series` は N:1 の関係。`series_id` が NULL の場合は系列未設定。
- `hn_event_members` は複合主キーではなく、`(event_id, member_id)` の組で管理（イベント保存時に全件 delete-insert）。
- `hn_event_movies` は `com_media_assets` を参照する FK を持つ（`ON DELETE CASCADE`）。`EventRelatedLinkService::syncYoutubeMovie()` で自動同期。
- `hn_user_events_status` は `(user_id, event_id)` の UNIQUE KEY で upsert（`ON DUPLICATE KEY UPDATE`）。
- `hn_event_attendance` は `(user_id, event_id)` の UNIQUE KEY でトグル（存在すれば DELETE、なければ INSERT）。
- `hn_setlists` の `center_member_id` はレガシーカラムで、`hn_setlist_centers` が正。互換ロジックで両方を保持。
- `hn_event_shadow_narrations` は `event_id` が PK（イベントに1件のみ）。メンバーは `hn_event_shadow_narration_members` で複数管理。
- `hn_event_guide_songs` は `(event_id, song_id)` の UNIQUE KEY で重複登録を防止。`hn_songs` への FK あり（`ON DELETE CASCADE`）。
