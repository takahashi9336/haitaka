# 管理画面 (admin) 機能一覧

## 1. 画面一覧
| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| 管理ポータル | `www/admin/index.php` | 管理機能へのカード型リンク一覧。テーマ色を `sys_apps` から継承 |
| ユーザー管理 | `www/admin/users.php` | ユーザー一覧表示、追加・ロール変更・パスワードリセット（モーダル） |
| アプリ管理 | `www/admin/apps.php` | `sys_apps` のツリー一覧・追加/編集/削除（モーダル）。テーマ色プリセット選択あり |
| ロール管理 | `www/admin/roles.php` | `sys_roles` の一覧（PC: テーブル / SP: カード）・追加/編集/削除（モーダル） |
| DB ビューワ | `www/db_viewer/index.php` | テーブル選択 → データ/構造/CREATE文のタブ切替表示。ページネーション・ダウンロード・コピー |
| DB 一括抽出 | `www/admin/db_export.php` | 全CREATE文(.sql) / スキーマ概要(.md) / JSON(.json) / 全データCSV(.zip) の一括ダウンロード |
| ガイド管理（一覧） | `www/admin/guides.php` | `sys_guides` の一覧表示。新規作成・編集・削除 |
| ガイド管理（編集） | `www/admin/guides.php?id=N` / `?new=1` | ブロックエディタによるガイド編集。テキスト/画像ブロックの追加・並替・削除 |
| 対応管理 | `www/admin/improvement_list.php` | `sys_improvement_items` の一覧。ステータス/画面名フィルタ・新規追加・編集・削除 |
| 友達管理 | `www/admin/friends.php` | `sys_user_friends` のペア一覧・登録（モーダル）・削除 |
| グループ管理 | `www/admin/friend_groups.php` | `sys_user_groups` の一覧・作成（モーダル）・編集（インライン）・削除 |
| テキスト管理 | `www/admin/text_files.php` | txt/md/html の登録・一覧・プレビュー（Markdown/HTML対応）・編集・削除 |

