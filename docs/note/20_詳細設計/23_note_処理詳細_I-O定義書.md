# メモ（Note） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `note/index.php` | `NoteController::index` | URLパラメータ: `tab`, `kind`（任意） | HTML |
| POST | `note/api/save.php` | `NoteController::store` | JSON Body | JSON |
| POST | `note/api/update.php` | `NoteController::update` | JSON Body | JSON |
| POST | `note/api/delete.php` | `NoteController::delete` | JSON Body | JSON |
| POST | `note/api/toggle_pin.php` | `NoteController::togglePin` | JSON Body | JSON |
| POST | `note/api/list_save.php` | `NoteController::listStore` | JSON Body | JSON |
| POST | `note/api/list_update.php` | `NoteController::listUpdate` | JSON Body | JSON |
| POST | `note/api/list_delete.php` | `NoteController::listDelete` | JSON Body | JSON |
| POST | `note/api/list_toggle_pin.php` | `NoteController::listTogglePin` | JSON Body | JSON |

## 2. 処理フロー詳細

### note/index.php（メモ一覧画面）
1. **リクエスト受け取り・認証**:
   - `bootstrap.php` を読み込み、セッション・オートロードを初期化
   - `NoteController::index()` を呼び出し
   - `Auth::requireLogin()` でログインチェック（未ログインの場合はリダイレクト）
2. **データ取得**:
   - `NoteModel::getActiveNotes()` でアクティブメモを取得（`WHERE user_id = :uid AND status = 'active' ORDER BY is_pinned DESC, created_at DESC`）
   - `NoteModel::getArchivedNotes()` でアーカイブ済みメモを取得（`WHERE user_id = :uid AND status = 'archived' ORDER BY is_pinned DESC, created_at DESC`）
   - `NoteListEntryModel::LIST_KINDS` の全種別について `getActiveByKind()` / `getArchivedByKind()` を実行し、種別別のリストエントリを取得
   - エラー発生時は空配列をフォールバック値として使用（テーブル未作成時などに対応）
3. **レスポンス**:
   - `note_index.php` ビューをレンダリング
   - メモ・リストデータは `json_encode` でJavaScript変数（`NoteManager.notes`, `NoteManager.archivedNotes`, `ListManager.entriesByKind`, `ListManager.archivedEntriesByKind`）に埋め込み
   - URLパラメータ `tab=list` 指定時は DOMContentLoaded 後に `ListManager.switchMain('list')` を実行

### note/api/save.php（メモ新規保存）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック（未認証: HTTP 401 + エラーJSON）
   - `php://input` から JSON をデコード
   - JSONデコードエラーチェック、入力形式チェック
   - `content` が空の場合はエラー（「メモの内容を入力してください」）
2. **ビジネスロジック・DB更新**:
   - `NoteModel::createNote()` を呼び出し
   - `title` が空の場合、`content` の先頭30文字を自動でタイトルに設定（30文字超の場合は末尾に `...` を付加）
   - `BaseModel::create()` で `nt_notes` に INSERT（`user_id` は `BaseModel` が自動付与）
   - INSERT 成功後、`lastInsertId()` で新規IDを取得し、`find()` で完全なレコードを再取得
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "メモを保存しました", "id": <int>, "note": <object> }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| title | string | 任意 | メモタイトル（空の場合は本文から自動生成） |
| content | string | 必須 | メモ本文 |
| bg_color | string | 任意 | 背景色（デフォルト: `#ffffff`） |
| is_pinned | int | 任意 | ピン留め（デフォルト: `0`） |

### note/api/update.php（メモ更新）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - 入力に含まれるフィールド（`title`, `content`, `bg_color`, `is_pinned`, `status`）のみを更新対象として抽出
   - `NoteModel::update()` で `nt_notes` を UPDATE
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "メモを更新しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 更新対象のメモID |
| title | string | 任意 | タイトル |
| content | string | 任意 | 本文 |
| bg_color | string | 任意 | 背景色 |
| is_pinned | int | 任意 | ピン留めフラグ |
| status | string | 任意 | ステータス（active/archived/trash） |

