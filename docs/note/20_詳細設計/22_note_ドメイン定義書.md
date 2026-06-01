# メモ（Note） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### nt_notes（メモ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | メモID | bigint(20) unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, INDEX | `BaseModel` によるユーザー隔離に必須 |
| title | タイトル | varchar(255) | NULL許可 | 空の場合、`createNote()` で本文先頭30文字を自動設定 |
| content | 本文 | text | NOT NULL | メモ本文（Markdown対応を想定） |
| bg_color | 背景色 | varchar(20) | | 初期値: `#ffffff` |
| is_pinned | ピン留め | tinyint(1) | | 初期値: `0`（0=通常, 1=ピン留め） |
| status | ステータス | varchar(20) | | 初期値: `active` |
| created_at | 作成日時 | datetime | | 初期値: `CURRENT_TIMESTAMP` |
| updated_at | 更新日時 | datetime | | 初期値: `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

### nt_list_entries（リストエントリ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | エントリID | bigint(20) unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int(11) | NOT NULL, INDEX | `BaseModel` によるユーザー隔離に必須 |
| list_kind | リスト種別 | varchar(32) | NOT NULL, INDEX | `LIST_KINDS` 定数で定義される6種別のいずれか |
| title | タイトル | varchar(255) | NULL許可 | 一覧表示用（空なら payload から要約して保存してもよい） |
| payload | データ本体 | json | NOT NULL | 種別ごとに構造が異なる（後述） |
| bg_color | 背景色 | varchar(20) | | 初期値: `#ffffff` |
| is_pinned | ピン留め | tinyint(1) | | 初期値: `0`（0=通常, 1=ピン留め） |
| status | ステータス | varchar(20) | | 初期値: `active` |
| created_at | 作成日時 | datetime | | 初期値: `CURRENT_TIMESTAMP` |
| updated_at | 更新日時 | datetime | | 初期値: `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

## 2. ステータス・区分値定義 (マジックナンバー)

### nt_notes.status
- `active` = アクティブ（通常表示対象）
- `archived` = アーカイブ済み（アーカイブタブで表示）
- `trash` = ゴミ箱（現時点ではUI上の導線なし、将来対応用）

### nt_list_entries.status
- `active` = アクティブ（通常表示対象）
- `archived` = アーカイブ済み（アーカイブタブで表示）

### nt_notes.is_pinned / nt_list_entries.is_pinned
- `0` = 通常
- `1` = ピン留め（一覧表示時にソート優先）

### nt_list_entries.list_kind
- `todo` = やること（チェックリスト）
- `question` = 疑問・仮説
- `first_time` = はじめて（初体験記録）
- `fun` = おもろかったこと
- `book` = 書籍メモ
- `generic_list` = 汎用リスト

## 3. payload JSON スキーマ（種別ごと）

### todo（やること）
```json
{
  "items": [
    { "id": "string|null", "text": "string", "done": 0 }
  ]
}
```
- `id`: 項目の一意識別子（null の場合は新規）
- `text`: 項目テキスト（空文字はバリデーションで除外）
- `done`: 完了フラグ（0=未完了, 1=完了）

### generic_list（汎用リスト）
```json
{
  "items": [
    { "id": "string|null", "text": "string" }
  ]
}
```
- `id`: 項目の一意識別子（null の場合は新規）
- `text`: 項目テキスト（空文字はバリデーションで除外）

### question（疑問・仮説）
```json
{
  "question": "string",
  "hypothesis": "string",
  "gap": "string",
  "answer": "string",
  "transfer": "string"
}
```
- `question`: 疑問文
- `hypothesis`: 仮説
- `gap`: 仮説とのギャップ
- `answer`: 回答（空の場合は "In Progress" 表示）
- `transfer`: 他への転用

### first_time（はじめて）
```json
{
  "occurred_at": "YYYY-MM-DD",
  "what": "string",
  "memo": "string"
}
```
- `occurred_at`: 発生日（ISO 8601 日付形式）
- `what`: 何をしたか
- `memo`: 補足メモ

### fun（おもろかったこと）
```json
{
  "hook": "string",
  "detail": "string"
}
```
- `hook`: ひとこと（要約）
- `detail`: 詳細

### book（書籍メモ）
```json
{
  "title": "string",
  "why_read": "string",
  "notes": "string"
}
```
- `title`: 書籍名
- `why_read`: なぜ読んだか
- `notes`: 感想・メモ

## 4. ソートルール
- 両テーブルとも `is_pinned DESC, created_at DESC` の順でソートされる。
- ピン留めされたメモ/エントリが常に先頭に表示され、その中では作成日時の新しい順に並ぶ。
