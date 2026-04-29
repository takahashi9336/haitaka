# 遠征管理（LiveTrip）DB設計・ER図

## 概要

- 中心エンティティは `lt_trip_plans`（遠征本体）
- `lt_trip_plans` に対して、イベント・費用・宿泊・移動・目的地・タイムライン・チェックリストが 1対多
- イベントは `lt_trip_plan_events` で多イベント紐付け（`hinata` と `generic` 混在可）
- ユーザー単位テンプレートは `lt_my_lists` / `lt_my_list_items`
- Maps関連補助として `lt_user_places`（自宅等）と `lt_maps_api_usage`（月次利用量）を保持

## 主要テーブル一覧（要点）

- `lt_trip_plans`: 遠征本体（`id`, `impression`, timestamps）
- `lt_trip_members`: 遠征参加者（`trip_plan_id`, `user_id`, `role`）
- `lt_trip_plan_events`: 遠征とイベントの紐付け（`event_type`, `hn_event_id`, `lt_event_id`, `seat_info`, `impression`）
- `lt_events`: 汎用イベントマスタ（ユーザー作成）
- `lt_expenses`: 費用
- `lt_hotel_stays`: 宿泊
- `lt_transport_legs`: 移動区間（`departure_date`, `scheduled_time`, `amount`, `maps_link` あり）
- `lt_destinations`: 目的地
- `lt_timeline_items`: 当日/複数日タイムライン（`scheduled_date`, `duration_min`, 位置情報あり）
- `lt_checklist_items`: 遠征ごとのチェックリスト
- `lt_my_lists` / `lt_my_list_items`: 持ち物テンプレート
- `lt_user_places`: ユーザー別地点（home等）
- `lt_maps_api_usage`: Maps API利用量（月次SKU）

## ER図（Mermaid）

```mermaid
erDiagram
    lt_trip_plans ||--o{ lt_trip_members : has
    lt_trip_plans ||--o{ lt_trip_plan_events : links
    lt_trip_plans ||--o{ lt_expenses : has
    lt_trip_plans ||--o{ lt_hotel_stays : has
    lt_trip_plans ||--o{ lt_transport_legs : has
    lt_trip_plans ||--o{ lt_destinations : has
    lt_trip_plans ||--o{ lt_timeline_items : has
    lt_trip_plans ||--o{ lt_checklist_items : has

    lt_my_lists ||--o{ lt_my_list_items : contains

    lt_events ||--o{ lt_trip_plan_events : referenced_by

    users ||--o{ lt_trip_members : participates
    users ||--o{ lt_events : owns
    users ||--o{ lt_my_lists : owns
    users ||--o{ lt_user_places : owns

    hn_events ||--o{ lt_trip_plan_events : referenced_by
    hn_events ||--o{ hn_user_events_status : has_status
    users ||--o{ hn_user_events_status : writes

    lt_trip_plans {
      bigint id PK
      text impression
      datetime created_at
      datetime updated_at
    }

    lt_trip_members {
      bigint id PK
      bigint trip_plan_id FK
      int user_id FK
      varchar role
      datetime created_at
    }

    lt_trip_plan_events {
      bigint id PK
      bigint trip_plan_id FK
      varchar event_type
      bigint hn_event_id
      bigint lt_event_id
      smallint sort_order
      varchar seat_info
      text impression
      datetime created_at
      datetime updated_at
    }

    lt_events {
      bigint id PK
      int user_id FK
      varchar event_name
      date event_date
      varchar event_place
      text event_info
      varchar event_place_address
      decimal latitude
      decimal longitude
      varchar place_id
      datetime created_at
      datetime updated_at
    }

    lt_expenses {
      bigint id PK
      bigint trip_plan_id FK
      varchar category
      int amount
      text memo
      datetime created_at
      datetime updated_at
    }

    lt_hotel_stays {
      bigint id PK
      bigint trip_plan_id FK
      varchar hotel_name
      varchar address
      date check_in
      date check_out
      int price
      tinyint num_guests
      decimal latitude
      decimal longitude
      varchar place_id
      datetime created_at
      datetime updated_at
    }

    lt_transport_legs {
      bigint id PK
      bigint trip_plan_id FK
      date departure_date
      varchar transport_type
      varchar departure
      varchar arrival
      int duration_min
      varchar scheduled_time
      int amount
      varchar maps_link
      smallint sort_order
      datetime created_at
      datetime updated_at
    }

    lt_destinations {
      bigint id PK
      bigint trip_plan_id FK
      varchar name
      varchar destination_type
      varchar address
      date visit_date
      varchar visit_time
      decimal latitude
      decimal longitude
      varchar place_id
      smallint sort_order
      datetime created_at
      datetime updated_at
    }

    lt_timeline_items {
      bigint id PK
      bigint trip_plan_id FK
      date scheduled_date
      varchar label
      varchar scheduled_time
      smallint duration_min
      varchar memo
      varchar place_id
      decimal latitude
      decimal longitude
      varchar location_label
      varchar location_address
      smallint sort_order
      datetime created_at
      datetime updated_at
    }

    lt_checklist_items {
      bigint id PK
      bigint trip_plan_id FK
      varchar item_name
      tinyint checked
      smallint sort_order
      datetime created_at
      datetime updated_at
    }

    lt_my_lists {
      bigint id PK
      int user_id FK
      varchar list_name
      datetime created_at
      datetime updated_at
    }

    lt_my_list_items {
      bigint id PK
      bigint my_list_id FK
      varchar item_name
      smallint sort_order
      datetime created_at
    }

    lt_user_places {
      bigint id PK
      int user_id FK
      varchar place_key
      varchar label
      varchar address
      varchar place_id
      decimal latitude
      decimal longitude
      datetime created_at
      datetime updated_at
    }

    lt_maps_api_usage {
      bigint id PK
      varchar sku
      char year_month
      int count
      datetime updated_at
    }
```

## 補足

- `lt_trip_plan_events.event_type` は `hinata|generic` を想定
- `lt_trip_plan_events.hn_event_id` / `lt_trip_plan_events.lt_event_id` は排他的に利用する想定（片方のみ設定）
- `lt_maps_api_usage` は業務エンティティではなく、外部API利用量の運用管理テーブル
