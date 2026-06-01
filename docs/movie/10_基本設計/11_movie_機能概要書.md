# 映画（Movie） 機能概要書

## 1. 目的と背景
- MyPlatform 内で映画の「見たい」「見た」管理を一元的に行う機能を提供する。
- TMDB（The Movie Database）APIと連携し、映画情報の検索・取得・キャッシュを自動化することで、手入力の手間を削減する。
- 個人の鑑賞記録（視聴日・評価・メモ・タグ）を蓄積し、統計・分析・レコメンドに活用する。

## 2. 解決するペインポイント（課題）
- 見たい映画を思いつくたびにメモアプリ等に散らばって記録していたものを、構造化されたリストで一元管理したい。
- 映画を「見た」あとに感想や評価を残す場所がなく、過去の鑑賞履歴を振り返りにくい。
- 「次に何を観るか」を決められない問題に対し、ガチャ（ランダム抽選）やおすすめ機能で解決したい。
- 配信サービス（Netflix、Amazon Prime Video 等）での視聴可否を手動で調べる手間を省きたい。

## 3. コアバリュー（主要な提供価値）
- **TMDB連携による自動データ補完**: タイトル検索でポスター・あらすじ・公開日・ジャンル・上映時間・評価・キャスト・監督情報を自動取得。
- **ウォッチリスト管理**: 見たい（watchlist）⇔ 見た（watched）のステータス管理と、評価（1-10）・視聴日・メモ・タグによる記録。
- **ガチャ機能**: 見たいリストからランダムに1本を抽選し、「次に観る映画」を決定支援（1日最大2回）。
- **おすすめ機能**: 高評価映画ベースのパーソナルレコメンド、好みジャンルベースのDiscover、トレンド映画、友人の視聴履歴からの発見。
- **配信情報の自動取得**: TMDB watch/providers（JustWatch経由）から日本の配信サービス情報を取得・表示。
- **出演者ランキングと探索**: 鑑賞済み映画の俳優・監督・脚本家をランキング表示し、その人物の未登録映画をピックアップ。
- **統計ダッシュボード**: 月別鑑賞本数、ジャンル分布、評価スコア分布、総視聴時間をグラフで可視化。
- **一括操作**: テキスト入力からの一括登録、リスト全体の一括編集・一括削除。
- **統合検索**: 映画・人物・キーワード・ジャンルを横断的に検索し、追加や探索の導線を提供。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` 前提）
- 関連する主要システム/外部API:
  - **TMDB API**: 映画検索・詳細取得・レコメンド・トレンド・Discover・配信情報・人物検索・キーワード検索・人物クレジット
  - **JustWatch**: TMDB経由で日本の配信サービス情報を取得（flatrate/rent/buy）
  - **FriendsActivity**: 友人の視聴履歴を映画ダッシュボードに表示
  - **GachaState**: ガチャの状態管理（日次リセット、回数制御）
- 外部連携に必要な環境変数（.env）:
  - `TMDB_API_KEY`: TMDB APIキー
- 非スコープ（本設計書では扱わない/別設計で扱う）
  - FriendsActivity のデータ収集・権限モデル自体（映画側は表示・追加導線として利用のみ）
  - sys_apps / sys_roles / sys_role_apps の汎用管理仕様
  - エンタメ統合ダッシュボード（`/entame/`）側の集計ロジック

## 5. 現状（実装）サマリ
- アプリ登録: `sys_apps.app_key = 'movie'`（`/movie` プレフィックス、アイコン `fa-film`、テーマカラー `violet`）
- エントリポイント: `www/movie/index.php`（ダッシュボード）
- Controller: `private/apps/Movie/Controller/MovieController.php`
- Model:
  - `private/apps/Movie/Model/MovieModel.php`（`mv_movies` テーブル操作）
  - `private/apps/Movie/Model/UserMovieModel.php`（`mv_user_movies` テーブル操作）
  - `private/apps/Movie/Model/TmdbApiClient.php`（TMDB API通信）
- Service: `private/apps/Movie/Service/MovieCreditsService.php`（出演者情報の保存）
- View: `private/apps/Movie/Views/` 配下の各PHPファイル
- バッチ:
  - `www/movie/batch_providers.php`（配信情報一括更新）
  - `www/movie/batch_credits.php`（出演者情報一括投入）

## 6. データの基本方針（キャッシュ＋ユーザーリスト）
- **作品キャッシュ**: `mv_movies`
  - TMDBから取得した映画情報をローカルDBにキャッシュ。
  - 仮登録（TMDB未連携）の場合は `tmdb_id` が NULL。
  - `watch_providers` カラムにJustWatch経由の配信情報をJSON保存。
- **ユーザーリスト**: `mv_user_movies`
  - `status` = `watchlist`（見たい）/ `watched`（見た）で管理。
  - 個人の `rating`（1-10）、`memo`、`watched_date`、`tags`（JSON配列）を保持。
  - `user_id` + `movie_id` でユニーク制約。
- **出演者情報**: `mv_movie_credits`
  - TMDB credits から上位キャスト20名、監督10名、脚本家10名を保存。
  - `role_kind` = `cast` / `director` / `writer` で分類。
  - ユーザーの鑑賞映画と結合してランキング集計に使用。

## 7. 外部連携の基本仕様（TMDB API）
- **認証**: APIキー（`TMDB_API_KEY`）をクエリパラメータ `api_key` として付与
- **言語**: `language=ja-JP`（日本語レスポンス）
- **レート制限対策**: バッチ処理では `usleep()` によるウェイトとジッター付き遅延を適用
- **主要エンドポイント**:
  - 映画検索: `GET /search/movie`
  - 映画詳細: `GET /movie/{tmdb_id}`（`append_to_response=credits,watch/providers`）
  - レコメンド: `GET /movie/{tmdb_id}/recommendations`
  - Discover: `GET /discover/movie`（`with_genres`, `vote_average.gte` 等）
  - トレンド: `GET /trending/movie/week`
  - 人物検索: `GET /search/person`
  - キーワード検索: `GET /search/keyword`
  - 人物クレジット: `GET /person/{person_id}/movie_credits`
- **レコメンドキャッシュ**: ファイルベースで6時間TTL（`private/cache/rec/` ディレクトリ）
