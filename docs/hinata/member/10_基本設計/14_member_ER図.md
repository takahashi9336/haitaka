# メンバー (Member) ER図

## 1. データモデル関係図

```mermaid
erDiagram
    hn_members ||--o{ hn_member_images : "has many"
    hn_members ||--o{ hn_member_activities : "has many"
    hn_members ||--o{ hn_favorites : "has many"
    hn_members ||--o{ hn_oshi_images : "has many"
    hn_members ||--o{ hn_user_member_profiles : "has many"
    hn_members }o--|| hn_colors : "color_id1"
    hn_members }o--|| hn_colors : "color_id2"

    hn_members {
        int id PK
        varchar name "メンバー名"
        varchar kana "かな"
        int generation "期生 (0=ポカ/期別なし, 1-5)"
        date birth_date "生年月日"
        varchar blood_type "血液型"
        decimal height "身長"
        varchar birth_place "出身地"
        int color_id1 FK "サイリウムカラー1"
        int color_id2 FK "サイリウムカラー2"
        tinyint is_active "1=現役 0=卒業"
        varchar image_url "メイン画像 (後方互換)"
        varchar blog_url "ブログURL"
        varchar insta_url "Instagram URL"
        varchar twitter_url "X(Twitter) URL"
        varchar pv_movie_id "PV動画ID (後方互換)"
        text member_info "メンバー情報メモ"
        datetime updated_at "更新日時"
        varchar update_user "更新者"
    }

    hn_colors {
        int id PK
        varchar color_name "色名"
        varchar color_code "HEXカラーコード"
    }

    hn_member_images {
        int id PK
        int member_id FK "hn_members.id"
        varchar image_url "画像ファイル名"
        tinyint sort_order "表示順 (0-4)"
        varchar update_user "更新者"
    }

    hn_member_activities {
        int id PK
        int member_id FK "hn_members.id"
        varchar category "カテゴリ"
        varchar title "活動名"
        text description "概要"
        varchar url "誘導先URL"
        varchar url_label "ボタンラベル"
        varchar image_url "サムネイル画像"
        tinyint is_active "1=表示 0=非表示"
        tinyint sort_order "表示順"
        date start_date "開始日"
        date end_date "終了日"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }

    hn_favorites {
        int id PK
        int user_id "sys_users.id"
        int member_id FK "hn_members.id"
        int level "推しレベル"
        datetime created_at "作成日時"
    }

    hn_oshi_images {
        bigint id PK
        int user_id "sys_users.id"
        int member_id FK "hn_members.id"
        varchar image_path "画像パス"
        varchar caption "キャプション"
        tinyint sort_order "表示順"
        datetime created_at "作成日時"
    }

    hn_user_member_profiles {
        int id PK
        int user_id "sys_users.id"
        int member_id FK "hn_members.id"
        varchar image_path "画像パス"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }
```

## 2. 参照テーブル関係図（他ドメインとの接点）

```mermaid
erDiagram
    hn_members ||--o{ hn_song_members : "参加楽曲"
    hn_members ||--o{ hn_media_members : "出演動画"
    hn_members ||--o{ hn_event_members : "参加イベント"
    hn_members ||--o{ hn_blog_posts : "ブログ"
    hn_members ||--o{ hn_news_members : "ニュース"
    hn_members ||--o{ hn_schedule_members : "スケジュール"
    hn_members ||--o{ hn_neta : "ミーグリネタ"
    hn_members ||--o{ hn_release_member_images : "リリースアー写"

    hn_song_members {
        int song_id FK
        int member_id FK
        tinyint is_center
        int row_number
        int position
    }

    hn_media_members {
        int media_meta_id FK
        int member_id FK
        varchar update_user
    }

    hn_event_members {
        int event_id FK
        int member_id FK
    }

    hn_blog_posts {
        int id PK
        int member_id FK
        varchar title
        varchar thumbnail_url
        datetime published_at
    }

    hn_news_members {
        int news_id FK
        int member_id FK
    }

    hn_schedule_members {
        int schedule_id FK
        int member_id FK
    }

    hn_neta {
        int id PK
        int user_id
        int member_id FK
        text content
        varchar status
    }

    hn_release_member_images {
        int release_id FK
        int member_id FK
        varchar image_url
    }
```

## 3. テーブル間の関係補足

| 関係 | 説明 |
| :--- | :--- |
| `hn_members` → `hn_colors` | color_id1, color_id2 で 2 色を参照。LEFT JOIN のため NULL 許容 |
| `hn_members` → `hn_member_images` | 1メンバーあたり最大5枚。CASCADE DELETE |
| `hn_members` → `hn_member_activities` | 1メンバーに対して複数の個人活動。CASCADE DELETE |
| `hn_members` → `hn_favorites` | ユーザーごとに1レコード。level 7-9 はユーザーあたり各1名の排他制約 |
| `hn_members` → `hn_oshi_images` | ユーザー+メンバーの組み合わせごとに最大10枚 |
| `hn_members` → `hn_user_member_profiles` | ユーザー+メンバーで UNIQUE。推し画像のカスタムプロフィール |
| `hn_members` → 他ドメインテーブル | 楽曲・動画・イベント・ブログ等は member ドメインからは参照 (READ) のみ |
