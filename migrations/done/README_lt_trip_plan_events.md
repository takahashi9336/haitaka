# 遠征 複数イベント対応マイグレーション

## 実行ファイル
`add_lt_trip_plan_events_multi_event.sql`

## 実行内容
1. `hn_user_events_status` に `seat_info`, `impression` を追加
2. `lt_trip_plan_events` を新規作成
3. 既存の `lt_trip_plans` からイベントデータを移行
4. `lt_trip_plans` から `event_type`, `hn_event_id`, `lt_event_id`, `seat_info` を削除

## 実行前
- 本番環境では必ずバックアップを取得してください
- 既存データがある場合は移行が行われます

## 実行方法
phpMyAdmin または MySQL クライアントで SQL を実行してください。
