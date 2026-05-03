-- イベントの関連リンク（タグ入力）を canonical JSON で保持する。
-- アプリ側は保存時に event_url / collaboration_urls / hn_event_movies と同期する。
ALTER TABLE hn_events
    ADD COLUMN related_links TEXT NULL COMMENT 'JSON: [{url, kind, manual_override}, ...]' AFTER collaboration_urls;
