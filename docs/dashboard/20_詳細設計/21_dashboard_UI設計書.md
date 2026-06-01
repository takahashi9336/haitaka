# ダッシュボード (dashboard) UI設計書

## 1. 画面構成・レイアウト

### メインダッシュボード (www/index.php)
- **構成要素**:
  - 共通サイドバー (`private/components/sidebar.php`)
  - ヘッダー領域（アプリアイコン、タイトル「ダッシュボード」、ユーザーID表示）
  - 日付・曜日表示セクション（未完了タスク件数を併記）
  - 気になる記事セクション（RSSフィード：好奇心ブースト・AI関連・パレオな男）
  - 2カラムレイアウト（左: 次の日向坂イベント + 各リストへ、右: タスク一覧）
  - 推しの誕生日セクション（30日以内の推しメンバー、条件付き表示）
  - ナビゲーションカードグリッド（モバイル: 2列、PC: 3-4列）

### 記事トレーニング画面 (www/dashboard/article_training.php)
- **構成要素**:
  - 共通サイドバー (`private/components/sidebar.php`)
  - ヘッダー領域（アイコン: `fa-pen-to-square`、タイトル「記事トレーニング」、状態に応じたサブテキスト）
  - URL入力フォームセクション（記事未選択時）-- URLフィールド（必須）、タイトルフィールド（任意）、ニュースサイトリンク（Yahoo! / Google）
  - 記事情報表示セクション（記事選択済み時）-- 記事リンク、別の記事を入力するリンク
  - タイマーセクション（記事選択済み時）-- プルダウン（3/5/8/10/15/20分）、開始/リセットボタン、カウントダウン表示
  - ほめポイントセクション（記事選択済み時）-- テキストエリア3つ（各500文字以内）
  - ツッコミポイントセクション（記事選択済み時）-- テキストエリア3つ（各500文字以内）
  - フッターアクション（記事選択済み時）-- 履歴リンク、ダッシュボードリンク、保存ボタン

### 記事トレーニング履歴画面 (www/dashboard/article_training_history.php)
- **構成要素**:
  - 共通サイドバー (`private/components/sidebar.php`)
  - ヘッダー領域（アイコン: `fa-clock-rotate-left`、タイトル「記事トレーニング履歴」、「新しい記事でトレーニング」ボタン）
  - 履歴リスト（最大50件、各アイテム: 記事タイトル、URL、最終更新日時）
  - 空状態メッセージ（履歴がない場合の案内テキスト + トレーニング開始ボタン）

### YouTube集中視聴画面 (www/dashboard/youtube_focus.php)
- **構成要素**:
  - 共通サイドバー (`private/components/sidebar.php`)
  - ヘッダー領域（アイコン: `fa-brands fa-youtube`、タイトル「YouTube 集中視聴」、ダッシュボードへのリンクボタン）
  - 表示モード切替タブ（投稿日時順 / チャンネル別）
  - 投稿日時順表示（Shortsは横スクロール、通常動画はグリッド: 1/2/3列）
  - チャンネル別表示（チャンネルごとにセクション分割、横スクロール/3列グリッド）
  - 動画再生モーダル (`private/components/video_modal.php`)
  - 設定未完了時の警告メッセージ（チャンネル未設定 / APIキー未設定）

## 2. 共通コンポーネントの利用
- `private/components/sidebar.php`: 全画面で共通サイドバーを表示
- `private/components/head_favicon.php`: 全画面で共通faviconを読み込み
- `private/components/theme_from_session.php`: `$appKey = 'dashboard'` でテーマカラーを解決し、ヘッダーアイコン背景・ボーダー等に適用
- `private/components/video_modal.php`: YouTube集中視聴画面でモーダル動画再生に使用
- `/assets/js/core.js`: 全画面で共通JS（`App.post()`, `App.toast()` 等）を読み込み

## 3. 状態と表示制御 (State & Conditional Rendering)

