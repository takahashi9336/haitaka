# ユーザー設定（users_settings） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧
| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `users_settings/index.php` | `SettingsController::index()` | なし | HTML |
| POST | `users_settings/api/update_self.php` | `SettingsController::updateSelf()` | JSON (`current_password`, `new_password`) | JSON |
| POST | `users_settings/api/create_user.php` | `SettingsController::createUser()` | JSON (`id_name`, `password`, `role`) | JSON |
| POST | `users_settings/api/admin_reset.php` | `SettingsController::adminReset()` | JSON (`target_id`, `new_password`) | JSON |
| POST | `users_settings/api/admin_update_role.php` | `SettingsController::adminUpdateRole()` | JSON (`target_id`, `role`) | JSON |
| GET | `users_settings/api/ping.php` | インライン処理 | なし | テキスト ("OK") |

## 2. 処理フロー詳細

### index.php（設定画面表示）
1. **リクエスト受け取り・バリデーション**:
   - `bootstrap.php` を読み込み、オートローダーとエラーハンドラを初期化する。
   - `SettingsController::index()` を呼び出す。
2. **ビジネスロジック**:
   - `Auth::requireLogin()` でログイン状態を確認する。未ログインの場合は `/login.php` へリダイレクトして処理を終了する。
   - `$_SESSION['user']` からログインユーザー情報を取得する。
3. **レスポンス**:
   - `private/apps/Settings/Views/index.php` を `require_once` して HTML を描画する。

### update_self.php（自分のパスワード変更）
1. **リクエスト受け取り・バリデーション**:
   - `REQUEST_METHOD` が `POST` であることを確認する（POST 以外は無応答）。
   - `Auth::check()` でセッション有効性を確認する。無効の場合は `{"status":"error","message":"セッション切れ"}` を返す。
   - リクエストボディから JSON を読み取り、`current_password` と `new_password` を取得する。
   - いずれかが空の場合は `{"status":"error","message":"入力不足"}` を返す。
2. **ビジネスロジック・DB更新**:
   - `$_SESSION['user']['id']` からユーザーIDを取得する。
   - `UserModel::findById()` で現在のユーザーレコードを取得する。
   - `password_verify()` で `current_password` と DB 上のハッシュを照合する。不一致の場合は `{"status":"error","message":"現在のパスワードが違います"}` を返す。
   - `password_hash($newPass, PASSWORD_DEFAULT)` で新パスワードの bcrypt ハッシュを生成する。
   - `UserModel::updatePassword()` で `sys_users.password` と `updated_at` を更新する。
3. **レスポンス**:
   - 成功: `{"status":"success"}`
   - 例外発生時: HTTP 500 + `{"status":"error","message":"[例外メッセージ]"}`

### create_user.php（ユーザー新規作成 - 管理者専用）
1. **リクエスト受け取り・バリデーション**:
   - `REQUEST_METHOD` が `POST` であることを確認する。
   - `Auth::check()` + `Auth::isAdmin()` で管理者権限を確認する。非管理者の場合は `{"status":"error","message":"権限なし"}` を返す。
   - リクエストボディから `id_name`、`password`、`role`（デフォルト `user`）を取得する。
   - `id_name` または `password` が空の場合は `{"status":"error","message":"ID/パスワード必須"}` を返す。
   - `role` が `admin` の場合は `{"status":"error","message":"Admin作成禁止"}` を返す。
2. **ビジネスロジック・DB更新**:
   - `UserModel::findByIdName()` で同一 `id_name` の既存ユーザーを確認する。存在する場合は `{"status":"error","message":"このユーザーIDは既に登録されています"}` を返す。
   - `password_hash()` で bcrypt ハッシュを生成する。
   - `UserModel::createUser()` で `sys_users` に INSERT する（`id_name`, `password`, `role`, `created_at`, `updated_at`）。
3. **レスポンス**:
   - 成功: `{"status":"success"}`
   - 失敗: `{"status":"error","message":"作成失敗"}`

### admin_reset.php（パスワードリセット - 管理者専用）
1. **リクエスト受け取り・バリデーション**:
   - `REQUEST_METHOD` が `POST` であることを確認する。
   - `Auth::check()` + `Auth::isAdmin()` で管理者権限を確認する。非管理者の場合は `{"status":"error","message":"権限なし"}` を返す。
   - リクエストボディから `target_id`、`new_password` を取得する。
   - いずれかが空の場合は `{"status":"error","message":"入力不足"}` を返す。
2. **ビジネスロジック・DB更新**:
   - `password_hash()` で bcrypt ハッシュを生成する。
   - `UserModel::updatePassword()` で対象ユーザーの `sys_users.password` と `updated_at` を更新する。
3. **レスポンス**:
   - 成功: `{"status":"success"}`

### admin_update_role.php（ロール変更 - 管理者専用）
1. **リクエスト受け取り・バリデーション**:
   - `REQUEST_METHOD` が `POST` であることを確認する。
   - `Auth::check()` + `Auth::isAdmin()` で管理者権限を確認する。非管理者の場合は `{"status":"error","message":"権限なし"}` を返す。
   - リクエストボディから `target_id`（int キャスト）と `role`（trim 処理）を取得する。
   - いずれかが空の場合は `{"status":"error","message":"入力不足"}` を返す。
2. **ビジネスロジック・DB更新**:
   - `RoleModel::getByRoleKey()` で指定ロールの存在を確認する。存在しない場合は `{"status":"error","message":"存在しないロールです"}` を返す。
   - `UserModel::findById()` で対象ユーザーの存在を確認する。存在しない場合は `{"status":"error","message":"ユーザーが見つかりません"}` を返す。
   - 対象ユーザーが操作者自身の場合は `{"status":"error","message":"自分自身のロールはここでは変更できません"}` を返す。
   - `UserModel::updateRole()` で `sys_users.role` と `updated_at` を更新する。
   - `SessionManager::invalidateSessionsForUser()` で対象ユーザーのセッションを全て削除する（`sys_sessions` から `user_id` 一致レコードを DELETE）。
3. **レスポンス**:
   - 成功: `{"status":"success"}`

### ping.php（ヘルスチェック）
1. **処理**: `echo "OK"` のみ。認証・バリデーションなし。
2. **レスポンス**: テキスト "OK"

## 3. 共通エラーハンドリング
- `bootstrap.php` により `Core\Bootstrap::registerErrorHandlers()` が有効化される。
- API エンドポイント（URI に `/api/` を含む）で未捕捉例外が発生した場合: HTTP 500 + `{"status":"error","message":"サーバーエラーが発生しました"}`
- `update_self.php` のみ `try-catch` による個別エラーハンドリングを実装しており、例外メッセージをレスポンスに含める。
- `update_self.php` は `display_errors=0`, `log_errors=1` を明示的に設定している。

## 4. 認証・認可マトリクス
| エンドポイント | ログイン必須 | 管理者必須 | 自身のみ操作可 |
| :--- | :--- | :--- | :--- |
| index.php | はい | いいえ | - |
| update_self.php | はい | いいえ | はい（自分のパスワードのみ） |
| create_user.php | はい | はい | - |
| admin_reset.php | はい | はい | - |
| admin_update_role.php | はい | はい | いいえ（自分自身の変更は拒否） |
| ping.php | いいえ | いいえ | - |
