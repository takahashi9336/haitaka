# 映画（Movie） ドメイン・データモデル定義書

モデルの `$fields` を一次情報とし、migrations と相違がある場合は **ソースコードを優先**。

## 1. テーブル定義詳細

### mv_movies

MovieModel: `isUserIsolated = false`

| カラム名 | 論理名 | 型 | 制約・備考 |
|----------|--------|-----|------------|
| id | PK | BIGINT UNSIGNED | 自動採番 |
| tmdb_id | TMDB映画ID | INT UNSIGNED | UNIQUE（NULL許容）。NULLは仮登録（プレースホルダー） |
| title | 日本語タイトル | VARCHAR(500) | 必須 |
| original_title | 原題 | VARCHAR(500) | nullable |
| overview | あらすじ | TEXT | nullable |
| poster_path | ポスター画像パス | VARCHAR(255) | nullable。TMDBの相対パス（例: `/xxxxxx.jpg`）。表示時に `https://image.tmdb.org/t/p/{size}` を前置 |
| backdrop_path | 背景画像パス | VARCHAR(255) | nullable。TMDBの相対パス |
| release_date | 公開日 | DATE | nullable |
| vote_average | TMDB平均評価 | DECIMAL(3,1) | nullable。0.0-10.0 |
| vote_count | TMDB評価数 | INT | nullable |
| genres | ジャンル | VARCHAR(500) | nullable。JSON配列文字列（例: `["ドラマ","アクション"]`）。TMDB取得時はジャンル名、検索結果からの登録時はジャンルIDの場合あり |
| runtime | 上映時間（分） | INT | nullable |
| watch_providers | 配信サービス情報 | JSON | nullable。JustWatch経由TMDBデータ。JP リージョンの `flatrate` / `rent` / `buy` / `link` を保持 |
| watch_providers_updated_at | 配信情報取得日時 | DATETIME | nullable |
| created_at | 作成日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

**インデックス**:

| 名前 | カラム | 種類 |
|------|--------|------|
| PRIMARY | id | PK |
| uq_tmdb_id | tmdb_id | UNIQUE（NULL許容。MySQLではNULL複数行を許容） |

**補足**:
- `findOrCreateByTmdbId()`: TMDB API レスポンスから `tmdb_id` で検索し、なければ INSERT して返す。
- `createPlaceholder()`: `tmdb_id = NULL` で title のみの仮登録レコードを作成。
- `linkToTmdb()`: 仮登録レコードに TMDB 情報を紐付け（tmdb_id や各カラムを UPDATE）。
- `updateFromTmdb()`: 詳細画面表示時に runtime / genres / overview 等を最新化。
- `updateWatchProviders()`: 配信情報を JSON で保存。

### mv_user_movies

UserMovieModel: `isUserIsolated = true`

| カラム名 | 論理名 | 型 | 制約・備考 |
|----------|--------|-----|------------|
| id | PK | BIGINT UNSIGNED | 自動採番 |
| user_id | ユーザーID | INT | FK → sys_users.id。NOT NULL |
| movie_id | 映画ID | BIGINT UNSIGNED | FK → mv_movies.id。ON DELETE CASCADE |
| status | ステータス | VARCHAR(20) | NOT NULL。DEFAULT 'watchlist'。`watchlist` / `watched` |
| rating | 個人評価 | TINYINT UNSIGNED | nullable。1-10 |
| memo | 個人メモ・感想 | TEXT | nullable |
| watched_date | 視聴日 | DATE | nullable |
| tags | ユーザー定義タグ | JSON | nullable。JSON配列（例: `["SF","お気に入り"]`） |
| created_at | 作成日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

**インデックス**:

| 名前 | カラム | 種類 |
|------|--------|------|
| PRIMARY | id | PK |
| uq_user_movie | user_id, movie_id | UNIQUE |
| idx_user_status | user_id, status | INDEX |

**補足**:
- `addMovie()`: 既存エントリがあればステータスのみ UPDATE、なければ INSERT。
- `markAsWatched()`: status を `watched` に変更し、同時に watched_date / rating / memo を設定。credits 自動保存もトリガー（コントローラ側）。
- `moveToWatchlist()`: status を `watchlist` に戻し、watched_date / rating を NULL に。
- `getListByStatus()`: mv_movies と JOIN し、ソート・フィルタ・出演者フィルタ対応。出演者フィルタ時は mv_movie_credits を追加 JOIN し、GROUP_CONCAT で一致人物名を付与。
- `getRandomWatchlistItem()`: `ORDER BY RAND() LIMIT 1` でランダム取得（ガチャ用）。

### mv_movie_credits

出演者情報テーブル。専用の Model クラスは持たず、`MovieCreditsService` と `UserMovieModel::getCreditsRankingByRole()` から直接 SQL で操作。

| カラム名 | 論理名 | 型 | 制約・備考 |
|----------|--------|-----|------------|
| id | PK | BIGINT UNSIGNED | 自動採番 |
| movie_id | 映画ID | BIGINT UNSIGNED | FK → mv_movies.id。ON DELETE CASCADE |
| tmdb_movie_id | TMDB映画ID | INT UNSIGNED | mv_movies.tmdb_id の値。NOT NULL |
| role_kind | 役割種別 | VARCHAR(20) | NOT NULL。`cast` / `director` / `writer` |
| rank_no | 順序 | TINYINT UNSIGNED | NOT NULL。作品内の表示順（cast は order 基準、crew は出現順ベース） |
| person_tmdb_id | TMDB人物ID | INT UNSIGNED | NOT NULL |
| person_name | 表示名 | VARCHAR(255) | NOT NULL |
| character_name | 役名 | VARCHAR(255) | nullable。cast のみ |
| job_name | 職種 | VARCHAR(255) | nullable。crew のみ（Director / Writer / Screenplay / Story） |
| department | 部門 | VARCHAR(255) | nullable。crew のみ |
| created_at | 作成日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

