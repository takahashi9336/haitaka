# ダッシュボード (dashboard) 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧
| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `index.php` | インライン処理（www/index.php） | なし | HTML |
| GET | `dashboard/article_training.php` | インライン処理（www/dashboard/article_training.php） | `?url=`, `?title=` (任意) | HTML |
| GET | `dashboard/article_training_history.php` | インライン処理（www/dashboard/article_training_history.php） | なし | HTML |
| POST | `dashboard/api/save_article_training.php` | インライン処理（www/dashboard/api/save_article_training.php） | JSON body | JSON |
| GET | `dashboard/youtube_focus.php` | インライン処理（www/dashboard/youtube_focus.php） | `?view=`, `?refresh=` (任意) | HTML |

## 2. 処理フロー詳細

### メインダッシュボード (GET index.php)
1. **認証チェック**:
   - `Core\Auth::check()` で認証を確認。未認証なら `/login.php` へリダイレクト。
   - `sidebar_mode === 'restricted'` かつ dashboard アプリが許可アプリ一覧にない場合は `$user['default_route']` へリダイレクト。
2. **データ取得**:
   - `TaskModel::getActiveTasks()` で未完了タスクを取得し、優先度降順・期限昇順でソート。上位5件をダッシュボード表示用に抽出。
   - `NetaModel::getGroupedNeta()` でネタ件数を集計。
   - `EventModel::getNextEvent()` で次の日向坂イベントを取得。
   - イベントに紐づく遠征プラン（`TripPlanModel::findLatestTripIdForHinataEvent()`）、ミーグリ予定（`MeetGreetModel::getSlotsByEventId()`）、チェックリスト（`ChecklistItemModel::getByTripPlanId()`）を取得。
   - `FavoriteModel::getOshiMembers()` で推しメンバーを取得し、誕生日が30日以内のメンバーを抽出。
   - `DashboardFeedService::getCuriosityItem()` で好奇心ブースト記事1件を取得。
   - `DashboardFeedService::getAiItem()` でAI関連記事1件を取得。
   - `DashboardFeedService::getPaleoItems()` でパレオな男ブログ記事を最大3件取得。
   - `dashboard_article_training` テーブルからユーザーの記事トレーニング総件数をCOUNTで取得。
   - `WeeklyPageModel` + `QuestionActionModel` で Focus Note の未完了アクション件数を取得。
3. **レスポンス**: HTMLテンプレートを直接出力。

### 記事トレーニング画面 (GET dashboard/article_training.php)
1. **認証チェック**:
   - `Core\Auth::check()` で認証を確認。未認証なら `/login.php` へリダイレクト。
2. **URLパラメータ処理**:
   - `$_GET['url']` を取得し、`dashboard_normalize_article_training_url()` でバリデーション。
   - バリデーション内容: 空チェック、`http://` or `https://` スキーム確認（省略時は `https://` 自動付与）、500文字以内、`parse_url()` による構造チェック。
   - `$_GET['title']` を取得し、500文字を超える場合は切り捨て。
3. **既存データ取得**:
   - URLが有効な場合、`dashboard_article_training` テーブルから `user_id` + `article_url` で既存レコードを検索。
   - 既存レコードがあり `article_title` が保存されている場合、それを画面タイトルとして使用。
4. **レスポンス**: URLの有無に応じて入力フォームまたはトレーニング画面をHTML出力。

### 記事トレーニング保存API (POST dashboard/api/save_article_training.php)
1. **リクエスト受け取り・バリデーション**:
   - Content-Type: `application/json`（`php://input` からJSON読み取り。フォールバックとして `$_POST` も対応）
   - 認証チェック: `Core\Auth::check()`。未認証なら HTTP 401 + JSON エラー。
   - `article_url` が空なら HTTP 400 + JSON エラー。
   - 各コメント（`praise_1` 〜 `praise_3`, `tsukkomi_1` 〜 `tsukkomi_3`）を `norm()` 関数で正規化（trim + 500文字制限）。
   - 全コメントが空なら HTTP 400 + JSON エラー（「少なくとも1つはコメントを入力してください」）。
   - `article_title` が空の場合は `article_url` で補完。
2. **ビジネスロジック・DB更新**:
   - `INSERT INTO dashboard_article_training ... ON DUPLICATE KEY UPDATE` で UPSERT。
   - ユニーク制約 `uk_user_article (user_id, article_url)` により、同一ユーザー・同一URLの場合は既存レコードを更新。
   - `created_at` はINSERT時のみ設定、`updated_at` は常に `NOW()` で更新。
3. **レスポンス**:
   - 成功時: `{"status": "success"}` (HTTP 200)
   - エラー時: `{"status": "error", "message": "エラーメッセージ"}` (HTTP 400/401/500)

