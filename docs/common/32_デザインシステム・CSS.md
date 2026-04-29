# デザインシステム・CSS定義書

## 1. 技術スタック（画面側）

| 項目 | 内容 |
|------|------|
| **ユーティリティCSS** | 多くの画面で [Tailwind CSS](https://tailwindcss.com/) を CDN (`cdn.tailwindcss.com`) から読込 |
| **アイコン** | [Font Awesome 6.x](https://fontawesome.com/)（CDN、`fa-solid` 等） |
| **サイト共通CSS** | [`www/assets/css/common.css`](../../www/assets/css/common.css) — `head_favicon.php` 経由で読込 |

## 2. カラー・テーマ（アプリ単位）

- **マスタ**: `sys_apps.theme_primary` / `theme_light`（DB）。ログイン時に `Auth::login` がツリー化した `$_SESSION['user']['apps']` に載る。
- **解決ロジック**: [`private/components/theme_from_session.php`](../../private/components/theme_from_session.php)
  - **Tailwind名**（例: `indigo`, `sky`）: `THEME_ALLOWED_TW` に含まれるもののみ拡張。`bg-{name}-50` 等のクラスを生成。
  - **HEX**（例: `#6366f1`）: インライン `style` と薄い背景色（rgba）でカード・ヘッダを表現。
- **サイドバーアクティブ表示**: [`private/components/sidebar.php`](../../private/components/sidebar.php) 内の `$themeActiveAttrs` と同じ許容パレット。

## 3. 共通スタイル（common.css）

| 内容 | 説明 |
|------|------|
| **フォームコントロール** | `input, textarea, select` に `font-size: max(16px, 1em) !important` — iOS のフォーカス時ズーム抑制 |
| **レイアウト変数** | `:root { --sidebar-width: 240px; }` |
| **body** | 背景 `#f8fafc`、文字色 `#1e293b` |
| **.main-container** | `display: flex; min-height: 100vh` |
| **.content-wrapper** | メインカラム。`flex: 1; overflow-y: auto` |

画面固有の大きなスタイルはアプリ配下（例: `www/live_trip/css/show.css`）に置く。

## 4. よく使うTailwindパターン（非網羅）

- カード: `bg-white border border-slate-200 rounded-xl shadow-sm`
- テキスト: `text-slate-700` / `text-slate-600`
- トースト（`flash_toast`）: 成功 `bg-emerald-600`、失敗 `bg-red-600`

## 5. レスポンシブ

- Tailwind の `md:` / `lg:` を画面ごとに使用。サイドバーのモバイル開閉は **`sidebar.php` に集約**（`www/assets/js/core.js` の `App.initSidebar` は意図的に no-op）。
