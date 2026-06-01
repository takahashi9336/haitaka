# 映画（Movie） UI設計書

## 1. 画面構成・レイアウト（共通）

- **共通**: `$appKey = 'movie'` で [theme_from_session.php](../../../private/components/theme_from_session.php)、[head_favicon.php](../../../private/components/head_favicon.php)、[sidebar.php](../../../private/components/sidebar.php)。詳細は [docs/common/31](../../common/31_共通UIコンポーネント.md)。
- **ヘッダ**: `fa-film` アイコン＋タイトル「映画」。モバイルはハンバーガーでサイドバー。右側に「見たい」「見た」「一括登録」のショートカットリンク。
- **テーマ**: `sys_apps` で violet 系。`:root { --mv-theme: <?= $themePrimaryHex ?> }` による `.mv-theme-btn` / `.mv-theme-text` が各画面で使用。
- **外部CDN**: Tailwind CSS（CDN）、Font Awesome 6.5.1、Chart.js 4（ダッシュボードのみ）。フォントは Inter / Noto Sans JP。
- **共有コンポーネント**: `_movie_search_shared.php`（ポスタープレビューモーダル `PosterPreview`、映画プレビューモーダル `MoviePreview`、統合検索 `MovieSearch`）、`_tmdb_attribution.php`（TMDB/JustWatch クレジット表示）。
- **クライアントJS**: `/assets/js/core.js`（`App.toast` / `App.post` 等）を全画面で使用。

## 2. 画面別

### 映画ダッシュボード（index.php → movie_dashboard.php）

- **レイアウト**: `max-w-6xl` 中央寄せ。縦スクロール 1 ページにセクションを積層。
- **セクション構成（上から）**:

  1. **スタッツカード**: 4 カラムグリッド（`grid-cols-2 lg:grid-cols-4`）。各セルはリンク付き。
     - 総鑑賞本数 → `/movie/list.php?tab=watched`
     - 今月の鑑賞数 → `/movie/list.php?tab=watched&filter=this_month`
     - 見たいリスト件数 → `/movie/list.php?tab=watchlist`
     - 総視聴時間（時間＋分表記）
  2. **TMDB検索ボックス**: `$tmdbConfigured` が true の場合のみ表示。入力＋検索ボタン。検索結果はドロップダウン（`_movie_search_shared.php` の `MovieSearch`）。統合検索（映画・人物・キーワード・ジャンル横断）。
  3. **ガチャセクション**: ダーク背景（`from-slate-800 to-slate-900`）のグラデーションカード。
     - **未ガチャ**: アンバー色の正方形ボタン（`gacha-idle` アニメーション付き）。
     - **結果表示**: ポスター＋タイトル＋メタ情報＋アクションボタン（詳細を見る / 見た! / Google検索）。
     - **もう1回ボタン**: 1回目の結果表示時に「もう1回だけ引く」導線（2回目が最後）。
     - **上限到達**: 月アイコン＋「本日のガチャは終了」メッセージ。
     - **空リスト**: 見たいリストが 0 件のとき追加誘導。
     - **JS**: `Gacha` オブジェクトが状態管理（`/movie/api/gacha.php` と通信）。ページ読み込み時に `Gacha.init()` で前回状態を復元。
  4. **出演者ランキング**: 3 カラム（俳優 / 監督 / 脚本）。各カラムにトップ 5 のランキング表示と「見たリスト表示」「見てない映画を探す」リンク。
  5. **おすすめ（統合カード）**: タブ切り替え（高評価ベース / 友人が視聴 / 未開拓ジャンル / 今週のトレンド）。
     - 各パネルは横スクロール（`rec-scroll`）＋左右矢印ボタン。
     - 初回表示時にスケルトンカード → API 非同期取得 → カード描画。
     - カードにはポスター＋タイトル＋年＋評価バッジ＋追加ボタン（見たい / 見た）。登録済みはチェックマーク。
     - 友人タブは `FriendsActivityService` からサーバーサイドで取得し PHP で描画。
  6. **視聴分析（統合カード）**: 3 カラム（月別鑑賞本数棒グラフ / ジャンル分布ドーナツ＋凡例 / 評価スコア分布棒グラフ）。Chart.js で描画。データ 0 件時はプレースホルダアイコン＋メッセージ。
  7. **TMDB Attribution**: `_tmdb_attribution.php`。

