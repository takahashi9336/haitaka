# live_trip 処理詳細・I/O定義書

認可の共通前提: **`Core\Auth` でログイン済み**、かつ **`requireAdmin()`** が `LiveTripController` 各メソッドで要求される（コントローラ経由の処理）。

## 1. エンドポイント / アクション一覧

### 1.1 LiveTripController → HTML

| HTTP | 公開パス (live_trip/ より) | メソッド | 入力 | 出力 |
|------|---------------------------|----------|------|------|
| GET | index.php | index | `period`, `sort` | HTML 一覧 |
| GET | show.php | show | `id` | HTML 詳細 |
| GET | create.php | createForm | — | HTML フォーム |
| POST | store.php | store | フォーム body | リダイレクト |
| GET | edit.php | editForm | `id` | HTML |
| POST | update.php | update | form | リダイレクト |
| POST | delete.php | delete | — | リダイレクト |
| POST | participation_update.php | updateParticipation | form | リダイレクト/JSON（実装準拠） |
| POST | home_place_store.php | storeHomePlace | form/JSON | 同上 |
| GET | shiori.php | shiori | `id` 等 | HTML |
| GET | lt_event_create.php | ltEventCreateForm | — | HTML |
| POST | lt_event_store.php | ltEventStore | form | リダイレクト |
| POST | expense_store.php / expense_update.php / expense_delete.php | storeExpense / … | form | リダイレクト |
| POST | hotel_store.php / hotel_update.php / hotel_delete.php | … | form | 同上 |
| POST | destination_store.php / destination_update.php / destination_delete.php | … | form | 同上 |
| POST | transport_store.php / transport_update.php / transport_delete.php | … | form | 同上 |
| POST | timeline_store.php / timeline_update.php / timeline_delete.php | … | form | 同上 |
| POST | checklist_store.php / checklist_update.php / checklist_delete.php | … | form | 同上 |
| POST | checklist_reorder.php | reorderChecklist | form | 同上 |
| POST | checklist_toggle.php | toggleChecklist | form | 同上 |
| POST | apply_mylist.php | applyMyList | form | 同上 |
| POST | save_checklist_to_mylist.php | saveChecklistToMyList | form | 同上 |
| GET | my_list.php | myListIndex | — | HTML |
| POST | my_list_store.php / my_list_delete.php | … | form | 同上 |
| POST | my_list_item_store.php / my_list_item_delete.php | … | form | 同上 |
| POST | my_list_item_reorder.php | reorderMyListItems | form | 同上 |

※ 各 `*.php` が `new LiveTripController()` のどのメソッドかは [`www/live_trip/`](../../../www/live_trip/) と [`LiveTripController`](../../../private/apps/LiveTrip/Controller/LiveTripController.php) で一対一対応している。

### 1.2 Maps / JSON API（LiveTripController）

| HTTP | 公開パス | メソッド | 出力 |
|------|----------|----------|------|
| POST/GET | api/resolve_maps_link.php | resolveMapsLink | JSON |
| GET | api/places_autocomplete.php | placesAutocomplete | JSON |
| GET | api/directions_polyline.php | directionsPolyline | JSON |

### 1.3 その他

| HTTP | 公開パス | 説明 |
|------|----------|------|
| GET | expense_export.php | CSV/text 等エクスポート。**Controller 外**で `Auth`・`TripPlanModel`・`Views/lib/expense_summary.php` を直接利用。 |
| GET | api/debug_log.php | 開発用ロギング。固定 sessionId 以外は無視。 |

## 2. 処理フロー詳細（代表）

### show（show.php）

1. **認可**: `requireAccess()`（ログイン＋管理者）。
2. **入力**: `id`（trip_plan_id）。不正なら `/live_trip/` へ。
3. **ロード**: `TripPlanModel::findForUser`, イベント・費用・ホテル・交通・目的地・タイムライン・チェックリスト、自宅 `UserPlaceModel`。
4. **加工**: `mergeTimelineWithTransport` 等で表示用配列をマージ。
5. **出力**: `Views/show.php` をレンダリング。

### store（store.php）

1. 認可後、POST から trip・イベント紐付けを検証。
2. `TripPlanModel` / `TripPlanEventModel` / `TripMemberModel` 等で INSERT。
3. 成功時 `Location` で詳細または一覧へ。

（他 CRUD も同型: 対応 Model の create/update/delete → リダイレクト。）

## 3. エラー・ログ

未捕捉例外は `Core\Bootstrap` により JSON/HTML 500 と `Logger::errorWithContext`。API パスは JSON エラー応答の条件あり（[Bootstrap](../../../private/lib/Bootstrap.php)）。

## 4. 外部連携の境界（LiveTrip側）

- **日向坂連携**: LiveTrip から日向坂データに触れる際は `private/apps/LiveTrip/Service/HinataEventBridge.php` を境界として利用する。
- **Google Maps 連携**: Controller から `MapsGeocodeService` / `MapsDistanceMatrixService` / `MapsDirectionsService` / `MapsPlacesAutocompleteService` / `MapsLinkResolveService` を経由して利用する。
- 本書では LiveTrip 側の依存境界のみ扱い、連携先（日向坂側）の内部実装詳細は対象外とする。
