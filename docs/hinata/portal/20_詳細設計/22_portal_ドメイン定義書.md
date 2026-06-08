# ポータル（Portal） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### hn_topics
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| title | タイトル | varchar(255) | NOT NULL | |
| summary | 概要 | text | NULL | |
| url | リンクURL | varchar(500) | NULL | 外部リンク先。LINE/公式サイト等 |
| image_url | 画像URL | varchar(500) | NULL | 相対パス `img/topics/xxx.jpg` または絶対URL。未登録時はグラデーション背景表示 |
| topic_type | 種別 | varchar(50) | NOT NULL | DEFAULT 'other'。区分値は下記参照 |
| start_date | 表示開始日 | date | NULL | NULL時は即時表示 |
| end_date | 表示終了日 | date | NULL | NULL時は無期限表示 |
| sort_order | 並び順 | tinyint | NOT NULL | DEFAULT 0。小さい順 |
| is_active | 有効フラグ | tinyint(1) | NOT NULL | DEFAULT 1。1=有効, 0=無効 |
| created_at | 作成日時 | datetime | NOT NULL | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP |

**インデックス**:
- `idx_active_dates` (is_active, start_date, end_date): ポータル表示用の絞り込み
- `idx_sort` (sort_order): ソート用

### hn_announcements
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| title | タイトル | varchar(255) | NOT NULL | |
| body | 本文 | text | NULL | |
| url | リンクURL | varchar(500) | NULL | 外部リンク先 |
| image_url | 画像URL | varchar(500) | NULL | 相対パス `img/announcements/xxx.jpg` または絶対URL |
| announcement_type | 種別 | varchar(50) | NOT NULL | DEFAULT 'other'。区分値は下記参照 |
| published_at | 公開日時 | datetime | NULL | NULL時は即時公開 |
| expires_at | 終了日時 | datetime | NULL | NULL時は無期限表示 |
| sort_order | 並び順 | tinyint | NOT NULL | DEFAULT 0。小さい順 |
| is_active | 有効フラグ | tinyint(1) | NOT NULL | DEFAULT 1。1=有効, 0=無効 |
| created_at | 作成日時 | datetime | NOT NULL | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP |

**インデックス**:
- `idx_active_published` (is_active, published_at, expires_at): ポータル表示用の絞り込み
- `idx_sort` (sort_order): ソート用

## 2. ステータス・区分値定義 (マジックナンバー)

### topic_type (TopicModel::TOPIC_TYPES)
| 値 | 表示名 | 用途 |
| :--- | :--- | :--- |
| `big_event` | ビッグイベント | ひな誕祭、ひなたフェス等の大型イベント |
| `goods` | グッズ | グッズ販売情報 |
| `news` | ニュース | 一般的なニュース・INFO |
| `other` | その他 | その他の話題 |

**ポータル表示時のバッジ分類** (`portalTopicBadge` 関数):
| 条件 | バッジラベル | バッジ色 |
| :--- | :--- | :--- |
| URLに `line.me` または `line.naver.jp` を含む | LINE | bg-emerald-100 / text-emerald-700 |
| topic_type = 'news' | INFO | bg-sky-100 / text-sky-700 |
| その他 | TOPICS | bg-orange-100 / text-orange-700 |

### announcement_type (AnnouncementModel::TYPES)
| 値 | 表示名 |
| :--- | :--- |
| `goods` | グッズ |
| `application_deadline` | 応募締切 |
| `big_event` | ビッグイベント |
| `media` | メディア |
| `release` | リリース |
| `ticket` | チケット |
| `fanclub` | ファンクラブ |
| `meetgreet` | ミート&グリート |
| `audition` | オーディション |
| `other` | その他 |

### is_active (共通)
| 値 | 意味 |
| :--- | :--- |
| `1` | 有効（表示対象） |
| `0` | 無効（非表示） |

## 3. モデルクラス定義

### TopicModel
- 継承: `Core\BaseModel`
- テーブル: `hn_topics`
- `isUserIsolated`: `false`（ユーザー分離なし、全ユーザー共通データ）
- 主要メソッド:
  - `getActiveTopics()`: 表示中のトピック一覧。`is_active=1` かつ `start_date <= CURDATE() <= end_date` の条件で絞り込み。`sort_order ASC, id ASC` でソート
  - `getAll()`: 管理用全件取得。`sort_order ASC, id ASC` でソート
  - `create($data)`: 新規作成（BaseModel継承）
  - `update($id, $data)`: 更新（BaseModel継承）

### AnnouncementModel
- 継承: `Core\BaseModel`
- テーブル: `hn_announcements`
- `isUserIsolated`: `false`（ユーザー分離なし）
- 主要メソッド:
  - `getActiveAnnouncements(int $limit = 20)`: 公開期間内のお知らせ一覧。`is_active=1` かつ `published_at <= NOW() <= expires_at` の条件で絞り込み。`sort_order ASC, published_at DESC, id DESC` でソート
  - `getAll()`: 管理用全件取得。`sort_order ASC, published_at DESC, id DESC` でソート
  - `create($data)`: 新規作成（BaseModel継承）
  - `update($id, $data)`: 更新（BaseModel継承）

## 4. 画像保存パス

| 対象 | 保存先ディレクトリ | ファイル名パターン | 参照パス（image_url カラム値） |
| :--- | :--- | :--- | :--- |
| トピック画像 | `www/assets/img/topics/` | `topic_YYYYMMDDHHmmss_XXXXXXXX.{ext}` | `img/topics/topic_...` |
| お知らせ画像 | `www/assets/img/announcements/` | `ann_YYYYMMDDHHmmss_XXXXXXXX.{ext}` | `img/announcements/ann_...` |

- 対応画像形式: jpg, jpeg, png, gif, webp
- 表示時: `image_url` が `/` 始まりまたは `http` 始まりならそのまま使用、それ以外は `/assets/` をプレフィクスして表示