### 映画一覧（list.php → movie_list.php）

- **レイアウト**: `max-w-6xl` 中央寄せ。
- **タブ切り替え**: 「見たい（watchlist）」/「見た（watched）」のタブ。件数バッジ付き。`?tab=` でサーバーサイド切替。
- **ツールバー**: ソートセレクト（登録日 / 更新日 / 視聴日 / 評価 / 公開日 / タイトル）、昇順/降順トグル、グリッド/リスト表示切替、TMDB検索ボックス（インライン）、一括登録・一括編集ボタン。
- **出演者フィルタ**: `credit_role` + `person_ids` クエリで出演者絞り込み。フィルタ適用時はバナー表示。
- **フィルタ**: `filter=this_month` で今月の鑑賞に絞り込み。タイトル/ジャンル/タグ/配信サービスのクライアントサイドフィルタ。
- **グリッド表示**: ポスターカード（アスペクト比 2:3）。ホバーで半透明オーバーレイ＋タイトル表示。
- **リスト表示**: テーブル行にポスターサムネ＋タイトル＋公開年＋評価＋ステータス。
- **検索結果 → 追加**: インライン TMDB 検索からカード選択 → `MoviePreview` モーダル → 追加。
- **「見た」登録モーダル**: watchlist から watched への状態変更時に日付・評価・メモの入力モーダル。

### 映画詳細（detail.php → movie_detail.php）

- **レイアウト**: `max-w-4xl` 中央寄せ。
- **ヒーロー**: バックドロップ画像（あれば全幅背景）＋ポスター＋タイトル＋原題＋公開年＋上映時間＋TMDB評価。ポスタークリックで `PosterPreview` 拡大表示。
- **あらすじ**: 折りたたみ対応（長文時）。
- **キャスト**: TMDB credits から横スクロールで表示。
- **配信サービス**: `watch_providers`（flatrate / rent / buy）のロゴ＋サービス名。JustWatch リンク。
- **マイレビュー**: status に応じた表示切替。
  - `watchlist`: 「見た！」ボタン → 見た登録モーダル。
  - `watched`: 評価（1-10 スター入力）/ 視聴日 / メモのインライン編集＋保存。「見たいに戻す」ボタン。
- **タグ管理**: タグバッジ＋追加入力＋削除（x ボタン）。API: `/movie/api/update_tags.php`。
- **TMDB紐付け**: `tmdb_id` が NULL（仮登録）の場合、TMDB 検索 → 候補選択 → 紐付けの UI を表示。
- **削除**: 「リストから削除」ボタン（確認ダイアログ付き）。

### 映画検索ページ（search.php → movie_search.php）

- **レイアウト**: `max-w-6xl` 中央寄せ。フルページ検索 UI。
- **検索ボックス**: ページ上部に検索入力＋ボタン。初期値は `?q=` から取得。
- **検索結果**: グリッド表示。無限スクロール（ページ番号を自動インクリメント）。
- **カード**: ポスター＋タイトル＋年＋TMDB評価＋追加ボタン。登録済みはチェックマーク。クリックで `MoviePreview` モーダル。
- **ナビゲーション**: ダッシュボードへ / 映画リストへのリンク。

### 映画一括登録（import.php → movie_import.php）

- **レイアウト**: `max-w-4xl` 中央寄せ。
- **入力**: テキストエリア（1行1タイトル）。
- **プレビュー**: 入力後「マッチング開始」→ TMDB 自動検索 → 各タイトルにマッチ結果カード表示。マッチ候補の選択・手動マッチ切替。ステータス選択（見たい / 見た）。
- **一括登録**: プレビュー確認後「一括登録」ボタン → `/movie/api/bulk_add.php`。結果サマリ表示。

### 映画一括編集（bulk_edit.php → movie_bulk_edit.php）

- **レイアウト**: `max-w-6xl` 中央寄せ。
- **タブ切り替え**: watchlist / watched。件数バッジ付き。
- **テーブル**: チェックボックス＋タイトル＋ステータスセレクト＋評価入力＋視聴日入力＋メモ入力。
- **一括操作**: 全選択 / 選択削除 / 一括保存。API: `/movie/api/bulk_update.php`。

