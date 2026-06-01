# Focus Note（フォーカスノート） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    fn_monthly_pages ||--o{ fn_daily_tasks : "has many"
    fn_weekly_pages ||--o{ fn_weekly_task_picks : "has many"
    fn_daily_tasks ||--o{ fn_weekly_task_picks : "referenced by"
    fn_weekly_task_picks ||--o| fn_question_actions : "has one"
    fn_goals ||--o{ fn_action_goals : "has many"
    fn_goals ||--o{ fn_if_then_rules : "has many"

    fn_monthly_pages {
        bigint id PK
        int user_id
        date ym "月の初日"
        text target "ターゲット"
        text importance_check "重要度チェック"
        text concrete_imaging "具象イメージング"
        text reverse_planning "リバースプランニング"
        datetime created_at
        datetime updated_at
    }

    fn_daily_tasks {
        bigint id PK
        int user_id
        bigint monthly_page_id FK
        text content "タスク内容"
        smallint sort_order "表示順"
        datetime created_at
    }

    fn_weekly_pages {
        bigint id PK
        int user_id
        date week_start "週の月曜日"
        text obstacle_contrast "障害コントラスト"
        text obstacle_fix "障害フィックス"
        datetime created_at
        datetime updated_at
    }

    fn_weekly_task_picks {
        bigint id PK
        int user_id
        bigint weekly_page_id FK
        bigint daily_task_id FK
        smallint sort_order "表示順"
        datetime created_at
    }

    fn_question_actions {
        bigint id PK
        int user_id
        bigint weekly_task_pick_id FK
        varchar scheduled_time "予定時間"
        varchar place "場所"
        text question_text "実施意図文"
        tinyint done "完了フラグ"
        int actual_duration_min "所要分数"
        datetime done_at "完了日時"
        datetime created_at
        datetime updated_at
    }

    fn_goals {
        bigint id PK
        int user_id
        text wish "WOOP: 願望"
        text outcome "WOOP: 成果イメージ"
        text obstacle "WOOP: 障害"
        text plan "WOOP: 計画"
        text being "ありたい姿"
        tinyint is_active "現在の目標フラグ"
        datetime created_at
        datetime updated_at
    }

    fn_action_goals {
        bigint id PK
        bigint goal_id FK
        int user_id
        text content "行動内容"
        varchar measurement "測定方法"
        tinyint is_process_goal "プロセス目標フラグ"
        smallint sort_order "表示順"
        datetime created_at
    }

    fn_if_then_rules {
        bigint id PK
        bigint goal_id FK
        int user_id
        text if_condition "If: 条件"
        text then_action "Then: 行動"
        smallint sort_order "表示順"
        datetime created_at
    }
```

## 2. テーブル関係の補足

### 報酬感覚プランニング系（マンスリー → ウィークリー → アクション）
- **fn_monthly_pages** は月ごとに1レコード。`user_id + ym` でユニーク制約。初回アクセス時に自動作成される。
- **fn_daily_tasks** はマンスリーページに紐づく複数のタスク。保存時は既存レコードを全削除してから再挿入（一括置換方式）。
- **fn_weekly_pages** は週ごとに1レコード。`user_id + week_start` でユニーク制約。月曜日を起点として自動計算される。
- **fn_weekly_task_picks** はウィークリーページから fn_daily_tasks への参照（3〜5個選択）。一括置換方式。
- **fn_question_actions** はピック1件に対して1件の実施意図（1:1）。時間・場所を設定すると「[名前]は、[時間]に[場所]で[タスク]をするか？」という質問文が自動生成される。

### 目標設定系（WOOP / MAC / If-Then）
- **fn_goals** はユーザーの目標（WOOP形式）。`is_active=1` のレコードが現在の目標。新規作成時に既存のactive目標を非活性化する。
- **fn_action_goals** は目標に紐づく行動目標（MAC原則）。`goal_id` で紐づき、CASCADE削除。一括置換方式。
- **fn_if_then_rules** は目標に紐づくIf-Thenルール。`goal_id` で紐づき、CASCADE削除。一括置換方式。

### ユーザー隔離
- 全テーブルに `user_id` カラムがあり、`BaseModel::isUserIsolated = true` により全クエリで `user_id` スコープが自動付加される。