## 2. 機能・アクション一覧
| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| ポータル表示 | 画面 | 管理機能へのカードリンク10種を表示 |
| ユーザー追加 | API (POST) | `/users_settings/api/create_user.php` にJSON送信。id_name/password/role を登録 |
| ユーザーロール変更 | API (POST) | `/users_settings/api/admin_update_role.php` に送信。対象ユーザーの role を更新 |
| パスワードリセット | API (POST) | `/users_settings/api/admin_reset.php` に送信。対象ユーザーのパスワードを再設定 |
| アプリ作成 | 画面/処理 (POST) | `sys_apps` に INSERT。子アプリの場合は `sys_role_apps` へ自動追加。セッション破棄 |
| アプリ更新 | 画面/処理 (POST) | `sys_apps` を UPDATE。セッション破棄 |
| アプリ削除 | 画面/処理 (POST) | `sys_apps` から DELETE。システム固定・子画面保有時はエラー。セッション破棄 |
| ロール作成 | 画面/処理 (POST) | `sys_roles` に INSERT。restricted 時は `sys_role_apps` も設定。セッション破棄 |
| ロール更新 | 画面/処理 (POST) | `sys_roles` を UPDATE + `sys_role_apps` 再設定。セッション破棄 |
| ロール削除 | 画面/処理 (POST) | `sys_roles` から DELETE。セッション破棄 |
| DB テーブル一覧取得 | 画面 | `information_schema.TABLES` から全テーブル名を取得 |
| DB データ閲覧 | 画面 | 選択テーブルの行データをページネーション付きで表示（50/100/250/500/all） |
| DB 構造閲覧 | 画面 | `information_schema.COLUMNS` からカラム定義を表示 |
| DB CREATE文閲覧 | 画面 | `SHOW CREATE TABLE` の結果を表示 |
| DB ダウンロード | 画面 (JS) | データ/構造を CSV、CREATE文を SQL としてブラウザ側で生成しダウンロード |
| DB コピー | 画面 (JS) | 表示中のデータ/構造/CREATE文をクリップボードにコピー |
| 全CREATE文ダウンロード | API (GET) | `/admin/db_export.php?download=all_create` -- .sql ファイル |
| スキーマMarkdownダウンロード | API (GET) | `/admin/db_export.php?download=schema_md` -- .md ファイル |
| スキーマJSONダウンロード | API (GET) | `/admin/db_export.php?download=schema_json` -- .json ファイル |
| 全データCSV ZIPダウンロード | API (GET) | `/admin/db_export.php?download=all_data_csv_zip` -- .zip ファイル |
| ガイド一覧表示 | 画面 | `sys_guides` 全件を guide_key/タイトル/ブロック数とともに表示 |
| ガイド作成/更新 | 画面/処理 (POST) | ブロックエディタで構成した JSON を `sys_guides.blocks` に保存 |
| ガイド画像アップロード | API (POST) | `/admin/api/guide_image_upload.php` -- JPEG/PNG/WebP/GIF (最大5MB) を `uploads/guides/` に保存 |
| ガイド削除 | 画面/処理 (POST) | `sys_guides` から DELETE |
| 改善事項一覧表示 | 画面 | ステータス/画面名でフィルタ可能な一覧。未対応→対応済→見送り順 |
| 改善事項新規追加 | 画面/処理 (POST) | 画面名・内容・優先度を入力して `sys_improvement_items` に INSERT |
| 改善事項更新 | 画面/処理 (POST) | 画面名・内容・ステータス・メモを更新。対応済み時は `resolved_at` 自動設定 |
| 改善事項削除 | 画面/処理 (POST) | `sys_improvement_items` から DELETE |
| 改善事項FAB登録 | API (POST) | `/admin/api/save_improvement_item.php` -- 任意画面から改善事項をJSON送信で登録 |
| 友達ペア登録 | 画面/処理 (POST) | 2ユーザーを選択して `sys_user_friends` に INSERT（user_id < friend_user_id で格納） |
| 友達ペア削除 | 画面/処理 (POST) | `sys_user_friends` から DELETE |
| グループ作成 | 画面/処理 (POST) | `sys_user_groups` に INSERT + `sys_user_group_members` にメンバー一括追加 |
| グループ更新 | 画面/処理 (POST) | グループ名更新 + メンバー一括再設定（DELETE→INSERT） |
| グループ削除 | 画面/処理 (POST) | `sys_user_groups` から DELETE（CASCADE でメンバーも削除） |
| テキスト一覧取得 | API (POST) | `/admin/api/text_files_list.php` -- index.json から一覧を返却（content 除外） |
| テキスト取得 | API (POST) | `/admin/api/text_files_get.php` -- id 指定でファイル内容含む詳細を返却 |
| テキスト保存 | API (POST) | `/admin/api/text_files_save.php` -- 新規/更新。html は Base64 で受信。最大512KB |
| テキスト削除 | API (POST) | `/admin/api/text_files_delete.php` -- id 指定で index.json とファイルを削除 |

## 3. 関連テーブル一覧
| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| `sys_users` | ユーザー | ユーザー管理で参照（R）。追加/ロール変更/パスワードリセットは外部API経由 |
| `sys_apps` | アプリ・画面マスタ | アプリ管理で CRUD |
| `sys_roles` | ロールマスタ | ロール管理で CRUD |
| `sys_role_apps` | ロール別アプリ許可 | ロール管理で CUD（restricted ロールのアプリ割当） |
| `sys_guides` | ガイドマスタ | ガイド管理で CRUD |
| `sys_improvement_items` | 改善事項 | 対応管理/FAB で CRUD |
| `sys_user_friends` | 友達ペア | 友達管理で CRD |
| `sys_user_groups` | ユーザーグループ | グループ管理で CRUD |
| `sys_user_group_members` | グループメンバー | グループ管理で CUD（グループ CRUD に連動） |
| `information_schema.TABLES` | テーブル情報 | DB ビューワ/エクスポートで R（テーブル一覧取得） |
| `information_schema.COLUMNS` | カラム情報 | DB ビューワ/エクスポートで R（構造取得） |
