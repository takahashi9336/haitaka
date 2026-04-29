# live_trip 機能一覧

## 1. 画面一覧

| 画面名 (論理名) | 公開パス (www より) | ビュー主ファイル | 概要 |
|----------------|---------------------|------------------|------|
| 遠征一覧 | `live_trip/index.php` | `private/apps/LiveTrip/Views/index.php` | 参加 trip の一覧・期間ソート・集計情報 |
| 遠征詳細・シオリハブ | `live_trip/show.php?id=` | `private/apps/LiveTrip/Views/show.php` | イベント・タイムライン・チェックリスト・費用・ホテル・移動・目的地・マップ等 |
| 新規作成 | `live_trip/create.php` | `private/apps/LiveTrip/Views/form.php` | `title` 必須で trip 作成（イベント未紐付可） |
| 編集 | `live_trip/edit.php?id=` | 同上 | タイトル・イベント紐付・感想を更新 |
| しおり出力 | `live_trip/shiori.php` | （コントローラが View を選択） | 印刷向けレイアウト |
| マイリスト（持ち物テンプレート）一覧 | `live_trip/my_list.php` | `private/apps/LiveTrip/Views/my_list_index.php` | ユーザー別テンプレ CRUD |
| 汎用イベント登録 | `live_trip/lt_event_create.php` | （該当 View） | `lt_events` 向けフォーム |

## 2. 機能・アクション一覧

処理の入口 PHP は `www/live_trip/*.php`。多くが `LiveTripController` メソッドに委譲。詳細は [23_live_trip_処理詳細_I-O定義書.md](../20_詳細設計/23_live_trip_処理詳細_I-O定義書.md)。

| 機能名 | 種類 | 概要 |
|--------|------|------|
| trip CRUD | 画面処理 | create / store / edit / update / delete |
| 参加ロール変更 | POST | participation_update.php |
| 自宅住所（Maps用）保存 | POST | home_place_store.php |
| 費用・宿・目的地・交通・タイムライン・チェックリストの各 CRUD / 並べ替え | POST/リダイレクト | `*_store|update|delete|reorder` ファイル群 |
| マイリスト適用／チェックからマイリスト保存 | POST | apply_mylist.php, save_checklist_to_mylist.php |
| マイリスト CRUD・アイテム並べ替え | POST | my_list*.php, my_list_item*.php |
| Maps API JSON | POST/GET(JSON) | `api/places_autocomplete.php`, `api/directions_polyline.php`, `api/resolve_maps_link.php` |
| 費用サマリー export | GET | expense_export.php（Controller 経由しない） |

## 3. 関連テーブル一覧

| 物理名 | 論理名 | 役割 |
|--------|--------|------|
| `lt_trip_plans` | 遠征プラン本体 | メインエンティティ |
| `lt_trip_members` | メンバー | user と trip を紐付け（role） |
| `lt_trip_plan_events` | 遠征イベント紐付け | `hinata` / `generic` の複数 |
| `lt_events` | 汎用イベント | ユーザー定義イベント |
| `lt_expenses` | 費用 | category + amount |
| `lt_hotel_stays` | 宿泊 | 住所・日付・価格・place_id |
| `lt_transport_legs` | 交通区間 | 日付・金額・maps_link |
| `lt_destinations` | 目的地 | 参照: `create_lt_destinations.sql` |
| `lt_timeline_items` | タイムライン | 開始日時・位置 |
| `lt_checklist_items` | チェックリスト | checked / sort_order |
| `lt_my_lists` / `lt_my_list_items` | マイリスト | ユーザー隔離あり |
| `com_user_places` | 自宅等ユーザー地点 | Maps 距離算出等（共通） |
| `com_maps_api_usage` | Maps 利用量記録 | 任意（共通） |
| `hn_events` | 日向坂イベント | 参照のみ（紐付け） |
| `hn_user_events_status` | 参加ステータス | 日向坂側座席・感想参照 |
