# lt_maps_api_usage テーブル

Google Maps API 利用量を自前でカウントし、無料枠の 90% で制限するためのテーブルです。

## 実行方法

- **phpMyAdmin**: SQL タブで `add_lt_maps_api_usage.sql` の内容を貼り付けて実行
- **コマンドライン**: `mysql -u USER -p DATABASE < private/migrations/done/add_lt_maps_api_usage.sql`

## 設定

制限値は `private/config/maps_api_limits.php` で管理。無料枠が変更された場合はそちらを更新してください。