**インデックス**:

| 名前 | カラム | 種類 |
|------|--------|------|
| PRIMARY | id | PK |
| uq_mv_movie_credits_movie_role_person | movie_id, role_kind, person_tmdb_id | UNIQUE |
| idx_mv_movie_credits_movie_role_rank | movie_id, role_kind, rank_no | INDEX |
| idx_mv_movie_credits_person | person_tmdb_id | INDEX |
| idx_mv_movie_credits_role | role_kind | INDEX |

**補足**:
- `MovieCreditsService::saveFromTmdbDetail()`: DELETE → INSERT のトランザクション処理。cast 上位 20 名、director 上位 10 名、writer（Writer / Screenplay / Story）上位 10 名を保存。
- `MovieCreditsService::ensureCreditsByTmdbId()`: 保存済みならスキップ（`skipIfExists` フラグ）。見た登録時にコントローラから呼び出し。
- ランキング集計: `UserMovieModel::getCreditsRankingByRole()` で watched 映画の credits を JOIN し、person_tmdb_id ごとの映画本数をカウント。

## 2. ステータス・区分値定義

### mv_user_movies.status

| 値 | 意味 | 備考 |
|----|------|------|
| `watchlist` | 見たい | デフォルト値。映画追加時の初期状態 |
| `watched` | 見た | 「見た」登録で設定。rating / watched_date / memo の入力が可能 |

### mv_movie_credits.role_kind

| 値 | 意味 | 保存上限 | 備考 |
|----|------|----------|------|
| `cast` | 俳優 | 20名 | TMDB credits の cast を order 昇順で上位取得 |
| `director` | 監督 | 10名 | TMDB credits の crew から job = 'Director' を抽出 |
| `writer` | 脚本 | 10名 | TMDB credits の crew から job = 'Writer' / 'Screenplay' / 'Story' を抽出 |

### mv_user_movies.rating

| 範囲 | 意味 |
|------|------|
| 1-10 | 個人評価スコア。NULL は未評価 |

### mv_movies.watch_providers（JSON構造）

```json
{
  "flatrate": [
    { "provider_id": 8, "provider_name": "Netflix", "logo_path": "/..." }
  ],
  "rent": [...],
  "buy": [...],
  "link": "https://www.themoviedb.org/movie/xxxx/watch?locale=JP"
}
```

- `flatrate`: 定額配信（Netflix, Amazon Prime Video 等）
- `rent`: レンタル配信
- `buy`: 購入配信
- `link`: JustWatch の詳細ページ URL

### mv_movies.genres（JSON配列の内容）

TMDB ジャンル名（日本語）の配列。以下は `TmdbApiClient::GENRE_MAP` に定義されたマッピング:

| ジャンル名 | TMDB Genre ID |
|------------|---------------|
| アクション | 28 |
| アドベンチャー | 12 |
| アニメーション | 16 |
| コメディ | 35 |
| クライム | 80 |
| ドキュメンタリー | 99 |
| ドラマ | 18 |
| ファミリー | 10751 |
| ファンタジー | 14 |
| ヒストリー | 36 |
| ホラー | 27 |
| ミュージック | 10402 |
| ミステリー | 9648 |
| ロマンス | 10749 |
| サイエンスフィクション | 878 |
| テレビ映画 | 10770 |
| スリラー | 53 |
| 戦争 | 10752 |
| 西部劇 | 37 |

## 3. ガチャ状態管理（GachaState）

ガチャの状態は DB ではなく `Core\GachaState` によるファイルベース管理（`private/cache/gacha/` 配下）。キーは `movie_gacha`。

| フィールド | 型 | 説明 |
|------------|-----|------|
| date | string | ガチャ日（YYYY-MM-DD）。日が変わるとリセット |
| spins | int | 本日の使用回数（最大 2） |
| movie | object/null | 最後に引いた映画データ（id, title, poster_path, release_date, vote_average, runtime） |
| updated_at | string | 最終更新タイムスタンプ（ISO 8601） |

## 4. レコメンドキャッシュ

ファイルベース（`private/cache/rec/` ディレクトリ）。TTL = 6 時間（21600 秒）。

| ファイル名パターン | 説明 |
|--------------------|------|
| `personal_{userId}.json` | 高評価ベースレコメンド（ユーザー別） |
| `genre_{userId}.json` | ジャンルベースレコメンド（ユーザー別） |
| `trending.json` | トレンド（全ユーザー共通） |

## 5. マイグレーション履歴

| ファイル | 内容 |
|----------|------|
| `create_mv_movies_tables.sql` | `mv_movies` / `mv_user_movies` の初期作成。`sys_apps` への movie 登録 |
| `alter_mv_movies_nullable_tmdb.sql` | `mv_movies.tmdb_id` を NULL 許容に変更（仮登録対応） |
| `add_watch_providers_to_mv_movies.sql` | `mv_movies` に `watch_providers` / `watch_providers_updated_at` カラム追加 |
| `add_tags_to_mv_user_movies.sql` | `mv_user_movies` に `tags`（JSON）カラム追加 |
| `create_mv_movie_credits.sql` | `mv_movie_credits` テーブル作成 |
