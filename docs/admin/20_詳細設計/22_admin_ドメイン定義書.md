# 管理画面 (admin) ドメイン・データモデル定義書

## 1. テーブル定義詳細

### sys_users
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| id_name | ログインID | varchar | UNIQUE, NOT NULL | |
| password | パスワード | varchar | NOT NULL | bcrypt ハッシュ |
| role | ロール | varchar | NOT NULL | sys_roles.role_key に対応 |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

- **管理画面での操作**: 参照 (R) のみ。追加・ロール変更・パスワードリセットは `/users_settings/api/` の外部 API 経由
- **isUserIsolated**: false (BaseModel 継承)

### sys_apps
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| app_key | アプリ識別子 | varchar | UNIQUE (`uk_app_key`), NOT NULL | 英数スネーク |
| name | 表示名 | varchar | NOT NULL | |
| parent_id | 親アプリID | int | FK (self), NULL 可 | NULL = トップレベル |
| route_prefix | ルートプレフィックス | varchar | NOT NULL | 例: `/admin/` |
| path | ファイル名 | varchar | NULL 可 | 子画面のみ。例: `users.php` |
| icon_class | アイコンクラス | varchar | NULL 可 | Font Awesome クラス名 |
| theme_primary | テーマ主色 | varchar | NULL 可 | Tailwind カラー名 or HEX |
| theme_light | テーマ薄色 | varchar | NULL 可 | Tailwind カラー名 or HEX |
| default_route | デフォルトルート | varchar | NULL 可 | トップレベルのリンク先 |
| description | 説明 | varchar | NULL 可 | |
| is_system | システム固定 | tinyint | NOT NULL | 0/1。1 = 削除不可 |
| sort_order | 表示順 | int | NOT NULL | DEFAULT 0 |
| is_visible | 表示フラグ | tinyint | NOT NULL | 0/1 |
| admin_only | 管理者専用 | tinyint | NOT NULL | 0/1 |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

- **管理画面での操作**: CRUD
- **isUserIsolated**: false
- **Model**: `Core\AppModel` (`private/lib/AppModel.php`)
- **ビジネスルール**: 作成・更新・削除の後に `SessionManager::invalidateAllSessions()` で全ユーザーセッション破棄。子画面作成時は `RoleAppModel::grantToRolesWithParent()` で restricted ロールに自動許可

### sys_roles
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| role_key | ロール識別子 | varchar | UNIQUE, NOT NULL | 例: `admin`, `user`, `hinata` |
| name | ロール名 | varchar | NOT NULL | |
| description | 説明 | varchar | NULL 可 | |
| default_route | デフォルトルート | varchar | | DEFAULT `/index.php` |
| logo_text | ロゴテキスト | varchar | NULL 可 | サイドバーロゴの上書き |
| sidebar_mode | サイドバーモード | varchar | | `full` or `restricted` |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

- **管理画面での操作**: CRUD
- **Model**: `Core\RoleModel` (`private/lib/RoleModel.php`)
- **ビジネスルール**: 作成・更新・削除の後にセッション全破棄。`sidebar_mode = 'restricted'` 時のみ `sys_role_apps` でアプリ割り当てを設定

### sys_role_apps
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| role_id | ロールID | int | FK → sys_roles.id, NOT NULL | |
| app_id | アプリID | int | FK → sys_apps.id, NOT NULL | |
| sort_order | 表示順 | int | | DEFAULT 0 |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

- **管理画面での操作**: CUD (ロール管理に連動)
- **Model**: `Core\RoleAppModel` (`private/lib/RoleAppModel.php`)
- **ビジネスルール**: `setForRole()` で既存を全 DELETE してから再 INSERT (トランザクション内)。`grantToRolesWithParent()` で子画面作成時に restricted ロールへ自動追加

### sys_guides
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| guide_key | ガイド識別子 | varchar(100) | UNIQUE, NOT NULL | 画面識別子。例: `meetgreet_import` |
| title | タイトル | varchar(200) | NOT NULL | |
| blocks | ブロック配列 | json | NOT NULL | `[{type:"text"|"image", content|src, alt?}]` |
| app_key | 紐づけアプリ | varchar(50) | NULL 可 | sys_apps.app_key に対応 (任意) |
| show_on_first_visit | 初回表示 | tinyint(1) | NOT NULL | DEFAULT 0 |
| sort_order | 表示順 | int | NOT NULL | DEFAULT 0 |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | DEFAULT CURRENT_TIMESTAMP ON UPDATE |

- **管理画面での操作**: CRUD
- **isUserIsolated**: false
- **Model**: `Core\GuideModel` (`private/lib/GuideModel.php`)
- **ビジネスルール**: `blocks` カラムは PHP 配列 ⇔ JSON 文字列の変換を Model 内で行う

### sys_improvement_items
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int | PK, Auto Inc | |
| screen_name | 画面名 | varchar(200) | NOT NULL, INDEX | 登録元の画面名 |
| content | 改善事項内容 | text | NOT NULL | |
| status | ステータス | enum | NOT NULL | `pending` / `done` / `cancelled`。DEFAULT `pending` |
| priority | 優先度 | tinyint | NULL 可 | 1-5 |
| source_url | 登録時URL | varchar(500) | NULL 可 | FAB 登録時のパス |
| created_by | 登録者 | int | NOT NULL | sys_users.id |
| created_at | 作成日時 | datetime | INDEX | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | datetime | | DEFAULT CURRENT_TIMESTAMP ON UPDATE |
| resolved_at | 対応日 | datetime | NULL 可 | ステータス `done` 時に自動設定 |
| memo | 管理者メモ | text | NULL 可 | |

