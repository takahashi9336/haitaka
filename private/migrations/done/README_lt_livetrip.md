# LiveTrip マイグレーション

## 実行方法

1. `create_lt_livetrip_tables.sql` を実行
2. `add_lt_timeline_checklist_mylist.sql` を実行（タイムライン・チェックリスト・マイリスト用）

- phpMyAdmin: SQLタブでファイルの内容を貼り付けて実行
- コマンドライン: `mysql -u USER -p DATABASE < private/migrations/done/create_lt_livetrip_tables.sql`

## 実行後

1. **ログインし直す**: sys_apps に live_trip が追加されるため、Admin ユーザーは一度ログアウトして再ログインするとサイドバーに「遠征管理」が表示されます。
2. **試験運用**: 当初は Admin 権限者のみアクセス可能です。
