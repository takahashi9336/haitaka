# Music（楽曲・リリース・アーティスト写真） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    hn_releases ||--o{ hn_release_editions : "has many"
    hn_releases ||--o{ hn_songs : "has many"
    hn_releases ||--o{ hn_release_member_images : "has many"
    hn_songs ||--o{ hn_song_members : "has many"
    hn_songs ||--o{ hn_song_media_links : "has many"
    hn_members ||--o{ hn_song_members : "参加"
    hn_members ||--o{ hn_release_member_images : "写真"
    hn_song_media_links }o--|| hn_media_metadata : "参照"
    hn_media_metadata }o--|| com_media_assets : "参照"

    hn_releases {
        int id PK
        enum release_type "single/album/digital/ep/best"
        varchar group_name "hinatazaka46/hiragana_keyaki"
        varchar release_number "1st, 2nd等"
        varchar title
        varchar title_kana
        date release_date
        text description
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_release_editions {
        bigint id PK
        int release_id FK
        enum edition "type_a/type_b/type_c/type_d/normal"
        varchar jacket_image_url
        tinyint sort_order
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_songs {
        bigint id PK
        int release_id FK
        bigint media_meta_id FK "レガシー（現在はhn_song_media_links使用）"
        varchar title
        varchar title_kana
        enum track_type "title/read/sub/type_a-d/normal/other"
        int track_number
        enum formation_type "all/kibetsu/senbatsu/solo/under/unit/other"
        tinyint generation "期別曲の場合の期"
        varchar lyricist
        varchar composer
        varchar arranger
        varchar mv_director
        varchar choreographer
        int duration "秒"
        text memo
        varchar apple_music_url
        varchar spotify_url
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_song_members {
        bigint id PK
        bigint song_id FK
        int member_id FK
        tinyint is_center "0/1 ダブルセンター対応"
        int row_number "1-5列"
        int position "列内位置（左端=1）"
        varchar part_description
        datetime updated_at
        varchar update_user
    }

    hn_song_media_links {
        bigint id PK
        bigint song_id FK
        bigint media_meta_id FK "UNIQUE"
        datetime created_at
    }

    hn_release_member_images {
        bigint id PK
        int release_id FK
        int member_id FK
        varchar image_url
        tinyint sort_order
        datetime created_at
        datetime updated_at
        varchar update_user
    }

    hn_members {
        int id PK
        varchar name
        varchar image_url
        int generation
        tinyint is_active
    }

    hn_media_metadata {
        bigint id PK
        bigint asset_id FK
        varchar category "MV/Call等"
    }

    com_media_assets {
        bigint id PK
        varchar platform "youtube等"
        varchar media_key
        varchar title
        varchar thumbnail_url
    }
```

## 2. 補足
- `hn_songs.media_meta_id` はレガシーカラムであり、現在の動画紐付けは `hn_song_media_links` 中間テーブルで管理する。既存データ移行済み。
- `hn_releases` → `hn_songs` は ON DELETE CASCADE。リリース削除時に収録曲も連鎖削除される。
- `hn_song_members` の UNIQUE KEY は `(song_id, member_id)` で同一楽曲への同一メンバー重複登録を防止。
- `hn_release_editions` の UNIQUE KEY は `(release_id, edition)` で同一リリースに同一版の重複を防止。
- `hn_release_member_images` の UNIQUE KEY は `(release_id, member_id)` で同一リリースに同一メンバーの写真は1枚のみ。
