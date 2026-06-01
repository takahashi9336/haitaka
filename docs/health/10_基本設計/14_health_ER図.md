# Health（ヘルスケア/健康管理） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sys_users ||--o{ hl_kitchen_stock_items : "has many"
    sys_users ||--o{ hl_training_menu_items : "has many"
    sys_users ||--o{ hl_training_logs : "has many"
    hl_training_menu_items ||--o{ hl_training_logs : "referenced by (SET NULL on delete)"

    sys_users {
        int id PK
        varchar id_name
    }

    hl_kitchen_stock_items {
        bigint_unsigned id PK
        int user_id FK "sys_users.id / CASCADE"
        varchar(255) name "食材名"
        varchar(32) item_group "food / seasoning / other"
        varchar(100) qty "数量（自由入力）"
        date purchased_date "購入日"
        tinyint is_frozen "冷凍フラグ 0|1"
        datetime created_at
        datetime updated_at
    }

    hl_training_menu_items {
        bigint_unsigned id PK
        int user_id FK "sys_users.id / CASCADE"
        varchar(255) name "メニュー名"
        int_unsigned reps "回数"
        int_unsigned duration_sec "1セットあたり秒数"
        datetime created_at
        datetime updated_at
    }

    hl_training_logs {
        bigint_unsigned id PK
        int user_id FK "sys_users.id / CASCADE"
        varchar(20) log_kind "exercise | hiit"
        bigint_unsigned menu_item_id FK "SET NULL on delete"
        varchar(255) menu_name "スナップショット"
        int_unsigned reps "スナップショット"
        int_unsigned duration_sec "スナップショット（秒）"
        json menu_snapshot "HIIT時の種目一覧"
        date performed_at "実施日"
        datetime created_at
        datetime updated_at
    }
```

## 2. インデックス一覧

| テーブル | インデックス名 | カラム | 備考 |
| :--- | :--- | :--- | :--- |
| `hl_kitchen_stock_items` | PRIMARY | `id` | |
| `hl_kitchen_stock_items` | `idx_user_purchased_date` | `user_id, purchased_date` | 一覧取得用 |
| `hl_kitchen_stock_items` | `idx_user_created_at` | `user_id, created_at` | |
| `hl_kitchen_stock_items` | `idx_user_group_purchased_date` | `user_id, item_group, purchased_date` | グループフィルタ用 |
| `hl_training_menu_items` | PRIMARY | `id` | |
| `hl_training_menu_items` | `idx_user_id` | `user_id` | |
| `hl_training_menu_items` | `idx_user_created_at` | `user_id, created_at` | |
| `hl_training_logs` | PRIMARY | `id` | |
| `hl_training_logs` | `idx_user_performed_at` | `user_id, performed_at` | 期間検索用 |
| `hl_training_logs` | `idx_user_menu_item` | `user_id, menu_item_id` | |

## 3. 外部キー制約

| テーブル | 制約名 | 参照先 | ON DELETE |
| :--- | :--- | :--- | :--- |
| `hl_kitchen_stock_items` | `fk_hl_kitchen_stock_items_user` | `sys_users(id)` | CASCADE |
| `hl_training_menu_items` | `fk_hl_training_menu_items_user` | `sys_users(id)` | CASCADE |
| `hl_training_logs` | `fk_hl_training_logs_user` | `sys_users(id)` | CASCADE |
| `hl_training_logs` | `fk_hl_training_logs_menu` | `hl_training_menu_items(id)` | SET NULL |

## 4. マイグレーション適用順

| ファイル名 | 内容 |
| :--- | :--- |
| `create_hl_kitchen_stock_items.sql` | `hl_kitchen_stock_items` テーブル作成 |
| `add_health_to_sys_apps.sql` | `sys_apps` に health / health_kitchen_stock を登録 |
| `add_hl_kitchen_stock_items_item_group.sql` | `item_group` カラム追加 |
| `create_hl_training_menu_items_and_sys_app.sql` | `hl_training_menu_items` テーブル作成 + sys_apps 登録 |
| `add_hl_training_menu_items_duration_sec.sql` | `duration_sec` カラム追加 |
| `create_hl_training_logs_and_sys_app.sql` | `hl_training_logs` テーブル作成 + sys_apps 登録 |
| `add_hl_training_logs_hiit_session.sql` | `log_kind`, `menu_snapshot` カラム追加 |
