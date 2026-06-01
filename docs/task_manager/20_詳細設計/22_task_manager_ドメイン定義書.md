# タスク管理 ドメイン・データモデル定義書

## 1. テーブル定義詳細

### tm_tasks
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | タスクID | bigint | PK, Auto Inc | |
| user_id | ユーザーID | bigint | FK (sys_users.id), NOT NULL | BaseModelにより自動設定。isUserIsolated=true |
| category_id | カテゴリID | bigint | FK (tm_categories.id), NULL許容 | NULLの場合「未分類」として表示 |
| title | タスク名 | varchar | NOT NULL | バリデーション: required |
| description | 説明 | text | NULL許容 | 空文字列がデフォルト |
| status | ステータス | varchar | NOT NULL | 初期値: `'todo'`。取りうる値は「2. ステータス・区分値定義」参照 |
| priority | 優先度 | int | NOT NULL | 初期値: `2`。取りうる値: 1, 2, 3 |
| start_date | 開始日 | date | NULL許容 | 形式: YYYY-MM-DD |
| due_date | 期限日 | date | NULL許容 | 形式: YYYY-MM-DD |
| created_at | 作成日時 | datetime | NOT NULL | `date('Y-m-d H:i:s')` で設定 |
| updated_at | 更新日時 | datetime | NOT NULL | 作成時・更新時に `date('Y-m-d H:i:s')` で設定 |

### tm_categories
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | カテゴリID | bigint | PK, Auto Inc | |
| user_id | ユーザーID | bigint | FK (sys_users.id), NOT NULL | BaseModelにより自動設定。isUserIsolated=true |
| name | カテゴリ名 | varchar | NOT NULL | ユーザー内でユニーク（アプリロジックで制御） |
| color | カラーコード | varchar | NOT NULL | HEXカラー文字列。初期値: `'#4f46e5'` |
| created_at | 作成日時 | datetime | NOT NULL | `date('Y-m-d H:i:s')` で設定 |

## 2. ステータス・区分値定義 (マジックナンバー)

### `tm_tasks.status` ステータス値
| 値 | 表示ラベル | ドット色 | テキスト色 | 背景色 | 説明 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `todo` | 未着手 | `bg-blue-500` | `text-blue-600` | `bg-blue-50` | 初期状態。まだ着手していないタスク |
| `doing` | 進行中 | `bg-amber-500` | `text-amber-600` | `bg-amber-50` | 現在作業中のタスク |
| `pending` | 保留 | `bg-slate-400` | `text-slate-500` | `bg-slate-50` | 一時的に保留されたタスク |
| `done` | 完了 | `bg-green-500` | `text-green-600` | `bg-green-50` | 完了したタスク。リスト行は半透明+取り消し線 |

ステータスの定義元: JavaScript定数 `STATUS_MAP`（`private/apps/TaskManager/Views/index.php` 内）

### `tm_tasks.priority` 優先度値
| 値 | 表示 | 色 | 説明 |
| :--- | :--- | :--- | :--- |
| `3` | `!!!` (高) | `text-red-500` | 高優先度 |
| `2` | `!!` (中) | `text-orange-400` | 中優先度（デフォルト） |
| `1` | `!` (低) | `text-slate-300` | 低優先度 |

### 期限バッジの区分
| 条件 | バッジテキスト | CSSクラス | 行背景変更 |
| :--- | :--- | :--- | :--- |
| `days < 0`（超過） | `N日超過` | `badge-overdue` | `bg-red-50/60`, `border-red-200` |
| `days === 0`（本日期限） | `本日期限` | `badge-today` | `bg-orange-50/60`, `border-orange-200` |
| `0 < days <= 3`（間近） | `あとN日` | `badge-soon` | なし |
| `days > 3` | なし | なし | なし |

## 3. BaseModel 継承の共通仕様

### TaskModel (`private/apps/TaskManager/Model/TaskModel.php`)
- 継承元: `Core\BaseModel`
- `$table`: `'tm_tasks'`
- `$isUserIsolated`: `true`（デフォルト。全クエリに `user_id = :uid` を自動付加）
- `$fields`: `['id', 'user_id', 'category_id', 'title', 'description', 'status', 'priority', 'start_date', 'due_date', 'created_at', 'updated_at']`
- `$encryptedFields`: なし
- カスタムメソッド:
  - `getActiveTasks()`: 完了以外 かつ 開始日が過去1年以内のタスクをカテゴリ情報付きで取得（期限昇順→優先度降順）
  - `getAllTasks()`: 全タスクをカテゴリ情報付きで取得（優先度降順→期限昇順）
  - `getTaskWithCategory(int $id)`: 指定IDのタスクをカテゴリ情報付きで1件取得

### CategoryModel (`private/apps/TaskManager/Model/CategoryModel.php`)
- 継承元: `Core\BaseModel`
- `$table`: `'tm_categories'`
- `$isUserIsolated`: `true`（デフォルト）
- `$fields`: `['id', 'user_id', 'name', 'color', 'created_at']`
- `$encryptedFields`: なし
- カスタムメソッド:
  - `getOrCreate(string $name, string $color)`: 名前でカテゴリを検索し、存在すれば色を更新してIDを返す。存在しなければ新規作成してIDを返す
