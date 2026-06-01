# Focus Note（フォーカスノート） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### fn_monthly_pages（マンスリーページ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `ym` | 月（初日） | `DATE` | NOT NULL | 月の初日を格納（例: `2026-06-01`）。`UNIQUE KEY uk_user_ym (user_id, ym)` |
| `target` | ターゲット | `TEXT` | NULL | ステップ1: 集中力が続かない作業の中で最も重要なもの。初期値 `''` |
| `importance_check` | 重要度チェック | `TEXT` | NULL | ステップ2: 達成しなければならない最大の理由。初期値 `''` |
| `concrete_imaging` | 具象イメージング | `TEXT` | NULL | ステップ3: ターゲットを具体的な映像イメージに変換。初期値 `''` |
| `reverse_planning` | リバースプランニング | `TEXT` | NULL | ステップ4: 達成した未来から逆算した短期目標。初期値 `''` |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_focus_note.sql`
- インデックス: `UNIQUE KEY uk_user_ym (user_id, ym)`, `KEY idx_user (user_id)`
- 初回アクセス時に `findOrCreateForYearMonth()` で自動作成

### fn_daily_tasks（デイリータスク）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `monthly_page_id` | マンスリーページID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_monthly_pages.id` への外部キー（`ON DELETE CASCADE`） |
| `content` | タスク内容 | `TEXT` | NOT NULL | 毎日実行するタスクの内容 |
| `sort_order` | 表示順 | `SMALLINT UNSIGNED` | NOT NULL | `DEFAULT 0`。配列のインデックス値を格納 |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_focus_note.sql`
- インデックス: `KEY idx_monthly (monthly_page_id)`, `KEY idx_user (user_id)`
- 更新方式: 一括削除 + 再挿入（`replaceTasks()`）。空文字のタスクはスキップ

### fn_weekly_pages（ウィークリーページ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `week_start` | 週開始日 | `DATE` | NOT NULL | 週の月曜日（例: `2026-06-01`）。`UNIQUE KEY uk_user_week (user_id, week_start)` |
| `obstacle_contrast` | 障害コントラスト | `TEXT` | NULL | 発生しそうなトラブル。初期値 `''` |
| `obstacle_fix` | 障害フィックス | `TEXT` | NULL | トラブルへの対策。初期値 `''` |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_focus_note.sql`
- インデックス: `UNIQUE KEY uk_user_week (user_id, week_start)`, `KEY idx_user (user_id)`
- 週の月曜日計算: `WeeklyPageModel::getWeekStart()` で `date('w')` から月曜オフセットを算出
- 初回アクセス時に `findOrCreateForWeek()` で自動作成

### fn_weekly_task_picks（ウィークリータスク選択）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `weekly_page_id` | ウィークリーページID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_weekly_pages.id` への外部キー（`ON DELETE CASCADE`） |
| `daily_task_id` | デイリータスクID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_daily_tasks.id` への外部キー（`ON DELETE CASCADE`） |
| `sort_order` | 表示順 | `SMALLINT UNSIGNED` | NOT NULL | `DEFAULT 0` |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_focus_note.sql`
- インデックス: `KEY idx_weekly (weekly_page_id)`, `KEY idx_user (user_id)`
- 更新方式: 一括削除 + 再挿入（`replacePicks()`）
- 選択数制約: API側で3〜5個のバリデーション

### fn_question_actions（質問型アクション）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `weekly_task_pick_id` | ウィークリータスク選択ID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_weekly_task_picks.id` への外部キー（`ON DELETE CASCADE`） |
| `scheduled_time` | 予定時間 | `VARCHAR(50)` | NULL | 例: `9:00` |
| `place` | 場所 | `VARCHAR(100)` | NULL | 例: `自宅デスク` |
| `question_text` | 実施意図文 | `TEXT` | NULL | 「[名前]は、[時間]に[場所]で[タスク]をするか？」形式の自動生成文 |
| `done` | 完了フラグ | `TINYINT(1)` | NOT NULL | `DEFAULT 0`。`0` = 未完了、`1` = 完了 |
| `actual_duration_min` | 所要分数 | `INT UNSIGNED` | NULL | 完了時に任意で記録する所要時間（分） |
| `done_at` | 完了日時 | `DATETIME` | NULL | 完了時にセットされる日時。未完了に戻すとNULL |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_focus_note.sql`
- インデックス: `KEY idx_weekly_pick (weekly_task_pick_id)`, `KEY idx_user (user_id)`, `KEY idx_done (done)`
- ピック1件に対して1件（1:1関係）。`upsertForPick()` で既存があれば更新、なければ新規作成

### fn_goals（目標 / WOOP）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `wish` | 願望 | `TEXT` | NULL | WOOP: 仕事で達成したいこと |
| `outcome` | 成果イメージ | `TEXT` | NULL | WOOP: 達成時の最高のメリット |
| `obstacle` | 障害 | `TEXT` | NULL | WOOP: 達成を阻む内面の障害 |
| `plan` | 計画 | `TEXT` | NULL | WOOP: 障害が起きた時の対策 |
| `being` | ありたい姿 | `TEXT` | NULL | 抽象度の高い目的 |
| `is_active` | 現在の目標フラグ | `TINYINT(1)` | NOT NULL | `DEFAULT 1`。`1` = 現在の目標、`0` = 非活性 |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_goals.sql`
- インデックス: `KEY idx_user (user_id)`, `KEY idx_active (user_id, is_active)`
- 新規作成時: 既存の `is_active=1` レコードを全て `is_active=0` に更新してから INSERT

