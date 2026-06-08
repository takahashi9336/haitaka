# メディア管理（Media） 機能概要書

## 1. 目的と背景
- 日向坂46 に関連するメディアコンテンツ（YouTube 動画、TikTok、Instagram リール、公式ブログ、ニュース、スケジュール）を一元的に管理し、ポータル利用者がいつでも閲覧・検索できる環境を提供する。
- 外部プラットフォームに散在するコンテンツを定期バッチで自動収集し、メンバーや楽曲との紐付けを行うことで、ファン活動の情報基盤として機能させる。

## 2. 解決するペインポイント（課題）
- YouTube・TikTok・ブログ・ニュース・スケジュールなど情報源が分散しており、横断的に把握できない。
- 動画とメンバー・楽曲の関連性が整理されておらず、「あのメンバーの MV を探したい」「このシングルの Trailer はどれか」といった検索ニーズに応えられない。
- 公式サイトのブログ・ニュース・スケジュール情報は公式 Web でしか参照できず、メンバー別の絞り込みやポータルとの統合ができない。

## 3. コアバリュー（主要な提供価値）
- **統合メディアライブラリ**: YouTube / TikTok / Instagram の動画を com_media_assets に集約し、日向坂固有のメタデータ（カテゴリ・メンバー紐付け・楽曲紐付け・ハッシュタグ）を hn_media_metadata 経由で管理する。
- **自動コンテンツ収集**: バッチジョブ（cron ベース）により、公式ブログ・ニュース・スケジュール・YouTube チャンネル動画を定期的にスクレイピング / API 取得し、DB を最新状態に維持する。
- **多様な登録導線**: 管理者は YouTube チャンネル検索、URL 貼り付け（oEmbed 自動取得）、CSV/TSV 一括インポート、外部クライアントアプリ連携（TikTok）の 4 つの方法でメディアを登録できる。
- **メンバー・楽曲との自動紐付け**: 動画タイトルや説明文からメンバー名を自動検出し、hn_media_members に紐付けを行う。MV カテゴリの場合はリリース/楽曲情報との紐付けも可能。

## 4. スコープ
- **対象ユーザー**:
  - 閲覧系（動画一覧）: ログインユーザー全般（`Core\Auth::requireLogin()`）
  - 管理系（登録・メンバー紐付け・楽曲紐付け・設定）: 日向坂ポータル管理者（`admin` / `hinata_admin` ロール、`HinataAuth::requireHinataAdmin()` による制御）
  - バッチ: CLI 実行 または 管理者ロールによる HTTP 実行
- **関連する主要システム / 外部 API**:
  - **YouTube Data API v3**: チャンネル動画取得、キーワード検索、動画詳細取得（videos.list）、oEmbed
  - **TikTok oEmbed API**: タイトル・サムネイル取得（APIキー不要）
  - **Instagram oEmbed API (Graph API v21.0)**: タイトル・サムネイル取得
  - **日向坂46 公式サイト**: ブログ・ニュース・スケジュールのスクレイピング
- **外部連携に必要な環境変数 (.env)**:
  - `YOUTUBE_API_KEY`: YouTube Data API v3 キー
  - `INSTAGRAM_ACCESS_TOKEN`: Instagram oEmbed（Meta App Access Token）
  - `HINATA_TIKTOK_CLIENT_TOKEN`: TikTok クライアントアプリ連携用の共有シークレットトークン
- **対象機能**:
  - **メディア閲覧**: 動画一覧画面（無限スクロール、フィルタ、ソート）
  - **メディア登録**: YouTube チャンネル検索 / URL 貼り付け / CSV 一括インポート
  - **メディア管理（統合画面）**: メンバー紐付け、楽曲紐付け、カテゴリ変更、メタデータ編集、ハッシュタグ管理
  - **バッチジョブ**: ブログスクレイプ、ニューススクレイプ、スケジュールスクレイプ、YouTube インポート / リフレッシュ、TikTok インポート / サムネイルリフレッシュ
- **非スコープ**:
  - メンバー情報管理自体（hn_members の CRUD は別機能）
  - リリース / 楽曲管理自体（hn_releases / hn_songs の CRUD は別機能）
  - イベント管理（hn_events）とのメディア連携はスコープ外

