# 管理画面 (admin) 機能概要書

## 1. 目的と背景
- MyPlatform 全体のシステム管理を一元的に行うための管理者専用ポータルを提供する。
- ユーザー管理、アプリ定義、ロール制御、データベース参照・エクスポート、ガイド作成、改善事項トラッキング、友達/グループ管理、テキストファイル管理など、プラットフォーム運用に必要な機能を集約する。

## 2. 解決するペインポイント（課題）
- ユーザーの追加・パスワードリセット・ロール変更を直接 DB 操作なしに行えない。
- アプリ（`sys_apps`）やロール（`sys_roles`）の定義変更がマイグレーション頼みになり、運用中の即時変更が困難。
- テーブル構造やデータの確認にツール（phpMyAdmin 等）への切り替えが必要。
- 開発・運用中に気づいた改善事項をその場で記録し、進捗管理する仕組みがない。
- 視聴履歴共有のための友達関係やグループを DB 直接操作で管理していた。

## 3. コアバリュー（主要な提供価値）
- ポータル形式のカード UI で管理機能へ素早くアクセスできる。
- アプリ・ロールの CRUD 操作後に全ユーザーのセッションを即時破棄し、権限変更を確実に反映する。
- DB ビューワ・一括エクスポートにより、スキーマ確認と AI 共有・バックアップをブラウザだけで完結させる。
- 改善事項 FAB を全画面に配置し、どの画面からでもワンクリックで改善提案を登録できる。
- テキストファイル管理でサーバ上に txt/md/html を保存し、Markdown プレビュー付きで閲覧できる。

## 4. スコープ
- 対象ユーザー: 管理者（`role = 'admin'`）のみ。全画面で `Auth::requireAdmin()` による認可を実施。
- 関連する主要システム/外部API: なし（外部 API 連携は不要。DB 操作のみ）。
- 対象機能（管理画面配下）:
  - **ポータル**: `/admin/index.php` -- 管理機能カード一覧
  - **ユーザー管理**: `/admin/users.php` -- ユーザー追加・ロール変更・パスワードリセット
  - **アプリ管理**: `/admin/apps.php` -- `sys_apps` のツリー表示・CRUD
  - **ロール管理**: `/admin/roles.php` -- `sys_roles` の CRUD・アプリ割り当て
  - **DB ビューワ**: `/db_viewer/` -- テーブル選択・データ/構造/CREATE 文の閲覧・ダウンロード・コピー
  - **DB 一括抽出**: `/admin/db_export.php` -- 全 CREATE 文/スキーマ Markdown/JSON/全データ CSV ZIP のダウンロード
  - **ガイド管理**: `/admin/guides.php` -- `sys_guides` のブロックエディタによる手順ガイド CRUD
  - **対応管理**: `/admin/improvement_list.php` -- `sys_improvement_items` の改善事項 CRUD・フィルタ
  - **友達管理**: `/admin/friends.php` -- `sys_user_friends` の友達ペア登録・削除
  - **グループ管理**: `/admin/friend_groups.php` -- `sys_user_groups` / `sys_user_group_members` のグループ CRUD
  - **テキスト管理**: `/admin/text_files.php` -- ファイルベース（`private/storage/admin_text_files`）のテキスト CRUD
  - **改善事項 FAB**: 全画面共通パーシャル -- どの画面からでも改善事項を登録
- 非スコープ:
  - 各業務アプリ（日向坂ポータル、エンタメ等）の個別ロジック
  - ログイン・認証処理そのもの（`Core\Auth` の内部仕様）

## 5. 現状（実装）サマリ
- 親アプリ（メニュー階層）: `sys_apps.app_key = 'admin'`（id=5, parent_id=NULL, admin_only=1）
- 子画面アプリ: `admin_users`(id=12), `admin_apps`(id=13), `admin_roles`(id=14), `admin_db_viewer`(id=15), `admin_guides`(id=17), `admin_improvement_list`(id=18) 他
- 初期データ投入: `migrations/done/002_seed_sys_apps_sys_roles.sql`
- ガイド機能追加: `migrations/done/create_sys_guides.sql`
- 改善事項追加: `migrations/done/create_sys_improvement_items.sql`
- 友達/グループ追加: `migrations/done/create_sys_user_friends_and_groups.sql`
- テキスト管理: DB レス（ファイルベース、`private/storage/admin_text_files/index.json` で管理）

## 6. アーキテクチャ概要
- **パターン**: MVC（エントリ PHP → Controller → Model → View）
- **認可**: 全エントリポイントで `Auth::requireAdmin()` を呼び出し、admin ロール以外は 403 リダイレクト
- **セッション破棄**: アプリ・ロール変更時に `SessionManager::invalidateAllSessions()` で全ユーザーのセッションを強制破棄
- **テーマ**: `theme_from_session.php` コンポーネントにより、`sys_apps` の `theme_primary` / `theme_light` をヘッダー・ボタン等に動的適用
- **API**: JSON レスポンスの REST 風 API（`/admin/api/*`）を一部画面（テキスト管理、改善事項 FAB、ガイド画像アップロード）で利用
