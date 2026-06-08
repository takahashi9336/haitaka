# メンバー（Member） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### hn_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK | 0 は長濱ねる（ポカ）用に予約 |
| name | メンバー名 | varchar(255) | NOT NULL | |
| kana | かな | varchar(255) | | ソート・検索用 |
| generation | 期生 | int | | 0=ポカ/期別なし, 1-5=各期生 |
| birth_date | 生年月日 | date | NULL | |
| blood_type | 血液型 | varchar(10) | | A/B/O/AB/不明 |
| height | 身長 | decimal | NULL | cm単位 |
| birth_place | 出身地 | varchar(100) | | |
| color_id1 | サイリウムカラー1 | int | FK → hn_colors.id, NULL | LEFT JOIN のため NULL 許容 |
| color_id2 | サイリウムカラー2 | int | FK → hn_colors.id, NULL | LEFT JOIN のため NULL 許容 |
| is_active | 現役フラグ | tinyint(1) | NOT NULL | 1=現役, 0=卒業 |
| image_url | メイン画像 | varchar(255) | NULL | 後方互換。現在は hn_member_images の先頭画像を優先 |
| blog_url | ブログURL | varchar(500) | | 日向坂公式ブログ等 |
| insta_url | Instagram URL | varchar(500) | | |
| twitter_url | X(Twitter) URL | varchar(500) | | |
| pv_movie_id | PV動画ID | varchar(100) | NULL | 後方互換。現在は hn_media_members + com_media_assets で管理 |
| member_info | メンバー情報メモ | text | NULL | 管理者が自由記述する補足情報 |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新者 | varchar(100) | | `$_SESSION['user']['id_name']` を記録 |

**インデックス・ソート**:
- 一覧取得時の既定ソート: `is_active DESC, generation ASC, kana ASC`
- ポカ（id=`MemberGroupHelper::POKA_MEMBER_ID`）はソート末尾に配置（`(m.id = POKA_ID) ASC`）
- `isUserIsolated = false`（共通マスタのためユーザー分離なし）

### hn_colors
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK | |
| color_name | 色名 | varchar(50) | | 「ライトブルー」「ピンク」等 |
| color_code | HEXカラーコード | varchar(10) | | `#RRGGBB` 形式 |

**用途**: ペンライトカラー表・メンバーカードのグラデーション帯・推し設定のリングカラーに使用。

### hn_member_images
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| member_id | メンバーID | int | FK → hn_members.id, ON DELETE CASCADE, NOT NULL | |
| image_url | 画像ファイル名 | varchar(255) | NOT NULL | `member_{id}_{slot}.{ext}` 形式 |
| sort_order | 表示順 | tinyint unsigned | NOT NULL | DEFAULT 0。0-4 の範囲 |
| update_user | 更新者 | varchar(100) | | |

**インデックス**: `KEY member_id (member_id)`
**制約**: 1メンバーあたり最大5枚。保存時は DELETE → INSERT で全件洗い替え。

### hn_member_activities
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| member_id | メンバーID | int | FK → hn_members.id, ON DELETE CASCADE, NOT NULL | |
| category | カテゴリ | varchar(50) | NOT NULL | DEFAULT 'other'。区分値は下記参照 |
| title | 活動名 | varchar(200) | NOT NULL | |
| description | 概要 | text | NULL | |
| url | 誘導先URL | varchar(500) | NULL | |
| url_label | ボタンラベル | varchar(100) | NULL | カスタムリンクテキスト |
| image_url | サムネイル画像 | varchar(500) | NULL | `activity_{memberId}_{timestamp}.{ext}` 形式 |
| is_active | 表示フラグ | tinyint(1) | NOT NULL | DEFAULT 1。1=表示, 0=非表示 |
| sort_order | 表示順 | tinyint unsigned | NOT NULL | DEFAULT 0 |
| start_date | 開始日 | date | NULL | |
| end_date | 終了日 | date | NULL | |
| created_at | 作成日時 | datetime | NOT NULL | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP |

