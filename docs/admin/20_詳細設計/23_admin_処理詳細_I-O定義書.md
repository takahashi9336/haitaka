# 管理画面 (admin) 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `admin/index.php` | AdminController::index | なし | HTML (ポータル) |
| GET | `admin/users.php` | AdminController::users | なし | HTML (ユーザー一覧) |
| GET | `admin/apps.php` | AdminController::apps | なし | HTML (アプリ一覧) |
| POST | `admin/apps.php` | AdminController::apps | action, フォームデータ | リダイレクト → GET |
| GET | `admin/roles.php` | AdminController::roles | なし | HTML (ロール一覧) |
| POST | `admin/roles.php` | AdminController::roles | action, フォームデータ | リダイレクト → GET |
| GET | `db_viewer/index.php` | DbViewerController::index | ?table, ?page, ?limit | HTML (DBビューワ) |
| GET | `admin/db_export.php` | DbExportController::index | なし | HTML (ダウンロード画面) |
| GET | `admin/db_export.php?download=all_create` | DbExportController::downloadAllCreate | なし | .sql ファイル |
| GET | `admin/db_export.php?download=schema_md` | DbExportController::downloadSchemaMarkdown | なし | .md ファイル |
| GET | `admin/db_export.php?download=schema_json` | DbExportController::downloadSchemaJson | なし | .json ファイル |
| GET | `admin/db_export.php?download=all_data_csv_zip` | DbExportController::downloadAllDataCsvZip | なし | .zip ファイル |
| GET | `admin/guides.php` | GuideController::index | なし | HTML (ガイド一覧) |
| GET | `admin/guides.php?new=1` | GuideController::edit(null) | なし | HTML (ガイド新規) |
| GET | `admin/guides.php?id=N` | GuideController::edit(N) | id (int) | HTML (ガイド編集) |
| POST | `admin/guides.php` | GuideController::edit / delete | action, フォームデータ | リダイレクト → 一覧 |
| POST | `admin/api/guide_image_upload.php` | (直接処理) | file (multipart), guide_id | JSON |
| GET | `admin/improvement_list.php` | ImprovementController::index | ?status, ?screen_name | HTML (対応管理一覧) |
| POST | `admin/improvement_list.php` | ImprovementController::create/update/delete | action, フォームデータ | リダイレクト → 一覧 |
| POST | `admin/api/save_improvement_item.php` | (直接処理) | JSON body | JSON |
| GET | `admin/friends.php` | AdminController::friends | なし | HTML (友達一覧) |
| POST | `admin/friends.php` | AdminController::friends | action, フォームデータ | リダイレクト → GET |
| GET | `admin/friend_groups.php` | AdminController::friendGroups | ?edit=N | HTML (グループ一覧/編集) |
| POST | `admin/friend_groups.php` | AdminController::friendGroups | action, フォームデータ | リダイレクト → GET |
| GET | `admin/text_files.php` | AdminController::textFiles | なし | HTML (テキスト管理 SPA) |
| POST | `admin/api/text_files_list.php` | (直接処理) | なし | JSON |
| POST | `admin/api/text_files_get.php` | (直接処理) | JSON {id} | JSON |
| POST | `admin/api/text_files_save.php` | (直接処理) | JSON {id?, title, ext, content, content_b64?} | JSON |
| POST | `admin/api/text_files_delete.php` | (直接処理) | JSON {id} | JSON |

## 2. 処理フロー詳細

### ポータル表示 (admin/index.php → AdminController::index)
1. **認可**: `Auth::requireAdmin()` で admin ロール確認。非 admin は 403 リダイレクト
2. **データ取得**: `$_SESSION['user']['apps']` から `app_key = 'admin'` のテーマ色を取得 (DB アクセスなし)
3. **レスポンス**: `portal.php` をレンダリング。テーマ色が HEX の場合はインライン CSS、Tailwind 名の場合は動的クラスを適用

