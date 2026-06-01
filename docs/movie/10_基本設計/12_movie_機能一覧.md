# 映画（Movie） 機能一覧

## 1. 画面一覧
| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| 映画ダッシュボード | `www/movie/index.php` → `private/apps/Movie/Views/movie_dashboard.php` | スタッツ、TMDB検索、ガチャ、出演者ランキング、おすすめ（パーソナル/友人/ジャンル/トレンド）、視聴分析グラフを統合表示 |
| 映画一覧（見たい/見た） | `www/movie/list.php` → `private/apps/Movie/Views/movie_list.php` | タブ切り替え（watchlist/watched）でリスト表示。グリッド/リスト切替、ソート、フィルタ（タイトル/ジャンル/タグ/配信サービス）、出演者フィルタ、TMDB検索→追加、「見た」登録モーダル |
| 映画詳細 | `www/movie/detail.php` → `private/apps/Movie/Views/movie_detail.php` | 映画の詳細情報（ポスター、バックドロップ、あらすじ、キャスト、配信サービス）、マイレビュー（評価/視聴日/メモ）、タグ管理、仮登録→TMDB紐付け |
| 映画検索ページ | `www/movie/search.php` → `private/apps/Movie/Views/movie_search.php` | TMDB検索結果のフルページ表示（グリッド形式、無限スクロール、追加ボタン付き） |
| 映画一括登録 | `www/movie/import.php` → `private/apps/Movie/Views/movie_import.php` | テキスト入力 → TMDB自動マッチング → プレビュー → 一括登録 |
| 映画一括編集 | `www/movie/bulk_edit.php` → `private/apps/Movie/Views/movie_bulk_edit.php` | リスト全体の一括更新（ステータス/評価/視聴日/メモ）・一括削除 |
| 配信情報バッチ | `www/movie/batch_providers.php` | TMDB連携済み映画の配信情報を一括取得・更新するバッチUI |
| 出演者情報バッチ | `www/movie/batch_credits.php` | TMDB連携済み映画の出演者（キャスト/監督/脚本）情報を一括投入するバッチUI |
| 人物別ピックアップ | `www/movie/pickup.php` → `private/apps/Movie/Views/movie_person_pickup.php` | 指定した人物（俳優/監督/脚本家）の出演映画のうち、未登録のものをTMDBから検索・表示 |
| 旧ダッシュボードURL | `www/movie/dashboard.php` | `/movie/` へ301リダイレクト |

## 2. 機能・アクション一覧
| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| ダッシュボード表示 | 画面 | スタッツ集計、月別/ジャンル/評価チャート描画、ガチャ状態復元、レコメンド非同期取得 |
| TMDB映画検索 | API | `GET /movie/api/search.php?q=&page=` TMDB検索し結果をJSON返却。ユーザー登録状況を付与 |
| TMDB人物検索 | API | `GET /movie/api/search_person.php?q=&page=` TMDB人物検索結果をJSON返却 |
| TMDBキーワード検索 | API | `GET /movie/api/search_keyword.php?q=&page=` TMDBキーワード検索結果をJSON返却 |
| TMDB映画詳細（配信含む） | API | `GET /movie/api/tmdb_detail.php?tmdb_id=` 映画詳細＋配信情報＋ユーザー状態をJSON返却 |
| 映画追加（TMDB連携） | API | `POST /movie/api/add.php` TMDBデータから映画をリストに追加 |
| 映画追加（手動/仮登録） | API | `POST /movie/api/add_manual.php` タイトルのみでプレースホルダー作成・リスト追加 |
| 映画更新（ステータス/評価/メモ/視聴日） | API | `POST /movie/api/update.php` ユーザー映画エントリの更新。見た登録時にcredits自動保存 |
| タグ更新 | API | `POST /movie/api/update_tags.php` ユーザー映画エントリのタグ（JSON配列）を更新 |
| 映画削除 | API | `POST /movie/api/remove.php` ユーザーリストから削除 |
| TMDB紐付け | API | `POST /movie/api/link_tmdb.php` 仮登録映画にTMDB情報を紐付け |
| ガチャ（ランダム抽選） | API | `GET /movie/api/gacha.php` 見たいリストからランダム1件抽選（1日最大2回）。`POST`でrefund |
| おすすめ映画 | API | `GET /movie/api/recommendations.php?type=` personal/genre/trending の3タイプ。6時間キャッシュ |
| 一括登録 | API | `POST /movie/api/bulk_add.php` 複数映画の一括登録 |
| 一括更新 | API | `POST /movie/api/bulk_update.php` 複数映画の一括更新・一括削除 |
| 人物別ピックアップ | API | `GET /movie/api/person_pickup.php` 指定人物の出演映画から未登録分を取得 |
| 配信情報バッチ | Batch | `www/movie/batch_providers.php` 配信情報未取得の映画を5件ずつTMDB APIで更新 |
| 出演者情報バッチ | Batch | `www/movie/batch_credits.php` credits未投入の映画を3件ずつTMDB APIで取得・保存 |

## 3. 関連テーブル一覧
| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| `mv_movies` | 映画マスタ（TMDBキャッシュ/仮登録） | TMDB取得データのキャッシュ。作品追加時にCR、詳細表示時にU（runtime/genres/watch_providers更新） |
| `mv_user_movies` | ユーザー映画リスト | ユーザーごとの映画管理（CRUD全て）。status/rating/memo/watched_date/tags を保持 |
| `mv_movie_credits` | 映画出演者情報 | TMDB creditsから上位キャスト/監督/脚本を保存。ランキング集計・フィルタに使用（CR、バッチでD→I） |
| `sys_apps` | アプリ定義 | `app_key='movie'` の参照（R） |
| `sys_users` | ユーザー | `mv_user_movies.user_id` のFK参照元（R） |
