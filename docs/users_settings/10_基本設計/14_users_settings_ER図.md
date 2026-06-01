# ユーザー設定（users_settings） ER図

## 1. データモデル関係図

本機能はユーザー設定専用のテーブルを持たず、システム共通テーブル（`sys_users`、`sys_roles`、`sys_sessions`）を操作する。

```mermaid
erDiagram
    sys_users ||--o{ sys_sessions : "セッション保持"
    sys_roles ||--o{ sys_users : "ロール割当"

    sys_users {
        int id PK "AUTO_INCREMENT"
        varchar id_name "ログインID (UNIQUE)"
        varchar password "bcryptハッシュ"
        varchar role "ロールキー (sys_roles.role_key参照)"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }

    sys_roles {
        int id PK "AUTO_INCREMENT"
        varchar role_key "ロールキー (UNIQUE)"
        varchar name "表示名"
        varchar description "説明"
        varchar default_route "デフォルトルート"
        varchar logo_text "ロゴテキスト"
        varchar sidebar_mode "full または restricted"
        datetime created_at "作成日時"
        datetime updated_at "更新日時"
    }

    sys_sessions {
        varchar id PK "セッションID"
        int user_id "ユーザーID (sys_users.id参照)"
        text data "セッションデータ"
        varchar ip_address "IPアドレス"
        varchar user_agent "ユーザーエージェント"
        datetime last_activity "最終アクティビティ"
    }
```

## 2. 補足
- `sys_users.role` は `sys_roles.role_key` を文字列で格納する設計（外部キー制約ではなくアプリケーション層で整合性を担保）。
- `sys_sessions` はPHPのカスタムセッションハンドラ（`Core\SessionManager`）が管理する。`user_id` カラムにより特定ユーザーのセッション一括無効化が可能。
- `sys_users` と `sys_roles` の関係は `role` カラム（文字列）を介した論理的な参照であり、RDB上の外部キー制約は設定されていない。
