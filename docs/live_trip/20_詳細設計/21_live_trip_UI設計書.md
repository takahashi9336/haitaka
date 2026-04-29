# live_trip UI設計書

## 1. 画面構成・レイアウト（共通）

- **共通**: `$appKey = 'live_trip'` で [theme_from_session.php](../../../private/components/theme_from_session.php)、[head_favicon.php](../../../private/components/head_favicon.php)、[sidebar.php](../../../private/components/sidebar.php)。詳細は [docs/common/31](../../common/31_共通UIコンポーネント.md)。
- **一覧・詳細のヘッダ**: `fa-plane` アイコン＋タイトル「遠征管理」。モバイルはハンバーガーでサイドバー（`www/assets/css/common.css` のレイアウトおよび `.cursor/rules/html-no-nested-anchors.mdc` のサイドバー規則）。
- **テーマ**: `sys_apps` で emerald系。詳細ヘッダ下で `:root { --lt-theme: <?= $themePrimaryHex ?> }` による `.lt-theme-btn` が使われる（`Views/index.php` 等）。
- **詳細ページ**: [`show.css`](../../../www/live_trip/css/show.css) を併用。マップ・タイムライン・チェックリスト等の **大きなブロック** は同一 HTML 内でセクション分割。

## 2. 画面別（要点）

### 遠征一覧（index.php）

- **構成**: フィルタ（期間 `period`、ソート `sort`）、カードグリッド。各カードに費用合計・チェックリスト進捗。
- **状態**: クエリ `period` / `sort` は `TripPlanFilterService` に委譲。

### 遠征詳細（show.php）

- **構成**: ヒーロー（日程レンジ・残日数）、イベントカード、マップ（`live_trip_map.js`）、タイムラインと交通のマージ表示、費用・ホテル・チェックリスト等のタブ／アコーディオン（実装に準拠）。
- **Maps**: `UserPlaceModel` の `home`、目的地・会場座標の連携。API は `www/live_trip/api/*.php` 経由。
- **目的地タブ（改善）**: タブ内の先頭に「KPIカード（件数・カテゴリ・エリア）」を表示し、会場（イベント開催地）も読み取り専用の目的地として表示する（編集不可）。

### フォーム（create / edit）

- **form.php**:
  - `遠征タイトル`（必須）を入力して遠征プロジェクトを作成。
  - イベント選択（日向坂 / 汎用）は任意。未選択でも保存可能。
  - 保存後 `show` または一覧へ。

### イベント後追い紐付け（show / edit）

- **show.php（参加情報タブ）**: イベント未紐付時は空状態メッセージと「イベントを紐づける」導線を表示。
- **edit.php（form.php 再利用）**: 既存イベント紐付・追加・削除を実施。

## 3. 共通コンポーネントの利用（ライブ trip 固有）

| 参照 | 用途 |
|------|------|
| `theme_from_session` | `live_trip` のテーマ変数 |
| `flash_toast` | 成功・失敗メッセージ（該当画面で include される場合） |
| `App.toast` / `App.post`（[core.js](../../../www/assets/js/core.js)） | クライアント側操作フィードバック・JSON API |

## 4. 状態と表示制御

- **管理者以外**: コントローラで弾かれ、画面は原則表示されない。
- **データ未作成テーブル**: 一部 `try/catch` で空配列に落とす互換コードあり（マイグレーション前フォールバック）。

## 5. スタイル

- **Tailwind CDN** + 冒頭 `<style>` でフォント（Inter / Noto Sans JP）。カードの白背景・枠は `docs/common/32` のパターンに準拠。
