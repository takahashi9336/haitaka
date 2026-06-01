# Focus Note（フォーカスノート） UI設計書

## 1. 画面構成・レイアウト

### ダッシュボード (index.php → Views/dashboard.php)
- **構成要素**:
  - 共通サイドバー（`private/components/sidebar.php`）
  - ヘッダー領域（アイコン `fa-bolt` + タイトル「Focus Note」）
  - 今週のアクションセクション（質問型アクション一覧、完了チェックボックス + 所要時間記録ボタン）
  - ナビゲーションセクション（マンスリー / ウィークリー / 目標設定の考え方 / 目標・行動目標を設定 への4つのリンクカード）
  - 所要時間入力モーダル（非表示状態で常駐、数値入力 + キャンセル/記録ボタン）
- **レイアウト**: サイドバー + メインコンテンツ（`flex h-screen`）、最大幅 `max-w-2xl`
- **空状態**: アクション0件時は案内テキスト「ウィークリーページでタスクを選択すると、ここに表示されます。」+ ウィークリーページへの誘導リンクを表示

### マンスリーページ (monthly.php → Views/monthly.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（ロゴリンク（ダッシュボードへ遷移）+ タイトル「マンスリー」+ 前月/次月ナビゲーション矢印 + 年月表示）
  - 自動保存ステータス表示（`#saveStatus`、保存成功時は emerald / 失敗時は amber）
  - マンスリーフォーム（5ステップ）:
    1. ターゲット（`textarea` rows=2）
    2. 重要度チェック（`textarea` rows=2）
    3. 具象イメージング（`textarea` rows=3）
    4. リバースプランニング（`textarea` rows=4）
    5. デイリータスク設定（動的 `input[type=text]` リスト + 「行を追加」ボタン）
- **レイアウト**: 最大幅 `max-w-2xl`、セクション間 `space-y-6`
- **自動保存**: 各フィールドの `input` / `blur` イベントから1.5秒のデバウンスで `api/save_monthly.php` へPOST

### ウィークリーページ (weekly.php → Views/weekly.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（ロゴリンク + タイトル「ウィークリー」+ 前週/次週ナビゲーション矢印 + 週表示「n/d 〜 n/d」）
  - セクション1: デイリータスク選択（チェックボックスリスト + 「選択を保存」ボタン）
  - セクション2・3: 障害コントラスト / 障害フィックス（2カラムグリッド `md:grid-cols-2`、`textarea` rows=4、自動保存）
  - セクション4: 質問型アクション（各ピックに対する時間入力 `w-24` + 場所入力 + プレビュー文表示 + 「時間・場所を保存」ボタン）
- **レイアウト**: 最大幅 `max-w-2xl`、セクション間 `space-y-8`
- **空状態**:
  - デイリータスク未設定時: 「マンスリーページでデイリータスクを設定してください。」+ マンスリーページへの誘導リンク
  - タスク未選択時: 「上でタスクを選択して保存すると、ここに表示されます。」
- **自動保存**: 障害コントラスト/フィックスは `input` / `blur` イベントから1.2秒デバウンスで `api/save_weekly.php` へPOST
- **リアルタイムプレビュー**: 時間・場所入力時に「[ユーザー名]は、[時間]に[場所]で[タスク]をするか？」の質問文をリアルタイム更新

### 目標設定の考え方 (goal_setting.php → Views/goal_setting.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（戻るリンク（ダッシュボードへ）+ アイコン `fa-bullseye` + タイトル「目標設定の考え方」）
  - 説明テキスト + 「設定する」ボタン（目標設定フォームへ遷移）
  - 5つの解説セクション（カード形式、番号付き）:
    1. MACの原則（Measurable / Actionable / Competent）
    2. WOOP（Wish / Outcome / Obstacle / Plan）
    3. If-Thenプランニング
    4. プロセス目標に集中する（成果目標 vs プロセス目標の対比グリッド `md:grid-cols-2`）
    5. 目標設定の落とし穴（副作用）
  - 推奨の始め方セクション（`fa-lightbulb` アイコン付き）
  - フッター（参考元リンク）
- **レイアウト**: 最大幅 `max-w-3xl`、セクション間 `space-y-8`
- **操作**: 読み取り専用ページ（入力フォームなし）

### 目標・行動目標設定フォーム (goal_setting_form.php → Views/goal_setting_form.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（戻るリンク + アイコン `fa-bullseye` + タイトル「目標・行動目標を設定」）
  - 「目標設定の考え方」リンク
  - セクション1: WOOP入力（Wish / Outcome / Obstacle / Plan / Being の5つの `textarea` + 「WOOPを保存」ボタン）
  - セクション2: 行動目標（MAC原則）入力（動的行リスト、各行: 行動内容 `input` + 測定方法 `input` + プロセス目標 `checkbox` + 「行を追加」リンク + 「行動目標を保存」ボタン）
  - セクション3: If-Thenルール入力（動的行リスト、各行: If条件 `input` + Then行動 `input` + 「行を追加」リンク + 「If-Thenを保存」ボタン）
  - セクション4（条件付き）: 目標削除ボタン（`goalId > 0` の場合のみ表示、赤枠ボーダー、`confirm()` ダイアログ付き）