## 5. 現状（実装）サマリ
- **コントローラ**: `private/apps/Hinata/Controller/MediaController.php`（約 1780 行）
  - 画面表示（list / registerPage / mediaAdmin）、API（loadMore / bulkSave / bulkRegister / fetchOembed / YouTube 系 / メンバー紐付け / 楽曲紐付け / カテゴリ管理 / メタデータ更新 / 削除）、バッチ補助（refreshTikTokThumbnails）を包含
- **モデル**:
  - `MediaAssetModel.php` -- com_media_assets / hn_media_metadata の CRUD
  - `BlogModel.php` / `BlogScraper.php` -- hn_blog_posts のスクレイピングと管理
  - `NewsModel.php` / `NewsScraper.php` -- hn_news のスクレイピングと管理
  - `ScheduleModel.php` / `ScheduleScraper.php` -- hn_schedule のスクレイピングと管理
  - `YouTubeApiClient.php` -- YouTube Data API v3 ラッパー
- **バッチ統合ランナー**: `www/hinata/batch/run_all.php`
  - cron 式: `0 0,9,12,15,18,21 * * *`（3 時間ごと）
  - 毎回: ブログ最新 / ニュース / スケジュール / YouTube 新着
  - 週次（日曜 09:00）: YouTube リフレッシュ / ブログ全メンバー補充

## 6. データの基本方針
- **共通メディアアセット**: `com_media_assets` は日向坂以外の機能（例: イベント動画）からも参照される共通テーブル。platform + media_key で一意。
- **日向坂固有メタデータ**: `hn_media_metadata` は com_media_assets に対する 1:1 拡張テーブル。カテゴリ分類、メンバー紐付け（hn_media_members）、楽曲紐付け（hn_song_media_links）、ハッシュタグ（hn_media_hashtags）の起点。
- **スクレイピングデータ**: hn_blog_posts / hn_news / hn_schedule は公式サイトからの自動収集データ。article_id / article_code / schedule_code による UPSERT で重複を防止。
- **メンバー自動紐付け**: 動画タイトル・説明文中のメンバー名（漢字フルネーム or 名前 2 文字以上）を自動検出し紐付ける。

## 7. 外部連携の基本仕様（要点）

### YouTube Data API v3
- **認証**: API キー（`YOUTUBE_API_KEY`）をクエリパラメータ `key` として付与
- **主要利用**:
  - チャンネル情報: `/channels`（contentDetails.relatedPlaylists.uploads）
  - チャンネル動画一覧: `/playlistItems`（quota: 1 unit/call）
  - キーワード検索: `/search`（type=video、quota: 100 units/call）
  - 動画詳細: `/videos`（snippet,contentDetails,liveStreamingDetails、quota: 1 unit/call）
  - oEmbed: `https://www.youtube.com/oembed`（API キー不要）
- **プリセットチャンネル**: 日向坂46公式チャンネル / 日向坂ちゃんねる / ひなこいYouTubeチャンネル
- **media_type 判定**: contentDetails.duration <= 180s で short、liveStreamingDetails 存在で live、それ以外は video

### TikTok oEmbed API
- **認証**: API キー不要
- **エンドポイント**: `https://www.tiktok.com/oembed?url={url}`
- **取得情報**: title, author_name, thumbnail_url
- **公開日時**: 動画 ID の Snowflake ID 方式（上位 32 ビット = Unix timestamp）から推定
- **サムネイル**: TikTok CDN のホットリンク制限を回避するため、サーバ側でダウンロードし `/uploads/thumbnails/` に保存

### Instagram oEmbed API
- **認証**: Meta App Access Token（`INSTAGRAM_ACCESS_TOKEN`）
- **エンドポイント**: `https://graph.facebook.com/v21.0/instagram_oembed`
- **取得情報**: title, author_name, thumbnail_url

### 公式サイトスクレイピング
- **対象 URL**: `https://www.hinatazaka46.com/s/official/`
  - ブログ: `/diary/member/list`
  - ニュース: `/news/list`
  - スケジュール: `/media/list`
- **パーサー**: DOMDocument + DOMXPath
- **レート制限**: リクエスト間に 0.3～0.5 秒の待機（`usleep`）
