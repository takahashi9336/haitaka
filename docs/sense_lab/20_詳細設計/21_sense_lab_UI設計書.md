# Sense Lab（センスラボ） UI設計書

## 1. 画面構成・レイアウト

### スクラップ一覧画面 (index.php)
- **構成要素**:
  - 共通サイドバー（`private/components/sidebar.php`）
  - ヘッダー領域（アイコン `fa-wand-magic-sparkles` + タイトル「Sense Lab」 + 「新規スクラップ」ボタン）
  - カテゴリ別集計セクション（合計件数 + カテゴリ別バッジ）
  - 本番スクラップ一覧セクション（2カラムグリッド `md:grid-cols-2`、カード形式）
  - クイックスクラップ一覧セクション（リスト形式、divider区切り）
- **レイアウト**: サイドバー + メインコンテンツ（`flex h-screen`）、最大幅 `max-w-5xl`
- **空状態**: スクラップ0件時は案内テキスト表示（「まだスクラップがありません」）

### スクラップ新規登録画面 (new.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（タイトル「Sense Lab」 + サブタイトル「新規スクラップ」 + 「一覧へ」リンク）
  - エラー表示領域（バリデーションエラー時のみ表示、`border-rose-200 bg-rose-50`）
  - インプットセクション（画像ファイル選択 + タイトル入力 + カテゴリ選択）
  - 言語化セクション（理由1〜3のテキストエリア）
  - 送信ボタン（「保存する」）
- **レイアウト**: 最大幅 `max-w-3xl`、セクション間 `space-y-6`

### スクラップ詳細画面 (show.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（エントリタイトル + カテゴリバッジ + 作成日時 + 操作リンク「一覧/編集/削除」）
  - 画像セクション（画像がある場合のみ表示、`max-h-[520px] object-contain`）
  - 理由セクション（理由1〜3をリスト表示、テーマカラーの丸印付き）
  - AIコメントセクション（将来拡張用プレースホルダ、破線ボーダー）
- **レイアウト**: 最大幅 `max-w-4xl`

### スクラップ編集画面 (edit.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（タイトル「Sense Lab」 + サブタイトル「スクラップ編集」 + 「詳細へ」リンク）
  - エラー表示領域
  - 画像セクション（現在の画像プレビュー + 差し替え用ファイル選択）
  - タイトル・カテゴリ入力セクション
  - 言語化セクション（理由1〜3、既存値をプリフィル）
  - 送信ボタン（「更新する」）
- **レイアウト**: 最大幅 `max-w-3xl`

### クイックスクラップ編集画面 (quick_edit.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域（タイトル「クイックスクラップ編集」 + サブタイトル + 「一覧へ」リンク）
  - エラー表示領域
  - 画像プレビュー（存在する場合のみ）
  - 画像差し替えセクション
  - メモ入力セクション（テキストエリア、必須）
  - 理由入力セクション（理由1〜3、任意）
  - カテゴリ選択セクション（未設定/食事/デザイン/日常/その他）
  - 送信ボタン（「更新する」）
- **レイアウト**: 最大幅 `max-w-3xl`

### クイックスクラップFABパネル (sense_lab_utility_fab.php)
- **構成要素**:
  - FABボタン（右下固定、`w-14 h-14`、紫色 `bg-violet-600`、`fa-wand-magic-sparkles` アイコン）
  - オーバーレイ（パネル展開時に背面を覆う）
  - 入力パネル（メモテキストエリア + 画像ファイル選択 + カテゴリ選択 + 保存ボタン）
- **レイアウト**: `fixed right-6 bottom-32 z-[9000]`、パネルはモバイルで全幅・デスクトップで `md:max-w-sm`
- **状態遷移**: `data-state="closed"` / `data-state="open"` によるCSS制御（opacity + translateY）

## 2. 共通コンポーネントの利用
- `private/components/sidebar.php`: 共通サイドバー（ナビゲーション、ユーザー情報）
- `private/components/head_favicon.php`: ファビコン設定
- `private/components/theme_from_session.php`: セッションからテーマカラー変数（`$bodyBgClass`, `$headerBorder`, `$headerIconBg`, `$btnBgClass`, `$cardBorder`, `$cardIconBg`, `$cardIconText`, `$cardDeco` 等）を展開
- `www/assets/js/core.js`: 共通JS（`App.toast()` 等のユーティリティ、`App.currentAppKey()` の取得）
- `www/assets/js/sense_lab_image_compress.js`: 2MB超の画像を自動JPEG圧縮するクライアント処理

