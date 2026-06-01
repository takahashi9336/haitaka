# 管理画面 (admin) ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sys_users ||--o{ sys_user_friends : "user_id / friend_user_id"
    sys_users ||--o{ sys_user_group_members : "user_id"
    sys_user_groups ||--o{ sys_user_group_members : "group_id (CASCADE)"
    sys_users ||--o{ sys_improvement_items : "created_by"
    sys_apps ||--o{ sys_apps : "parent_id (自己参照)"
    sys_apps ||--o{ sys_role_apps : "app_id"
    sys_roles ||--o{ sys_role_apps : "role_id"

    sys_users {
        int id PK
        varchar id_name "ログインID (UNIQUE)"
        varchar password "bcryptハッシュ"
        varchar role "ロールキー"
        datetime created_at
        datetime updated_at
    }

    sys_apps {
        int id PK
        varchar app_key "アプリ識別子 (UNIQUE)"
        varchar name "表示名"
        int parent_id FK "親アプリID (NULL=トップレベル)"
        varchar route_prefix "ルートプレフィックス"
        varchar path "子画面のファイル名"
        varchar icon_class "FontAwesomeクラス"
        varchar theme_primary "テーマ色 (Tailwind名 or HEX)"
        varchar theme_light "テーマ薄色"
        varchar default_route "デフォルトルート"
        varchar description "説明"
        tinyint is_system "システム固定フラグ"
        int sort_order "表示順"
        tinyint is_visible "表示フラグ"
        tinyint admin_only "管理者専用フラグ"
        datetime created_at
        datetime updated_at
    }

    sys_roles {
        int id PK
        varchar role_key "ロール識別子 (UNIQUE)"
        varchar name "ロール名"
        varchar description "説明"
        varchar default_route "デフォルトルート"
        varchar logo_text "サイドバーロゴテキスト"
        varchar sidebar_mode "full / restricted"
        datetime created_at
        datetime updated_at
    }

    sys_role_apps {
        int id PK
        int role_id FK "sys_roles.id"
        int app_id FK "sys_apps.id"
        int sort_order "表示順"
        datetime created_at
    }

    sys_guides {
        int id PK
        varchar guide_key "画面識別子 (UNIQUE)"
        varchar title "ガイドタイトル"
        json blocks "ブロック配列"
        varchar app_key "紐づけアプリキー"
        tinyint show_on_first_visit "初回表示フラグ"
        int sort_order "表示順"
        datetime created_at
        datetime updated_at
    }

    sys_improvement_items {
        int id PK
        varchar screen_name "画面名"
        text content "改善事項内容"
        enum status "pending / done / cancelled"
        tinyint priority "優先度 1-5"
        varchar source_url "登録時URL"
        int created_by FK "sys_users.id"
        datetime created_at
        datetime updated_at
        datetime resolved_at "対応日"
        text memo "管理者メモ"
    }

    sys_user_friends {
        int id PK
        int user_id FK "sys_users.id (CASCADE)"
        int friend_user_id FK "sys_users.id (CASCADE)"
        int created_by "登録管理者"
        datetime created_at
    }

    sys_user_groups {
        int id PK
        varchar name "グループ名"
        int created_by "作成者"
        datetime created_at
    }

    sys_user_group_members {
        int id PK
        int group_id FK "sys_user_groups.id (CASCADE)"
        int user_id FK "sys_users.id (CASCADE)"
        datetime created_at
    }
```

## 2. テキスト管理のデータ構造（ファイルベース）

テキスト管理は DB テーブルを持たず、ファイルシステム上の `private/storage/admin_text_files/` ディレクトリで管理する。

- **メタデータ管理**: `index.json` に全エントリのメタ情報を JSON 配列で保持
- **ファイル本体**: `{id}_{slug}.{ext}` の命名規則でディレクトリ内に保存

```mermaid
erDiagram
    index_json ||--|| text_file : "filename で参照"

    index_json {
        string id "YYYYMMDDHHmmss_ランダム8桁hex"
        string title "タイトル (最大120文字)"
        string ext "txt / md / html"
        string filename "id_slug.ext"
        int size "バイト数"
        int created_by "作成者 user_id"
        datetime created_at
        datetime updated_at
    }

    text_file {
        string content "ファイル本文 (最大512KB)"
    }
```

## 3. information_schema 参照（DB ビューワ / DB 一括抽出）

DB ビューワおよび DB 一括抽出では、MySQL の `information_schema` を読み取り専用で参照する。管理画面が所有するテーブルではなく、データベース全体のメタ情報を閲覧する用途である。

- `information_schema.TABLES`: テーブル一覧取得 (`TABLE_NAME`)
- `information_schema.COLUMNS`: カラム構造取得 (`COLUMN_NAME`, `COLUMN_TYPE`, `IS_NULLABLE`, `COLUMN_KEY`, `COLUMN_DEFAULT`, `EXTRA`)
- `SHOW CREATE TABLE`: CREATE 文取得
