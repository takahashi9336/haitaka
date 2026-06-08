# Media（メディア管理） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### com_media_assets
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| platform | プラットフォーム | varchar(20) | NOT NULL | 'youtube' / 'tiktok' / 'instagram' |
| media_key | メディアキー | varchar(255) | NOT NULL, UNIQUE(platform, media_key) | YouTube: 11 文字動画 ID、TikTok: 動画 ID（数字）または短縮キー、Instagram: shortcode |
| sub_key | サブキー | varchar(255) | | TikTok: '@username'、その他: NULL |
| media_type | メディア種別 | varchar(20) | | 'video' / 'short' / 'live'。NULL 許容 |
| title | タイトル | varchar(255) | | 動画タイトル |
| thumbnail_url | サムネイル画像URL | text | | YouTube: CDN URL、TikTok/Instagram: サーバ保存後の /uploads/thumbnails/ パス |
| description | 説明文 | text | | 動画の説明文。YouTube API / oEmbed から取得 |
| upload_date | アップロード日時 | datetime | | プラットフォームへの投稿日時 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

### hn_media_metadata
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| asset_id | アセットID | bigint(20) unsigned | FK → com_media_assets.id, UNIQUE | 1 アセットに対し 1 メタデータ |
| category | カテゴリ | varchar(64) | | hn_media_categories.name の値。NULL = 未分類 |
| release_date | リリース日 | date | | レガシー。現在は com_media_assets.upload_date を使用 |
| memo | メモ | text | | |

### hn_media_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| media_meta_id | メディアメタID | bigint(20) unsigned | FK → hn_media_metadata.id ON DELETE CASCADE, NOT NULL | UNIQUE(media_meta_id, member_id) |
| member_id | メンバーID | int(11) | FK → hn_members.id ON DELETE CASCADE, NOT NULL | |
| update_user | 更新ユーザー | varchar(50) | | 手動紐付け時のユーザー名。自動紐付け時は 'auto' |

### hn_media_categories
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(11) unsigned | PK, Auto Inc | |
| name | カテゴリ名 | varchar(64) | NOT NULL, UNIQUE | hn_media_metadata.category に格納する値 |
| sort_order | 表示順 | int(11) | NOT NULL | 初期値: 0 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

### hn_media_hashtags
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| media_meta_id | メディアメタID | bigint(20) unsigned | FK → hn_media_metadata.id ON DELETE CASCADE, NOT NULL | UNIQUE(media_meta_id, hashtag) |
| hashtag | ハッシュタグ | varchar(100) | NOT NULL | # なしで保存。INDEX あり |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

### hn_blog_posts
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) | PK, Auto Inc | |
| member_id | メンバーID | int(11) | FK → hn_members.id | NULL 許容（メンバー不明時） |
| article_id | 記事ID | int(11) | NOT NULL, UNIQUE | 公式サイトの diary detail ID |
| title | タイトル | varchar(500) | NOT NULL | 初期値: '' |
| body_html | 本文HTML | mediumtext | | スクレイピングした HTML |
| body_text | 本文テキスト | text | | 検索用プレーンテキスト（5000 文字で切り詰め） |
| thumbnail_url | サムネイルURL | varchar(500) | | 記事先頭画像の URL |
| published_at | 公開日時 | datetime | NOT NULL | INDEX(published_at DESC) |
| detail_url | 詳細ページURL | varchar(500) | NOT NULL | 公式サイトのフル URL |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NOT NULL | ON UPDATE CURRENT_TIMESTAMP |

### hn_news
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) | PK, Auto Inc | |
| article_code | 記事コード | varchar(30) | NOT NULL, UNIQUE | 公式サイトの記事コード（例: M02621） |
| published_date | 公開日 | date | NOT NULL | INDEX(published_date DESC) |
| category | カテゴリ | varchar(50) | NOT NULL | 初期値: ''。メディア / イベント / グッズ 等 |
| title | タイトル | varchar(1000) | NOT NULL | 初期値: '' |
| detail_url | 詳細ページURL | varchar(500) | NOT NULL | 公式サイトのフル URL |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NOT NULL | ON UPDATE CURRENT_TIMESTAMP |

