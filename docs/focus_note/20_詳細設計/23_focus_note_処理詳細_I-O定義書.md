# Focus Note（フォーカスノート） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `focus_note/index.php` | `FocusNoteController::dashboard` | なし | HTML |
| GET | `focus_note/monthly.php` | `FocusNoteController::monthly` | `?ym=YYYY-MM` | HTML |
| GET | `focus_note/weekly.php` | `FocusNoteController::weekly` | `?week=YYYY-MM-DD` | HTML |
| GET | `focus_note/goal_setting.php` | `FocusNoteController::goalSetting` | なし | HTML |
| GET | `focus_note/goal_setting_form.php` | `FocusNoteController::goalSettingForm` | なし | HTML |
| POST | `focus_note/api/save_monthly.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/save_weekly.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/save_picks.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/save_question_actions.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/toggle_done.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/save_duration.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/goal_save.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/action_goals_save.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/if_then_rules_save.php` | 直接処理 | JSON | JSON |
| POST | `focus_note/api/goal_delete.php` | 直接処理 | JSON | JSON |

## 2. 処理フロー詳細

### index.php（ダッシュボード表示）
1. **認証**: `Auth::requireLogin()` でログイン済みを確認
2. **データ取得**:
   - `WeeklyPageModel::getWeekStart(today)` で今週の月曜日を計算
   - `WeeklyPageModel::findByWeekStart($weekStart)` で今週のウィークリーページを検索
   - ウィークリーページが存在する場合、`QuestionActionModel::getActionsByWeeklyPageId()` で質問型アクション一覧を取得（JOINで `fn_weekly_task_picks` + `fn_daily_tasks` のタスク内容も含む。未完了優先・sort_order昇順）
   - マンスリーリンク（`?ym=当月`）、ウィークリーリンク（`?week=今週月曜`）を生成
3. **レスポンス**: `Views/dashboard.php` をレンダリング（変数: `$user`, `$todayActions`, `$monthlyLink`, `$weeklyLink`, `$yearMonth`, `$weekStart`）

### monthly.php（マンスリーページ表示）
1. **認証**: `Auth::requireLogin()`
2. **パラメータ処理**:
   - `$_GET['ym']` を取得（未指定時は当月 `YYYY-MM-01`）
   - 正規表現 `/^\d{4}-\d{2}(-\d{2})?$/` でバリデーション（不正値は当月にフォールバック）
   - 7文字（`YYYY-MM`）の場合は `-01` を付加
3. **データ取得**:
   - `MonthlyPageModel::findOrCreateForYearMonth($ym)` でページを取得または新規作成
   - `DailyTaskModel::getByMonthlyPageId()` でデイリータスク一覧を取得（sort_order昇順）
   - 前月・次月の日付文字列を生成
4. **レスポンス**: `Views/monthly.php` をレンダリング（変数: `$user`, `$page`, `$dailyTasks`, `$ym`, `$prevMonth`, `$nextMonth`）
5. **エラーハンドリング**: PDOException（テーブル未存在時はマイグレーション案内）、Throwable（一般エラー）

### weekly.php（ウィークリーページ表示）
1. **認証**: `Auth::requireLogin()`
2. **パラメータ処理**:
   - `$_GET['week']` を取得（正規表現 `/^\d{4}-\d{2}-\d{2}$/` でバリデーション）
   - `WeeklyPageModel::getWeekStart()` で月曜日に補正（日曜日指定時は前週月曜に変換）
3. **データ取得**:
   - `WeeklyPageModel::findOrCreateForWeek($weekStart)` でページを取得または新規作成
   - `WeeklyTaskPickModel::getPicksWithTasks()` で選択済みタスク一覧を取得（JOIN で `fn_daily_tasks.content` を含む）
   - `QuestionActionModel::getActionsByWeeklyPageId()` で質問型アクション一覧を取得
   - 当週の月に対応するマンスリーページを検索し、`DailyTaskModel::getByMonthlyPageId()` でデイリータスク一覧を取得（選択候補用）
   - 前週・次週の日付文字列を生成
   - `$userName` を `$_SESSION['user']['id_name']`（未設定時は `私`）から取得
4. **レスポンス**: `Views/weekly.php` をレンダリング（変数: `$user`, `$weeklyPage`, `$picks`, `$questionActions`, `$availableDailyTasks`, `$weekStart`, `$prevWeek`, `$nextWeek`, `$userName`）
5. **エラーハンドリング**: monthly.php と同様

### goal_setting.php（目標設定の考え方 表示）
1. **認証**: `Auth::requireLogin()`
2. **レスポンス**: `Views/goal_setting.php` をレンダリング（変数: `$user`）
3. 読み取り専用ページのためデータ取得なし

### goal_setting_form.php（目標・行動目標設定フォーム表示）
1. **認証**: `Auth::requireLogin()`
2. **データ取得**:
   - `GoalModel::findActive()` で現在の目標（`is_active=1`）を取得
   - 目標が存在する場合:
     - `ActionGoalModel::getByGoalId()` で行動目標一覧を取得（sort_order昇順）
     - `IfThenRuleModel::getByGoalId()` でIf-Thenルール一覧を取得（sort_order昇順）
   - 目標が存在しない場合: 空配列をセット