**入力パラメータ詳細:**
| パラメータ名 | 型 | 必須 | 最大長 | 説明 |
| :--- | :--- | :--- | :--- | :--- |
| article_url | string | 必須 | - | 対象記事のURL |
| article_title | string | 任意 | - | 記事タイトル。空の場合はarticle_urlで補完 |
| praise_1 | string | 任意 | 500文字 | ほめポイント1 |
| praise_2 | string | 任意 | 500文字 | ほめポイント2 |
| praise_3 | string | 任意 | 500文字 | ほめポイント3 |
| tsukkomi_1 | string | 任意 | 500文字 | ツッコミポイント1 |
| tsukkomi_2 | string | 任意 | 500文字 | ツッコミポイント2 |
| tsukkomi_3 | string | 任意 | 500文字 | ツッコミポイント3 |

### 記事トレーニング履歴 (GET dashboard/article_training_history.php)
1. **認証チェック**:
   - `Core\Auth::check()` で認証を確認。未認証なら `/login.php` へリダイレクト。
2. **データ取得**:
   - `dashboard_article_training` テーブルから `user_id` でフィルタし、`updated_at DESC` で最大50件取得。
   - 取得カラム: `article_url`, `article_title`, `created_at`, `updated_at`。
3. **レスポンス**: 履歴リストまたは空状態メッセージをHTML出力。

### YouTube集中視聴 (GET dashboard/youtube_focus.php)
1. **認証チェック**:
   - `Core\Auth::check()` で認証を確認。未認証なら `/login.php` へリダイレクト。
2. **パラメータ処理**:
   - `$_GET['refresh']`: `'1'` の場合はキャッシュを無視して強制再取得。
   - `$_GET['view']`: `'grouped'` の場合はチャンネル別表示、それ以外は投稿日時順表示（デフォルト `'all'`）。
3. **フィード取得** (`YouTubeFocusChannelService::getFeed()`):
   - 環境変数 `DASHBOARD_YOUTUBE_FOCUS_CHANNELS` を読み取り、カンマ区切りでパース。
   - 各エントリは `チャンネルID|video` or `@ハンドル|short` 形式。パイプ右辺が省略されたら `video` モード。
   - `YouTubeApiClient::getUploadsPlaylistContext()` でチャンネルのアップロードプレイリストIDを解決。
   - `/playlistItems` APIを最大5ページ・2並列で走査し、モードに応じて通常動画またはShortsをフィルタして最大3件取得。
   - 結果をJSON形式でファイルキャッシュ（30分TTL）。
4. **表示処理**:
   - 投稿日時順 (`view=all`): 全チャンネルの動画を `published_at` の降順でソート。ShortsとRegularを分離して表示。
   - チャンネル別 (`view=grouped`): チャンネルごとにセクションを分け、動画一覧を表示。
5. **レスポンス**: HTML出力。

## 3. サービスクラス処理詳細

### DashboardFeedService

#### getCuriosityItem(int $userId): ?array
1. 日付キーをJST基準で生成（`Y-m-d` 形式）。
2. 過去に表示したURLリストを `dashboard_curiosity_shown_{userId}.json` から読み込み（当日以外の日付のURL）。
3. 10テーマの検索クエリを `crc32(baseSeed|theme|index)` で決定的にシャッフル。
4. シャッフル順にテーマを走査し、各テーマの Google News RSS を取得・キャッシュ。
5. 7日以内の記事のみにフィルタし、表示済みURLを除外。
6. `crc32(pickSeed)` で決定的に1記事を選択。
7. 全テーマで該当なしならフォールバッククエリ（`科学 最新`）で再試行。
8. 選択した記事URLを表示済みリストに記録（最大24件保持）。

#### getAiItem(): ?array
1. `生成AI` キーワードの Google News RSS を取得・キャッシュ。
2. 7日以内の記事にフィルタ。
3. `array_rand()` でランダムに1件選択。

#### getPaleoItems(): array
1. パレオな男ブログの Blogger RSS を取得・キャッシュ。
2. 最大3件を返す。

### YouTubeFocusChannelService

#### getFeed(bool $forceRefresh): array
1. 環境変数 `DASHBOARD_YOUTUBE_FOCUS_CHANNELS` を読み取り。未設定なら `configured: false` を返す。
2. `YouTubeApiClient::isConfigured()` でAPIキーの有無を確認。未設定なら `api_configured: false` を返す。
3. キャッシュキーは環境変数値のSHA-256ハッシュ。キャッシュが有効（30分以内）かつ強制再取得でなければキャッシュを返す。
4. `parseEnvEntries()` でカンマ区切りの環境変数をパースし、各エントリの `spec`（チャンネルID/ハンドル）と `mode`（video/short）を抽出。
5. `fetchAllFocusChannels()` で全チャンネルの動画を取得。結果をキャッシュファイルに保存。
