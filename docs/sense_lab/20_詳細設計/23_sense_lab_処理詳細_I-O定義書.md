# Sense Lab（センスラボ） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `sense_lab/index.php` | `SenseLabController::index` | `?category=`（任意） | HTML |
| GET | `sense_lab/new.php` | `SenseLabController::new` | なし | HTML |
| POST | `sense_lab/create.php` | `SenseLabController::create` | フォーム（multipart/form-data） | リダイレクト |
| GET | `sense_lab/show.php` | `SenseLabController::show` | `?id=` | HTML |
| GET | `sense_lab/edit.php` | `SenseLabController::edit` | `?id=` | HTML |
| POST | `sense_lab/update.php` | `SenseLabController::update` | フォーム（multipart/form-data） | リダイレクト |
| POST | `sense_lab/delete.php` | `SenseLabController::delete` | フォーム（`id`） | リダイレクト |
| POST | `sense_lab/api/quick_save.php` | 直接処理（Controllerなし） | JSON または multipart/form-data | JSON |
| GET | `sense_lab/quick_edit.php` | `SenseLabController::quickEdit` | `?id=` | HTML |
| POST | `sense_lab/quick_update.php` | `SenseLabController::quickUpdate` | フォーム（multipart/form-data） | リダイレクト |

## 2. 処理フロー詳細

### index.php（スクラップ一覧表示）
1. **認証**: `Auth::requireAdmin()` で管理者権限を確認
2. **データ取得**:
   - `SenseEntryModel::getList($userId, $category)` で本番スクラップ一覧を取得（`created_at DESC`）
   - `SenseEntryModel::getStats($userId)` でカテゴリ別集計を取得（`GROUP BY category`）
   - `SenseQuickEntryModel::getListByUser($userId)` でクイックスクラップ一覧を取得（`created_at DESC`）
3. **レスポンス**: `Views/index.php` をレンダリング（変数: `$entries`, `$stats`, `$quickEntries`, `$user`）

### new.php（スクラップ新規登録画面表示）
1. **認証**: `Auth::requireAdmin()`
2. **セッション確認**: `$_SESSION['sense_lab_errors']` からバリデーションエラーを取得し、即座に `unset`
3. **レスポンス**: `Views/new.php` をレンダリング（変数: `$user`, `$errors`）

### create.php（スクラップ新規登録処理）
1. **認証**: `Auth::requireAdmin()`
2. **リクエスト受け取り・バリデーション**:
   - `$_POST` から `title`（必須）、`category`（デフォルト `other`）、`reason_1` 〜 `reason_3`（最低1つ必須）を取得
   - `$_FILES['image']` から画像ファイルを取得（任意）
   - 画像バリデーション: サイズ上限 2MB、MIME制限（`image/jpeg`, `image/png`, `image/gif`）
3. **画像保存**:
   - ファイル名: `{yyyymmdd_HHiiss}_{8桁hex}.{拡張子}`
   - 保存先: `www/uploads/sense_lab/`
   - `move_uploaded_file()` で保存
4. **DB登録**: `SenseEntryModel::create()` で `sl_sense_entries` にINSERT
5. **レスポンス**:
   - 成功: `/sense_lab/` にリダイレクト
   - バリデーションエラー: `$_SESSION['sense_lab_errors']` にエラーをセットし `/sense_lab/new.php` にリダイレクト

### show.php（スクラップ詳細表示）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseEntryModel::findByIdAndUser($id, $userId)` で該当エントリを取得
3. **存在チェック**: エントリが見つからない場合は 404 レスポンス
4. **レスポンス**: `Views/show.php` をレンダリング（変数: `$entry`, `$user`）

### edit.php（スクラップ編集画面表示）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseEntryModel::findByIdAndUser($id, $userId)`
3. **存在チェック**: エントリが見つからない場合は 404 レスポンス
4. **セッション確認**: `$_SESSION['sense_lab_errors']` からバリデーションエラーを取得
5. **レスポンス**: `Views/edit.php` をレンダリング（変数: `$entry`, `$user`, `$errors`）

### update.php（スクラップ更新処理）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseEntryModel::findByIdAndUser($id, $userId)` で既存エントリを取得
3. **存在チェック**: エントリが見つからない場合は 404 レスポンス
4. **リクエスト受け取り・バリデーション**:
   - `$_POST` から `title`（必須）、`category`、`reason_1` 〜 `reason_3`（最低1つ必須）
   - `$_FILES['image']` から新画像（任意、未選択時は既存画像を維持）
   - 画像バリデーション: create と同一
5. **DB更新**: `SenseEntryModel::update($id, $userId, $data)` で `sl_sense_entries` をUPDATE
6. **レスポンス**:
   - 成功: `/sense_lab/show.php?id={id}` にリダイレクト
   - バリデーションエラー: `$_SESSION['sense_lab_errors']` にエラーをセットし `/sense_lab/edit.php?id={id}` にリダイレクト

