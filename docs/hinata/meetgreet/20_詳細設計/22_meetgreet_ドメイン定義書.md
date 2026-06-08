# ミーグリ（お話し会）& ネタ帳 ドメイン・データモデル定義書

## 1. テーブル定義詳細

### hn_meetgreet_slots
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(11) | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, INDEX(user_date) | BaseModel.isUserIsolated によるスコープ |
| event_id | イベントID | int(11) | NULL, INDEX | hn_events.id への任意紐付け。後付け追加カラム |
| event_date | ミーグリ日付 | date | NOT NULL, INDEX(user_date) | |
| slot_name | 部名 | varchar(50) | NOT NULL | 「第1部」「第2部」等 |
| start_time | 開始時刻 | time | NULL | テキストインポート時にパース。手動追加時は任意 |
| end_time | 終了時刻 | time | NULL | 同上 |
| member_id | メンバーID | int(11) | NULL, INDEX | hn_members.id。マッチ成功時にセット |
| member_name_raw | パース元メンバー名 | varchar(100) | NULL | メンバーマスタ未マッチ時の保持用 |
| ticket_count | 保有枚数 | int(11) | | 初期値: 0 |
| report | メモ | text | NULL | 暗号化対象（Core\Encryption）。スロット単位の自由メモ |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | ON UPDATE CURRENT_TIMESTAMP |

### hn_meetgreet_reports
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, INDEX | BaseModel.isUserIsolated によるスコープ |
| slot_id | スロットID | int(11) | NOT NULL, FK → hn_meetgreet_slots.id, ON DELETE CASCADE, INDEX(slot_order) | |
| ticket_used | 使用枚数 | int(11) | NOT NULL | 初期値: 1。最小値1（Controller側でバリデーション） |
| my_nickname | ニックネーム | text | NULL | 暗号化対象。マイグレーションで varchar(50) → TEXT に変更済み（alter_encrypted_columns_to_text.sql） |
| sort_order | 表示順 | int(11) | NOT NULL, INDEX(slot_order) | 初期値: 0。作成時に MAX(sort_order)+1 を自動採番 |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP |

### hn_meetgreet_report_messages
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, INDEX | BaseModel.isUserIsolated によるスコープ |
| report_id | レポID | bigint(20) unsigned | NOT NULL, FK → hn_meetgreet_reports.id, ON DELETE CASCADE, INDEX(report_order) | |
| sender_type | 送信者タイプ | enum('member','self','narration','self_thought') | NOT NULL | 初期値: 'self'。初期スキーマは3値、後にself_thoughtを追加（alter_hn_meetgreet_messages_add_sender_types.sql） |
| content | メッセージ本文 | text | NOT NULL | 暗号化対象（Core\Encryption）。bulkSave時にEncryption::encrypt()で暗号化して挿入 |
| sort_order | 表示順 | int(11) | NOT NULL, INDEX(report_order) | 初期値: 0。bulkSave時にクライアント指定のインデックス順 |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP |

### hn_meetgreet_report_avatars
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint(20) unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, UNIQUE(user_id, member_id) | |
| member_id | メンバーID | int(11) | NOT NULL, FK → hn_members.id, ON DELETE CASCADE, INDEX | |
| image_path | 画像パス | varchar(500) | NOT NULL | `uploads/mg_avatar/{user_id}/{member_id}/avatar_{timestamp}.{ext}` 形式の相対パス |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NULL | ON UPDATE CURRENT_TIMESTAMP。UPSERT時に自動更新 |

### hn_neta
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL | BaseModel.isUserIsolated によるスコープ |
| member_id | メンバーID | int | NOT NULL, FK → hn_members.id | |
| content | ネタ内容 | text | NOT NULL | 暗号化対象（Core\Encryption） |
| memo | メモ | text | NULL | 暗号化対象（Core\Encryption） |
| neta_type | ネタ種類 | varchar(20) | NULL | 後付け追加カラム（add_neta_types_favorites_tags.sql）。NULL=未登録 |
| is_favorite | お気に入り | tinyint(1) | NOT NULL | 初期値: 0。後付け追加カラム |
| status | ステータス | varchar | NOT NULL | 初期値: 'stock' |
| created_at | 作成日時 | datetime | | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | |

### hn_tags
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL, INDEX, UNIQUE(user_id, name) | ユーザー別タグ名前空間 |
| name | タグ名 | varchar(64) | NOT NULL, UNIQUE(user_id, name) | |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP。UPSERT時にも更新 |

### hn_neta_tags
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| neta_id | ネタID | bigint | PK, FK → hn_neta.id, ON DELETE CASCADE | |
| tag_id | タグID | int | PK, FK → hn_tags.id, ON DELETE CASCADE, INDEX | |
| created_at | 作成日時 | datetime | NOT NULL | 初期値: CURRENT_TIMESTAMP |

## 2. ステータス・区分値定義 (マジックナンバー)