### アプリ作成 (admin/apps.php POST action=create)
1. **リクエスト受け取り**: フォームデータ (`application/x-www-form-urlencoded`) から `app_key`, `name`, `parent_id`, `route_prefix`, `path`, `icon_class`, `theme_primary`, `theme_light`, `default_route`, `description`, `is_system`, `sort_order`, `is_visible`, `admin_only` を取得
2. **バリデーション**: 空文字は `NULL` に正規化 (`$nullIfEmpty` クロージャ)。親がない場合は `path` を `NULL` に寄せる
3. **ビジネスロジック・DB更新**:
   - `AppModel::create($data)` で `sys_apps` に INSERT
   - 子画面の場合 (`parent_id` あり)、`RoleAppModel::grantToRolesWithParent()` で restricted ロールへ自動許可
   - `SessionManager::invalidateAllSessions()` で全ユーザーセッション破棄
4. **エラーハンドリング**: `PDOException` を catch。`uk_app_key` 重複はユーザー向けメッセージに変換し `$_SESSION['admin_error']` に格納
5. **レスポンス**: `Location: /admin/apps.php` へリダイレクト (PRG)

### アプリ更新 (admin/apps.php POST action=update)
1. **リクエスト受け取り**: フォームデータから `id` + 作成時と同じフィールドを取得
2. **ビジネスロジック・DB更新**: `AppModel::update($id, $data)` + セッション全破棄
3. **レスポンス**: リダイレクト

### アプリ削除 (admin/apps.php POST action=delete)
1. **リクエスト受け取り**: `id` (int)
2. **バリデーション**:
   - `AppModel::findById($id)` で存在確認
   - `is_system = 1` → エラー「システム固定のアプリは削除できません」
   - `AppModel::hasChildren($id)` → エラー「子画面があるアプリは先に子を削除してください」
3. **ビジネスロジック・DB更新**: `AppModel::delete($id)` + セッション全破棄
4. **レスポンス**: リダイレクト

### ロール作成 (admin/roles.php POST action=create)
1. **リクエスト受け取り**: `role_key`, `name`, `description`, `default_route`, `logo_text`, `sidebar_mode`
2. **ビジネスロジック・DB更新**:
   - `RoleModel::create($data)` で `sys_roles` に INSERT
   - `sidebar_mode = 'restricted'` かつ `app_ids` 配列ありの場合、`RoleAppModel::setForRole($newId, $appIds)`
   - セッション全破棄
3. **レスポンス**: リダイレクト

### ロール更新 (admin/roles.php POST action=update)
1. **リクエスト受け取り**: `id` + 作成時と同じフィールド + `app_ids[]`
2. **ビジネスロジック・DB更新**:
   - `RoleModel::update($id, $data)`
   - `sidebar_mode = 'restricted'` の場合のみ `RoleAppModel::setForRole($roleId, $appIds)`、それ以外は空配列で全削除
   - セッション全破棄
3. **レスポンス**: リダイレクト

### ロール削除 (admin/roles.php POST action=delete)
1. **リクエスト受け取り**: `id` (int)
2. **ビジネスロジック・DB更新**: `RoleModel::delete($id)` + セッション全破棄
3. **レスポンス**: リダイレクト

### DB ビューワ表示 (db_viewer/index.php → DbViewerController::index)
1. **認可**: `Auth::requireAdmin()`
2. **データ取得**:
   - `DbSchemaTrait::getTableList()` で `information_schema.TABLES` からテーブル一覧
   - `$_GET['table']` でテーブル選択時: `getColumns()`, `getTableStructure()`, `getCreateTable()`, `getCount()`, `getRows()` を実行
   - ページネーション: `limit` パラメータ (50/100/250/500/all)、`page` パラメータ。`all` の場合は上限 10,000 行
3. **レスポンス**: `db_viewer.php` をレンダリング

### DB 一括抽出 ダウンロード (admin/db_export.php?download=xxx)
1. **認可**: `Auth::requireAdmin()`
2. **分岐**: `$_GET['download']` の値により処理を切替
   - `all_create`: 全テーブルの CREATE TABLE 文を連結し `.sql` として出力。ヘッダーにテーブル数・取得日時のコメント付き
   - `schema_md`: テーブルごとのカラム一覧を Markdown テーブル形式で `.md` として出力
   - `schema_json`: テーブル名・カラム情報・CREATE 文を含む JSON を `.json` として出力
   - `all_data_csv_zip`: 全テーブルの全行データをテーブルごとに CSV 化し、ZIP にまとめて出力。CSV は UTF-8 BOM 付き。一時ファイルは `finally` ブロックで確実に削除
