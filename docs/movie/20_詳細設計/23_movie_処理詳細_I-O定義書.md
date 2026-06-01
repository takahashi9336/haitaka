# 映画（Movie） 処理詳細・I/O定義書

認可の共通前提: **`Core\Auth` でログイン済み**。全画面は `MovieController` で `$this->auth->requireLogin()` を呼び出し、全 API エンドポイントは `$auth->check()` で未認証時に 401 JSON を返す。

## 1. エンドポイント / アクション一覧

### 1.1 MovieController → HTML（画面）

| HTTP | 公開パス (movie/ より) | メソッド | 入力 | 出力 |
|------|------------------------|----------|------|------|
| GET | index.php | dashboard | ― | HTML。スタッツ集計、Chart.js データ、ガチャ状態、出演者ランキング、友人視聴を PHP で取得しサーバーサイド描画。おすすめは JS 非同期ロード |
| GET | list.php | index | `tab`（既定 `watchlist`）, `sort`, `order`, `filter`, `credit_role`, `person_id`, `person_ids`, `person_name`, `person_names` | HTML 一覧 |
| GET | detail.php | detail | `id`（mv_user_movies.id） | HTML 詳細。TMDB 詳細も取得し runtime/genres/overview/watch_providers を最新化 |
| GET | search.php | searchPage | `q`（検索クエリ） | HTML 検索結果ページ |
| GET | import.php | import | ― | HTML 一括登録 |
| GET | bulk_edit.php | bulkEdit | `tab`, `sort`, `order` | HTML 一括編集 |
| GET | pickup.php | personPickupPage | `role_kind`, `person_id`, `person_ids`, `person_name`, `person_names`, `person_names_csv` | HTML 人物別ピックアップ |
| GET | dashboard.php | ― | ― | 301 リダイレクト → `/movie/` |

### 1.2 MovieController → JSON API

| HTTP | 公開パス (movie/api/ より) | メソッド | 入力形式 | 出力 |
|------|----------------------------|----------|----------|------|
| GET | search.php | search | `q`, `page` | TMDB映画検索結果 + ユーザー登録状況 |
| GET | tmdb_detail.php | tmdbDetailApi | `tmdb_id` | TMDB映画詳細（runtime/tagline/providers/user_status） |
| POST | add.php | add | JSON: `tmdb_id`, `status`, `tmdb_data`（任意）, `title`, `poster_path` 等 | 映画追加結果 |
| POST | add_manual.php | addManual | JSON: `title`, `status` | 仮登録映画追加結果 |
| POST | update.php | updateEntry | JSON: `id`, `status`（任意）, `rating`, `memo`, `watched_date` | 更新結果。見た登録時に credits 自動保存 |
| POST | update_tags.php | updateTags | JSON: `id`, `tags`（配列） | タグ更新結果 |
| POST | remove.php | remove | JSON: `id` | 削除結果 |
| POST | link_tmdb.php | linkTmdb | JSON: `movie_id`, `tmdb_id`, `tmdb_data`（任意） | TMDB紐付け結果 |
| GET | gacha.php | gachaApi | `action`（`status` / 省略で spin） | ガチャ結果（status/spins/movie） |
| POST | gacha.php | gachaApi | JSON: `action=refund` | ガチャ回数 refund |
| GET | recommendations.php | recommendationsApi | `type`（`personal` / `genre` / `trending`） | レコメンド映画リスト（6時間キャッシュ） |
| POST | bulk_add.php | bulkAdd | JSON: `items`（配列） | 一括登録結果（added/skipped/errors） |
| POST | bulk_update.php | bulkUpdate | JSON: `updates`（配列）, `deletes`（配列） | 一括更新/削除結果 |
| GET | person_pickup.php | personPickupApi | `role_kind`, `person_id`, `person_ids`, `limit`, `person_names_csv` | 未登録映画リスト |

### 1.3 直接コントローラ外の API（TmdbApiClient 直接利用）

| HTTP | 公開パス (movie/api/ より) | 処理クラス | 入力 | 出力 |
|------|----------------------------|------------|------|------|
| GET | search_person.php | TmdbApiClient::searchPersons | `q`, `page` | TMDB人物検索結果 |
| GET | search_keyword.php | TmdbApiClient::searchKeywords | `q`, `page` | TMDBキーワード検索結果 |

### 1.4 バッチ処理

| HTTP | 公開パス (movie/ より) | 処理内容 | 入出力 |
|------|------------------------|----------|--------|
| GET | batch_providers.php | 画面表示（対象件数・UI） | HTML |
| GET | batch_providers.php?api=1&offset=N | 5件ずつ配信情報取得・更新 | JSON: `processed`, `done`, `next_offset`, `total` |
| GET | batch_credits.php | 画面表示（対象件数・UI） | HTML |
| GET | batch_credits.php?api=1&cursor=N | 3件ずつ credits 取得・保存 | JSON: `processed`, `done`, `next_cursor`, `total`, `remaining` |
| GET | batch_credits.php?status=1&json=1 | 進捗状況取得 | JSON: `status`, `processed`, `total`, `errors`, `started_at`, `updated_at`, `last_title` |

