# Media Management System Refactoring Design

## 1. 目的と背景 (Context)
現在の `com_youtube_embed_data` はYouTube専用の構造となっており、将来的なTikTokやInstagramへの対応、および「どのメンバーがどの動画に出演しているか（参戦曲、個人PV等）」を柔軟に管理することが困難である。
本設計書では、データベースを正規化し、多対多の紐付けを可能にすることで、スケーラビリティと保守性を向上させる。

## 2. データベース設計 (Schema Refactoring)

### 2.1 com_media_assets (メディア素材マスター)
YouTube、TikTok、Instagramの素材データを集約する。プラットフォーム特有のURL構造に対応するため、補助キー（sub_key）を設ける。
- **platform**: `youtube`, `tiktok`, `instagram` のいずれか。
- **media_key**: 動画ID、ショートコード、投稿ID。
- **sub_key**: TikTokの `@ユーザ名` 等、URL形成に必要な補助情報。

``` sql
CREATE TABLE `com_media_assets` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` ENUM('youtube', 'tiktok', 'instagram') NOT NULL DEFAULT 'youtube',
  `media_key` varchar(100) NOT NULL COMMENT '動画ID、ショートコード等',
  `sub_key` varchar(100) DEFAULT NULL COMMENT 'TikTokのユーザ名等',
  `title` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_media_key` (`platform`, `media_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### 2.2 hn_media_metadata (日向坂コンテンツ定義)
- 素材に対して「アプリ内での意味（カテゴリ）」を付与する。
- category: MV, SoloPV, TikTok, Introduction, Variety 等。
``` sql
CREATE TABLE `hn_media_metadata` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(50) DEFAULT 'MV',
  `release_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`asset_id`) REFERENCES `com_media_assets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```
既存のメンバー管理画面で登録したyoutube動画については'member_kojin_pv'として登録済み。

### 2.3 hn_media_members (出演者紐付け)
- 「1つの動画に複数メンバーが出演する（ユニット曲、全体MV）」および「1人のメンバーが複数動画に出演する」関係を管理する中間テーブル。
- member_id: hn_members.id への外部キー。
``` SQL
CREATE TABLE `hn_media_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_meta_id` bigint(20) UNSIGNED NOT NULL,
  `member_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meta_member` (`media_meta_id`, `member_id`),
  FOREIGN KEY (`media_meta_id`) REFERENCES `hn_media_metadata`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

## 3. 実装ガイドライン (Implementation Guidelines)
### 3.2 URL解析ロジック (URL Parsing)
- YouTube: v= パラメータを抽出。
- TikTok: URLから @ユーザ名（sub_key）と video/ID（media_key）を抽出。
- Instagram: /reels/ または /p/ 以下のショートコードを media_key として抽出。