### 配信情報バッチ（batch_providers.php）

- **レイアウト**: 中央寄せカード（`max-w-lg`）。共通サイドバーなし。
- **UI**: 対象件数表示、プログレスバー、ログ表示、開始ボタン。完了時にチェックマーク＋完了メッセージ。
- **処理**: 5 件ずつ TMDB API で配信情報を取得。`?api=1&offset=N` で逐次リクエスト。

### 出演者情報バッチ（batch_credits.php）

- **レイアウト**: 中央寄せカード（`max-w-2xl`）。共通サイドバーなし。
- **UI**: 対象件数表示、保存上限表示（cast 20 / 監督 10 / 脚本 10）、プログレスバー、ログ表示、開始ボタン。進捗確認 URL / 自動開始 URL のリンク。
- **処理**: 3 件ずつ TMDB API で credits 取得 → DB 保存。cursor ベースのページング。ジッター付きウェイト（450ms + 0-250ms）。ファイルベースの進捗ステータス管理。

### 人物別ピックアップ（pickup.php → movie_person_pickup.php）

- **レイアウト**: `max-w-6xl` 中央寄せ。
- **パラメータ**: `role_kind`（cast / director / writer）、`person_ids`（カンマ区切り）、`person_names`。
- **表示**: 対象人物名＋役割の表示。TMDB API で人物ごとの出演映画を取得し、未登録分をグリッド表示。
- **カード**: ポスター＋タイトル＋公開年＋TMDB評価＋一致した人物名。追加ボタン（見たい / 見た）。
- **ナビゲーション**: 見たリスト表示 / 映画へ戻る。

## 3. 共通コンポーネントの利用

| 参照 | 用途 |
|------|------|
| `_movie_search_shared.php` | ポスタープレビュー / 映画プレビューモーダル / 統合検索（`MovieSearch` / `MoviePreview` / `PosterPreview`）。ダッシュボード・一覧・検索ページで include |
| `_tmdb_attribution.php` | TMDB / JustWatch のクレジット表記。ダッシュボード・一覧・詳細で include |
| `theme_from_session` | `movie` のテーマ変数（`$themePrimaryHex` 等） |
| `App.toast` / `App.post`（[core.js](/www/assets/js/core.js)） | クライアント側操作フィードバック・JSON API 通信 |

## 4. 状態と表示制御

- **ログイン必須**: 全画面・全 API で `Core\Auth::requireLogin()` または `$auth->check()` によるログインチェック。未認証は `/login.php` リダイレクトまたは 401 JSON 応答。
- **TMDB 未設定**: `$tmdbConfigured` が false の場合、検索ボックス非表示＋警告メッセージ。一覧・詳細・ダッシュボードで条件分岐。
- **仮登録（tmdb_id = NULL）**: 詳細画面で TMDB 紐付けセクションを表示。ポスター・あらすじ等は空状態。
- **ガチャ状態**: `GachaState` によるファイルベース管理。日次リセット。ページ読み込み時に JS で状態復元（idle / result / done の 3 状態）。
- **おすすめタブ**: 初期選択は「高評価ベース」。タブ切替は CSS クラストグルによるクライアント制御。各パネルのデータは非同期ロード（スケルトン → 実データ）。

## 5. スタイル・デザインルール

- **テーマカラー**: CSS 変数 `--mv-theme` に `$themePrimaryHex`（violet 系）を設定。`.mv-theme-btn`（背景）、`.mv-theme-text`（文字色）で適用。
- **カードUI**: `bg-white border border-slate-100 rounded-xl shadow-sm` を基調。ホバー時に `translateY(-2px)` と `box-shadow` 強化（`stat-card`）。
- **ガチャ**: 専用アニメーション（`gachaPulse` / `gachaGlow` / `gachaShine` / `gachaReveal`）でインタラクティブな演出。
- **おすすめカルーセル**: `rec-scroll`（横スクロール、スクロールバー非表示）＋ `rec-arrow`（左右矢印ボタン、端到達時に自動非表示）。カードは `rec-card`（固定幅 140px / md: 160px）。
- **スケルトン**: `skeleton-card` で `skeletonPulse` アニメーション（不透明度のパルス）。