### メインダッシュボード
- **気になる記事セクション**: `$dashboardFeedRows` が空でない場合のみ表示
- **次の日向坂イベントセクション**: `$nextEvent` が存在し `days_left >= 0` の場合はイベント情報を表示、それ以外は「近日のイベントはありません」
- **準備チェック進捗バー**: 遠征管理のチェックリストが1件以上ある場合のみ表示
- **遠征管理へボタン**: `$dashboardNextEventTripId` がある場合のみ表示
- **ミーグリ予定ボタン**: イベントカテゴリが2（ミーグリ）または3（個別ミーグリ）の場合のみ表示
- **推しの誕生日セクション**: `$oshiBirthdays` が空でない場合のみ表示
- **YouTube集中カード**: `$user['role'] === 'admin'` の場合のみナビカードに表示
- **アニメカード**: 環境変数 `ANIME_BETA_ID_NAMES` にユーザーの `id_name` が含まれる場合のみ表示
- **ナビカード数値表示**: 値が0でアイコン指定ありならチェックアイコン、0でテキスト指定ありなら代替テキスト、正の値ならカウントアップアニメーション、-1ならギアアイコン
- **restrictedロール**: `sidebar_mode === 'restricted'` かつ dashboard アプリが許可されていない場合は `default_route` へリダイレクト

### 記事トレーニング画面
- **URL未入力時**: URL入力フォームを表示（ニュースサイトへのリンクアイコン付き）
- **URL入力済み時**: 記事情報、タイマー、ほめポイント、ツッコミポイント、保存ボタンを表示
- **既存レコードあり時**: 各テキストエリアに保存済みの値をプリフィル
- **URLバリデーションエラー時**: エラーメッセージを表示し、入力値をプリフィル
- **保存ボタン**: 少なくとも1つのコメントが入力されていないとトースト警告

### YouTube集中視聴画面
- **チャンネル未設定**: 環境変数設定方法の案内メッセージを表示
- **APIキー未設定**: APIキー設定案内のエラーメッセージを表示
- **キャッシュ利用時**: 「直近の取得結果を表示しています（約30分キャッシュ）」を表示
- **表示モード**: `?view=all`（デフォルト）で投稿日時順、`?view=grouped` でチャンネル別
- **Shorts**: 投稿日時順表示ではShortsと通常動画を分離。Shortsはアスペクト比9:16の横スクロール
- **動画なし**: 「表示できる動画がありません。」メッセージ

## 4. スタイル・デザインルール
- **テーマカラー**: `$appKey = 'dashboard'` で `theme_from_session.php` からテーマを解決。各ボックスは関連アプリのテーマを個別適用（`$noteTheme`, `$hinataTheme`, `$taskTheme`, `$adminTheme` 等）
- **CSSカスタムプロパティ**: `--dashboard-theme`, `--note-theme`, `--hinata-box-theme`, `--task-box-theme`, `--admin-box-theme`
- **フォント**: Inter + Noto Sans JP（Google Fonts CDN）
- **カードUI**: `bg-white rounded-xl border border-slate-200 shadow-sm` を基本とし、各アプリテーマの `cardBorder` で色を変更
- **ナビカードグリッド**:
  - モバイル: `grid-cols-2 gap-3` + 最小高さ `min-h-[7.25rem]`
  - PC: `md:grid-cols-3 lg:grid-cols-4 gap-3`
- **タイマー表示**: `font-mono text-2xl font-black tabular-nums` でモノスペース大字
- **カウントアップ**: `.count-up` クラスで `requestAnimationFrame` による600ms easeOutCubicアニメーション
- **Tailwind CSS**: CDN版（`cdn.tailwindcss.com`）を全画面で使用
- **Font Awesome 6.5.1**: CDN版を全画面で使用
- **レスポンシブサイドバー**: 768px以下で非表示→モバイルメニューボタンでスライドイン表示
