# ミーグリ（お話し会）& ネタ帳 ER図

## 1. ミーグリ データモデル関係図

```mermaid
erDiagram
    hn_meetgreet_slots ||--o{ hn_meetgreet_reports : "has many"
    hn_meetgreet_reports ||--o{ hn_meetgreet_report_messages : "has many"
    hn_meetgreet_report_avatars }o--|| hn_members : "belongs to"
    hn_meetgreet_slots }o--o| hn_events : "optionally links to"
    hn_meetgreet_slots }o--o| hn_members : "optionally belongs to"

    hn_meetgreet_slots {
        int id PK
        int user_id FK
        int event_id FK "nullable"
        date event_date
        varchar slot_name "部名"
        time start_time "nullable"
        time end_time "nullable"
        int member_id FK "nullable"
        varchar member_name_raw "nullable"
        int ticket_count "default 0"
        text report "暗号化 nullable"
        datetime created_at
        datetime updated_at
    }

    hn_meetgreet_reports {
        bigint id PK
        int user_id FK
        int slot_id FK "CASCADE"
        int ticket_used "default 1"
        text my_nickname "暗号化 nullable"
        int sort_order "default 0"
        datetime created_at
        datetime updated_at
    }

    hn_meetgreet_report_messages {
        bigint id PK
        int user_id FK
        bigint report_id FK "CASCADE"
        enum sender_type "member/self/narration/self_thought"
        text content "暗号化"
        int sort_order "default 0"
        datetime created_at
        datetime updated_at
    }

    hn_meetgreet_report_avatars {
        bigint id PK
        int user_id FK
        int member_id FK "CASCADE, UNIQUE(user_id,member_id)"
        varchar image_path
        datetime created_at
        datetime updated_at
    }

    hn_members {
        int id PK
        varchar name
        int color_id1 FK
        int color_id2 FK
        varchar image_url
        int generation
        varchar kana
    }

    hn_events {
        int id PK
        varchar event_name
        date event_date
        int category "2=MG, 3=リアルMG"
        int mg_rounds
    }
```

## 2. ネタ帳 データモデル関係図

```mermaid
erDiagram
    hn_neta ||--o{ hn_neta_tags : "has many"
    hn_neta_tags }o--|| hn_tags : "references"
    hn_neta }o--|| hn_members : "belongs to"

    hn_neta {
        bigint id PK
        int user_id FK
        int member_id FK
        text content "暗号化"
        text memo "暗号化 nullable"
        varchar neta_type "nullable: question/impression/joke"
        tinyint is_favorite "default 0"
        varchar status "stock/done/delete"
        datetime created_at
        datetime updated_at
    }

    hn_tags {
        int id PK
        int user_id FK
        varchar name "UNIQUE(user_id,name)"
        datetime created_at
        datetime updated_at
    }

    hn_neta_tags {
        bigint neta_id PK_FK "CASCADE"
        int tag_id PK_FK "CASCADE"
        datetime created_at
    }
```

## 3. 全体関係概要図

```mermaid
erDiagram
    hn_meetgreet_slots ||--o{ hn_meetgreet_reports : "1:N"
    hn_meetgreet_reports ||--o{ hn_meetgreet_report_messages : "1:N"
    hn_meetgreet_report_avatars }o--|| hn_members : "N:1"
    hn_meetgreet_slots }o--o| hn_members : "N:0..1"
    hn_meetgreet_slots }o--o| hn_events : "N:0..1"
    hn_neta }o--|| hn_members : "N:1"
    hn_neta ||--o{ hn_neta_tags : "1:N"
    hn_neta_tags }o--|| hn_tags : "N:1"
    hn_members ||--o{ hn_colors : "references"
    hn_favorites }o--|| hn_members : "N:1"
```

## 4. カスケード削除チェーン

| 親テーブル | 子テーブル | 動作 |
| :--- | :--- | :--- |
| hn_meetgreet_slots | hn_meetgreet_reports | ON DELETE CASCADE |
| hn_meetgreet_reports | hn_meetgreet_report_messages | ON DELETE CASCADE |
| hn_members | hn_meetgreet_report_avatars | ON DELETE CASCADE |
| hn_neta | hn_neta_tags | ON DELETE CASCADE |
| hn_tags | hn_neta_tags | ON DELETE CASCADE |
