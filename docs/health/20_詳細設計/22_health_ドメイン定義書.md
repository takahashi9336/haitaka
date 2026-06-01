# Health（ヘルスケア/健康管理） ドメイン・データモデル定義書

## 1. テーブル定義詳細

### 1-1. hl_kitchen_stock_items（食材ストック）

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL, FK | `sys_users(id)` ON DELETE CASCADE |
| name | 食材名 | varchar(255) | NOT NULL | |
| item_group | グループ | varchar(32) | NOT NULL, DEFAULT `'food'` | `food` / `seasoning` / `other` |
| qty | 数量 | varchar(100) | NULL | 自由入力（例: "2枚", "500ml"） |
| purchased_date | 購入日 | date | NULL | |
| is_frozen | 冷凍フラグ | tinyint(1) | NOT NULL, DEFAULT `0` | 0=冷蔵, 1=冷凍 |
| created_at | 作成日時 | datetime | DEFAULT `CURRENT_TIMESTAMP` | |
| updated_at | 更新日時 | datetime | DEFAULT `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | |

**Model**: `App\Health\Model\KitchenStockModel` (`isUserIsolated = true`)

**ソート順**: `(purchased_date IS NULL) ASC, purchased_date DESC, created_at DESC, id DESC`（購入日NULLは末尾、購入日の新しい順）

### 1-2. hl_training_menu_items（トレーニングメニュー）

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL, FK | `sys_users(id)` ON DELETE CASCADE |
| name | メニュー名 | varchar(255) | NOT NULL | 例: "スクワット", "腕立て伏せ" |
| reps | 回数 | int unsigned | NOT NULL, DEFAULT `1` | 1以上 |
| duration_sec | 時間（秒） | int unsigned | NOT NULL, DEFAULT `60` | 1セットあたりの時間。0〜86400 |
| created_at | 作成日時 | datetime | DEFAULT `CURRENT_TIMESTAMP` | |
| updated_at | 更新日時 | datetime | DEFAULT `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | |

**Model**: `App\Health\Model\TrainingMenuModel` (`isUserIsolated = true`)

**ソート順**: `id ASC`（登録順）

### 1-3. hl_training_logs（トレーニング実施履歴）

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | bigint unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | NOT NULL, FK | `sys_users(id)` ON DELETE CASCADE |
| log_kind | 記録種別 | varchar(20) | NOT NULL, DEFAULT `'exercise'` | `exercise` / `hiit` |
| menu_item_id | メニューID | bigint unsigned | NULL, FK | `hl_training_menu_items(id)` ON DELETE SET NULL |
| menu_name | メニュー名 | varchar(255) | NOT NULL | 記録時点のスナップショット |
| reps | 回数/種目数 | int unsigned | NOT NULL | exercise: 回数, hiit: 種目数 |
| duration_sec | 合計時間（秒） | int unsigned | NOT NULL | exercise: 1種目の時間, hiit: 全種目合計時間 |
| menu_snapshot | メニュースナップショット | json | NULL | HIIT時のみ。各種目の name/reps/duration_sec 配列 |
| performed_at | 実施日 | date | NOT NULL | |
| created_at | 作成日時 | datetime | DEFAULT `CURRENT_TIMESTAMP` | |
| updated_at | 更新日時 | datetime | DEFAULT `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | |

**Model**: `App\Health\Model\TrainingLogModel` (`isUserIsolated = true`)

**ソート順**: `performed_at DESC, id DESC`（新しい順）

**CRUD**: CRD（更新なし。記録は不変として扱う）

## 2. ステータス・区分値定義

### 2-1. `hl_kitchen_stock_items.item_group`（食材グループ）

| 値 | 意味 | UI表示 | バッジ色 |
| :--- | :--- | :--- | :--- |
| `food` | 食材 | 食材 | `bg-emerald-50 text-emerald-900 border-emerald-100` |
| `seasoning` | 調味料 | 調味料 | `bg-amber-50 text-amber-900 border-amber-100` |
| `other` | その他 | その他 | `bg-slate-100 text-slate-800 border-slate-200` |

バリデーション: API側の `$allowedGroups` 配列で許可値を制限。未指定時はデフォルト `food`。

### 2-2. `hl_kitchen_stock_items.is_frozen`（冷凍フラグ）

| 値 | 意味 |
| :--- | :--- |
| `0` | 冷蔵（通常） |
| `1` | 冷凍 |

### 2-3. `hl_training_logs.log_kind`（記録種別）

| 値 | 意味 | 定数 | 備考 |
| :--- | :--- | :--- | :--- |
| `exercise` | 個別種目の実施記録 | `TrainingLogModel::LOG_KIND_EXERCISE` | `menu_item_id` にメニューIDを格納 |
| `hiit` | HIITセッション（全種目一括） | `TrainingLogModel::LOG_KIND_HIIT` | `menu_snapshot` に種目一覧JSONを格納、`menu_name` は `'HIIT'` 固定 |

### 2-4. `hl_training_logs.menu_snapshot`（JSON構造）

HIIT記録時に格納されるJSON配列の各要素:

```json
{
  "name": "スクワット",
  "reps": 10,
  "duration_sec": 60
}
```

## 3. ユーティリティクラス

### 3-1. TrainingDuration（`App\Health\Model\TrainingDuration`）

トレーニング時間（秒）のパース・バリデーション・フォーマット用ユーティリティ。

| 定数 | 値 | 意味 |
| :--- | :--- | :--- |
| `MAX_SEC` | 86400 | 最大秒数（24時間） |
| `DEFAULT_SEC` | 60 | デフォルト秒数（1分） |

| メソッド | 引数 | 戻り値 | 概要 |
| :--- | :--- | :--- | :--- |
| `parseFromInput(array, bool, ?int)` | `$input`, `$required`, `$default` | `{ok, sec?, message?}` | `duration_sec` または `duration_min` + `duration_sec_part` を秒に変換。バリデーション付き |
| `formatLabel(int)` | `$sec` | `string` | 秒数を「X分Y秒」形式の日本語ラベルに変換 |
