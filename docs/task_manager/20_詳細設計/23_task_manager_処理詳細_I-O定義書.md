# タスク管理 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧
| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `task_manager/index.php` | `TaskController::index()` | なし（URLパラメータ `?task_id=N` はJS側で処理） | HTML |
| POST | `task_manager/api/save.php` | `TaskController::store()` | JSON body | JSON |
| POST | `task_manager/api/update.php` | `TaskController::update()` | JSON body | JSON |
| POST | `task_manager/api/delete.php` | `TaskController::delete()` | JSON body | JSON |

## 2. 処理フロー詳細

### index.php (GET) - タスク管理画面表示

1. **リクエスト受け取り・認証**:
   - `www/task_manager/index.php` がエントリポイント
   - `private/bootstrap.php` を読み込み、オートローダーとセッションを初期化
   - `TaskController::index()` 内で `Auth::requireLogin()` により認証チェック。未認証なら `/login.php` にリダイレクト

2. **ビジネスロジック・データ取得**:
   - `TaskModel::getAllTasks()` で当該ユーザーの全タスクをカテゴリ情報付き（LEFT JOIN `tm_categories`）で取得。優先度降順→期限昇順
   - `CategoryModel::all()` で当該ユーザーの全カテゴリを取得（datalist用）
   - `EventModel::getAllUpcomingEvents()` で日向坂の今後のイベント一覧を取得（カレンダー重畳表示用）

3. **レスポンス**:
   - `private/apps/TaskManager/Views/index.php` をレンダリング
   - `$tasks`, `$categories`, `$hinataEvents` をJSON化してJavaScript変数 `rawTasks`, `hinataEvents` に埋め込み
   - カテゴリ一覧を `<datalist id="categoryList">` に出力

---

### api/save.php (POST) - タスク新規作成

1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック。未認証なら HTTP 401 + JSONエラーレスポンス
   - POSTメソッドのみ許可。それ以外は HTTP 405 + JSONエラーレスポンス
   - `TaskController::store()` を呼び出し
   - リクエストボディを `php://input` からJSON読み取り
   - `Validator` で `title` が必須 (`required`) であることを検証。不正なら HTTP 422 + バリデーションエラー

2. **ビジネスロジック・DB更新**:
   - `handleCategory()` でカテゴリ処理:
     - `category_name` が指定されていれば `CategoryModel::getOrCreate()` を呼び出し
     - 既存カテゴリがあれば色が異なる場合に更新、なければ新規作成
     - カテゴリ名が未指定なら `category_id = null`
   - `TaskModel::create()` で新規レコード作成:
     - `status`: 固定値 `'todo'`
     - `priority`: 入力値またはデフォルト `2`
     - `start_date`, `due_date`: 入力値（空の場合 `null`）
     - `created_at`, `updated_at`: 現在日時
   - `TaskModel::getTaskWithCategory()` で作成したレコードをカテゴリ情報付きで再取得

3. **レスポンス**:
   - 成功時: `{"status": "success", "task": {作成されたタスクオブジェクト}}`
   - エラー時: HTTP 500 + `{"status": "error", "message": "System Error"}`

**入力パラメータ:**
| パラメータ名 | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| title | string | 必須 | タスク名 |
| description | string | 任意 | タスクの説明。未指定時は空文字列 |
| priority | int | 任意 | 優先度 (1/2/3)。未指定時は `2` |
| category_name | string | 任意 | カテゴリ名。未指定時は未分類 |
| category_color | string | 任意 | HEXカラーコード。未指定時は `'#4f46e5'` |
| start_date | string | 任意 | 開始日 (YYYY-MM-DD)。空文字列は `null` |
| due_date | string | 任意 | 期限日 (YYYY-MM-DD)。空文字列は `null` |

---

### api/update.php (POST) - タスク更新

1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック。未認証なら HTTP 401
   - `TaskController::update()` を呼び出し
   - リクエストボディを `php://input` からJSON読み取り
   - `id` パラメータが必須。未指定なら例外をスロー

2. **ビジネスロジック・DB更新**:
   - **フル更新パターン** (`title` が含まれる場合):
     - `handleCategory()` でカテゴリ処理
     - `title`, `description`, `priority`, `status`, `start_date`, `due_date`, `category_id` を更新
   - **ステータスのみ更新パターン** (`status` のみ含まれる場合):
     - `status` のみを更新（チェックボックスやドラッグ&ドロップからの呼び出し）
   - いずれの場合も `updated_at` を現在日時に更新
   - `TaskModel::update()` で更新実行（BaseModel が `user_id` スコープを自動付加）
   - `TaskModel::getTaskWithCategory()` で更新後のレコードを再取得

3. **レスポンス**:
   - 成功時: `{"status": "success", "task": {更新されたタスクオブジェクト}}`
   - エラー時: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

