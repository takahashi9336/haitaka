# ダッシュボード (dashboard) 機能概要書

## 1. 目的と背景
- MyPlatform のログイン後トップページとして、ユーザーが日々参照する情報を一画面に集約する。
- タスク管理・日向坂イベント・RSSフィード・記事トレーニング・YouTube集中視聴など、各機能への導線と要約情報をまとめて提供し、情報のハブとして機能させる。
- 個人の成長・学習習慣を支援するため、ニュース記事を読んで「ほめポイント」「ツッコミポイント」を書き出す記事トレーニング機能を内包する。

## 2. 解決するペインポイント（課題）
- 各機能が独立しているため、ログイン後に何から手をつけるべきかが分散していた。
- ニュース記事を読み流すだけになりがちで、批判的思考や着眼点の言語化トレーニングを行う場がなかった。
- YouTube の複数チャンネルの最新動画を横断的に確認する手段がなかった。

## 3. コアバリュー（主要な提供価値）
- ログイン直後に「今日やるべきこと」「直近のイベント」「気になる記事」を一望できるポータル体験を提供する。
- 記事トレーニングにより、記事に対する「ほめ」「ツッコミ」を構造的に記録・蓄積し、文章力・批判的思考力の鍛錬を習慣化する。
- YouTube 集中視聴により、環境変数で指定したチャンネルの最新動画だけを一覧表示し、視聴対象を限定する集中モードを実現する。

## 4. スコープ
- 対象ユーザー: ログインユーザー全般（`Core\Auth::check()` 前提）。YouTube 集中視聴は管理者のみナビゲーションカードに表示。
- 関連する主要システム/外部API:
  - **Google News RSS**: 好奇心ブースト記事・AI関連記事の取得（`DashboardFeedService`）
  - **Blogger RSS (パレオな男)**: ブログ記事の取得（`DashboardFeedService`）
  - **YouTube Data API v3**: 集中視聴チャンネルの動画取得（`YouTubeFocusChannelService` 経由、`YouTubeApiClient` を利用）
- 外部連携に必要な環境変数（.env）:
  - **YouTube集中視聴**: `DASHBOARD_YOUTUBE_FOCUS_CHANNELS`（カンマ区切り `チャンネルID|video` / `@ハンドル|short`）
  - **YouTube API**: `YOUTUBE_API_KEY`
- 対象機能（ダッシュボード配下）:
  - **メインダッシュボード**: `www/index.php` -- ポータルトップ画面
  - **記事トレーニング**: `www/dashboard/article_training.php` -- 記事URL入力・ほめ/ツッコミ記入
  - **記事トレーニング履歴**: `www/dashboard/article_training_history.php` -- 過去のトレーニング一覧
  - **記事トレーニング保存API**: `www/dashboard/api/save_article_training.php` -- トレーニング内容のJSON API保存
  - **YouTube集中視聴**: `www/dashboard/youtube_focus.php` -- 指定チャンネルの最新動画一覧
- 非スコープ（本設計書では扱わない/別設計で扱う）:
  - タスク管理機能の詳細仕様（ダッシュボード側は `TaskModel::getActiveTasks()` の結果を表示するのみ）
  - 日向坂ポータル・イベント・ミーグリの詳細仕様（ダッシュボード側は `EventModel::getNextEvent()` の結果を表示するのみ）
  - Focus Note・メモ機能の詳細仕様（ダッシュボード側はカウント表示・リンクのみ）

## 5. 現状（実装）サマリ
- アプリキー: `dashboard`（`$appKey = 'dashboard'` でテーマ解決に使用）
- メインダッシュボード:
  - エントリ: `www/index.php`
  - 外部モデル参照: `TaskModel`, `NetaModel`, `EventModel`, `MeetGreetModel`, `FavoriteModel`, `WeeklyPageModel`, `QuestionActionModel`, `TripPlanModel`, `ChecklistItemModel`
  - RSSサービス: `private/apps/Dashboard/Service/DashboardFeedService.php`
- 記事トレーニング:
  - エントリ: `www/dashboard/article_training.php`
  - 保存API: `www/dashboard/api/save_article_training.php`
  - 履歴: `www/dashboard/article_training_history.php`
  - テーブル: `dashboard_article_training`
  - マイグレーション: `migrations/done/add_dashboard_article_training.sql`
- YouTube集中視聴:
  - エントリ: `www/dashboard/youtube_focus.php`
  - サービス: `private/apps/Dashboard/Service/YouTubeFocusChannelService.php`
  - 外部依存: `App\Hinata\Model\YouTubeApiClient`

## 6. データの基本方針（キャッシュ＋ユーザーデータ）
- **RSSフィード（好奇心ブースト・AI関連・パレオな男）**
  - キャッシュ: `private/cache/dashboard_feed_*.json`（TTL 1時間 = 3600秒）
  - 好奇心ブースト表示URL記録: `private/cache/dashboard_curiosity_shown_{userId}.json`（直近24件まで、日付・URLを保持）
  - 好奇心ブーストは同一ユーザー・同一日（JST）で同じ記事を表示し、リロードしても変わらない決定的選択ロジックを採用
- **記事トレーニング**
  - ユーザーデータ: `dashboard_article_training`（user_id + article_url のユニーク制約で UPSERT）
- **YouTube集中視聴**
  - キャッシュ: `private/cache/dashboard_youtube_focus_*.json`（TTL 30分 = 1800秒）
  - チャンネルごとに最大3件の動画を取得

## 7. 外部連携の基本仕様（要点）
### Google News RSS
- **認証**: 不要（公開RSS）
- **取得方式**: `file_get_contents` + User-Agent ヘッダ付き（Firefox偽装）
- **言語**: `hl=ja&gl=JP&ceid=JP:ja`
- **主要利用**:
  - 好奇心ブースト: テーマ別検索クエリ（宇宙、心理学、脳科学、デザイン等10テーマ）のRSS
  - AI関連: `生成AI` キーワードの検索RSS
- **直近フィルタ**: 7日以内（`RECENT_DAYS_DEFAULT = 7`）の記事のみ表示

### Blogger RSS（パレオな男）
- **認証**: 不要（公開RSS）
- **URL**: `https://yuchrszk.blogspot.com/feeds/posts/default?alt=rss`
- **取得件数**: 最大3件（`PALEO_MAX_ITEMS = 3`）

### YouTube Data API v3
- **認証**: APIキー（`YOUTUBE_API_KEY`）
- **主要利用**:
  - チャンネル解決: `YouTubeApiClient::getUploadsPlaylistContext()` でアップロードプレイリストIDを取得
  - 動画一覧: `/playlistItems`（`part=snippet`, `maxResults=50`）を最大5ページ走査
  - Shorts判定: `media_type` フィールドで `short` / `video` を区別
- **並列取得**: チャンネル間で最大2並列（`FETCH_PARALLELISM = 2`）