### hn_meetgreet_report_messages.sender_type
- `member` = メンバー発言（相手の発言。メンバーアバター付きで左寄せ表示）
- `self` = 自分の発言（テーマカラー背景で右寄せ表示）
- `narration` = ナレーション（中央寄せ、グレー背景の補足テキスト）
- `self_thought` = 内心（自分の心の声。ダッシュ破線ボーダー + イタリックで右寄せ表示）

### hn_neta.neta_type
- `NULL` = 未登録（フォーム上は「未登録」トグル、フィルタでは種類フィルタ対象外）
- `question` = 質問（青系バッジ `bg-blue-100 text-blue-700`）
- `impression` = 感想（緑系バッジ `bg-emerald-100 text-emerald-700`）
- `joke` = ネタ（琥珀系バッジ `bg-amber-100 text-amber-700`）

### hn_neta.status
- `stock` = 未使用（デフォルト。通常表示）
- `done` = 使用済み（カード半透明 `opacity: 0.45` + 「使用済」バッジ表示。「未使用」フィルタで非表示）
- `delete` = 論理削除（`getGroupedNeta()` のSQL条件 `status != 'delete'` で除外）

### hn_neta.is_favorite
- `0` = 通常
- `1` = お気に入り（星アイコン黄色塗り。「お気に入り」フィルタ対象）

### hn_events.category（参照のみ: イベント紐付け時の種類判定）
- `2` = オンラインミーグリ（MG）
- `3` = リアルミーグリ

### MeetGreetReportAvatarModel 定数
- `MAX_DIMENSION` = `400`（リサイズ時の最大辺ピクセル数）
- `ALLOWED_TYPES` = `['image/jpeg', 'image/png', 'image/webp']`
- `UPLOAD_BASE` = `'uploads/mg_avatar'`（ドキュメントルートからの相対パス）

## 3. 暗号化対象カラム一覧

| テーブル | カラム | 暗号化方式 | 型変更マイグレーション |
| :--- | :--- | :--- | :--- |
| hn_meetgreet_slots | report | Core\Encryption（BaseModel.encryptedFields） | なし（初期からTEXT型） |
| hn_meetgreet_reports | my_nickname | Core\Encryption（BaseModel.encryptedFields） | alter_encrypted_columns_to_text.sql（varchar→TEXT） |
| hn_meetgreet_report_messages | content | Core\Encryption（bulkSave内で直接Encryption::encrypt()呼び出し） | なし（初期からTEXT型） |
| hn_neta | content | Core\Encryption（BaseModel.encryptedFields） | なし（初期からTEXT型） |
| hn_neta | memo | Core\Encryption（BaseModel.encryptedFields） | なし（初期からTEXT型） |

暗号化後のデータは `base64(iv + tag + ciphertext)` 形式となり、元の文字列長より大幅に長くなる。このため `my_nickname` は varchar(50) から TEXT へ変更された。

## 4. アバター画像解決の優先順位

`MeetGreetReportAvatarModel::resolveAvatar()` は以下の優先順位でメンバーアバター画像を解決する:

1. レポ用アバター（`hn_meetgreet_report_avatars.image_path`）
2. ユーザー別メンバープロフィール画像（`hn_user_member_profiles.image_path`）
3. メンバーマスタ画像（`hn_members.image_url`）
4. デフォルト画像（`/assets/img/members/member_{id}.jpg`）

## 5. カスケード削除チェーン

| 親テーブル | 子テーブル | 動作 | 影響範囲 |
| :--- | :--- | :--- | :--- |
| hn_meetgreet_slots | hn_meetgreet_reports | ON DELETE CASCADE | スロット削除でレポも全削除 |
| hn_meetgreet_reports | hn_meetgreet_report_messages | ON DELETE CASCADE | レポ削除でメッセージも全削除 |
| hn_members | hn_meetgreet_report_avatars | ON DELETE CASCADE | メンバー削除でアバターも削除 |
| hn_neta | hn_neta_tags | ON DELETE CASCADE | ネタ削除でタグ紐付けも削除 |
| hn_tags | hn_neta_tags | ON DELETE CASCADE | タグ削除でネタ紐付けも削除 |

## 6. マイグレーション履歴

| ファイル名 | 内容 |
| :--- | :--- |
| create_hn_meetgreet_slots.sql | hn_meetgreet_slots テーブル作成 + sys_apps/sys_role_apps 登録 |
| add_event_id_to_meetgreet_slots.sql | hn_meetgreet_slots に event_id カラム追加 |
| create_hn_meetgreet_reports.sql | hn_meetgreet_reports + hn_meetgreet_report_messages テーブル作成 |
| create_hn_meetgreet_report_avatars.sql | hn_meetgreet_report_avatars テーブル作成 |
| alter_hn_meetgreet_messages_add_sender_types.sql | sender_type に self_thought を追加 |
| alter_encrypted_columns_to_text.sql | my_nickname を varchar → TEXT に変更 |
| add_neta_types_favorites_tags.sql | hn_neta に neta_type/is_favorite 追加、hn_tags/hn_neta_tags 作成 |
| add_meetgreet_report_to_role_apps.sql | ロール権限追加 |