### note/api/delete.php（メモ削除）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - `NoteModel::delete()` で `nt_notes` から物理削除
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "メモを削除しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 削除対象のメモID |

### note/api/toggle_pin.php（メモピン留めトグル）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - `NoteModel::togglePin()` を呼び出し
   - 現在の `is_pinned` 値を取得し、0→1 / 1→0 に反転して UPDATE
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "ピン留めを変更しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 対象のメモID |

### note/api/list_save.php（リストエントリ新規保存）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、入力形式チェック
   - `list_kind` が `LIST_KINDS` に含まれるかバリデーション
2. **ビジネスロジック・DB更新**:
   - `NoteListEntryModel::createEntry()` を呼び出し
   - `normalizePayload()` で種別に応じた payload のバリデーション・正規化を実行
   - 正規化後の payload が空配列の場合はエラー
   - payload を `json_encode()` して `nt_list_entries` に INSERT
   - INSERT 成功後、`lastInsertId()` + `find()` で完全なレコードを再取得し、`payload` を JSON デコードしてレスポンスに含める
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "保存しました", "id": <int>, "entry": <object> }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| list_kind | string | 必須 | リスト種別（todo/question/first_time/fun/book/generic_list） |
| payload | object | 必須 | 種別ごとのデータ本体 |
| bg_color | string | 任意 | 背景色（デフォルト: `#ffffff`） |
| is_pinned | int | 任意 | ピン留め（デフォルト: `0`） |

### note/api/list_update.php（リストエントリ更新）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - 既存レコードを `find()` で取得し、`list_kind` を確認
   - `payload` が指定されている場合は `normalizePayload()` で正規化後に `json_encode()` して更新
   - `bg_color`, `is_pinned`, `status` は指定されたフィールドのみ更新
   - `NoteListEntryModel::updateEntry()` → `BaseModel::update()` で UPDATE
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "更新しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 更新対象のエントリID |
| payload | object | 任意 | 種別ごとのデータ本体 |
| bg_color | string | 任意 | 背景色 |
| is_pinned | int | 任意 | ピン留めフラグ |
| status | string | 任意 | ステータス（active/archived） |

### note/api/list_delete.php（リストエントリ削除）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - `NoteListEntryModel::delete()` で `nt_list_entries` から物理削除
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "削除しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 削除対象のエントリID |

### note/api/list_toggle_pin.php（リストエントリピン留めトグル）
1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック
   - JSON デコード、`id` の存在チェック
2. **ビジネスロジック・DB更新**:
   - `NoteListEntryModel::togglePin()` を呼び出し
   - 現在の `is_pinned` 値を取得し、0→1 / 1→0 に反転して UPDATE
3. **レスポンス**:
   - 成功: `{ "status": "success", "message": "ピン留めを変更しました" }`
   - 失敗: `{ "status": "error", "message": "<エラーメッセージ>" }`

**入力パラメータ**:
| フィールド | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 対象のエントリID |

## 3. 認証・認可

- 全 API エンドポイント（`/note/api/*`）は `Core\Auth::check()` による認証が必須。
- 未認証の場合は HTTP 401 + `{ "status": "error", "message": "Unauthorized" }` を返す。
- 一覧画面（`/note/index.php`）は `Auth::requireLogin()` によるログイン必須（未ログイン時はログイン画面にリダイレクト）。
- データアクセスは `BaseModel::$isUserIsolated = true` により、ログインユーザーの `user_id` に自動的にスコープされる。

## 4. エラーハンドリング

- 全 API で `try-catch` による例外捕捉を実施。
- 例外発生時は `{ "status": "error", "message": "<例外メッセージ>" }` を返す。
- 一覧画面のデータ取得でエラーが発生した場合は `Logger::errorWithContext()` でログ記録後、空配列をフォールバック値として表示を継続する。
