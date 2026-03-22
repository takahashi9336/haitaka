-- ニュース・スケジュール テーブル
-- 実行: サーバ上の phpMyAdmin または SSH で手動実行

CREATE TABLE IF NOT EXISTS hn_news (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    article_code  VARCHAR(30)   NOT NULL UNIQUE COMMENT '公式サイトの記事コード (M02621等)',
    published_date DATE         NOT NULL,
    category      VARCHAR(50)   NOT NULL DEFAULT '',
    title         VARCHAR(1000) NOT NULL DEFAULT '',
    detail_url    VARCHAR(500)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (published_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hn_news_members (
    news_id   BIGINT NOT NULL,
    member_id INT    NOT NULL,
    PRIMARY KEY (news_id, member_id),
    INDEX idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hn_schedule (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    schedule_code VARCHAR(30)   NOT NULL UNIQUE COMMENT '公式サイトのスケジュールID',
    schedule_date DATE          NOT NULL,
    category      VARCHAR(50)   NOT NULL DEFAULT '',
    time_text     VARCHAR(50)   NULL COMMENT '時間帯テキスト (24:40～ 等)',
    title         VARCHAR(1000) NOT NULL DEFAULT '',
    detail_url    VARCHAR(500)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (schedule_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hn_schedule_members (
    schedule_id BIGINT NOT NULL,
    member_id   INT    NOT NULL,
    PRIMARY KEY (schedule_id, member_id),
    INDEX idx_member (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
