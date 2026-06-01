# Focus Note（フォーカスノート） 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> Dashboard : /focus_note/

    Dashboard --> Monthly : マンスリーリンク<br>?ym=YYYY-MM
    Dashboard --> Weekly : ウィークリーリンク<br>?week=YYYY-MM-DD
    Dashboard --> GoalSetting : 目標設定の考え方
    Dashboard --> GoalSettingForm : 目標・行動目標を設定

    Monthly --> Dashboard : ロゴリンク
    Monthly --> Monthly : 前月/次月ナビゲーション

    Weekly --> Dashboard : ロゴリンク
    Weekly --> Monthly : マンスリーページを開く<br>（タスク未設定時）
    Weekly --> Weekly : 前週/次週ナビゲーション

    GoalSetting --> Dashboard : 戻るリンク
    GoalSetting --> GoalSettingForm : 設定するボタン

    GoalSettingForm --> Dashboard : 戻るリンク
    GoalSettingForm --> GoalSetting : 目標設定の考え方リンク
```

## 2. 遷移の補足

### ダッシュボード（index.php）
- 今週の質問型アクション一覧を表示
- アクション未設定時はウィークリーページへの誘導リンクを表示
- ナビゲーションリンク: マンスリー（当月）、ウィークリー（今週）、目標設定の考え方、目標・行動目標を設定

### マンスリーページ（monthly.php）
- クエリパラメータ `?ym=YYYY-MM` で表示月を指定
- ヘッダーの左右矢印で前月・次月に遷移
- 入力はフォーカスアウト後1.5秒で自動保存（画面遷移なし）

### ウィークリーページ（weekly.php）
- クエリパラメータ `?week=YYYY-MM-DD` で表示週を指定（内部で月曜日に補正）
- ヘッダーの左右矢印で前週・次週に遷移
- タスク選択保存・質問型アクション保存後はページリロード
- デイリータスク未設定時はマンスリーページへの誘導リンクを表示

### 目標設定の考え方（goal_setting.php）
- 読み取り専用の解説ページ（MAC原則/WOOP/If-Then/プロセス目標/目標設定の落とし穴）
- 「設定する」ボタンで目標設定フォームへ遷移

### 目標・行動目標設定フォーム（goal_setting_form.php）
- WOOP保存 → 新規作成時はページリロード（goal_idを取得するため）
- 行動目標保存/If-Then保存 → トースト通知のみ（画面遷移なし）
- 目標削除 → 削除後に同ページへリダイレクト（新規入力状態になる）

## 3. API呼び出しフロー

```mermaid
flowchart LR
    subgraph ダッシュボード
        D1[完了チェック] -->|POST| A1[api/toggle_done.php]
        D2[所要時間記録] -->|POST| A2[api/save_duration.php]
    end

    subgraph マンスリーページ
        M1[自動保存] -->|POST| A3[api/save_monthly.php]
    end

    subgraph ウィークリーページ
        W1[タスク選択保存] -->|POST| A4[api/save_picks.php]
        W2[障害自動保存] -->|POST| A5[api/save_weekly.php]
        W3[質問型アクション保存] -->|POST| A6[api/save_question_actions.php]
    end

    subgraph 目標設定フォーム
        G1[WOOP保存] -->|POST| A7[api/goal_save.php]
        G2[行動目標保存] -->|POST| A8[api/action_goals_save.php]
        G3[If-Then保存] -->|POST| A9[api/if_then_rules_save.php]
        G4[目標削除] -->|POST| A10[api/goal_delete.php]
    end
```
