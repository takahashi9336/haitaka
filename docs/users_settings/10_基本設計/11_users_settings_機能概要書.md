# ユーザー設定（users_settings） 機能概要書

## 1. 目的と背景
- MyPlatform にログインしている各ユーザーが、自身のアカウント情報（パスワード）を自律的に管理できる画面を提供する。
- 管理者（admin ロール）は、他ユーザーの新規作成・パスワードリセット・ロール変更を一元的に行える API を本機能配下に集約し、管理画面（`/admin/users.php`）から呼び出す構成とする。

## 2. 解決するペインポイント（課題）
- パスワード変更のたびに管理者へ依頼する運用負荷を排除し、ユーザー自身でセルフサービス変更できるようにする。
- 管理者向けのユーザー管理操作（作成・リセット・ロール変更）の API エンドポイントが分散していると保守性が低下するため、`/users_settings/api/` に統一する。

## 3. コアバリュー（主要な提供価値）
- 一般ユーザーは設定画面から現在のパスワードを検証した上で新しいパスワードへ変更できる（セルフサービス）。
- 管理者はユーザー追加・パスワード強制リセット・ロール変更を API 経由で安全に実行できる（管理者権限チェック付き）。
- ロール変更時は対象ユーザーのセッションを即時無効化し、権限の即時反映を実現する。

## 4. スコープ
- 対象ユーザー: ログイン済みユーザー全般（パスワード変更）、管理者のみ（ユーザー作成・リセット・ロール変更）
- 関連する主要システム/外部API: なし（外部 API 連携は不要。内部の認証・セッション管理のみ）
- 対象機能
  - **設定画面（パスワード変更）**: `/users_settings/index.php`
  - **自分のパスワード変更 API**: `/users_settings/api/update_self.php`
  - **ユーザー新規作成 API（管理者）**: `/users_settings/api/create_user.php`
  - **パスワードリセット API（管理者）**: `/users_settings/api/admin_reset.php`
  - **ロール変更 API（管理者）**: `/users_settings/api/admin_update_role.php`
  - **ヘルスチェック**: `/users_settings/api/ping.php`
- 非スコープ（本設計書では扱わない）
  - 管理画面（`/admin/`）のUI自体（管理画面側の設計書で扱う）
  - ログイン・ログアウト処理（`Core\Auth` の設計書で扱う）
  - sys_apps / sys_roles / sys_role_apps の汎用管理仕様（本書では参照関係のみ記載）

## 5. 現状（実装）サマリ
- エントリポイント: `www/users_settings/index.php` → `SettingsController::index()`
- Controller: `private/apps/Settings/Controller/SettingsController.php`（1ファイルに全アクションを集約）
- View: `private/apps/Settings/Views/index.php`（パスワード変更フォームのみの単画面）
- 利用モデル:
  - `Core\UserModel`（`private/lib/UserModel.php`） -- `sys_users` テーブル操作
  - `Core\RoleModel`（`private/lib/RoleModel.php`） -- `sys_roles` テーブル参照
  - `Core\SessionManager`（`private/lib/SessionManager.php`） -- `sys_sessions` テーブル操作（セッション無効化）
  - `Core\Auth`（`private/lib/Auth.php`） -- 認証チェック・管理者判定
- サイドバーからの導線: 共通サイドバー（`private/components/sidebar.php`）のフッター部に設定アイコン（`/users_settings/`）を配置
