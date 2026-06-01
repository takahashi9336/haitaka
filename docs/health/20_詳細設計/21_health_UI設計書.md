# Health（ヘルスケア/健康管理） UI設計書

## 1. 画面構成・レイアウト

### 1-1. Health ポータル画面 (`portal.php`)

- **構成要素**:
  - 共通サイドバー（`components/sidebar.php`）
  - ヘッダー: アプリアイコン（`fa-heart-pulse`）＋タイトル「Health」＋ユーザーID表示
  - メインコンテンツ: カード型グリッド（2列 / md:3列）
- **カード一覧**:
  - 食材ストック（`fa-basket-shopping`）→ `/health/kitchen_stock.php`
  - トレーニングメニュー（`fa-dumbbell`）→ `/health/training_menu.php`
  - 実施履歴（`fa-clipboard-check`）→ `/health/training_history.php`
  - 1週間の献立（`fa-calendar-week`）→ disabled（準備中）
- **レスポンシブ**: モバイルは `aspect-square` の正方形カード、デスクトップは説明文付きの横長カード

### 1-2. 食材ストック画面 (`kitchen_stock.php`)

- **構成要素**:
  - 共通サイドバー
  - ヘッダー: 戻るボタン（`← /health/`）＋アプリアイコン（`fa-basket-shopping`）＋タイトル「食材ストック」＋「Markdown形式でコピー」ボタン
  - グループフィルタバー: 「全て / 食材 / 調味料 / その他」のトグルボタン群
  - 入力フォーム: 食材名・数量・グループ（select）・購入日（date）・冷凍チェックボックス・追加ボタン
  - 一覧テーブル: 食材/購入日、グループ、数量（インライン編集）、冷凍チェックボックス（インライン切替）、削除ボタン
- **特殊UI**:
  - 「全て」フィルタ時はグループヘッダ行（食材:緑、調味料:琥珀色、その他:スレート）を挿入し、グループ別に分類表示
  - 特定グループフィルタ時は各行にグループバッジを個別表示
  - 数量・冷凍フラグは `change` イベントで即時パッチ更新
  - `Ctrl+Enter` で追加可能

### 1-3. トレーニングメニュー画面 (`training_menu.php`)

- **構成要素**:
  - 共通サイドバー
  - ヘッダー: 戻るボタン（`← /health/`）＋アプリアイコン（`fa-dumbbell`）＋タイトル「トレーニングメニュー」＋「実施履歴」リンクボタン
  - メニュー追加フォーム: メニュー名・回数（number, 初期値10）・時間（分+秒の2フィールド, 初期値1分0秒）・追加ボタン
  - HIITセッションカード: 種目数・合計時間サマリ＋「HIIT完了（まとめて記録）」ボタン
  - 登録済みメニューテーブル: メニュー名（インライン編集）・回数（インライン編集）・時間（分+秒のインライン編集）・削除ボタン
  - 参照動画セクション: YouTube埋め込み（iframe, `ieQLwxA2qGY`）
- **特殊UI**:
  - HIITボタンはメニューが0件のとき `disabled`（半透明＋`cursor-not-allowed`）
  - 全フィールドのインライン編集は `change` イベントで即時パッチ更新
  - `Ctrl+Enter` で追加可能

### 1-4. トレーニング実施履歴画面 (`training_history.php`)

- **構成要素**:
  - 共通サイドバー
  - ヘッダー: 戻るボタン（`← /health/training_menu.php`）＋アプリアイコン（`fa-clipboard-check`）＋タイトル「トレーニング実施履歴」
  - アクティビティカード: ヒートマップ（GitHub風, 直近6ヶ月/26週）＋統計（直近6ヶ月回数・現在連続日数・最長連続日数・今月回数）＋凡例
  - HIIT追加フォーム: 実施日（date, 初期値=今日）＋「HIITを追加」ボタン
  - 記録一覧テーブル: 実施日・内容・種目数・合計時間・削除ボタン
- **特殊UI - ヒートマップ**:
  - CSS Grid による7行 x 26+列のセルレイアウト
  - セル色はテーマカラー（`--health-theme`）を `color-mix()` で3段階に分割
    - level 0: `#ebedf0`（未実施）
    - level 1: テーマ色 30%
    - level 2: テーマ色 60%
    - level 3: テーマ色 100%
  - 月ラベル行＋曜日ラベル列（月・水・金）を表示
  - ホバー時に `outline` でハイライト、`title` 属性で日付と回数をツールチップ表示
- **特殊UI - HIIT行**:
  - `<details>` による折りたたみ。展開するとスナップショット（メニュー名・回数・時間）のサブテーブルを表示
  - HIITバッジ: `bg-emerald-50 text-emerald-700`

## 2. 共通コンポーネントの利用

| コンポーネント | パス | 役割 |
| :--- | :--- | :--- |
| サイドバー | `private/components/sidebar.php` | アプリ共通ナビゲーション |
| テーマ設定 | `private/components/theme_from_session.php` | `$appKey` に基づくテーマカラーとクラス変数の生成 |
| ファビコン | `private/components/head_favicon.php` | `<link rel="icon">` タグ出力 |
| core.js | `www/assets/js/core.js` | `App.post()` / `App.toast()` 等の共通ユーティリティ |

## 3. 状態と表示制御

### 3-1. ポータル - カード状態

| 条件 | 表示 |
| :--- | :--- |
| 実装済み機能 | `<a>` タグ、ホバー時に `translateY(-2px)` + シャドウ |
| 未実装機能（1週間の献立） | `<div>` + `.disabled`（`opacity: 0.55`, `cursor: not-allowed`, ホバーエフェクト無し） |

### 3-2. 食材ストック - グループフィルタ

| 条件 | 表示 |
| :--- | :--- |
| アクティブフィルタ | `bg-slate-900 text-white border-slate-900` |
| 非アクティブフィルタ | `bg-white text-slate-600 border-slate-200` |

### 3-3. トレーニングメニュー - HIITボタン

| 条件 | 表示 |
| :--- | :--- |
| メニュー0件 | `disabled`, `opacity-50`, `cursor-not-allowed` |
| メニュー1件以上 | 有効化、サマリに「N種目 . 合計 X分Y秒」を表示 |

### 3-4. 実施履歴 - 記録行

| 条件 | 表示 |
| :--- | :--- |
| `log_kind = 'hiit'` | `<details>` 折りたたみ、HIITバッジ、スナップショットサブテーブル |
| `log_kind = 'exercise'` | 通常行（メニュー名・回数・時間を直接表示） |

## 4. スタイル・デザインルール

- **テーマカラー**: `theme_from_session.php` が `$appKey` に対応する `sys_apps.theme_primary` からテーマ色を解決し、CSS変数 `--health-theme` として出力。ボタン・アイコン背景・ヒートマップに適用
- **フォント**: `Inter`（英数字）＋ `Noto Sans JP`（日本語）
- **アイコン**: Font Awesome 6.5.1（CDN）
- **CSS フレームワーク**: Tailwind CSS（CDN版 `cdn.tailwindcss.com`）
- **カード**: `bg-white rounded-xl border shadow-sm` に `$cardBorder` テーマクラスを付与
- **ヘッダー**: 高さ `h-16`, `bg-white/80 backdrop-blur-md`, `sticky top-0 z-10`
- **レスポンシブ**: モバイル時にサイドバーは `position: fixed` + `translateX(-100%)` で隠し、ハンバーガーボタンで `.mobile-open` クラスをトグル
