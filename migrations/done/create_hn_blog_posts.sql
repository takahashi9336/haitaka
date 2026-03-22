CREATE TABLE IF NOT EXISTS hn_blog_posts (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT            NULL COMMENT 'FK to hn_members.id',
    article_id    INT            NOT NULL UNIQUE COMMENT '公式サイトの記事ID',
    title         VARCHAR(500)   NOT NULL DEFAULT '',
    body_html     MEDIUMTEXT     NULL COMMENT '本文HTML',
    body_text     TEXT           NULL COMMENT '本文プレーンテキスト (検索用)',
    thumbnail_url VARCHAR(500)   NULL COMMENT '先頭画像URL',
    published_at  DATETIME       NOT NULL,
    detail_url    VARCHAR(500)   NOT NULL,
    created_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member (member_id),
    INDEX idx_published (published_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE hn_members ADD COLUMN official_blog_ct SMALLINT NULL AFTER blog_url;

-- 公式ブログ ct パラメータ → hn_members マッピング
UPDATE hn_members SET official_blog_ct = 12 WHERE name LIKE '%金村%美玖%';
UPDATE hn_members SET official_blog_ct = 14 WHERE name LIKE '%小坂%菜緒%';
UPDATE hn_members SET official_blog_ct = 18 WHERE name LIKE '%松田%好花%';
UPDATE hn_members SET official_blog_ct = 21 WHERE name LIKE '%上村%ひなの%';
UPDATE hn_members SET official_blog_ct = 22 WHERE name LIKE '%髙橋%未来虹%';
UPDATE hn_members SET official_blog_ct = 23 WHERE name LIKE '%森本%茉莉%';
UPDATE hn_members SET official_blog_ct = 24 WHERE name LIKE '%山口%陽世%';
UPDATE hn_members SET official_blog_ct = 25 WHERE name LIKE '%石塚%瑶季%';
UPDATE hn_members SET official_blog_ct = 27 WHERE name LIKE '%小西%夏菜実%';
UPDATE hn_members SET official_blog_ct = 28 WHERE name LIKE '%清水%理央%';
UPDATE hn_members SET official_blog_ct = 29 WHERE name LIKE '%正源司%陽子%';
UPDATE hn_members SET official_blog_ct = 30 WHERE name LIKE '%竹内%希来里%';
UPDATE hn_members SET official_blog_ct = 31 WHERE name LIKE '%平尾%帆夏%';
UPDATE hn_members SET official_blog_ct = 32 WHERE name LIKE '%平岡%海月%';
UPDATE hn_members SET official_blog_ct = 33 WHERE name LIKE '%藤嶌%果歩%';
UPDATE hn_members SET official_blog_ct = 34 WHERE name LIKE '%宮地%すみれ%';
UPDATE hn_members SET official_blog_ct = 35 WHERE name LIKE '%山下%葉留花%';
UPDATE hn_members SET official_blog_ct = 36 WHERE name LIKE '%渡辺%莉奈%';
UPDATE hn_members SET official_blog_ct = 37 WHERE name LIKE '%大田%美月%';
UPDATE hn_members SET official_blog_ct = 38 WHERE name LIKE '%大野%愛実%';
UPDATE hn_members SET official_blog_ct = 39 WHERE name LIKE '%片山%紗希%';
UPDATE hn_members SET official_blog_ct = 40 WHERE name LIKE '%蔵盛%妃那乃%';
UPDATE hn_members SET official_blog_ct = 41 WHERE name LIKE '%坂井%新奈%';
UPDATE hn_members SET official_blog_ct = 42 WHERE name LIKE '%佐藤%優羽%';
UPDATE hn_members SET official_blog_ct = 43 WHERE name LIKE '%下田%衣珠季%';
UPDATE hn_members SET official_blog_ct = 44 WHERE name LIKE '%高井%俐香%';
UPDATE hn_members SET official_blog_ct = 45 WHERE name LIKE '%鶴崎%仁香%';
UPDATE hn_members SET official_blog_ct = 46 WHERE name LIKE '%松尾%桜%';