- **管理画面での操作**: CRUD (対応管理画面 + FAB)
- **isUserIsolated**: false
- **Model**: `App\Admin\Model\ImprovementItemModel` (`private/apps/Admin/Model/ImprovementItemModel.php`)

### sys_user_friends
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| user_id | ユーザーID | int | FK → sys_users.id (CASCADE), NOT NULL | 常に小さい方の ID |
| friend_user_id | 友達ユーザーID | int | FK → sys_users.id (CASCADE), NOT NULL | 常に大きい方の ID |
| created_by | 登録管理者 | int | NOT NULL | 登録した管理者の user_id |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

- **UNIQUE 制約**: `uq_user_friend (user_id, friend_user_id)`
- **管理画面での操作**: CRD (作成・参照・削除のみ。更新なし)
- **ビジネスルール**: 登録時に `user_id < friend_user_id` となるよう正規化。同一ユーザーのペアは拒否。重複チェックは `friendPairExists()` で事前検証

### sys_user_groups
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| name | グループ名 | varchar(100) | NOT NULL | |
| created_by | 作成者 | int | NOT NULL, INDEX | |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

- **管理画面での操作**: CRUD
- **ビジネスルール**: 削除時は CASCADE で `sys_user_group_members` も連動削除

### sys_user_group_members
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int unsigned | PK, Auto Inc | |
| group_id | グループID | int unsigned | FK → sys_user_groups.id (CASCADE), NOT NULL | |
| user_id | ユーザーID | int | FK → sys_users.id (CASCADE), NOT NULL | |
| created_at | 作成日時 | datetime | | DEFAULT CURRENT_TIMESTAMP |

- **UNIQUE 制約**: `uq_group_user (group_id, user_id)`
- **管理画面での操作**: CUD (グループの CRUD に連動)
- **ビジネスルール**: `setGroupMembers()` で既存メンバーを全 DELETE してから再 INSERT (トランザクション内)

## 2. ステータス・区分値定義 (マジックナンバー)

### sys_improvement_items.status
| 値 | ラベル | 説明 |
| :--- | :--- | :--- |
| `pending` | 未対応 | 初期値。まだ対応していない |
| `done` | 対応済 | 対応完了。`resolved_at` に日時を自動設定 |
| `cancelled` | 見送り | 対応しないと判断した |

- 定数定義: `ImprovementItemModel::STATUS_PENDING`, `STATUS_DONE`, `STATUS_CANCELLED`
- 日本語ラベル: `ImprovementItemModel::STATUS_LABELS`

### sys_roles.sidebar_mode
| 値 | 説明 |
| :--- | :--- |
| `full` | サイドバーに全アプリを表示。`sys_role_apps` への登録不要 |
| `restricted` | `sys_role_apps` に登録されたアプリのみサイドバーに表示 |

### sys_apps.is_system
| 値 | 説明 |
| :--- | :--- |
| `0` | 通常アプリ。削除可能 |
| `1` | システム固定アプリ。削除不可 |

### sys_apps.admin_only
| 値 | 説明 |
| :--- | :--- |
| `0` | 全ロールで表示対象 |
| `1` | admin ロールのみ表示 |

### sys_guides.blocks (JSON 構造)
```json
[
  { "type": "text", "content": "手順の説明テキスト" },
  { "type": "image", "src": "/uploads/guides/xxx.jpg", "alt": "画像の説明" }
]
```

### テキスト管理: 拡張子 (ext)
| 値 | 説明 |
| :--- | :--- |
| `txt` | プレーンテキスト |
| `md` | Markdown (プレビュー時に marked.js でレンダリング) |
| `html` | HTML (プレビュー時にインライン表示。保存時は Base64 で送信) |

- 定数定義: `TextFileAdminStorage::ALLOWED_EXT`
- 最大サイズ: 512KB (`TextFileAdminStorage::MAX_CONTENT_BYTES = 524288`)

## 3. ファイルベースストレージ定義 (テキスト管理)

### index.json エントリ
| フィールド | 型 | 説明 |
| :--- | :--- | :--- |
| id | string | `YYYYMMDDHHmmss_xxxxxxxx` (日時+ランダム hex 8桁) |
| title | string | タイトル (最大120文字) |
| ext | string | `txt` / `md` / `html` |
| filename | string | `{id}_{slug}.{ext}` |
| size | int | ファイルサイズ (バイト) |
| created_by | int | 作成者の user_id |
| created_at | string | `Y-m-d H:i:s` |
| updated_at | string | `Y-m-d H:i:s` |

- **保存先**: `private/storage/admin_text_files/`
- **排他制御**: `flock(LOCK_EX)` による index.json のファイルロック
- **Model**: `App\Admin\Model\TextFileAdminStorage` (`private/apps/Admin/Model/TextFileAdminStorage.php`)
