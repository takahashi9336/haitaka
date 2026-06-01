# メモ（Note） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sys_users ||--o{ nt_notes : "has many"
    sys_users ||--o{ nt_list_entries : "has many"

    sys_users {
        int id PK
        varchar username
        varchar email
    }

    nt_notes {
        bigint id PK
        int user_id FK
        varchar title "省略可能（本文先頭30文字を自動生成）"
        text content "メモ本文"
        varchar bg_color "カード背景色（デフォルト: #ffffff）"
        tinyint is_pinned "ピン留めフラグ（0/1）"
        varchar status "active / archived / trash"
        datetime created_at
        datetime updated_at
    }

    nt_list_entries {
        bigint id PK
        int user_id FK
        varchar list_kind "todo / question / first_time / fun / book / generic_list"
        varchar title "一覧表示用タイトル（オプション）"
        json payload "種別ごとのデータ本体"
        varchar bg_color "カード背景色（デフォルト: #ffffff）"
        tinyint is_pinned "ピン留めフラグ（0/1）"
        varchar status "active / archived"
        datetime created_at
        datetime updated_at
    }
```

## 2. テーブル間の関連

- `nt_notes` と `nt_list_entries` は直接の外部キー関連を持たず、独立したテーブルとして運用される。
- 両テーブルとも `user_id` により `sys_users` と紐づき、`BaseModel::$isUserIsolated = true` によってユーザー単位でデータが隔離される。

## 3. インデックス

### nt_notes
| インデックス名 | カラム | 用途 |
| :--- | :--- | :--- |
| PRIMARY | `id` | 主キー |
| `idx_user_status` | `user_id`, `status` | ユーザー別・ステータス別のメモ取得に使用 |

### nt_list_entries
| インデックス名 | カラム | 用途 |
| :--- | :--- | :--- |
| PRIMARY | `id` | 主キー |
| `idx_user_kind_status` | `user_id`, `list_kind`, `status` | ユーザー別・種別別・ステータス別のリスト取得に使用 |
| `idx_user_status` | `user_id`, `status` | ユーザー別・ステータス別の横断取得に使用 |