**インデックス**: `KEY idx_member_active (member_id, is_active, sort_order)`
**制約**: `isUserIsolated = false`（管理者が登録する共通データ）

### hn_favorites
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL | sys_users.id |
| member_id | メンバーID | int | FK → hn_members.id, NOT NULL | |
| level | 推しレベル | int | NOT NULL | 区分値は下記参照 |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

**排他制約**: level 7-9 はユーザーあたり各1名のみ。同レベルに別メンバーを設定すると既存レコードを DELETE してから INSERT/UPDATE。
**`isUserIsolated = true`**: ユーザーごとにデータを分離。

### hn_oshi_images
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL | sys_users.id |
| member_id | メンバーID | int | FK → hn_members.id, ON DELETE CASCADE, NOT NULL | |
| image_path | 画像パス | varchar(500) | NOT NULL | `uploads/oshi/{user_id}/{member_id}/{filename}` 形式 |
| caption | キャプション | varchar(200) | NULL | ユーザーが任意入力 |
| sort_order | 表示順 | tinyint unsigned | DEFAULT 0 | |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

**インデックス**: `KEY idx_user_member (user_id, member_id)`
**制約**: 1ユーザー+1メンバーあたり最大10枚（`OshiImageModel::MAX_IMAGES_PER_MEMBER`）。アップロード時に長辺1200px以下にリサイズ（`MAX_DIMENSION`）。対応形式: JPEG, PNG, WebP。
**`isUserIsolated = true`**。

### hn_user_member_profiles
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL | sys_users.id |
| member_id | メンバーID | int | NOT NULL | hn_members.id |
| image_path | 画像パス | varchar(500) | NOT NULL | `uploads/member_profile/{user_id}/member_{memberId}_{uniqid}.{ext}` |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |

**インデックス**: `UNIQUE KEY uq_user_member (user_id, member_id)`, `INDEX idx_member (member_id)`
**制約**: ユーザー+メンバーで UNIQUE。既存レコードがある場合は `ON DUPLICATE KEY UPDATE` で画像パスを更新し、旧画像ファイルを物理削除。

## 2. ステータス・区分値定義 (マジックナンバー)

### level (FavoriteModel 定数)
| 値 | 定数名 | 意味 | 排他制約 |
| :--- | :--- | :--- | :--- |
| `0` | `LEVEL_NONE` | その他（お気に入り解除） | なし |
| `1` | `LEVEL_KINNINARU` | 気になる | なし（複数メンバー可） |
| `7` | `LEVEL_OSHI_3` | 3推し | ユーザーあたり1名のみ |
| `8` | `LEVEL_OSHI_2` | 2推し | ユーザーあたり1名のみ |
| `9` | `LEVEL_OSHI_TOP` | 最推し | ユーザーあたり1名のみ |

**ラベル定義** (`LEVEL_LABELS`):
| レベル | 表示ラベル |
| :--- | :--- |
| 9 | 最推し |
| 8 | 2推し |
| 7 | 3推し |
| 1 | 気になる |

### generation (hn_members.generation)
| 値 | 意味 |
| :--- | :--- |
| `0` | ポカ / 期別なし |
| `1` | 1期生 |
| `2` | 2期生 |
| `3` | 3期生 |
| `4` | 4期生 |
| `5` | 5期生（仮。今後の加入に備え拡張可能） |

### is_active (hn_members.is_active)
| 値 | 意味 |
| :--- | :--- |
| `1` | 現役メンバー |
| `0` | 卒業メンバー |

### category (MemberActivityModel::CATEGORIES)
| 値 | 表示名 | アイコン |
| :--- | :--- | :--- |
| `radio` | ラジオ番組 | fa-solid fa-radio |
| `podcast` | ポッドキャスト | fa-solid fa-podcast |
| `drama` | ドラマ・映画 | fa-solid fa-film |
| `magazine` | 雑誌・モデル | fa-solid fa-book-open |
| `youtube_personal` | YouTube個人 | fa-brands fa-youtube |
| `cm` | CM | fa-solid fa-tv |
| `stage` | 舞台 | fa-solid fa-masks-theater |
| `other` | その他 | fa-solid fa-star |

