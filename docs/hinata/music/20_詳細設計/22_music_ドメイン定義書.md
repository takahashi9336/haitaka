# Music（楽曲・リリース・アーティスト写真） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### hn_releases
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(11) | PK, Auto Inc | |
| release_type | リリース種別 | enum('single','album','digital','ep','best') | NOT NULL | 初期値: 'single' |
| group_name | グループ名 | varchar(30) | NOT NULL | 初期値: 'hinatazaka46' |
| release_number | リリース番号 | varchar(20) | | '1st', '2nd', 'ベスト' 等 |
| title | タイトル | varchar(255) | NOT NULL | |
| title_kana | よみがな | varchar(255) | | |
| release_date | 発売日 | date | | INDEX あり |
| description | 説明・備考 | text | | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新ユーザー | varchar | | |

### hn_release_editions
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| release_id | リリースID | int(11) | FK → hn_releases.id, ON DELETE CASCADE | |
| edition | 版 | enum('type_a','type_b','type_c','type_d','normal') | NOT NULL, UNIQUE(release_id, edition) | |
| jacket_image_url | ジャケット画像URL | varchar(255) | | |
| sort_order | 表示順 | tinyint(4) | | 初期値: 0 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新ユーザー | varchar | | |

### hn_songs
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| release_id | リリースID | int(11) | FK → hn_releases.id, ON DELETE CASCADE, NOT NULL | |
| media_meta_id | メディアメタID | bigint(20) unsigned | FK → hn_media_metadata.id, ON DELETE SET NULL, UNIQUE | レガシー。現在は hn_song_media_links を使用 |
| title | 楽曲タイトル | varchar(255) | NOT NULL | |
| title_kana | よみがな | varchar(255) | | |
| track_type | トラックタイプ | enum('title','read','sub','type_a','type_b','type_c','type_d','normal','other') | | 初期値: 'other' |
| track_number | トラック番号 | int(11) | | |
| formation_type | フォーメーションタイプ | enum('all','kibetsu','senbatsu','solo','under','unit','other') | | 初期値: 'other' |
| generation | 期 | tinyint | | 期別曲の場合の期番号 |
| lyricist | 作詞 | varchar(255) | | |
| composer | 作曲 | varchar(255) | | |
| arranger | 編曲 | varchar(50) | | |
| mv_director | MV監督 | varchar(50) | | |
| choreographer | 振付師 | varchar(50) | | |
| duration | 再生時間 | int(11) | | 秒単位 |
| memo | 備考 | text | | |
| apple_music_url | Apple Music URL | varchar(500) | | |
| spotify_url | Spotify URL | varchar(500) | | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新ユーザー | varchar | | |

### hn_song_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| song_id | 楽曲ID | bigint(20) unsigned | FK → hn_songs.id, ON DELETE CASCADE, NOT NULL | |
| member_id | メンバーID | int(11) | FK → hn_members.id, ON DELETE CASCADE, NOT NULL | UNIQUE(song_id, member_id) |
| is_center | センターフラグ | tinyint(1) | NOT NULL | 初期値: 0。ダブルセンター時は複数行が 1 |
| row_number | 列番号 | int(11) | | 1=フロント ～ 5=奥。NULL=フォーメーション外 |
| position | 列内位置 | int(11) | | 左端=1、右にカウントアップ |
| part_description | パート説明 | varchar(255) | | |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新ユーザー | varchar | | |

### hn_song_media_links
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| song_id | 楽曲ID | bigint(20) unsigned | FK → hn_songs.id, ON DELETE CASCADE, NOT NULL | INDEX あり |
| media_meta_id | メディアメタID | bigint(20) unsigned | FK → hn_media_metadata.id, ON DELETE CASCADE, UNIQUE | 1動画は1曲にのみ紐付く |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

### hn_release_member_images
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| release_id | リリースID | int(11) | NOT NULL | UNIQUE(release_id, member_id) |
| member_id | メンバーID | int(11) | NOT NULL | INDEX あり |
| image_url | アーティスト写真URL | varchar(255) | NOT NULL | |
| sort_order | 表示順 | tinyint(4) | | 初期値: 0 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | |
| update_user | 更新ユーザー | varchar | | |

## 2. ステータス・区分値定義 (マジックナンバー)

### hn_releases.release_type（ReleaseModel::RELEASE_TYPES）
- `single` = シングル
- `album` = アルバム
- `digital` = デジタルシングル
- `ep` = EP
- `best` = ベストアルバム

### hn_releases.group_name（ReleaseModel::GROUP_NAMES）
- `hinatazaka46` = 日向坂46
- `hiragana_keyaki` = けやき坂46

### hn_release_editions.edition（ReleaseEditionModel::EDITIONS）
- `type_a` = 初回限定 TYPE-A（メインジャケット）
- `type_b` = 初回限定 TYPE-B
- `type_c` = 初回限定 TYPE-C
- `type_d` = 初回限定 TYPE-D
- `normal` = 通常版

### hn_songs.track_type（SongModel::TRACK_TYPES_DISPLAY）
- `title` = 表題曲
- `read` = 読み曲
- `sub` = カップリング
- `type_a` = TYPE-A
- `type_b` = TYPE-B
- `type_c` = TYPE-C
- `type_d` = TYPE-D
- `normal` = 通常
- `other` = その他

### hn_songs.formation_type（SongModel::FORMATION_TYPES_DISPLAY）
- `all` = 全員
- `kibetsu` = 期別
- `senbatsu` = 選抜
- `solo` = ソロ
- `under` = アンダー
- `unit` = ユニット
- `other` = その他

### hn_song_members.row_number（SongMemberModel::ROW_NAMES）
- `1` = フロント（1列目）
- `2` = 2列目
- `3` = 3列目
- `4` = 4列目
- `5` = 5列目（奥）
- `NULL` = フォーメーション外（非参加メンバー扱い）

### hn_song_members.is_center
- `0` = 非センター
- `1` = センター（ダブルセンターの場合は複数行が 1）
