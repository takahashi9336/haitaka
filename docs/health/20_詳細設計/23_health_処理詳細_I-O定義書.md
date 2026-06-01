# Health（ヘルスケア/健康管理） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

### 1-1. 画面表示

| HTTP | 公開パス | Controller メソッド | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `/health/index.php` | `HealthController::portal` | なし | HTML |
| GET | `/health/kitchen_stock.php` | `HealthController::kitchenStock` | なし | HTML |
| GET | `/health/training_menu.php` | `HealthController::trainingMenu` | なし | HTML |
| GET | `/health/training_history.php` | `HealthController::trainingHistory` | なし | HTML |

### 1-2. 食材ストック API

| HTTP | 公開パス | Model / 処理 | 認証 |
| :--- | :--- | :--- | :--- |
| POST | `/health/api/list.php` | `KitchenStockModel::getAllItems` | 必須 |
| POST | `/health/api/create.php` | `KitchenStockModel::create` | 必須 |
| POST | `/health/api/update.php` | `KitchenStockModel::update` | 必須 |
| POST | `/health/api/delete.php` | `KitchenStockModel::delete` | 必須 |

### 1-3. トレーニングメニュー API

| HTTP | 公開パス | Model / 処理 | 認証 |
| :--- | :--- | :--- | :--- |
| POST | `/health/api/training_list.php` | `TrainingMenuModel::getAllItems` | 必須 |
| POST | `/health/api/training_create.php` | `TrainingMenuModel::create` | 必須 |
| POST | `/health/api/training_update.php` | `TrainingMenuModel::update` | 必須 |
| POST | `/health/api/training_delete.php` | `TrainingMenuModel::delete` | 必須 |

### 1-4. トレーニング実施履歴 API

| HTTP | 公開パス | Model / 処理 | 認証 |
| :--- | :--- | :--- | :--- |
| POST | `/health/api/training_log_list.php` | `TrainingLogModel::getLogs` | 必須 |
| POST | `/health/api/training_log_create.php` | `TrainingLogModel::createFromMenuItem` | 必須 |
| POST | `/health/api/training_log_create_hiit.php` | `TrainingLogModel::createHiitSession` | 必須 |
| POST | `/health/api/training_log_delete.php` | `TrainingLogModel::delete` | 必須 |

---

## 2. 処理フロー詳細

### 2-1. 食材ストック一覧取得 (`api/list.php`)

1. **認証チェック**: `Auth::check()` で未認証なら 401 を返す
2. **データ取得**: `KitchenStockModel::getAllItems()` でログインユーザーの全食材を取得（`purchased_date DESC` 順）
3. **レスポンス**: `{"status":"success","items":[...]}`

### 2-2. 食材ストック登録 (`api/create.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: `php://input`（JSON）または `$_POST` から取得
3. **バリデーション**:
   - `name`: 必須（空文字不可）→ 422
   - `item_group`: `food` / `seasoning` / `other` のいずれか（デフォルト `food`）→ 422
4. **データ組立**:
   - `qty`: 空なら NULL
   - `purchased_date`: 空なら NULL
   - `is_frozen`: truthy なら 1、それ以外 0
5. **DB登録**: `KitchenStockModel::create($data)`
6. **レスポンス**: `{"status":"success","id":123}`

### 2-3. 食材ストック更新 (`api/update.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**:
   - `id`: 必須、正の整数 → 422
   - `name`: 指定時は空文字不可 → 422
   - `item_group`: 指定時は許可値チェック → 422
4. **パッチ構築**: 入力に含まれるフィールドのみ `$patch` 配列に格納
5. **DB更新**: `KitchenStockModel::update($id, $patch)`（パッチが空なら更新せず成功を返す）
6. **レスポンス**: `{"status":"success"}`

### 2-4. 食材ストック削除 (`api/delete.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**: `id` 必須、正の整数 → 422
4. **DB削除**: `KitchenStockModel::delete($id)`（BaseModel で `user_id` スコープ適用）
5. **レスポンス**: `{"status":"success"}`

### 2-5. トレーニングメニュー一覧取得 (`api/training_list.php`)

1. **認証チェック**: 未認証なら 401
2. **データ取得**: `TrainingMenuModel::getAllItems()` で全メニュー取得（`id ASC` 順）
3. **レスポンス**: `{"status":"success","items":[...]}`

### 2-6. トレーニングメニュー登録 (`api/training_create.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**:
   - `name`: 必須 → 422
   - `reps`: 1以上の整数（デフォルト1）→ 422
   - `duration_sec`: `TrainingDuration::parseFromInput()` で秒に変換（`duration_min` + `duration_sec_part` 形式にも対応）→ 422