### hn_news_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| news_id | ニュースID | bigint(20) | PK（複合）| hn_news.id |
| member_id | メンバーID | int(11) | PK（複合）, INDEX | hn_members.id |

### hn_schedule
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) | PK, Auto Inc | |
| schedule_code | スケジュールコード | varchar(30) | NOT NULL, UNIQUE | 公式サイト ID + '_' + 日付（例: 12345_2026-06-07） |
| schedule_date | 日付 | date | NOT NULL | INDEX(schedule_date DESC) |
| category | カテゴリ | varchar(50) | NOT NULL | 初期値: ''。テレビ / ラジオ / 雑誌 等 |
| time_text | 時間帯テキスト | varchar(50) | | 例: '24:40～' |
| title | タイトル | varchar(1000) | NOT NULL | 初期値: '' |
| detail_url | 詳細ページURL | varchar(500) | NOT NULL | 公式サイトのフル URL |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NOT NULL | ON UPDATE CURRENT_TIMESTAMP |

### hn_schedule_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| schedule_id | スケジュールID | bigint(20) | PK（複合）| hn_schedule.id |
| member_id | メンバーID | int(11) | PK（複合）, INDEX | hn_members.id |

## 2. ステータス・区分値定義 (マジックナンバー)

### com_media_assets.platform
- `youtube` = YouTube
- `tiktok` = TikTok
- `instagram` = Instagram

### com_media_assets.media_type
- `video` = 通常動画（YouTube: duration > 180s）
- `short` = ショート動画（YouTube Shorts / TikTok / Instagram Reels）
- `live` = ライブ配信 / アーカイブ（YouTube: liveStreamingDetails 存在時）
- `NULL` = 未判定

### hn_media_metadata.category（MediaController::CATEGORIES / hn_media_categories）
- `Call` = コール動画
- `CM` = CM
- `Hinareha` = ひなリハ
- `Live` = ライブ映像
- `MV` = ミュージックビデオ
- `SelfIntro` = 自己紹介
- `SoloPV` = ソロ PV
- `Special` = スペシャル映像
- `Teaser` = ティザー
- `Trailer` = トレーラー
- `Variety` = バラエティ
- `NULL` / `''` = 未分類

### メディア登録時の重複ステータス（MediaController::checkDuplicateStatus）
- `New` = com_media_assets に未登録。完全新規
- `Linked` = com_media_assets に存在するが hn_media_metadata は未登録。素材のみ存在
- `Registered` = hn_media_metadata として登録済み。スキップ対象

### hn_news.category（NewsScraper で自動分類）
- `メディア` / `イベント` / `グッズ` / `チケット` / `リリース` / `ファンクラブ` / `ミート＆グリート` / `オーディション` / `その他`

### hn_schedule.category（ScheduleScraper で自動分類）
- `テレビ` / `ラジオ` / `雑誌` / `配信` / `イベント` / `WEB連載` / `放送` / `誕生日` / `グッズ` / `チケット` / `リリース` / `その他`

### YouTube media_type 判定ロジック（YouTubeApiClient::detectMediaTypeAdvanced）
1. `liveStreamingDetails` が存在 → `live`
2. `snippet.liveBroadcastContent` が `live` / `upcoming` → `live`
3. `contentDetails.duration` を ISO 8601 からパースし 180 秒以下 → `short`
4. タイトルに `#shorts` / `#short` を含む → `short`
5. 上記いずれにも該当しない → `video`

### TikTok 公開日時推定ロジック（MediaController::extractTikTokTimestamp）
- 動画 ID（Snowflake ID 方式）の上位 32 ビットを右シフトし Unix タイムスタンプとして解釈
- 有効範囲: 2015-01-01 ～ 2050-01-01