3. **エラーハンドリング**: ZipArchive 未対応時は 500 エラー + テキストメッセージ

### ガイド作成/更新 (admin/guides.php POST)
1. **リクエスト受け取り**: `guide_key`, `title`, `app_key`, `show_on_first_visit`, `blocks_json` (JSON 文字列)
2. **バリデーション**: `guide_key` と `title` は必須。`blocks_json` を `json_decode` で配列化 (失敗時は空配列)
3. **ビジネスロジック・DB更新**:
   - `$id` ありかつ既存レコードあり → `GuideModel::updateGuide($id, $data)`
   - それ以外 → `GuideModel::createGuide($data)`
   - Model 内で `blocks` 配列を `json_encode` して DB に保存
4. **レスポンス**: 成功時は `$_SESSION['guide_success']` にメッセージを設定しリダイレクト

### ガイド画像アップロード (admin/api/guide_image_upload.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **バリデーション**:
   - `$_FILES['file']` のアップロードエラーチェック
   - MIME タイプ判定: `image/jpeg`, `image/png`, `image/webp`, `image/gif` のみ許可
   - ファイルサイズ: 最大 5MB
3. **ビジネスロジック**:
   - ファイル名: `guide_{id}_{timestamp}.{ext}` (新規ガイドの場合は `guide_new_{random}.{ext}`)
   - 保存先: `www/uploads/guides/`
4. **レスポンス**: `{"status":"success","url":"/uploads/guides/filename.ext"}`

### 改善事項 新規追加 (admin/improvement_list.php POST action=create)
1. **リクエスト受け取り**: `screen_name`, `content`, `source_url`, `priority`
2. **バリデーション**: `screen_name` と `content` は必須
3. **ビジネスロジック・DB更新**: `ImprovementItemModel::createItem($data)` で INSERT。`created_by` はセッションの `user_id` を自動設定
4. **レスポンス**: リダイレクト

### 改善事項 更新 (admin/improvement_list.php POST action=update)
1. **リクエスト受け取り**: `id`, `screen_name`, `content`, `status`, `priority`, `memo`
2. **バリデーション**: `screen_name` と `content` は必須、`status` は enum 値チェック
3. **ビジネスロジック・DB更新**:
   - `ImprovementItemModel::update($id, $data)`
   - `status = 'done'` の場合、`resolved_at` に現在日時を設定
   - `status != 'done'` の場合、`resolved_at` を `NULL` にクリア
4. **レスポンス**: フィルタパラメータを保持してリダイレクト

### 改善事項 FAB 登録 (admin/api/save_improvement_item.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **リクエスト受け取り**: JSON body (`Content-Type: application/json`) から `screen_name`, `content`, `source_url`
3. **バリデーション**: `content` は必須。`screen_name` 未入力時は `'不明'` に設定
4. **ビジネスロジック・DB更新**: `ImprovementItemModel::createItem()` で INSERT。`status` は `pending` 固定
5. **レスポンス**: `{"status":"success"}` / エラー時は `{"status":"error","message":"..."}`

### 友達ペア登録 (admin/friends.php POST action=add_friend)
1. **リクエスト受け取り**: `user_id_a`, `user_id_b` (int)
2. **バリデーション**:
   - 両方の値が必要
   - 同一ユーザーは拒否
   - `friendPairExists()` で既存チェック
3. **ビジネスロジック・DB更新**: `FriendGroupAdminModel::addFriend($userIdA, $userIdB, $currentUserId)` で INSERT。内部で `min/max` により `user_id < friend_user_id` に正規化。`INSERT IGNORE` で競合回避
4. **レスポンス**: リダイレクト

### グループ作成 (admin/friend_groups.php POST action=create_group)
1. **リクエスト受け取り**: `group_name`, `member_ids[]`
2. **バリデーション**: グループ名は必須
3. **ビジネスロジック・DB更新**:
   - `FriendGroupAdminModel::createGroup($name, $userId)` で `sys_user_groups` に INSERT
   - `setGroupMembers($gid, $memberIds)` でメンバーを一括追加
4. **レスポンス**: リダイレクト