3. **ビュー前処理**:
   - `$actionGoals` が空の場合、空行1つ（`content='', measurement='', is_process_goal=1`）をセット
   - `$ifThenRules` が空の場合、空行1つ（`if_condition='', then_action=''`）をセット
4. **レスポンス**: `Views/goal_setting_form.php` をレンダリング（変数: `$user`, `$goal`, `$actionGoals`, `$ifThenRules`, `$goalId`）
5. **エラーハンドリング**: PDOException（テーブル未存在時はマイグレーション案内 `create_fn_goals.sql`）

### api/save_monthly.php（マンスリー保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード（失敗時は `Invalid JSON` 例外）
   - `page_id`（int、必須、1以上）
3. **ビジネスロジック・DB更新**:
   - `MonthlyPageModel::find($pageId)` で存在確認（見つからない場合は `Page not found` 例外）
   - `MonthlyPageModel::savePage()` で本文4項目を更新（ホワイトリスト: `target`, `importance_check`, `concrete_imaging`, `reverse_planning`）
   - `DailyTaskModel::replaceTasks()` でデイリータスクを一括置換（トランザクション: DELETE + INSERT、空文字タスクはスキップ）
4. **レスポンス**: `{"status": "success", "message": "保存しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`。`Logger::errorWithContext()` でログ記録

### api/save_weekly.php（ウィークリー障害保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `page_id`（int、必須、1以上）
3. **ビジネスロジック・DB更新**:
   - `WeeklyPageModel::find($pageId)` で存在確認
   - `WeeklyPageModel::savePage()` で障害情報を更新（ホワイトリスト: `obstacle_contrast`, `obstacle_fix`）