4. **DB登録**: `TrainingMenuModel::create($data)`
5. **レスポンス**: `{"status":"success","id":123}`

### 2-7. トレーニングメニュー更新 (`api/training_update.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**:
   - `id`: 必須、正の整数 → 422
   - `name`: 指定時は空文字不可 → 422
   - `reps`: 指定時は1以上 → 422
   - `duration_sec` / `duration_min` / `duration_sec_part`: いずれか指定時に `TrainingDuration::parseFromInput()` で変換 → 422
4. **パッチ構築**: 入力に含まれるフィールドのみ格納
5. **DB更新**: `TrainingMenuModel::update($id, $patch)`
6. **レスポンス**: `{"status":"success"}`

### 2-8. トレーニングメニュー削除 (`api/training_delete.php`)

1. **認証チェック**: 未認証なら 401
2. **バリデーション**: `id` 必須、正の整数 → 422
3. **DB削除**: `TrainingMenuModel::delete($id)`
4. **レスポンス**: `{"status":"success"}`
5. **副作用**: `hl_training_logs.menu_item_id` が SET NULL になる（FK制約）

### 2-9. トレーニング実施履歴一覧取得 (`api/training_log_list.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST / GET
3. **バリデーション**:
   - `from`: 指定時は `Y-m-d` 形式チェック → 422（未指定時は3ヶ月前を自動設定）
   - `to`: 指定時は `Y-m-d` 形式チェック → 422
4. **データ取得**: `TrainingLogModel::getLogs($from, $to)`（最大500件、`performed_at DESC` 順）
5. **レスポンス**: `{"status":"success","items":[...]}`

### 2-10. トレーニング実施履歴 個別登録 (`api/training_log_create.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**:
   - `menu_item_id`: 必須、正の整数 → 422
   - `performed_at`: `Y-m-d` 形式（デフォルト今日）→ 422
   - `reps`: 指定時は1以上 → 422
   - `duration_sec` / `duration_min` / `duration_sec_part`: 指定時に `TrainingDuration::parseFromInput()` → 422
4. **ビジネスロジック**: `TrainingLogModel::createFromMenuItem()`
   - メニューマスタから `name`, `reps`, `duration_sec` をスナップショット取得
   - `repsOverride` / `durationOverride` があれば上書き
   - `duration_sec` は 0〜86400 の範囲チェック
5. **DB登録**: `log_kind = 'exercise'` で INSERT
6. **レスポンス**: `{"status":"success","id":123}`

### 2-11. HIIT一括記録 (`api/training_log_create_hiit.php`)

1. **認証チェック**: 未認証なら 401
2. **入力パース**: JSON / POST
3. **バリデーション**:
   - `performed_at`: `Y-m-d` 形式（デフォルト今日）→ 422
4. **ビジネスロジック**: `TrainingLogModel::createHiitSession($performedAt)`
   - `TrainingMenuModel::getAllItems()` で全メニューを取得（0件なら 422 エラー）
   - 全種目の `name`, `reps`, `duration_sec` を配列化して `menu_snapshot`（JSON）に格納
   - `reps` = 種目数、`duration_sec` = 全種目の合計秒数
   - `menu_name` = `'HIIT'` 固定
5. **DB登録**: `log_kind = 'hiit'` で INSERT
6. **レスポンス**: `{"status":"success","id":123,"duration_sec":480,"exercise_count":8}`

### 2-12. トレーニング実施履歴削除 (`api/training_log_delete.php`)

1. **認証チェック**: 未認証なら 401
2. **バリデーション**: `id` 必須、正の整数 → 422
3. **DB削除**: `TrainingLogModel::delete($id)`
4. **レスポンス**: `{"status":"success"}`

---

## 3. 共通エラーレスポンス

全APIで統一されたエラーレスポンス形式:

| HTTP Status | 条件 | レスポンス |
| :--- | :--- | :--- |
| 401 | 未認証 | `{"status":"error","message":"Unauthorized"}` |
| 422 | バリデーションエラー | `{"status":"error","message":"（エラー内容）"}` |
| 500 | サーバーエラー | `{"status":"error","message":"System Error"}` |

## 4. 認証・認可

- 全画面: `HealthController` のコンストラクタで `Auth` インスタンスを生成し、各メソッド冒頭で `$this->auth->requireLogin()` を呼び出す（未認証時はログイン画面にリダイレクト）
- 全API: エントリ PHP 冒頭で `Auth::check()` を呼び出し、`false` なら 401 JSON を返却して `exit`
- データ隔離: 全 Model で `isUserIsolated = true` により、CRUD 操作が自動的に `user_id` スコープに限定される
