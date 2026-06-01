# フレンズアクティビティ (FriendsActivity) UI設計書

## 1. 画面構成・レイアウト

### 1.1 友人の視聴一覧画面 (activity.php)

- **エントリポイント**: `www/friends_activity.php` → `FriendsActivityController::activity()` → `private/apps/FriendsActivity/Views/activity.php`
- **構成要素**:
  - 共通サイドバー (`private/components/sidebar.php`)
  - ヘッダー領域 (戻るボタン `<a href="/">`、`fa-user-group` アイコン、タイトル「友人の視聴」)
  - ユーザーフィルタ帯 (友達/グループメンバーのピルボタン一覧)
  - 種別タブ (全て / アニメ / 映画 / ドラマ)
  - 作品カードグリッド (5カラム最大のレスポンシブグリッド)
  - プレビューモーダル (各アプリの `_*_search_shared.php` を読み込み)

- **レイアウト構造**:
  ```
  <body class="flex h-screen overflow-hidden">
    ├── sidebar.php (左 240px / モバイルはオーバーレイ)
    └── <main class="flex-1 flex flex-col min-w-0">
        ├── <header> 固定ヘッダー (h-16, sticky top-0)
        └── <div class="flex-1 overflow-y-auto p-6 md:p-12">
            └── <div class="max-w-5xl mx-auto">
                ├── ユーザーフィルタ帯
                ├── 種別タブ
                └── 作品カードグリッド or 空メッセージ
  ```

### 1.2 友達管理画面 (friends.php / 管理者専用)

- **エントリポイント**: `www/admin/friends.php` → `AdminController::friends()` → `private/apps/Admin/Views/friends.php`
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域 (戻るボタン `/admin/`、`fa-user-group` アイコン、タイトル「友達管理」)
  - 成功/エラーフラッシュメッセージ (`$_SESSION['admin_success']` / `$_SESSION['admin_error']`)
  - 友達一覧テーブル (ユーザーA / ユーザーB / 登録日 / 削除ボタン)
  - 友達登録モーダル (`#addFriendModal`: ユーザーA/Bセレクト + 登録ボタン)

### 1.3 グループ管理画面 (friend_groups.php / 管理者専用)

- **エントリポイント**: `www/admin/friend_groups.php` → `AdminController::friendGroups()` → `private/apps/Admin/Views/friend_groups.php`
- **構成要素**:
  - 共通サイドバー
  - ヘッダー領域 (戻るボタン `/admin/`、`fa-users-rectangle` アイコン、タイトル「グループ管理」)
  - 成功/エラーフラッシュメッセージ
  - グループ編集フォーム (GETパラメータ `edit=ID` 指定時にインライン表示: グループ名入力 + メンバーチェックボックス)
  - グループ一覧カード (グループ名 / メンバー数 / 編集・削除ボタン)
  - グループ作成モーダル (`#createGroupModal`: グループ名入力 + メンバーチェックボックス + 作成ボタン)

### 1.4 ダッシュボード埋め込みセクション

各エンタメダッシュボード内の「友人の視聴」タブパネルに、横スクロール形式で最大12件の作品カードを表示する。

| ダッシュボード | ファイルパス | フィルタ値 | 表示件数 |
| :--- | :--- | :--- | :--- |
| 映画ダッシュボード | `private/apps/Movie/Views/movie_dashboard.php` | `filter=movie` | 最大12件 |
| アニメダッシュボード | `private/apps/Anime/Views/anime_dashboard.php` | `filter=anime` | 最大12件 |
| ドラマダッシュボード | `private/apps/Drama/Views/drama_dashboard.php` | `filter=drama` | 最大12件 |

各セクションには「もっと見る」リンクがあり、`/friends_activity.php?filter=<type>` へ遷移する。

## 2. 共通コンポーネントの利用

| コンポーネント | ファイルパス | 利用画面 | 役割 |
| :--- | :--- | :--- | :--- |
| サイドバー | `private/components/sidebar.php` | 全画面 | アプリメニュー、モバイル開閉 |
| Favicon/共通CSS | `private/components/head_favicon.php` | 全画面 | `<head>` 内のfavicon・共通スタイル |
| テーマ変数解決 | `private/components/theme_from_session.php` | 全画面 | セッションからテーマカラー変数を生成 |
| アニメプレビュー | `private/apps/Anime/Views/_anime_search_shared.php` | 一覧画面 | `AnimePreview.open()` モーダル |
| 映画プレビュー | `private/apps/Movie/Views/_movie_search_shared.php` | 一覧画面 | `MoviePreview.open()` モーダル |
| ドラマプレビュー | `private/apps/Drama/Views/_drama_search_shared.php` | 一覧画面 | `DramaPreview.open()` モーダル |
| core.js | `www/assets/js/core.js` | 全画面 | `App.post()` (非同期POST)、`App.toast()` (トースト通知) |

## 3. 状態と表示制御 (State & Conditional Rendering)

### 3.1 友人の視聴一覧画面