## 2. 処理フロー詳細

### dashboard（index.php）

1. **認可**: `requireLogin()`。
2. **データ取得**:
   - `UserMovieModel`: watchlist/watched 件数、今月鑑賞数、総視聴時間、月別鑑賞本数（12ヶ月）、ジャンル分布、評価スコア分布、出演者ランキング（cast/director/writer 各5名）。
   - 平均評価を rating 分布から算出。
   - `TmdbApiClient::isConfigured()` で TMDB 利用可否チェック。
   - `FriendsActivityService`: 友人の映画視聴履歴（最大12件）。
3. **出力**: `movie_dashboard.php` をレンダリング。おすすめ映画は JS から非同期で `/movie/api/recommendations.php` を呼び出し。

### search（api/search.php）

1. **バリデーション**: `q` が空でないこと、TMDB が設定済みであること。
2. **TMDB検索**: `TmdbApiClient::searchMovies($query, $page)`。
3. **ユーザー状態付与**: 検索結果の各映画について `UserMovieModel::findByTmdbId()` でユーザーの登録状況（`user_status` / `user_movie_id`）を付与。
4. **レスポンス**: `{ status: 'success', data: { results: [...], total_results, total_pages, page } }`。

### add（api/add.php）

1. **バリデーション**: `tmdb_id > 0`、`status` が `watchlist` / `watched` のいずれか。
2. **映画キャッシュ**: `MovieModel::findOrCreateByTmdbId()` で mv_movies にキャッシュ。TMDB詳細データが POST に含まれない場合は API から取得。
3. **ユーザーリスト追加**: `UserMovieModel::addMovie()` で mv_user_movies に INSERT。既存エントリがあれば status のみ UPDATE。
4. **レスポンス**: `{ status: 'success', message: '見たいリストに追加しました', data: { id, movie_status } }`。

### updateEntry（api/update.php）

1. **ID解決**: `id` をまず mv_user_movies.id として検索。見つからなければ movie_id として引き直し（互換処理）。
2. **ステータス変更**:
   - `status = 'watched'`: `markAsWatched()` で watched_date / rating / memo を設定。成功時に `MovieCreditsService::ensureCreditsByTmdbId()` で credits を自動保存（TMDB連携済みの場合）。credits 保存失敗は更新成功として扱う（ログのみ）。
   - `status = 'watchlist'`: `moveToWatchlist()` で watched_date / rating を NULL に。
3. **レビュー更新**: status 指定なしの場合、`updateReview()` で rating / memo / watched_date を部分更新。`watched_date` が空文字列＋ key 存在の場合は NULL クリア。
4. **レスポンス**: `{ status: 'success', message: '更新しました' }`。

### gachaApi（api/gacha.php）

1. **POST（refund）**: `GachaState` の spins を -1 し、movie を null に。「見た」登録後にガチャ回数を戻す用途。
2. **GET（status）**: 現在のガチャ状態（spins / max_spins / movie）を返却。日付が変わっていればリセット。
3. **GET（spin）**: spins < maxSpins なら `getRandomWatchlistItem()` でランダム1件取得。spins をインクリメントし `GachaState` に保存。
   - `status = 'success'`: 正常抽選。
   - `status = 'done'`: 上限到達（2回使用済み）。
   - `status = 'empty'`: 見たいリストが空。

### recommendationsApi（api/recommendations.php）

1. **type バリデーション**: `personal` / `genre` / `trending` のいずれか。
2. **キャッシュ確認**: ファイルベースキャッシュ（6時間TTL）。ヒット時はユーザー登録状況（`_registered`）を再付与して返却。
3. **キャッシュミス時の取得**:
   - **personal**: 高評価映画（rating >= 7）上位5件の TMDB ID → 各映画の recommendations API → 重複除去 → 未登録のみ → vote_average 降順 → 上位20件。
   - **genre**: ユーザーの上位3ジャンル → TMDB Discover API（popularity.desc / vote_average >= 6.0 / vote_count >= 100）→ 未登録のみ → 上位20件。
   - **trending**: TMDB Trending（week）→ 上位20件（登録済みフラグ付き、除外はしない）。
4. **キャッシュ保存**: 取得結果をファイルに書き込み。

### linkTmdb（api/link_tmdb.php）

