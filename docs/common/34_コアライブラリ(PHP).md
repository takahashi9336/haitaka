# コアライブラリ（PHP）

名前空間 `Core`、物理パスは [`private/lib/`](../../private/lib/)。エントリは [`private/bootstrap.php`](../../private/bootstrap.php)（`vendor/autoload` + `Core\Bootstrap::registerErrorHandlers()`）。

## 1. 初期化・実行環境

| クラス / ファイル | 役割 |
|-------------------|------|
| **Bootstrap** | [`private/lib/Bootstrap.php`](../../private/lib/Bootstrap.php) — `set_error_handler` / `set_exception_handler` / `register_shutdown_function`。未捕捉例外はログ出力のうえ、URI が `/api/` っぽい場合は JSON 500、それ以外は簡易 HTML 500 |
| **Database** | [`private/lib/Database.php`](../../private/lib/Database.php) — シングルトン PDO。`private/.env` から `DB_*` を読み、`utf8mb4` |
| **Logger** | [`private/lib/Logger.php`](../../private/lib/Logger.php) — `private/logs/app_YYYY-MM-DD.log` / `app_error_YYYY-MM-DD.log` |

## 2. 認証・セッション

| クラス | 役割 |
|--------|------|
| **Auth** | [`private/lib/Auth.php`](../../private/lib/Auth.php) — `SessionManager` 経由のセッション、`login` / `logout`、`requireLogin` 等。ログイン時に `RoleModel` / `AppModel` / `RoleAppModel` で `sys_apps` をツリー化し `$_SESSION['user']` に格納 |
| **SessionManager** | [`private/lib/SessionManager.php`](../../private/lib/SessionManager.php) — セッション保存ハンドラ実装 |
| **UserModel** | ユーザー行の参照・更新 |
| **RoleModel** / **RoleAppModel** | ロールとアプリ許可 |

## 3. データアクセス層

| クラス | 役割 |
|--------|------|
| **BaseModel** | [`private/lib/BaseModel.php`](../../private/lib/BaseModel.php) — PDO 注入、`user_id` 自動スコープ（`$isUserIsolated`）、`Encryption` 対象フィールドの暗号化 |
| **AppModel** | [`private/lib/AppModel.php`](../../private/lib/AppModel.php) — `sys_apps` の参照・ツリー構築（ユーザー隔離なし） |

## 4. 横断ユーティリティ

| クラス | 役割 |
|--------|------|
| **Validator** | 入力検証ヘルパ |
| **Encryption** | フィールド単位の暗号化 |
| **Utils\\DateUtil** / **Utils\\StringUtil** | 日付・文字列ユーティリティ |
| **GuideModel** | ガイドAPI用（[`guide_display.php`](../../private/components/guide_display.php) と対） |

アプリ別の Model / Controller / View は **`private/apps/{AppName}/`** に配置する（例: [`private/apps/LiveTrip/`](../../private/apps/LiveTrip/)）。
