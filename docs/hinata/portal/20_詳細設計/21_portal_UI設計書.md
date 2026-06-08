# ポータル（Portal） UI設計書

## 1. 画面構成・レイアウト

### ポータルダッシュボード (portal.php)

- **構成要素**:
  - 共通サイドバー (`components/sidebar.php`)
  - ヘッダー: アプリアイコン（太陽）、タイトル「日向坂ポータル」、検索ボックス（PC表示、`Cmd+K` ショートカットヒント付き）
  - メインコンテンツ（`v2-grid` CSS Gridレイアウト）:
    - **推しエリア** (`v2-oshi`): メイン推し＋サブ推しサムネイル切り替え、KPIカード群
    - **TOPICSエリア** (`v2-topics`): トピックカルーセル、お知らせカルーセル、応募締め切り
    - **次のイベント** (`v2-nextEvent`): カウントダウンバー、iCal/Googleカレンダーボタン
    - **応募締め切り** (`v2-deadlines`): 応募締切日が近いイベントの表示
    - **お知らせ** (`v2-announcements`): お知らせカード横スクロール
    - **最新リリース** (`v2-release`): ジャケット、収録曲アコーディオン、MV/ストリーミング
    - **誕生日** (`v2-birthday`): 次の誕生日メンバー表示
    - **本日のミーグリ予定** (`v2-meetgreet`): 当日のスロット一覧
    - **今日は何の日** (`v2-todayInHistory`): 過去のリリース/イベント
    - **最新ブログ** (`v2-blog`): 全メンバーのブログカルーセル
    - **YouTube** (`v2-youtube`): チャンネルタブ切替、2行グリッド横スクロール
    - **TikTok** (`v2-tiktok`): 縦長カード、2行グリッド横スクロール
    - **SNSリンク** (`v2-sns`): 公式リンクアイコン群
    - **アプリ** (`v2-apps`): サブ機能へのカード導線、管理者のみ管理ツールdetails
  - 管理者FAB: 右下固定ボタン（ポータル情報管理へ遷移）
  - 共通コンポーネント: `video_modal.php`, `blog_image_modal.php`

- **レスポンシブグリッド配置** (`v2-grid`):
  - SP (768px未満): 1カラム、推し -> TOPICS -> 次のイベント -> リリース -> 誕生日 の順で縦積み
  - PC (768px以上): 10カラムグリッド
    - 推し: 全幅 (row 1)
    - TOPICS: col 1-7 / 次のイベント: col 8-10 (row 2、高さ揃え)
    - 応募締め切り: 全幅 (row 3)
    - お知らせ: 全幅 (row 4)
    - リリース: col 1-7 / 誕生日: col 8-10 (row 5)
    - ミーグリ: 全幅 (row 6)
    - 今日は何の日: 全幅 (row 7)
    - ブログ: 全幅 (row 8)
    - YouTube: col 1-5 / TikTok: col 6-10 (row 9)
    - アプリ: 全幅 (row 10)
    - SNS: 全幅 (row 11)
  - 中間幅 (768-999px): 誕生日が下段に回り、リリースが全幅化

### ポータル情報管理 (portal_info_admin.php)

- **構成要素**:
  - 共通サイドバー (`components/sidebar.php`)
  - ヘッダー: アプリアイコン（新聞）、タイトル「ポータル情報管理」、ポータル戻りリンク
  - タブバー: TOPICS / お知らせ / 応募締め切り
  - メインコンテンツ（3カラムグリッド）:
    - 左2/3: 登録/編集フォーム
    - 右1/3: 一覧リスト（クリックでフォームにデータセット）

## 2. 共通コンポーネントの利用
- `components/sidebar.php`: 全画面共通サイドバー
- `components/head_favicon.php`: favicon/メタ情報
- `components/theme_from_session.php`: セッションからテーマカラー変数を取得（`$themePrimaryHex`, `$cardBorder`, `$headerIconBg` 等）
- `components/video_modal.php`: YouTube/TikTok動画のモーダル再生
- `components/blog_image_modal.php`: ブログ画像のズームモーダル