- **レイアウト**: 最大幅 `max-w-3xl`、セクション間 `space-y-8`
- **状態制御**: 行動目標保存・If-Then保存は `goalId > 0`（WOOP保存済み）が前提。未保存時はトースト「先にWOOPを保存してください」を表示

## 2. 共通コンポーネントの利用
- `private/components/sidebar.php`: 共通サイドバー（ナビゲーション、ユーザー情報）
- `private/components/head_favicon.php`: ファビコン設定
- `private/components/theme_from_session.php`: セッションからテーマカラー変数（`$bodyBgClass`, `$headerBorder`, `$headerIconBg`, `$headerShadow`, `$headerIconStyle`, `$cardBorder`, `$bodyStyle` 等）を展開。`$appKey = 'focus_note'` で初期化
- `www/assets/js/core.js`: 共通JS（`App.post()` による非同期POST、`App.toast()` によるトースト通知）

## 3. 状態と表示制御 (State & Conditional Rendering)

### ダッシュボード
- **アクション一覧**:
  - `$todayActions` が空配列: 案内テキスト + ウィークリーページへのリンクを表示
  - `$todayActions` が存在: チェックボックス付きアクションリストを表示
- **アクション項目内**:
  - `$action['done']` が真: チェック済み + テキストに打ち消し線（`line-through text-slate-400`）
  - `$action['done']` が真 かつ `$action['actual_duration_min']` が存在: 所要時間（分）をバッジ表示
  - `$action['done']` が偽: 「時間」ボタンを表示（所要時間入力モーダルを開く）
- **所要時間モーダル**:
  - 初期状態: `display: none`
  - 「時間」ボタン押下時: `display: flex` に変更、数値入力にフォーカス

### マンスリーページ
- **デイリータスク行**:
  - `$dailyTasks` が空配列: 空のプレースホルダ入力2行を表示
  - `$dailyTasks` が存在: 既存タスク内容をプリフィルした入力行を表示
- **保存ステータス**:
  - 保存成功: `text-emerald-500`「保存しました」
  - 保存失敗: `text-amber-500` + エラーメッセージ

### ウィークリーページ
- **デイリータスク選択**:
  - `$availableDailyTasks` が空: マンスリーページへの誘導リンク表示
  - `$availableDailyTasks` が存在: チェックボックスリスト表示（選択済みタスクは `checked`）
- **質問型アクション**:
  - `$picks` が空: 案内テキスト「上でタスクを選択して保存すると、ここに表示されます。」
  - `$picks` が存在: 各ピックの時間・場所入力カード + プレビュー文を表示
- **タスク選択バリデーション**: 3〜5個の範囲外で保存ボタン押下時はトースト「3〜5つ選んでください」

### 目標設定フォーム
- **WOOP保存ボタン**: 常に表示（新規作成 / 更新の両方に対応）
- **行動目標保存 / If-Then保存**: `goalId <= 0`（WOOP未保存）の場合、トースト「先にWOOPを保存してください」を表示して処理中断
- **目標削除ボタン**: `$goalId > 0` の場合のみ表示（PHPの条件分岐）
- **WOOP新規作成後**: ページリロード（`goal_id` を取得して行動目標/If-Then保存を可能にするため）

## 4. スタイル・デザインルール
- **テーマカラー**: `emerald`（`sys_apps.theme_primary = 'emerald'`）。CSS変数 `--fn-theme` に `$themePrimaryHex` を設定し、`.fn-theme-btn` / `.fn-theme-link` で適用
- **フォント**: `'Inter', 'Noto Sans JP', sans-serif`（Google Fonts）
- **アイコン**: Font Awesome 6.5.1（`fa-bolt`, `fa-calendar-alt`, `fa-calendar-week`, `fa-bullseye`, `fa-pen`, `fa-chevron-left`, `fa-chevron-right`, `fa-plus`, `fa-lightbulb`, `fa-bars`, `fa-arrow-left`）
- **カードUI**: `bg-white rounded-xl border shadow-sm`（テーマ連動ボーダー `$cardBorder`）
- **入力フォーム（textarea）**: `w-full px-4 py-3 border border-slate-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]`
- **入力フォーム（text input）**: `w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--fn-theme)]`
- **ボタン（プライマリ）**: `.fn-theme-btn text-white text-sm font-bold rounded-xl px-4 py-2`、ホバー時 `filter: brightness(1.08)`
- **ボタン（削除）**: `border border-red-200 text-red-600 rounded-xl hover:bg-red-50`
- **ナビゲーションリンク（ダッシュボード）**: `inline-flex items-center gap-2 px-4 py-2 rounded-xl border bg-white hover:shadow-sm text-sm font-medium text-slate-600`
- **完了状態**: チェック済みアクションは `.fn-action-check:checked + .fn-action-label` で `text-decoration: line-through; color: #94a3b8`
- **自動保存ステータス**: `.fn-auto-save { font-size: 11px; color: #94a3b8; }`
- **レスポンシブ**: サイドバーはモバイルで `position: fixed; transform: translateX(-100%)`、ハンバーガーメニュー `#mobileMenuBtn` で `mobile-open` クラスをトグル。障害セクションは `md:grid-cols-2` で2カラム化