### fn_action_goals（行動目標 / MAC）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `goal_id` | 目標ID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_goals.id` への外部キー（`ON DELETE CASCADE`） |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `content` | 行動内容 | `TEXT` | NOT NULL | Actionable: 具体的なプロセス行動 |
| `measurement` | 測定方法 | `VARCHAR(255)` | NULL | Measurable: 達成度の測定手段 |
| `is_process_goal` | プロセス目標フラグ | `TINYINT(1)` | NOT NULL | `DEFAULT 1`。`1` = プロセス目標、`0` = 成果目標 |
| `sort_order` | 表示順 | `SMALLINT UNSIGNED` | NOT NULL | `DEFAULT 0` |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_goals.sql`
- インデックス: `KEY idx_goal (goal_id)`, `KEY idx_user (user_id)`
- 更新方式: 一括削除 + 再挿入（`replaceForGoal()`）。content が空文字の行はスキップ

### fn_if_then_rules（If-Thenルール）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `goal_id` | 目標ID | `BIGINT UNSIGNED` | NOT NULL, FK | `fn_goals.id` への外部キー（`ON DELETE CASCADE`） |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `if_condition` | If: 条件 | `TEXT` | NOT NULL | 「もし A が起きたら」の条件部 |
| `then_action` | Then: 行動 | `TEXT` | NOT NULL | 「B をする」の行動部 |
| `sort_order` | 表示順 | `SMALLINT UNSIGNED` | NOT NULL | `DEFAULT 0` |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |

- マイグレーション: `migrations/done/create_fn_goals.sql`
- インデックス: `KEY idx_goal (goal_id)`, `KEY idx_user (user_id)`
- 更新方式: 一括削除 + 再挿入（`replaceForGoal()`）。if_condition と then_action が両方空文字の行はスキップ

## 2. ステータス・区分値定義（マジックナンバー）

### `fn_question_actions.done`（完了フラグ）
| 値 | 意味 | 説明 |
| :--- | :--- | :--- |
| `0` | 未完了 | 初期値。ダッシュボードでチェック未済として表示 |
| `1` | 完了 | チェック済み。`done_at` に完了日時がセットされる |

### `fn_goals.is_active`（現在の目標フラグ）
| 値 | 意味 | 説明 |
| :--- | :--- | :--- |
| `0` | 非活性 | 過去の目標。新しい目標作成時に自動で切り替え |
| `1` | 現在の目標 | ユーザーあたり最大1件。`findActive()` で取得 |

### `fn_action_goals.is_process_goal`（プロセス目標フラグ）
| 値 | 意味 | 説明 |
| :--- | :--- | :--- |
| `0` | 成果目標 | コントロールできない結果指標（例: コンペで勝つ） |
| `1` | プロセス目標 | 100%コントロールできる行動指標（例: 毎日3件電話する）。初期値 |

### 実施意図文（question_text）の生成ルール
- テンプレート: `{ユーザー名}は、{時間}に{場所}で{タスク内容}をするか？`
- `{ユーザー名}`: `$_SESSION['user']['id_name']`（未設定時は `私`）
- `{時間}`: `scheduled_time`（未入力時は `[時間]`）
- `{場所}`: `place`（未入力時は `[場所]`）
- `{タスク内容}`: `fn_daily_tasks.content`（ピック経由で取得）

## 3. データスコープ
- 全8テーブルに `user_id` カラムがあり、全モデルクラスで `isUserIsolated = true` を設定
- `BaseModel` が全クエリに `user_id` スコープを自動付加し、他ユーザーのデータへのアクセスを防止
- 更新・削除は `id` と `user_id` の複合条件で実行