### グループ更新 (admin/friend_groups.php POST action=update_group)
1. **リクエスト受け取り**: `group_id`, `group_name`, `member_ids[]`
2. **バリデーション**: グループ名は必須
3. **ビジネスロジック・DB更新**:
   - `updateGroupName($groupId, $name)` でグループ名更新
   - `setGroupMembers($groupId, $memberIds)` でメンバーを DELETE → 再 INSERT (トランザクション)
4. **レスポンス**: リダイレクト

### グループ削除 (admin/friend_groups.php POST action=delete_group)
1. **リクエスト受け取り**: `group_id` (int)
2. **ビジネスロジック・DB更新**: `deleteGroup($groupId)` で `sys_user_groups` から DELETE。CASCADE により `sys_user_group_members` も連動削除
3. **レスポンス**: リダイレクト

### テキスト一覧取得 (admin/api/text_files_list.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **ビジネスロジック**: `TextFileAdminStorage::list()` で `index.json` を読み取り、`updated_at` 降順でソート。`content` フィールドは除外して返却
3. **レスポンス**: `{"status":"success","items":[...]}`

### テキスト取得 (admin/api/text_files_get.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **リクエスト受け取り**: JSON body `{id}`
3. **バリデーション**: `id` は必須
4. **ビジネスロジック**: `TextFileAdminStorage::get($id)` で `index.json` からメタ情報を検索し、対応するファイルの内容を読み込み
5. **レスポンス**: `{"status":"success","item":{...}}` / 404: `{"status":"error","message":"見つかりません"}`

### テキスト保存 (admin/api/text_files_save.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **リクエスト受け取り**: JSON body `{id?, title, ext, content, content_b64?}`
3. **バリデーション** (TextFileAdminStorage::save 内):
   - `title`: 必須、最大120文字
   - `ext`: `txt` / `md` / `html` のみ
   - `content`: 最大 512KB
   - `html` 形式で `content_b64` がある場合は Base64 デコードして `content` に設定
4. **ビジネスロジック**:
   - `id` 空の場合: 新規 ID 生成 (`YYYYMMDDHHmmss_random`)
   - ファイル名: `{id}_{slug}.{ext}`
   - `flock(LOCK_EX)` で排他ロック取得
   - ファイル名が変わった場合は旧ファイルを削除
   - ファイル本体を `file_put_contents` で保存
   - `index.json` を更新
5. **レスポンス**: `{"status":"success","id":"..."}`

### テキスト削除 (admin/api/text_files_delete.php)
1. **認可**: `Auth::check()` + `role === 'admin'`
2. **リクエスト受け取り**: JSON body `{id}`
3. **バリデーション**: `id` は必須
4. **ビジネスロジック**:
   - `flock(LOCK_EX)` で排他ロック
   - `index.json` から該当エントリを除去
   - 対応するファイル本体を削除
5. **レスポンス**: `{"status":"success"}` / 404: `{"status":"error","message":"見つかりません"}`

## 3. 共通処理パターン

### 認可
- 画面系エンドポイント: `Auth::requireAdmin()` -- admin ロール以外は 403 リダイレクト
- API 系エンドポイント: `Auth::check()` で認証確認 (401) → `$_SESSION['user']['role'] === 'admin'` で認可確認 (403)。レスポンスは JSON

### PRG (Post/Redirect/Get) パターン
- アプリ管理、ロール管理、ガイド管理、対応管理、友達管理、グループ管理のすべての POST 処理は、処理後に `header('Location: ...')` でリダイレクトする
- フラッシュメッセージ: `$_SESSION['admin_success']`, `$_SESSION['admin_error']`, `$_SESSION['guide_success']`, `$_SESSION['improvement_success']` 等のセッション変数を利用

### セッション破棄
- アプリ管理・ロール管理の作成・更新・削除後に `SessionManager::invalidateAllSessions()` を呼び出し、全ユーザーのセッションを強制破棄する
- これにより権限変更が即座に反映される (次回リクエスト時に再ログインが必要)

### テーブル名のサニタイズ
- DB ビューワ / DB 一括抽出で使用するテーブル名は `StringUtil::sanitizeIdentifier()` でサニタイズし、バッククォートでエスケープしてから SQL に使用する
