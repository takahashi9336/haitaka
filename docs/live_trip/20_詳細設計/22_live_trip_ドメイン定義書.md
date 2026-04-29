# live_trip ドメイン・データモデル定義書

モデルの `$fields` を一次情報とし、migrations と相違がある場合は **ソースコードを優先**。BaseModel は本機能のモデルでは多く **`isUserIsolated = false`**（`lt_my_lists` 系のみユーザー隔離）。

## 1. テーブル定義詳細（抜粋）

### lt_trip_plans

TripPlanModel: `id`, `title`, `impression`, `created_at`, `updated_at`

| カラム名 | 論理名 | 型 | 制約・備考 |
|----------|--------|-----|------------|
| id | PK | BIGINT UNSIGNED | 自動採番 |
| title | 遠征タイトル | VARCHAR(255) | 必須。遠征プロジェクト表示名 |
| impression | 感想 | TEXT | nullable |
| created_at | 作成 | DATETIME | |
| updated_at | 更新 | DATETIME | |

※ イベント列は過去 DDL から是正され、現状は **`lt_trip_plan_events`** 側にのみ存在する。

### lt_trip_members

| カラム名 | 論理名 | 備考 |
|----------|--------|------|
| trip_plan_id | FK | |
| user_id | ユーザー | |
| role | owner/editor/viewer | 既定 owner |

### lt_trip_plan_events

TripPlanEventModel の `$fields` に準拠。`event_type`: `hinata` | `generic`。

### lt_expenses

| category | migrations 上の例 |
|----------|-------------------|
| transport/hotel/ticket/food/goods/other | `create_lt_livetrip_tables.sql` コメント参照 |

### lt_transport_legs

TransportLegModel に `departure_date`, `scheduled_time`, `amount`, `maps_link` 等。初期 DDL の `direction` 等は後続マイグレーションで変更されている可能性があるため **モデル確認必須**。

### lt_hotel_stays

HotelStayModel に合わせ、距離文言・memo・`place_id` 等がある。

### lt_timeline_items

TimelineItemModel: `scheduled_date`, `label`, `scheduled_time`, `duration_min`, 位置関連 `latitude`/`longitude`/`place_id`/`location_label`/`location_address`, `sort_order`。

### lt_checklist_items

ChecklistItemModel: `item_name`, `checked`（0/1）, `sort_order`。

### lt_my_lists / lt_my_list_items

MyListModel は **`isUserIsolated = true`**。

### com_user_places

自宅などキー単位で保持（`UserPlaceModel::getByUserAndKey($userId, 'home')`）。

## 2. ステータス・区分値（要点）

| 区分 | 値 | 意味 |
|------|-----|------|
| `lt_trip_plan_events.event_type` | hinata | 日向坂 `hn_events` |
| | generic | `lt_events` |
| `lt_expenses.category` | transport, hotel, ticket, food, goods, other | 費用カテゴリ |
| `lt_trip_members.role` | owner, editor, viewer | 参加者権限 |