## 3. 状態と表示制御 (State & Conditional Rendering)

### 一覧画面 (index.php)
- **本番スクラップ一覧**:
  - `$entries` が空配列: 「まだスクラップがありません」の案内カードを表示
  - `$entries` が存在: 2カラムグリッドでカード表示
- **スクラップカード内の画像**:
  - `$entry['image_path']` が空: 画像部分を非表示、テキストのみのカード
  - `$entry['image_path']` が存在: `h-44 object-cover` で画像を表示
- **理由リスト**:
  - `$entry['reason_1']` 〜 `$entry['reason_3']`: 値がある理由のみ `・` 付きリストで表示
- **クイックスクラップ一覧**:
  - `$quickEntries` が空配列: 案内テキスト表示（FABボタンの使い方を説明）
  - `$quickEntries` が存在: divider区切りのリスト形式で表示
- **クイックスクラップ項目内**:
  - `$q['category_hint']`: 存在する場合のみバッジ表示（`bg-violet-50 text-violet-600`）
  - `$q['app_key']`: 存在する場合のみ起点アプリ名を表示
  - `$q['page_title']`: 存在する場合のみページタイトルを表示
  - `$q['image_path']`: 存在する場合のみサムネイル表示（`w-16 h-16`）
  - `$q['source_url']`: 存在する場合のみURLを表示

### 詳細画面 (show.php)
- **画像セクション**: `$entry['image_path']` がある場合のみ画像カードを表示
- **理由リスト**: `$entry['reason_1']` 〜 `$entry['reason_3']` がある理由のみテーマカラーの丸印付きで表示

### 編集画面 (edit.php)
- **現在の画像プレビュー**:
  - `$entry['image_path']` が空: 「画像は登録されていません。」のテキスト表示
  - `$entry['image_path']` が存在: 画像プレビュー表示
- **カテゴリ選択**: 現在の値で `selected` 属性をセット

### クイックスクラップ編集画面 (quick_edit.php)
- **画像プレビュー**: `$quick['image_path']` がある場合のみ上部に画像表示
- **カテゴリ選択**: 現在の `$quick['category_hint']` で `selected` 属性をセット

### FABパネル (sense_lab_utility_fab.php)
- **パネル開閉**:
  - `data-state="closed"`: `opacity: 0`, `translateY(10px)`, `pointer-events: none`
  - `data-state="open"`: `opacity: 1`, `translateY(0)`, `pointer-events: auto`
- **保存後の遷移**: 保存成功時に `location.href` でクイック編集画面へ自動遷移

## 4. スタイル・デザインルール
- **テーマカラー**: `violet`（`theme_from_session.php` によるセッション連動、`$headerIconBg`, `$btnBgClass` 等で適用）
- **フォント**: `'Inter', 'Noto Sans JP', sans-serif`（Google Fonts）
- **アイコン**: Font Awesome 6.5.1（`fa-wand-magic-sparkles`, `fa-chart-simple`, `fa-image`, `fa-pen-to-square`, `fa-comment-dots`, `fa-note-sticky`, `fa-robot` 等）
- **カードUI**: `bg-white rounded-xl border shadow-sm`（テーマ連動ボーダー `$cardBorder`）
- **入力フォーム**: `border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100`
- **テキストエリア**: `rounded-xl px-4 py-3 resize-none`
- **ボタン**: テーマカラー背景 + `text-white rounded-xl text-xs font-black tracking-wider shadow-md`
- **エラー表示**: `border-rose-200 bg-rose-50 text-rose-800`
- **削除ボタン**: `text-rose-500 hover:text-rose-700`、確認ダイアログ付き（`confirm()`）
- **レスポンシブ**: サイドバーはモバイルで固定位置、ハンバーガーメニューで開閉（`mobile-open` クラス）
- **FABボタン**: `fixed right-6 bottom-32 z-[9000]`、`bg-violet-600 shadow-lg shadow-violet-300 border-2 border-white rounded-full w-14 h-14`