**入力パラメータ:**
| パラメータ名 | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 更新対象のタスクID |
| title | string | 条件付き | フル更新時に指定 |
| description | string | 任意 | フル更新時に指定 |
| priority | int | 任意 | フル更新時に指定。未指定時は `2` |
| status | string | 条件付き | ステータスのみ更新時に指定 (todo/doing/pending/done) |
| category_name | string | 任意 | フル更新時に指定 |
| category_color | string | 任意 | フル更新時に指定 |
| start_date | string | 任意 | フル更新時に指定 |
| due_date | string | 任意 | フル更新時に指定 |

---

### api/delete.php (POST) - タスク削除

1. **リクエスト受け取り・バリデーション**:
   - `Auth::check()` で認証チェック。未認証なら HTTP 401
   - `TaskController::delete()` を呼び出し
   - リクエストボディを `php://input` からJSON読み取り
   - `id` パラメータが必須。未指定なら例外をスロー

2. **ビジネスロジック・DB更新**:
   - `TaskModel::delete()` で指定IDのレコードを削除（BaseModel が `user_id` スコープを自動付加し、他ユーザーのタスクは削除不可）

3. **レスポンス**:
   - 成功時: `{"status": "success"}`
   - エラー時: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

**入力パラメータ:**
| パラメータ名 | 型 | 必須 | 説明 |
| :--- | :--- | :--- | :--- |
| id | int | 必須 | 削除対象のタスクID |

## 3. エラーハンドリング一覧

| 発生箇所 | 条件 | HTTPステータス | レスポンス |
| :--- | :--- | :--- | :--- |
| api/save.php, api/update.php, api/delete.php | 未認証（`Auth::check()` が false） | 401 | `{"status": "error", "message": "セッションが終了しました。再度ログインしてください。"}` または `{"status": "error", "message": "Unauthorized"}` |
| api/save.php | POST以外のメソッド | 405 | `{"status": "error", "message": "Method Not Allowed"}` |
| TaskController::store() | title が未指定 | 422 | `{"status": "error", "errors": {バリデーションエラー詳細}}` |
| TaskController::update(), delete() | id が未指定 | 500 | `{"status": "error", "message": "Missing ID"}` |
| 全API | 予期せぬ例外 | 500 | `{"status": "error", "message": "System Error"}` または例外メッセージ |

## 4. フロントエンド JavaScript 関数一覧

| 関数名 | 呼び出し元 | 説明 |
| :--- | :--- | :--- |
| `saveNewTask(e)` | クイック追加フォームのsubmit | `App.post('api/save.php', fd)` でタスク作成。成功時に `rawTasks` に追加し全体再描画 |
| `toggleStatus(id, checked)` | リスト行のチェックボックス | `App.post('api/update.php', {id, status})` でステータスをtodo/doneに切替 |
| `updateTaskStatus(id, newStatus)` | ボードのドラッグ&ドロップ | `App.post('api/update.php', {id, status})` でステータス変更 |
| `saveTaskFromModal(e)` | モーダルの保存ボタン | `App.post('api/update.php', data)` でフル更新 |
| `deleteTaskFromModal()` | モーダルの削除ボタン | `confirm()` 後に `App.post('api/delete.php', {id})` で削除 |
| `switchMode(mode)` | タブバーのボタン | ビューモード切替。`renderCurrentMode()` を呼び出し |
| `renderList()` | `renderCurrentMode()` | リストビュー描画。ソート・フィルタ適用 |
| `renderBoard()` | `renderCurrentMode()` | ボードビュー描画。SortableJS初期化 |
| `renderGantt()` | `renderCurrentMode()` | ガントチャートビュー描画 |
| `renderFullCalendar()` | `renderCurrentMode()` | カレンダーのイベント更新 |
| `renderStats()` | 各CRUD操作後 | 統計サマリバー更新 |
| `openTaskModal(task)` | 各ビューのタスククリック | モーダルに値を設定してスライドイン表示 |
| `closeTaskModal()` | キャンセル/Escape/オーバーレイクリック | モーダルを閉じる |
| `getFilteredTasks()` | 各レンダリング関数 | 検索クエリ・カテゴリ・優先度フィルタ・完了表示フラグを適用してタスクを絞り込み |
| `sortTasks(tasks)` | `renderList()` | ソート順（default/priority/category）に応じてタスクをソート |
| `getDueDateInfo(dueDate)` | 各カード描画 | 期限日からバッジ情報（クラス・テキスト・緊急度）を算出 |
| `populateFilterCategories()` | 初期化時・タスク追加時 | カテゴリフィルタのプルダウンを `rawTasks` から動的生成 |
| `onSearchInput()` | 検索入力のoninput | `searchQuery` を更新して再描画 |
| `onFilterChange()` | フィルタ変更のonchange | 再描画を実行 |
| `toggleQuickAdd()` | モバイル用トグルボタン | クイック追加フォームの展開/格納 |