## 3. 状態と表示制御 (State & Conditional Rendering)

### ポータルダッシュボード

- **推しエリア**:
  - `$hasOshi === true`: 推しサマリを表示（メイン推し + サブ推しサムネイル）
  - `$hasOshi === false`: 「推しを設定しましょう！」CTA表示
  - JS `OshiPortal.switchMain(level)`: 推しレベル切り替え時にDOM要素を動的更新（API不要）

- **次のイベント**:
  - `$hasNextEvent === true`: カウントダウンバー、イベント名、カレンダー連携ボタン表示
  - イベント当日 (`$days === 0`): 「今日」表示、ミーグリスロットセクション出現

- **本日のミーグリ予定**:
  - `!empty($todayMeetGreetSlots)`: 次のイベントが当日の場合のみ表示

- **TOPICSエリア**:
  - `!empty($topics) || !empty($announcements) || !empty($upcomingDeadlines)`: いずれかがある場合のみセクション表示
  - 各サブセクション（TOPICS/お知らせ/応募締め切り）は個別に空判定

- **最新リリース**:
  - `$releaseIsNew === true` (60日以内): グリッド上部配置、NEWバッジ付き
  - `$releaseIsNew === false`: グリッド下部配置（アプリカード前）

- **誕生日**:
  - `$hasBdBanner === true` (2週間以内): 表示
  - 当日誕生日: 「本日！」テキスト

- **今日は何の日**:
  - `!empty($todayInHistory)`: 過去のリリース/イベントがある場合のみ表示

- **管理者限定**:
  - `$isAdmin === true` (admin / hinata_admin): 管理ツールdetails表示、FABボタン表示、TOPICS横に管理リンク表示

### ポータル情報管理

- **タブ切り替え**: `$_GET['tab']` パラメータ (`topics` / `announcements` / `deadlines`) でアクティブタブを制御
- **編集モード**: 一覧のアイテムクリックでフォームにデータセット、キャンセルボタン出現
- **応募締め切り**: イベント選択で動的にフォーム行を表示、行の追加/削除が可能

## 4. スタイル・デザインルール

- **テーマカラー**: `$themePrimaryHex` を CSS変数 `--mock-accent` として適用。カードアイコン、ボーダー等に動的反映。
- **デザイン体系 (hinata-portal)**:
  - メッシュグラデーション背景 (`mock-mesh`): 3つの放射グラデーションの重ね合わせ
  - ガラスモーフィズム (`mock-glass`): `backdrop-filter: blur(16px)` + 半透明白背景
  - カード: `border-radius: 24px`, ホバー時に `translateY(-2px)` + シャドウ強化
  - メディアカード: `border-radius: 18px`, ボーダー + シャドウ
  - スクロールバー非表示: `scrollbar-width: none` + webkit疑似要素
- **カルーセル共通パターン**:
  - 左右矢印ボタン (`yt-arrow`): 円形、白背景、端到達で自動非表示
  - 横スクロール: `overflow-x: auto`, `scroll-behavior: smooth`
  - YouTube/TikTok: 2行グリッド + 横スクロール (`yt-grid-scroll` / `tk-grid-scroll`)
- **推しエリア**:
  - メイン推し画像: `w-44 aspect-square rounded-2xl` (PC) / `w-28 aspect-square rounded-2xl` (SP)
  - サブ推しサムネイル: `w-[72px] h-[72px]` (PC) / `w-[56px] h-[56px]` (SP)、ガラスモーフィズム
  - KPIカード: `mock-glass rounded-2xl border border-white/60`
- **特徴的なTailwindクラス**:
  - アプリカード: `hinata-portal-card rounded-2xl border shadow-sm`
  - 次のイベントボックス: `next-event-box rounded-3xl` グラデーション背景
  - 進捗バー: `next-event-bar` 左端=今日、右端=イベント日、グラデーション塗り
- **アプリカードアイコン**: 各機能ごとにグラデーション背景色クラス（`app-icon-grad-sky`, `app-icon-grad-pink` 等）
