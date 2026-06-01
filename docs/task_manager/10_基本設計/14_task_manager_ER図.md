# タスク管理 ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sys_users ||--o{ tm_tasks : "has many"
    sys_users ||--o{ tm_categories : "has many"
    tm_categories ||--o{ tm_tasks : "has many"
    hn_events }o--o| tm_tasks : "参照 (カレンダー表示のみ)"

    sys_users {
        bigint id PK
        varchar id_name
        varchar password
        varchar role
    }

    tm_tasks {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        varchar title
        text description
        varchar status
        int priority
        date start_date
        date due_date
        datetime created_at
        datetime updated_at
    }

    tm_categories {
        bigint id PK
        bigint user_id FK
        varchar name
        varchar color
        datetime created_at
    }

    hn_events {
        bigint id PK
        varchar event_name
        date event_date
    }
```

## 2. テーブル間リレーション説明

| 親テーブル | 子テーブル | 関係 | 説明 |
| :--- | :--- | :--- | :--- |
| sys_users | tm_tasks | 1:N | 1ユーザーが複数のタスクを持つ。BaseModelの `isUserIsolated=true` により user_id で自動隔離 |
| sys_users | tm_categories | 1:N | 1ユーザーが複数のカテゴリを持つ。ユーザーごとに独立したカテゴリ体系 |
| tm_categories | tm_tasks | 1:N | 1カテゴリに複数のタスクが紐付く。category_id は NULL 許容（未分類） |
| hn_events | (参照のみ) | - | カレンダービューで日向坂イベントを重畳表示するために参照。tm_tasks との直接的なFK関係はない |