- **閲覧可能ユーザーの有無 (`$hasViewable`)**:
  - `false`: 「友達やグループに参加すると、ここに友人の視聴履歴が表示されます」メッセージを表示。ユーザーフィルタ帯・種別タブは表示するがカードグリッドは非表示。
  - `true` かつ `$items` が空: 「まだ視聴履歴はありません」メッセージを表示。
  - `true` かつ `$items` あり: カードグリッドを表示。プレビューモーダル用の共有スクリプト (`_*_search_shared.php`) と `FriendsActivity` オブジェクトのJSを読み込む。

- **ユーザーフィルタ帯**:
  - `$hasViewable && !empty($viewableUsers)` の場合のみ表示。
  - 現在選択中のユーザー (`$currentUserId`) に一致するピルは `bg-slate-800 text-white`、それ以外は `bg-slate-100 text-slate-600`。
  - 「全て」ピル (`user_id=0` 相当) は常に先頭に表示。

- **種別タブ (フィルタ)**:
  - 現在の `$currentFilter` に一致するタブは `.active` クラスが付与され、テーマカラーが適用される。
  - 各タブのリンクは現在の `$currentUserId` を維持するようクエリパラメータを組み立てる。

- **作品カード内の登録済み判定 (`$item['_registered']`)**:
  - `true`: 右上に緑色チェックマーク (`bg-emerald-500`, `fa-check`) を表示。追加ボタンは非表示。
  - `false`: 種別に応じた追加ボタン群を右上に縦並びで表示。

- **作品カード内の追加ボタン (種別別)**:
  - アニメ: 見たい (`wanna_watch`) / 見てる (`watching`) / 見た (`watched`) の3ボタン
  - 映画: 見たい (`watchlist`) / 見た (`watched`) の2ボタン
  - ドラマ: 見たい (`wanna_watch`) / 見てる (`watching`) / 見た (`watched`) の3ボタン

- **モーダル表示制御 (`$canModal`)**:
  - アニメ: 常にモーダル対応 (`AnimePreview.open`)
  - 映画: `tmdb_id` がある場合のみモーダル対応 (`MoviePreview.open`)
  - ドラマ: `tmdb_id` がある場合のみモーダル対応 (`DramaPreview.open`)
  - モーダル非対応の場合は `<a>` タグで `detail_url` へ直接リンク

### 3.2 友達管理画面

- **フラッシュメッセージ**: `$_SESSION['admin_error']` (amber系) / `$_SESSION['admin_success']` (emerald系) を表示後 `unset`
- **友達一覧テーブル**: `$friends` が空の場合は「登録済みの友達はありません」を表示
- **削除確認**: `onsubmit="return confirm('削除してよろしいですか？')"` でブラウザ標準ダイアログ

### 3.3 グループ管理画面

- **編集フォーム**: `$editGroup` が非null (GETパラメータ `edit=ID` 指定時) の場合のみ一覧の上部にインライン表示
- **作成ボタン**: 編集フォーム表示中は非表示 (`<?php if (!$editGroup): ?>`)
- **メンバーチェックボックス**: `$allUsers` をループし、`$editMemberIds` に含まれるユーザーは `checked` 属性を付与

## 4. スタイル・デザインルール

- **テーマカラー**: `theme_from_session.php` で解決された `$themePrimaryHex` をCSS変数 `--activity-theme` に設定。一覧画面は `$appKey = 'dashboard'`、管理画面は `$appKey = 'admin'` を使用。
- **フォント**: `Inter` (欧文) + `Noto Sans JP` (和文)、Google Fonts CDN読み込み
- **CSSフレームワーク**: Tailwind CSS (CDN版 `cdn.tailwindcss.com`)
- **アイコン**: Font Awesome 6.5.1 (CDN)
- **カードUI**: `bg-white rounded-xl border border-slate-100 shadow-sm` + ホバー時 `hover:shadow-md`
- **カードグリッド**: `grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4`
- **カード画像**: `aspect-[2/3]` 固定比率、`object-cover`、ホバー時 `group-hover:scale-105`
- **種別バッジ**: カード左上に `bg-black/60 text-white text-[10px] font-bold rounded` で表示
- **登録済みチェック**: カード右上に `w-6 h-6 bg-emerald-500 text-white rounded-full` のアイコン
- **追加ボタン**: `w-6 h-6 bg-white/90 rounded-full shadow backdrop-blur-sm` をベースに、ホバー時は各色に変化 (amber=見たい, sky=見てる, green=見た)
- **タブUI**: `.activity-tab.active` はテーマカラー対応、非アクティブは `text-slate-400`
- **ユーザーフィルタ**: `rounded-full` のピル型ボタン、選択中は `bg-slate-800 text-white`
- **管理画面テーブル**: `overflow-x-auto rounded-xl border border-slate-100` のラッパー内に配置
- **管理画面モーダル**: `fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm` + `max-w-md rounded-2xl`
- **モバイル対応**: サイドバーは768px以下で `position: fixed; transform: translateX(-100%)` のオーバーレイ方式。ハンバーガーメニューボタン (`#mobileMenuBtn`) で `mobile-open` クラスをトグル
