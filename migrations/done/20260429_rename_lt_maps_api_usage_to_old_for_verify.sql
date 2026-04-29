-- 検証用: 既存 lt_maps_api_usage を退避（移行できているか確認するため）
-- 目的:
--  - アプリ参照先を com_maps_api_usage に切り替えた後、
--    lt_maps_api_usage をリネームしても動作すること = 移行先を参照できていること
--
-- 注意:
--  - lt_maps_api_usage_old が既に存在する場合は失敗します

RENAME TABLE lt_maps_api_usage TO lt_maps_api_usage_old;

