# イベント管理・セットリスト・ライブガイド (event) ドメイン・データモデル定義書

## 1. テーブル定義詳細

### hn_events
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| event_name | イベント名 | varchar(255) | NOT NULL | |
| event_date | 開催日 | date | NOT NULL | INDEX あり |
| category | カテゴリ | int | NOT NULL | 1-6, 99。区分値定義参照 |
| series_id | 系列ID | bigint unsigned | FK → hn_event_series.id, NULL許可 | NULL = 系列未設定。INDEX あり |
| mg_rounds | ミーグリ部数 | tinyint unsigned | NULL許可 | category=2,3 の場合のみ使用 |
| event_place | 会場名 | varchar(255) | | |
| event_place_address | Maps連携用住所 | varchar(500) | NULL許可 | 都道府県を含める |
| latitude | 緯度 | decimal(10,8) | NULL許可 | GeocodeService で取得 |
| longitude | 経度 | decimal(11,8) | NULL許可 | GeocodeService で取得 |
| place_id | Google Places ID | varchar(255) | NULL許可 | GeocodeService で取得 |
| event_info | 詳細メモ | text | | |
| event_url | 特設サイトURL | varchar(255) | | related_links の tokusetsu から自動設定 |
| event_hashtag | ハッシュタグ | varchar(100) | NULL許可 | # なしで保存 |
| collaboration_urls | コラボURL配列 | json | NULL許可 | related_links の collab/other から自動設定 |
| related_links | 関連リンクJSON | text | NULL許可 | `[{url, kind, manual_override}, ...]` |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |
| update_user | 更新ユーザー | varchar(50) | | |

- `isUserIsolated = false`（全ユーザー共通データ）

### hn_event_series
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| name | 系列表示名 | varchar(255) | NOT NULL, UNIQUE | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

- `isUserIsolated = false`
- マスタテーブル。hn_events.series_id から参照される
- 参照件数 > 0 の場合は削除不可（Controller で事前チェック）

### hn_event_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id |
| member_id | メンバーID | int | NOT NULL | hn_members.id |

- 複合主キーではなく (event_id, member_id) の組で管理
- イベント保存時に event_id 単位で全件 DELETE → INSERT（delete-insert パターン）
- POKA_MEMBER_ID は除外される（MemberGroupHelper::POKA_MEMBER_ID）

### hn_event_movies
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id |
| movie_id | メディアアセットID | bigint unsigned | NOT NULL, FK → com_media_assets.id ON DELETE CASCADE | |

- 関連リンク中の先頭 YouTube URL から `EventRelatedLinkService::syncYoutubeMovie()` で自動同期
- イベント保存時に event_id 単位で全件 DELETE → INSERT

### hn_event_applications
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id。INDEX あり |
| round_name | ラウンド名 | varchar(100) | NOT NULL | 初期値: ''。例: '第1次', '第2次' |
| application_start | 応募開始日時 | datetime | NULL許可 | |
| application_deadline | 応募締切日時 | datetime | NOT NULL | INDEX あり |
| announcement_date | 当選発表日時 | datetime | NULL許可 | |
| application_url | 応募ページURL | varchar(500) | NULL許可 | |
| memo | メモ | text | NULL許可 | |
| sort_order | 並び順 | tinyint | NOT NULL | 初期値: 0 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |

- `isUserIsolated = false`
- 1イベントに複数ラウンドを持つ。保存時は event_id 単位で全件 DELETE → INSERT
- ポータル情報管理画面（portal_info_admin.php）の応募締切タブからも参照される

### hn_user_events_status
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| user_id | ユーザーID | int | NOT NULL | sys_users.id |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id |
| status | ステータス | int | NOT NULL | 1-5。区分値定義参照 |
| seat_info | 座席情報 | varchar(255) | NULL許可 | |
| impression | 感想 | text | NULL許可 | |

- UNIQUE KEY: (user_id, event_id)
- upsert パターン（`ON DUPLICATE KEY UPDATE`）
- status=0 でリクエストされた場合は行を DELETE

### hn_event_attendance
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL | sys_users.id |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id |
| memo | メモ | text | NULL許可 | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

- UNIQUE KEY: (user_id, event_id)
- トグル操作: 存在すれば DELETE、なければ INSERT

### hn_setlists
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_events.id。INDEX あり |
| song_id | 楽曲ID | bigint unsigned | NULL許可 | hn_songs.id。song 行のみ必須（アプリ側で担保） |
| entry_type | 行種別 | varchar(20) | NOT NULL | 初期値: 'song'。区分値定義参照 |
| sort_order | 表示順 | int | NOT NULL | 初期値: 0 |
| encore | アンコール区分 | tinyint | NOT NULL | 初期値: 0。0=本編, 1=EN, 2=W EN |
| label | ラベル | varchar(255) | NULL許可 | MC/ブロック行の表示テキスト |
| block_kind | ブロック種別 | varchar(50) | NULL許可 | ブロック行の種別 |
| center_member_id | レガシーセンター | int | NULL許可 | 旧: 単一センター。hn_setlist_centers が正 |
| memo | メモ | varchar(255) | NULL許可 | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |
| update_user | 更新ユーザー | varchar(50) | | |