## 3. モデルクラス定義

### MemberModel
- 継承: `Core\BaseModel`
- テーブル: `hn_members`
- `isUserIsolated`: `false`（共通マスタ）
- 主要メソッド:
  - `getMemberDetail(int $memberId)`: メンバー詳細取得。カラー情報・PV動画キー（`hn_media_members` + `hn_media_metadata` + `com_media_assets` の SoloPV）・推しレベル・複数画像を結合して返却
  - `getMembersForBook()`: メンバー帳用一覧。現役+卒業・カラー・推しレベル・PVキー・複数画像付き。ソート: ポカ末尾 → 現役優先 → 期生昇順 → かな昇順
  - `getActiveMembersWithColors()`: 現役メンバー一覧（推し設定・FABフォーム用）。推しレベル降順 → 期生昇順 → かな昇順
  - `getAllWithColors()`: 管理用全メンバー取得（カラー・複数画像付き）
  - `getColorMaster()`: hn_colors 全件取得
  - `getMemberImages(int $memberId)`: 指定メンバーの画像一覧（最大5枚、sort_order順）
  - `getMemberImagesMap(array $memberIds)`: 複数メンバーの画像をまとめて取得（member_id => [image_url, ...] マップ）
  - `saveMemberImages(int $memberId, array $imageUrls)`: 既存を全削除後に再挿入（最大5枚）
  - `find(int $id)`: BaseModel継承。主キーで1件取得
  - `update(int $id, array $data)`: BaseModel継承。主キーで更新

### MemberActivityModel
- 継承: `Core\BaseModel`
- テーブル: `hn_member_activities`
- `isUserIsolated`: `false`（管理者共通データ）
- 定数: `CATEGORIES`（カテゴリ定義。ラベル・アイコン・カラー情報を含む連想配列）
- 主要メソッド:
  - `getByMember(int $memberId, bool $activeOnly = true)`: メンバー単位の活動一覧。sort_order → id 昇順
  - `getAllGroupedByMember(bool $activeOnly = true)`: 全メンバーの活動をまとめて取得（member_id => [activities] マップ）
  - `saveActivity(array $data)`: 新規作成（id未指定時）または更新（id指定時）。戻り値は保存後のID

### FavoriteModel
- 継承: `Core\BaseModel`
- テーブル: `hn_favorites`
- `isUserIsolated`: `true`（ユーザーごとにデータ分離）
- 定数: `LEVEL_NONE`, `LEVEL_KINNINARU`, `LEVEL_OSHI_3`, `LEVEL_OSHI_2`, `LEVEL_OSHI_TOP`, `OSHI_LEVELS`, `LEVEL_LABELS`
- 主要メソッド:
  - `setLevel(int $memberId, int $level)`: 推しレベル設定。level 7-9 は排他制御付き（同レベルの既存を自動解除）。セッションキャッシュも更新。戻り値: `['status', 'level', 'swapped_member_id'?, 'swapped_member_name'?]`
  - `getMemberLevel(int $memberId)`: 指定メンバーの現在レベルを取得
  - `getUserFavorites()`: ユーザーの全お気に入り取得（メンバー名・かな・期生・画像付き）
  - `getOshiMembers()`: 推し3名取得（level 7-9、メンバー情報・カラー・ユーザー設定プロフィール画像付き）
  - `getOshiPortalSummary()`: ポータル用サマリ。推し3名に最新動画・次イベント・参加楽曲数を付加
  - `getOshiLatestItemPerMember(array $memberIds, int $limit)`: 推しメンバーごとの最新N件をブログ・ニュース・スケジュール・イベント・動画の UNION ALL で取得
  - `cacheOshiToSession()`: 推し情報（level 7-9）をセッション `$_SESSION['oshi']` にキャッシュ

