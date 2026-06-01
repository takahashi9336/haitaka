# Sense Lab（センスラボ） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### sl_sense_entries（本番スクラップ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子（外部キー相当） |
| `title` | タイトル | `VARCHAR(255)` | NOT NULL | 直感的なひとこと（例: 余白のある食卓） |
| `image_path` | 画像パス | `VARCHAR(500)` | NULL | `/uploads/sense_lab/yyyymmdd_hhmmss_xxxx.ext` 形式のURLパス |
| `category` | カテゴリ | `VARCHAR(50)` | NOT NULL | `food` / `design` / `daily` / `other` のいずれか |
| `reason_1` | 理由1 | `TEXT` | NULL | 「どこが一番いいと感じたか」 |
| `reason_2` | 理由2 | `TEXT` | NULL | 「色・構図・余白などテクニカルな視点」 |
| `reason_3` | 理由3 | `TEXT` | NULL | 「その場の空気・文脈としてなぜ響いたか」 |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

### sl_sense_quick_entries（クイックスクラップ）
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| `id` | ID | `BIGINT UNSIGNED` | PK, Auto Inc | |
| `user_id` | ユーザーID | `INT` | NOT NULL | ユーザー識別子 |
| `app_key` | 起点アプリキー | `VARCHAR(50)` | NULL | FABを押した画面のアプリキー（例: `task_manager`, `hinata`） |
| `page_title` | ページタイトル | `VARCHAR(255)` | NULL | 起点画面の `document.title` から「MyPlatform」を除去した値 |
| `source_url` | 起点URL | `VARCHAR(500)` | NULL | 起点画面の `pathname + search`（例: `/task_manager/?tab=today`） |
| `category_hint` | カテゴリヒント | `VARCHAR(50)` | NULL | `food` / `design` / `daily` / `other` または未設定 |
| `note` | メモ | `TEXT` | NOT NULL | 1〜3行程度のラフな「なぜ良いと思ったか」メモ |
| `image_path` | 画像パス | `VARCHAR(500)` | NULL | `/uploads/sense_lab/yyyymmdd_hhmmss_xxxx.ext` 形式のURLパス |
| `reason_1` | 理由1 | `TEXT` | NULL | 後から追記する「なぜ良いと思ったか 1つ目」（任意） |
| `reason_2` | 理由2 | `TEXT` | NULL | 後から追記する「なぜ良いと思ったか 2つ目」（任意） |
| `reason_3` | 理由3 | `TEXT` | NULL | 後から追記する「なぜ良いと思ったか 3つ目」（任意） |
| `linked_entry_id` | 紐付き本番ID | `BIGINT UNSIGNED` | NULL, FK | `sl_sense_entries.id` への外部キー（本番スクラップに昇格時にセット、現時点では未使用） |
| `created_at` | 作成日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | 更新日時 | `DATETIME` | NOT NULL | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |

## 2. ステータス・区分値定義（マジックナンバー）

### `sl_sense_entries.category`（カテゴリ）
| 値 | 表示名 | 説明 |
| :--- | :--- | :--- |
| `food` | 食事 | 食事・料理・食器・テーブルセッティングなど |
| `design` | デザイン | グラフィック・Webデザイン・UI・プロダクトデザインなど |
| `daily` | 日常 | 風景・空間・ファッション・日常のワンシーンなど |
| `other` | その他 | 上記に該当しないもの（デフォルト値） |

### `sl_sense_quick_entries.category_hint`（カテゴリヒント）
- 本番スクラップと同じ4値（`food` / `design` / `daily` / `other`）に加え、`NULL`（未設定）を許容
- クイックスクラップ段階では仮分類であり、後から変更可能

### 画像パス
- 形式: `/uploads/sense_lab/{yyyymmdd_HHiiss}_{8桁hex}.{jpg|png|gif}`
- 物理保存先: `www/uploads/sense_lab/`
- 許可MIME: `image/jpeg`, `image/png`, `image/gif`
- 最大サイズ: 2MB（サーバー側バリデーション）、超過時はクライアント側でJPEG圧縮

## 3. データスコープ
- 全クエリで `user_id` によるユーザースコープを適用（`WHERE user_id = :user_id`）
- 更新・削除は `id` と `user_id` の複合条件で実行し、他ユーザーのデータへのアクセスを防止
