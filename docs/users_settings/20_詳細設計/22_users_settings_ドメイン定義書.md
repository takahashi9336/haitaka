# ユーザー設定（users_settings） ドメイン・データモデル定義書

## 1. テーブル定義詳細

本機能は専用テーブルを持たず、システム共通テーブルを操作する。以下に本機能が参照・更新するテーブルの定義を記載する。

### sys_users
`Core\UserModel`（`private/lib/UserModel.php`）が操作するユーザーマスタテーブル。`BaseModel` を継承し、`isUserIsolated` はデフォルト（`true`）だが、UserModel 内のメソッドは独自 SQL を使用しユーザー隔離を適用しない（全ユーザー横断で操作）。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ユーザーID | int | PK, Auto Inc | |
| id_name | ログインID | varchar | UNIQUE, NOT NULL | ログイン時の識別子 |
| password | パスワード | varchar | NOT NULL | `password_hash()` による bcrypt ハッシュ |
| role | ロール | varchar | NOT NULL | `sys_roles.role_key` を格納（例: `admin`, `user`, `hinata`） |
| created_at | 作成日時 | datetime | | `NOW()` で自動設定 |
| updated_at | 更新日時 | datetime | | パスワード変更・ロール変更時に `NOW()` で更新 |

### sys_roles
`Core\RoleModel`（`private/lib/RoleModel.php`）が操作するロールマスタテーブル。本機能ではロール変更時の存在確認（`getByRoleKey()`）でのみ参照する。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ロールID | int | PK, Auto Inc | |
| role_key | ロールキー | varchar | UNIQUE | `admin`, `user`, `hinata` 等 |
| name | 表示名 | varchar | | 「管理者」「一般ユーザー」等 |
| description | 説明 | varchar | | ロールの用途説明 |
| default_route | デフォルトルート | varchar | | ログイン後のリダイレクト先。初期値 `/index.php` |
| logo_text | ロゴテキスト | varchar | | サイドバーロゴの表示テキスト |
| sidebar_mode | サイドバーモード | varchar | | `full` または `restricted` |
| created_at | 作成日時 | datetime | | |
| updated_at | 更新日時 | datetime | | |

### sys_sessions
`Core\SessionManager`（`private/lib/SessionManager.php`）が管理するセッションテーブル。PHP のカスタムセッションハンドラとして `SessionHandlerInterface` を実装する。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | セッションID | varchar | PK | PHP が発行するセッションID |
| user_id | ユーザーID | int | | `sys_users.id` への論理参照。未ログイン時は NULL |
| data | セッションデータ | text | | PHP のシリアライズされたセッションデータ |
| ip_address | IPアドレス | varchar | | `$_SERVER['REMOTE_ADDR']` |
| user_agent | ユーザーエージェント | varchar | | `$_SERVER['HTTP_USER_AGENT']` |
| last_activity | 最終アクティビティ | datetime | | `CURRENT_TIMESTAMP` で自動更新 |

## 2. ステータス・区分値定義 (マジックナンバー)

### sys_users.role（ロールキー）
本機能で扱うロール値。`sys_roles` テーブルに定義されたレコードの `role_key` と一致する必要がある。

| 値 | 意味 | 備考 |
| :--- | :--- | :--- |
| `admin` | 管理者 | 全アプリ＋管理画面へのアクセス権。ユーザー作成API でこのロールの新規作成は禁止 |
| `user` | 一般ユーザー | 管理画面以外の全アプリへのアクセス権 |
| `hinata` | 日向坂のみ | 日向坂ポータルとその子画面のみ（`sidebar_mode=restricted`） |
| `hinata_admin` | 日向坂管理者 | 日向坂ポータルの管理者権限 |
| `movie` | 映画のみ | 映画機能のみのアクセス権 |
| `hinata_movie` | 日向坂＋映画 | 日向坂ポータルと映画機能へのアクセス権 |

### sys_roles.sidebar_mode
| 値 | 意味 |
| :--- | :--- |
| `full` | 全アプリをサイドバーに表示（`admin`, `user` 等） |
| `restricted` | `sys_role_apps` で許可されたアプリのみサイドバーに表示 |