### OshiImageModel
- 継承: `Core\BaseModel`
- テーブル: `hn_oshi_images`
- `isUserIsolated`: `true`
- 定数: `MAX_IMAGES_PER_MEMBER = 10`, `MAX_DIMENSION = 1200`, `ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp']`, `UPLOAD_BASE = 'uploads/oshi'`
- 主要メソッド:
  - `getByMember(int $memberId)`: メンバーの画像一覧取得（sort_order → id 昇順）
  - `countByMember(int $memberId)`: メンバーの画像枚数を取得
  - `saveImage(int $memberId, string $imagePath, ?string $caption)`: 画像レコードを INSERT。sort_order は現在の枚数をインクリメント
  - `deleteImage(int $imageId)`: 画像レコードを DELETE し、物理ファイルも削除。user_id チェックによりオーナーのみ削除可能
  - `getUploadDir(int $memberId)`: アップロードディレクトリのパスを取得（`uploads/oshi/{user_id}/{member_id}`）。存在しなければ mkdir
  - `resizeImage(string $source, string $dest, string $mime)` (static): 長辺が MAX_DIMENSION を超える場合にリサイズ。JPEG品質85、PNG圧縮レベル6、WebP品質85

## 4. 画像保存パス

| 対象 | 保存先ディレクトリ | ファイル名パターン | 参照パス |
| :--- | :--- | :--- | :--- |
| メンバー画像 | `www/assets/img/members/` | `member_{id}_{slot}.{ext}` | `member_1_0.jpg` 等 |
| 個人活動サムネイル | `www/assets/img/activities/` | `activity_{memberId}_{timestamp}.{ext}` | `activity_5_1700000000.jpg` 等 |
| マイフォト (推し画像) | `www/uploads/oshi/{user_id}/{member_id}/` | `oshi_{uniqid}.{ext}` | `uploads/oshi/1/5/oshi_xxxx.jpg` |
| ユーザープロフィール画像 | `www/uploads/member_profile/{user_id}/` | `member_{memberId}_{uniqid}.{ext}` | `uploads/member_profile/1/member_5_xxxx.jpg` |

- 対応画像形式: jpg, jpeg, png, gif, webp（メンバー画像・活動サムネイル）、JPEG, PNG, WebP（マイフォト・プロフィール画像）
- マイフォト・プロフィール画像はアップロード時に `OshiImageModel::resizeImage()` で長辺1200px以下にリサイズ
- メンバー画像は `object-cover` で表示、アー写は `object-cover object-top` で頭切れ防止

## 5. 他ドメインテーブルとの参照関係

member ドメインから READ (SELECT) のみで参照するテーブル:

| テーブル | 参照元 | 用途 |
| :--- | :--- | :--- |
| `hn_song_members` / `hn_songs` / `hn_releases` | OshiController::memberPage | 参加楽曲一覧（リリース日降順・トラック番号順） |
| `hn_media_members` / `hn_media_metadata` / `com_media_assets` | MemberModel::getMemberDetail, OshiController | SoloPV動画キー取得、ソロ/グループ/プラットフォーム別動画一覧 |
| `hn_event_members` / `hn_events` | OshiController::memberPage | 参加イベント一覧（今後+過去） |
| `hn_blog_posts` | OshiController::memberPage | 最新ブログ（メンバー別最大10件） |
| `hn_news` / `hn_news_members` | OshiController::memberPage | メンバー関連ニュース（最大5件） |
| `hn_schedule` / `hn_schedule_members` | OshiController::memberPage | メンバー関連スケジュール（最大5件） |
| `hn_neta` | OshiController::memberPage | ミーグリネタ（ステータス != 'delete'、暗号化フィールド復号） |
| `hn_release_member_images` | PenlightController | 最新シングルのアー写マップ取得 |