1. **バリデーション**: `movie_id > 0`、`tmdb_id > 0`。対象映画が存在し、tmdb_id が NULL（仮登録）であること。
2. **既存 TMDB キャッシュとの統合**: 同一 tmdb_id の mv_movies レコードが既に存在する場合:
   - ユーザーエントリの movie_id を既存レコードに付け替え（UPDATE mv_user_movies）。
   - 仮登録レコードを DELETE。
3. **新規紐付け**: 既存キャッシュがない場合、TMDB API で詳細取得 → `linkToTmdb()` で UPDATE。

### bulkAdd（api/bulk_add.php）

1. **入力**: items 配列。各 item に `tmdb_id`（任意）、`tmdb_data`（任意）、`title`、`status`。
2. **処理**: 各 item について:
   - tmdb_id があれば `findOrCreateByTmdbId()` で映画キャッシュを確保。
   - tmdb_id がなければ `findPlaceholderByTitle()` → `createPlaceholder()` で仮登録。
   - 登録済みならスキップ。未登録なら `addMovie()`。
3. **レスポンス**: `{ status: 'success', message: 'N件を登録しました（M件スキップ）', data: { added, skipped, errors } }`。

### bulkUpdate（api/bulk_update.php）

1. **入力**: `updates`（配列: id, status, rating, watched_date, memo）、`deletes`（配列: id）。
2. **処理**: deletes を先に処理（`delete()`）、その後 updates を個別に `update()`。
3. **レスポンス**: `{ status: 'success', message: 'N件を更新、M件を削除しました' }`。

### personPickupApi（api/person_pickup.php）

1. **バリデーション**: `role_kind` が `cast` / `director` / `writer`。person_ids が1つ以上。
2. **TMDB人物クレジット取得**: 各 person_id について `TmdbApiClient::getPersonMovieCredits()` を呼び出し。
   - cast: credits.cast 全件を収集。
   - director/writer: credits.crew から job フィルタ（Director / Writer,Screenplay,Story）。
3. **フィルタ**: ユーザー登録済み映画を除外。タイトル空を除外。
4. **ソート**: popularity 降順 → release_date 降順。
5. **レスポンス**: `{ status: 'success', data: { results: [...] } }`。各映画に `matched_person_names`（一致した人物名）を付与。
6. **レート制限**: 人物ごとに `usleep(120000 + random_int(0, 80000))`（120-200ms）。

### batch_providers（batch_providers.php）

1. **対象**: `mv_movies` のうち tmdb_id が NOT NULL かつ watch_providers が NULL または空。
2. **処理**: 5件ずつ `TmdbApiClient::getMovieDetail()` → JP リージョンの providers を `updateWatchProviders()` で保存。
3. **ウェイト**: 各映画間で `usleep(300000)`（300ms）。
4. **フロント**: JS から `?api=1&offset=N` を再帰呼び出し。`done: true` で完了表示。

### batch_credits（batch_credits.php）

1. **対象**: `mv_movies` のうち tmdb_id が NOT NULL かつ mv_movie_credits に行がない映画。
2. **処理**: 3件ずつ（cursor ベース）`TmdbApiClient::getMovieDetail()` → `MovieCreditsService::saveFromTmdbDetail()` で DELETE → INSERT。
3. **ウェイト**: 各映画間で `usleep(450000 + random_int(0, 250000))`（450-700ms）。
4. **進捗管理**: ファイルベース（`private/cache/batch/movie_credits_status_{userId}.json`）。status = `idle` / `running` / `done` / `error`。
5. **自動開始**: `?autostart=1` で JS から自動キック。既に running なら進捗ポーリングに切替。

## 3. 外部連携の境界

### TMDB API（TmdbApiClient）

- **認証**: APIキー（`TMDB_API_KEY`）をクエリパラメータ `api_key` として付与。.env またはサーバー環境変数から取得。
- **言語**: `language=ja-JP`。
- **通信**: `file_get_contents` + `stream_context_create`。タイムアウト 10 秒。
- **エラー処理**: レスポンスが false または JSON パースエラー時は `Logger::error` でログ出力し null を返す。

### FriendsActivity（ダッシュボード）

- `FriendsActivityService::hasViewableUsers()` で閲覧可能ユーザーの有無を確認。
- `FriendsActivityService::getFriendsWatchedItems()` でフィルタ `'movie'` を指定し友人の映画視聴履歴を取得。
- 映画側は表示・追加導線として利用するのみ。FriendsActivity 側のデータ収集・権限モデルは対象外。

## 4. エラー・ログ

- コントローラ内の全処理は `try/catch` で囲み、`Logger::errorWithContext()` でログ出力。
- 画面処理のエラーは変数を 0 / 空配列にフォールバックし、画面は正常描画。
- API のエラーは `{ status: 'error', message: '...' }` の JSON を返す。
- credits 自動保存（見た登録時）の失敗は更新成功として扱い、ログのみ記録。