### delete.php（スクラップ削除処理）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseEntryModel::findByIdAndUser($id, $userId)` で既存エントリを取得
3. **存在チェック**: エントリが見つからない場合は何もしない（静かにスキップ）
4. **DB削除**: `SenseEntryModel::delete($id, $userId)` で `sl_sense_entries` からDELETE
5. **画像削除**: `$entry['image_path']` が存在する場合、物理ファイルを `@unlink()` で削除
6. **レスポンス**: `/sense_lab/` にリダイレクト

### api/quick_save.php（クイックスクラップAPI保存）
1. **認証**: `Auth::requireAdmin()` + セッションからユーザーID取得
2. **メソッドチェック**: POST以外は 405 エラー（JSON）
3. **リクエスト受け取り**:
   - `$_FILES` がある場合: `$_POST` + `$_FILES['image']` からデータ取得（FormData形式）
   - `$_FILES` がない場合: `php://input` からJSONデコード、フォールバックとして `$_POST`
   - `note`（必須）、`app_key`、`page_title`、`source_url`、`category_hint`（すべて任意、空文字はNULLに変換）
4. **画像処理**: `$_FILES['image']` が存在する場合:
   - MIME検証（`image/jpeg`, `image/png`, `image/gif`）
   - サイズ上限 2MB
   - ファイル名生成・保存（create.phpと同一ロジック）
5. **DB登録**: `SenseQuickEntryModel::create()` で `sl_sense_quick_entries` にINSERT
6. **レスポンス**: `{"status": "success", "quick_entry_id": {id}}` または `{"status": "error", "message": "..."}`

### quick_edit.php（クイックスクラップ編集画面表示）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseQuickEntryModel::findByIdAndUser($id, $userId)`
3. **存在チェック**: エントリが見つからない場合は 404 レスポンス
4. **セッション確認**: `$_SESSION['sense_lab_errors']` からバリデーションエラーを取得
5. **レスポンス**: `Views/quick_edit.php` をレンダリング（変数: `$quick`, `$user`, `$errors`）

### quick_update.php（クイックスクラップ更新処理）
1. **認証**: `Auth::requireAdmin()`
2. **データ取得**: `SenseQuickEntryModel::findByIdAndUser($id, $userId)`
3. **存在チェック**: エントリが見つからない場合は 404 レスポンス
4. **リクエスト受け取り・バリデーション**:
   - `$_POST` から `note`（必須）、`category_hint`（空文字はNULL）、`reason_1` 〜 `reason_3`（空文字はNULL）
   - `$_FILES['image']` から新画像（任意、未選択時は既存画像を維持）
   - 画像バリデーション: create と同一
5. **DB更新**: `SenseQuickEntryModel::updateByIdAndUser($id, $userId, $fields)` でUPDATE
   - 更新可能フィールド（ホワイトリスト）: `note`, `category_hint`, `image_path`, `reason_1`, `reason_2`, `reason_3`, `linked_entry_id`
6. **古い画像の削除**: 新画像でパスが変更された場合、旧画像ファイルを `@unlink()` で削除
7. **レスポンス**:
   - 成功: `/sense_lab/` にリダイレクト
   - バリデーションエラー: `$_SESSION['sense_lab_errors']` にエラーをセットし `/sense_lab/quick_edit.php?id={id}` にリダイレクト

## 3. バリデーションルール一覧

### 本番スクラップ（create / update）
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `title` | 必須（空文字不可） | 「タイトルは必須です。」 |
| `category` | 空の場合は `other` をデフォルト設定 | （エラーなし） |
| `reason_1` 〜 `reason_3` | 少なくとも1つは入力必須 | 「理由はいずれか1つ以上入力してください。」 |
| `image` (ファイル) | 任意、最大2MB | 「画像サイズは2MB以内にしてください。」 |
| `image` (MIME) | `image/jpeg`, `image/png`, `image/gif` のみ | 「許可されている画像形式は JPG/PNG/GIF のみです。」 |
| `image` (アップロード) | `move_uploaded_file` 成功 | 「画像ファイルの保存に失敗しました。」 |

### クイックスクラップ（quick_save / quick_update）
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `note` | 必須（空文字不可） | API: `"note is required"` / 画面: 「メモは必須です。」 |
| `image` (ファイル) | 任意、最大2MB | 「画像サイズは2MB以内にしてください。」 |
| `image` (MIME) | `image/jpeg`, `image/png`, `image/gif` のみ | 「許可されている画像形式は JPG/PNG/GIF のみです。」 |
| `category_hint` | 任意（空文字はNULLに変換） | （エラーなし） |
| `reason_1` 〜 `reason_3` | すべて任意（空文字はNULLに変換） | （エラーなし） |

## 4. エラーハンドリング
- **認証エラー**: `Auth::requireAdmin()` が失敗した場合、ログイン画面へリダイレクト（Coreの共通処理）
- **404 Not Found**: `findByIdAndUser()` で該当データが見つからない場合、HTTP 404 + テキスト「Not found」
- **バリデーションエラー（画面）**: `$_SESSION['sense_lab_errors']` にエラーメッセージ配列を格納し、元のフォーム画面へリダイレクト
- **サーバーエラー（API）**: `try-catch` で `\Throwable` を捕捉し、HTTP 500 + `{"status": "error", "message": "Server error"}` を返却
