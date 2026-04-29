# live_trip 機能概要書

## 1. 目的と背景

ライブ・フェスなどへの **遠征（複数日・複数会場・費用・宿泊・移動・当日動線）を一つのプラン（trip）として計画・振り返りできる**ようにする。実装コード上は **`App\LiveTrip`**、公開パスは `www/live_trip/`、`sys_apps.app_key = 'live_trip'`。

## 2. 解決するペインポイント

- 遠征をまず作りたいのに、イベント選択を先に求められる導線が分かりづらい。
- 日向坂イベント（`hn_events`）とユーザー定義イベント（`lt_events`）を **ひとつの遠征に複数紐付け**たい。
- **費用・宿・交通・チェックリスト・タイムライン**を離れた画面ではなく trip 単位で見たい。
- Maps 連携（経路リンク解決・Places・ポリラインなど）による入力補助。

## 3. コアバリュー

遠征横断で **一覧（集計ビュー）と詳細（シオリ／マップ／タイムライン）** が揃い、参加者ロールやマイリスト（持ち物テンプレート）により繰り返し利用できる。遠征は `title` を持つ独立プロジェクトとして先に作成でき、イベント紐付けは後追いで行える。

## 4. スコープ

- **対象ユーザー（現仕様）**: ログイン済み **かつ管理者**（`LiveTripController` が `requireAdmin()`）。`sys_apps` でも `admin_only`。
- **外部 API**: Google Maps 系（Geocoding / Places / Directions 等、アプリサービス経由）。
- **関連システム**: 日向坂 `hn_events` / `hn_user_events_status`。汎用 `lt_events`。
- **連携境界**: 日向坂データへのアクセスは `private/apps/LiveTrip/Service/HinataEventBridge.php` を境界として行う（日向坂側の内部実装詳細は本設計書の対象外）。
- **共通ドキュメント**: [docs/common/31](../../common/31_共通UIコンポーネント.md)（サイドバー・`theme_from_session`・`head_favicon`）、[32](../../common/32_デザインシステム・CSS.md)、[33](../../common/33_共通JS・ユーティリティ.md)（地図用 JS ほか機能固有は `live_trip_map.js`）、[34](../../common/34_コアライブラリ(PHP).md)。