4. **レスポンス**: `{"status": "success", "message": "保存しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/save_picks.php（タスク選択保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `page_id`（int、必須、1以上）
   - `daily_task_ids`（int配列、`array_map('intval', array_filter())` でサニタイズ）
   - 件数バリデーション: 3〜5個（範囲外は `3〜5つ選んでください` 例外）
3. **ビジネスロジック・DB更新**:
   - `WeeklyPageModel::find($pageId)` で存在確認
   - `WeeklyTaskPickModel::replacePicks()` で選択を一括置換（トランザクション: DELETE + INSERT）
4. **レスポンス**: `{"status": "success", "message": "選択を保存しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/save_question_actions.php（質問型アクション保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `items`（配列、各要素に `pick_id`, `time`, `place`, `task_content`）
3. **ビジネスロジック・DB更新**:
   - ユーザー名を `$_SESSION['user']['id_name']`（未設定時は `私`）から取得
   - 各 item について:
     - `pick_id` が0以下ならスキップ
     - 実施意図文を生成: `{ユーザー名}は、{時間 or [時間]}に{場所 or [場所]}で{タスク内容}をするか？`
     - `QuestionActionModel::upsertForPick()` でUPSERT（既存あれば更新、なければ新規作成。ホワイトリスト: `scheduled_time`, `place`, `question_text`）
4. **レスポンス**: `{"status": "success", "message": "保存しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/toggle_done.php（アクション完了トグル）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `id`（int、必須、1以上）
3. **ビジネスロジック・DB更新**:
   - `QuestionActionModel::find($id)` で存在確認
   - `QuestionActionModel::toggleDone($id)`: 現在の `done` 値を反転し更新。完了時は `done_at` に現在日時をセット、未完了時は `done_at` を NULL に
4. **レスポンス**: `{"status": "success", "done": 0|1, "message": "完了にしました"|"未完了に戻しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/save_duration.php（所要時間記録）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `id`（int、必須、1以上）
   - `duration_min`（int、必須、1以上）
3. **ビジネスロジック・DB更新**:
   - `QuestionActionModel::find($id)` で存在確認
   - `QuestionActionModel::markDone($id, $durationMin)`: `done=1`, `done_at=now()`, `actual_duration_min=$durationMin` を更新
4. **レスポンス**: `{"status": "success", "message": "記録しました"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/goal_save.php（目標 WOOP 保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `goal_id`（int、0 = 新規作成、1以上 = 更新）
   - `wish`, `outcome`, `obstacle`, `plan`, `being`（すべて文字列、空文字許容）
3. **ビジネスロジック・DB更新**:
   - `goal_id > 0`（更新）: `GoalModel::find()` で存在確認 → `GoalModel::updateGoal()` でホワイトリスト更新
   - `goal_id = 0`（新規）: `GoalModel::createGoal()` → 既存の `is_active=1` を全て `is_active=0` に更新してから INSERT（`is_active=1`）
4. **レスポンス**: `{"status": "success", "goal_id": {id}}`（新規作成時は新しいIDを返却）
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/action_goals_save.php（行動目標一括保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `goal_id`（int、必須、1以上）
   - `items`（配列、各要素に `content`, `measurement`, `is_process_goal`）
3. **ビジネスロジック・DB更新**:
   - `GoalModel::find($goalId)` で存在確認
   - `ActionGoalModel::replaceForGoal()`: DELETE（goal_id + user_id）+ 各 item を INSERT（content が空の行はスキップ、sort_order は配列のインデックス値）
4. **レスポンス**: `{"status": "success"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/if_then_rules_save.php（If-Thenルール一括保存）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `goal_id`（int、必須、1以上）
   - `items`（配列、各要素に `if_condition`, `then_action`）
3. **ビジネスロジック・DB更新**:
   - `GoalModel::find($goalId)` で存在確認
   - `IfThenRuleModel::replaceForGoal()`: DELETE（goal_id + user_id）+ 各 item を INSERT（if_condition と then_action が両方空の行はスキップ、sort_order は配列のインデックス値）
4. **レスポンス**: `{"status": "success"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

### api/goal_delete.php（目標削除）
1. **認証**: `Auth::requireLogin()`
2. **リクエスト受け取り・バリデーション**:
   - `php://input` からJSONデコード
   - `goal_id`（int、必須、1以上）
3. **ビジネスロジック・DB更新**:
   - `GoalModel::find($goalId)` で存在確認（見つからない場合は `Goal not found` 例外）
   - `GoalModel::delete($goalId)`: `fn_goals` から DELETE。紐づく `fn_action_goals`, `fn_if_then_rules` は外部キー `ON DELETE CASCADE` で連鎖削除
4. **レスポンス**: `{"status": "success"}`
5. **エラー時**: HTTP 400 + `{"status": "error", "message": "..."}`

## 3. バリデーションルール一覧

### 画面表示（GET）
| パラメータ | ルール | フォールバック |
| :--- | :--- | :--- |
| `ym`（monthly.php） | `/^\d{4}-\d{2}(-\d{2})?$/` に一致 | 当月 `YYYY-MM-01` |
| `week`（weekly.php） | `/^\d{4}-\d{2}-\d{2}$/` に一致 | 今日の日付から算出した月曜日 |

### API（POST）共通
| チェック項目 | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| リクエストボディ | `json_decode()` 成功 かつ 配列型 | `Invalid JSON` |

### api/save_monthly.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `page_id` | int, 1以上 | `page_id is required` |
| ページ存在 | `find($pageId)` が非null | `Page not found` |
| `target`, `importance_check`, `concrete_imaging`, `reverse_planning` | 文字列（空文字許容） | （エラーなし） |
| `daily_tasks` | 配列（空配列許容） | （エラーなし） |

### api/save_weekly.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `page_id` | int, 1以上 | `page_id is required` |
| ページ存在 | `find($pageId)` が非null | `Page not found` |
| `obstacle_contrast`, `obstacle_fix` | 文字列（空文字許容） | （エラーなし） |

### api/save_picks.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `page_id` | int, 1以上 | `page_id is required` |
| ページ存在 | `find($pageId)` が非null | `Page not found` |
| `daily_task_ids` | int配列, 3〜5個 | `3〜5つ選んでください` |

### api/save_question_actions.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `items` | 配列 | （エラーなし、空配列は許容） |
| `items[].pick_id` | int, 1以上でなければスキップ | （スキップのみ） |

### api/toggle_done.php / api/save_duration.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `id` | int, 1以上 | `id is required` |
| アクション存在 | `find($id)` が非null | `Not found` |
| `duration_min`（save_duration のみ） | int, 1以上 | `id and duration_min (1以上) are required` |

### api/goal_save.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `goal_id` | int（0 = 新規、1以上 = 更新） | （エラーなし） |
| 目標存在（更新時） | `find($goalId)` が非null | `Goal not found` |
| `wish`, `outcome`, `obstacle`, `plan`, `being` | 文字列（空文字許容） | （エラーなし） |

### api/action_goals_save.php / api/if_then_rules_save.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `goal_id` | int, 1以上 | `goal_id is required` |
| 目標存在 | `find($goalId)` が非null | `Goal not found` |
| `items` | 配列（空配列許容） | （エラーなし） |

### api/goal_delete.php
| フィールド | ルール | エラーメッセージ |
| :--- | :--- | :--- |
| `goal_id` | int, 1以上 | `goal_id is required` |
| 目標存在 | `find($goalId)` が非null | `Goal not found` |

## 4. エラーハンドリング
- **認証エラー**: `Auth::requireLogin()` が失敗した場合、ログイン画面へリダイレクト（Coreの共通処理）
- **テーブル未存在（画面）**: PDOException のメッセージに `doesn't exist` / `exist` が含まれる場合、マイグレーション実行案内を表示（`renderError()` メソッド）
- **一般エラー（画面）**: Throwable をキャッチし、エラーメッセージ + ファイル名・行番号を表示。ダッシュボードへの戻るリンクを表示
- **APIエラー**: `try-catch` で `\Exception` を捕捉し、HTTP 400 + `{"status": "error", "message": "..."}` を返却。`Logger::errorWithContext()` でエラーログを記録
