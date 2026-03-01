-- チャットメッセージに「相手の行動」「内心」タイプを追加
ALTER TABLE `hn_meetgreet_report_messages`
  MODIFY COLUMN `sender_type`
    enum('member','self','narration','self_thought')
    NOT NULL DEFAULT 'self'
    COMMENT 'メッセージ送信者タイプ';
