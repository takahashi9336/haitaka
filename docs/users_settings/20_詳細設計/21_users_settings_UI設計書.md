# ユーザー設定（users_settings） UI設計書

## 1. 画面構成・レイアウト

### 設定画面 (`private/apps/Settings/Views/index.php`)
- **構成要素**:
  - 共通サイドバー（`private/components/sidebar.php` を `require_once` で読み込み）
  - ヘッダー領域（モバイルメニューボタン、設定アイコン、タイトル「設定」）
  - メインコンテンツ（パスワード変更カード）

- **レイアウト詳細**:
  - `<body>`: `flex h-screen overflow-hidden` でサイドバーとメインを横並びに配置
  - `<main>`: `flex-1 flex flex-col min-w-0 relative` でメインエリアを構成
  - ヘッダー: 高さ `h-16`、`bg-white/80 backdrop-blur-md`、`sticky top-0 z-10`
  - コンテンツエリア: `flex-1 overflow-y-auto p-6 md:p-12` でスクロール可能
  - カードコンテナ: `max-w-4xl mx-auto w-full` で中央寄せ

- **パスワード変更カード**:
  - カード: `bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm`
  - アイコンバッジ: `w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg` に鍵アイコン（`fa-key`）
  - タイトル: 「パスワード変更」（`text-base font-bold text-slate-800`）
  - サブタイトル: 「現在のパスワードと新しいパスワードを入力してください」（`text-[10px] font-bold text-slate-400 tracking-wider`）
  - 入力フィールド（2つ）: `max-w-md` で幅制限、`h-12 rounded-xl` のパスワード入力
  - 送信ボタン: `bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black tracking-wider px-8 h-12 rounded-xl shadow-md shadow-indigo-200/50`

## 2. 共通コンポーネントの利用
- `private/components/sidebar.php`: 全画面共通のナビゲーションサイドバー。セッション情報からメニューツリーを動的に描画する。
- `private/components/head_favicon.php`: ファビコン・共通CSS読み込み用の`<head>`パーシャル。
- `/assets/js/core.js`: `App.post()` ヘルパーを提供する共通JavaScriptライブラリ。fetch APIのラッパーとして JSON の POST リクエストを送信する。

## 3. 状態と表示制御 (State & Conditional Rendering)
- **ログイン状態チェック**:
  - `SettingsController::index()` 内で `Auth::requireLogin()` を呼び出し、未ログイン時は `/login.php` へリダイレクト。
  - ログイン済みの場合のみ View を描画する。
- **パスワード変更フォーム**:
  - 常時表示（条件分岐なし）。ロールによる表示差異はない。
  - フォーム送信後:
    - 成功時: `alert('パスワードを更新しました')` → フォームリセット
    - 失敗時: `alert('エラー: ' + res.message)` → フォーム入力値は保持
- **モバイル対応**:
  - サイドバーは `768px` 以下で `position: fixed; transform: translateX(-100%)` で非表示になり、ハンバーガーメニュー（`#mobileMenuBtn`）で開閉する。

## 4. スタイル・デザインルール
- **テーマカラー**: インディゴ（`indigo-600`）を基調色として使用。ヘッダーアイコン・ボタン・フォーカスリングに適用。
- **フォント**: `Inter`（欧文）+ `Noto Sans JP`（和文）の組み合わせ。Google Fonts から CDN 読み込み。
- **特徴的なTailwindクラス**:
  - カードUI: `bg-white p-5 md:p-8 rounded-xl border border-slate-100 shadow-sm`
  - 入力フィールド: `border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none transition-all`
  - ラベル: `text-[10px] font-black text-slate-400 tracking-wider`
  - ボタン: `text-xs font-black tracking-wider h-12 rounded-xl shadow-md`
- **アイコンライブラリ**: Font Awesome 6.5.1（CDN）。`fa-solid fa-gear`（ヘッダー）、`fa-solid fa-key`（カード）を使用。
- **背景色**: `bg-[#f8fafc]`（本画面固有の薄グレー背景）。