- `isUserIsolated = false`
- UNIQUE KEY: (event_id, sort_order)
- 保存時は event_id 単位で全件 DELETE → INSERT（先に hn_setlist_centers を掃除）

### hn_setlist_centers
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| setlist_id | セットリスト行ID | bigint unsigned | NOT NULL | hn_setlists.id。INDEX あり |
| member_id | メンバーID | int | NOT NULL | hn_members.id。INDEX あり |

- UNIQUE KEY: (setlist_id, member_id)
- セットリスト保存時に setlist_id 単位で DELETE → INSERT
- hn_setlists.center_member_id との互換: center_member_ids が空で center_member_id がある場合は単体として扱う

### hn_event_guide_songs
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| event_id | イベントID | int | NOT NULL | hn_events.id。INDEX あり |
| song_id | 楽曲ID | bigint unsigned | NOT NULL, FK → hn_songs.id ON DELETE CASCADE | INDEX あり |
| likelihood | 出る確度 | enum('certain','high','possible') | NOT NULL | 初期値: 'possible'。INDEX あり |
| sort_order | 表示順 | int | NOT NULL | 初期値: 0 |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |

- `isUserIsolated = false`
- UNIQUE KEY: (event_id, song_id) で重複防止
- 保存時は event_id 単位で全件 DELETE → INSERT

### hn_event_shadow_narrations
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| event_id | イベントID | bigint unsigned | PK | hn_events.id（イベントに1件のみ） |
| memo | メモ | varchar(255) | NULL許可 | |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |
| update_user | 更新ユーザー | varchar(50) | | |

- upsert パターン（`ON DUPLICATE KEY UPDATE`）

### hn_event_shadow_narration_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| event_id | イベントID | bigint unsigned | NOT NULL | hn_event_shadow_narrations.event_id |
| member_id | メンバーID | int | NOT NULL | hn_members.id。INDEX あり |

- 複合主キー: (event_id, member_id)
- 影ナレ保存時に event_id 単位で全件 DELETE → INSERT

## 2. ステータス・区分値定義 (マジックナンバー)

### hn_events.category
- `1` = ライブ（ViewアイコンFA: `fa-music`、カラー: `#3b82f6`）
- `2` = ミーグリ（FA: `fa-handshake`、カラー: `#10b981`）
- `3` = リアルミーグリ（FA: `fa-users`、カラー: `#f59e0b`）
- `4` = リリース（FA: `fa-compact-disc`、カラー: `#8b5cf6`）
- `5` = メディア（FA: `fa-tv`、カラー: `#ec4899`）
- `6` = スペイベ（FA: `fa-star`、カラー: `#f97316`）
- `99` = その他（FA: `fa-calendar`、カラー: `#64748b`）

### hn_user_events_status.status
- `1` = 参加予定
- `2` = 不参加
- `3` = 検討中
- `4` = 当選
- `5` = 落選
- `0` = クリア（API上の値。行自体を DELETE する）

### hn_setlists.entry_type
- `song` = 楽曲行（song_id 必須）
- `mc` = MC行（label にテキスト、song_id は NULL）
- `block` = ブロック行（label にテキスト、block_kind に種別、song_id は NULL）

### hn_setlists.encore
- `0` = 本編
- `1` = アンコール (ENCORE)
- `2` = ダブルアンコール (W ENCORE)

> 2026-08-09 変更: encore（セクション区分）は従来「曲行のみ有効」だったが、MC行・ブロック行にも設定可能とした。これによりアンコール直後のMC等を正しくアンコールセクションに含められる。`SetlistModel::saveForEvent()` は entry_type を問わず encore を保持し、表示側（`setlist_show.php`）は全行の encore を見て ENCORE / W ENCORE 見出しを挿入する。

### hn_setlists.block_kind（セットリスト編集画面の blockKindOptions）
- `announcement` = 告知
- `dance_session` = ダンスセッション
- `session_other` = セッション
- `other` = その他

### hn_event_guide_songs.likelihood（EventGuideSongModel::LIKELIHOOD_LABELS）
- `certain` = ほぼ確実に出る
- `high` = 高確率で出る
- `possible` = 出る可能性がある

### EventRelatedLinkService::KINDS（関連リンク種別）
- `youtube` = YouTube 動画リンク（MediaAssetModel::parseUrl で判定）
- `tokusetsu` = 特設サイト（hinatazaka46.com ドメインまたは環境変数の追加ドメイン）
- `collab` = コラボ企画（URL パスに /collab, /campaign, /cp/, /special を含む、または上記以外の外部サイト）
- `other` = その他

### イベント管理画面 最近の編集リスト filter 値
- `live` = カテゴリ 1（ライブ）
- `mg` = カテゴリ 2, 3（ミーグリ/リアルMG）
- `other` = カテゴリ 4, 5, 6, 99（リリース/メディア/スペイベ/その他）
