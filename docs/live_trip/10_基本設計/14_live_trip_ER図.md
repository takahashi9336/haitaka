# live_trip ER図

## 1. データモデル関係図

中心は `lt_trip_plans`。イベント紐付けは **多対多に相当する中間** `lt_trip_plan_events`（`hn_events` または `lt_events` 参照）。

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
    lt_events ||--o{ lt_trip_plan_events : ref_generic
    hn_events ||--o{ lt_trip_plan_events : ref_hinata
    hn_user_events_status }o--|| lt_trip_plan_events : hinata_row_meta

    lt_trip_plans {
        bigint_unsigned id PK
        varchar title
        text impression
        datetime created_at
        datetime updated_at
    }

    lt_trip_plan_events {
        bigint_unsigned id PK
        bigint_unsigned trip_plan_id FK
        varchar event_type
        bigint_unsigned hn_event_id FK_null
        bigint_unsigned lt_event_id FK_null
        smallint_unsigned sort_order
        varchar seat_info
        text impression
    }

    lt_trip_members {
        bigint_unsigned id PK
        bigint_unsigned trip_plan_id FK
        int user_id
        varchar role
    }

    lt_expenses {
        bigint_unsigned id PK
        bigint_unsigned trip_plan_id FK
        varchar category
        int amount
    }

    lt_transport_legs {
        bigint_unsigned id PK
        bigint_unsigned trip_plan_id FK
        date departure_date_null
        int amount_null
        varchar maps_link_null
        smallint_unsigned sort_order
    }
```

## 2. スキーマ差分の読み方

初期作成は `migrations/done/create_lt_livetrip_tables.sql`。以後 `add_lt_*` で列追加・イベント正規化（`lt_trip_plan_events`）等。**カラムの最終状態はモデル `$fields`** と DDL を両方確認する。

補助テーブル（共通化済み）:

- `com_user_places`: ユーザー別地点（自宅など）
- `com_maps_api_usage`: Google Maps API 利用量（月次・SKU別）
