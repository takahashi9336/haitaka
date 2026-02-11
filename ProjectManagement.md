# マイポータルサイト構築プロジェクト_PJ管理ドキュメント

## 1. プロジェクト概要

- **目的**: 個人的なタスク管理や趣味などを集約したポータルサイトを構築して、活用する。
    
- **技術スタック**: 
	- PHP (独自MVC), MySQL (PDO), Tailwind CSS, JavaScript (Vanilla/FullCalendar)
	- REST API形式
    
- **設計思想**:
    
    - `Core\` 以下の共通基盤によるコードの再利用
        
    - `BaseModel` によるユーザー間のデータ隔離（$isUserIsolated）
        
    - 全画面日本語UI、モバイルファースト設計
        



# マイポータルサイト構築プロジェクト：マスター設計・管理ドキュメント

## 1. プロジェクト概要

- **目的**: 個人のタスク管理、趣味（日向坂46等）、集中力管理などの機能を一箇所に集約したポータルサイトの構築。
    
- **設計思想**:
    
    - **共通基盤（Core）の徹底活用**: 重複コードを排除し、保守性を高める。
        
    - **厳格なデータ隔離**: `BaseModel` による `user_id` 単位のフィルタリング。
        
    - **さくらインターネット最適化**: PHP 8.2系、ビルドプロセス不要な構成（Vanilla JS/Tailwind）。
        
    - **拡張性**: 将来的なスマホアプリ化や他ツール連携を見据えたREST API形式の採用。
        

## 2. システムアーキテクチャ

### ■ 技術スタック

- **Backend**: PHP 8.2.20 (独自MVC)
    
- **Database**: MariaDB (MySQL)
    
- **Frontend**: Vanilla JS / Tailwind CSS / Alpine.js / FullCalendar
    
- **API**: REST API形式
    

### ■ 名前空間と言語仕様

- `Core\`: `private/lib/`（Database, Auth, Logger 等の基盤クラス）
    
- `App\`: `private/apps/`（各アプリのController, Model, View）
    
- **オートロード**: Composer (PSR-4) による管理。
    

## 3. 共通基盤設計 (Core Infrastructure)

### ■ 認証・権限管理 (Auth.php)

- **方式**: ID/PASS方式。ログイン試行回数制限（5回）。
    
- **管理テーブル**: `sys_users`
    
- **認可**: ログイン時に `role` およびアプリ単位の権限（read, write, admin）をセッションにキャッシュ。
    

### ■ セッション管理 (SessionManager.php)

- **方式**: データベース方式（テーブル: `sys_sessions`）。
    
- **セキュリティ対策**:
    
    - **セッション固定攻撃防止**: ログイン成功時に `session_regenerate_id(true)`。
        
    - **タイムアウト**: 30日間（2,592,000秒）未操作で自動破棄。
        
    - **クッキー属性**: `Secure`, `HttpOnly`, `SameSite=Lax` を強制。
        
    - **ハイジャック防止**: IPアドレスおよびUser-Agentの検証。
        

### ■ セキュリティ対策

- **CSRF**: POST送信時のトークン検証。
    
- **XSS**: 出力時のエスケープ徹底。
    
- **SQLインジェクション**: `PDO` プリペアドステートメントの強制。
    

### ■ 例外・エラーハンドリング

- **グローバル例外ハンドラ**: 一貫したエラー画面またはJSONレスポンスを返却。
    
- **環境別表示**: `.env` のフラグによりデバッグ情報（localhost）と汎用メッセージ（sakura）を切替。
    

### ■ 現在のディレクトリ構成
- `/home/USER/private/`: 非公開領域（アプリケーションのコアロジック）
- `/home/USER/private/apps/`: 【各アプリ】ロジックの隔離
- `/home/USER/private/apps/(アプリ)/`: 各アプリ用のロジック
- `/home/USER/private/apps/(アプリ)/Controller`: コントローラ
- `/home/USER/private/apps/(アプリ)/Model`: モデル
- `/home/USER/private/apps/(アプリ)/Views`: ビュー
- `/home/USER/private/components/`: 共通ビューなどの配置。
- `/home/USER/private/components/sidebar.php`: 共通サイドバー
- `/home/USER/private/lib/`: 共通基盤ライブラリ
- `/home/USER/private/lib/Database.php`: PDOのラップ、接続管理
- `/home/USER/private/lib/Auth.php`: ログインチェック、権限判定、セッション管理
- `/home/USER/private/lib/Logger.php`: エラーログ、操作ログ出力
- `/home/USER/private/lib/Validator.php`: 入力値チェックの共通ルール
- `/home/USER/private/lib/SessionManager.php`: セッション管理共通ライブラリ
- `/home/USER/private/lib/Utils/`: 日付操作、文字列操作などのユーティリティ
- `/home/USER/private/vendor/`: PHPの外部ライブラリ
- `/home/USER/private/.env`: DB接続情報など
- `/home/USER/private/composer.json`: オートロード設定を記述
- `/home/USER/www/`: Document Root（公開領域）
- `/home/USER/www/assets/`: 【共通フロント】CSS/JS/画像
- `/home/USER/www/assets/css/common.css`: 全体のレイアウト、カラー定義
- `/home/USER/www/assets/css/components.mjs`: ボタンやフォームの共通スタイル
- `/home/USER/www/assets/js/core.js`: 共通のAjax処理、通知表示ロジック
- `/home/USER/www/assets/vendor/`: TailwindやAlpine.jsなどの外部ライブラリ
- `/home/USER/www/index.php`: ポータル画面：各アプリへのハブ
- `/home/USER/www/(アプリ)/index.php`: アプリのエントリポイント（private/apps内のロジックを呼び出す）
- `/home/USER/www/(アプリ)/api/`: 非同期通信用：save.phpなど
- `/home/USER/www/index.php`:ルートページ

## 4. データベース設計 (DB Schema)

### ① システム管理

- **sys_users**: ユーザーマスタ（id, id_name, password, role, created_at）
    
- **sys_sessions**: セッション管理（id, user_id, data, ip_address, user_agent, last_activity）
    
- **com_youtube_embed_data**: YouTube動画共通マスタ（video_key, title, category_tag, thumbnail_url）
    

### ② タスク管理 (tm_prefix)

- **tm_tasks**: タスクデータ
    
    - `id`, `user_id`, `category_id`, `title`, `description`, `status`, `priority`(1-3), `start_date`, `due_date`, `created_at`, `updated_at`
        
- **tm_categories**: カテゴリ（name, color, user_id）
    

### ③ 日向坂ポータル (hn_prefix)

- **hn_members**: メンバーマスタ（name, kana, generation, birth_date, blood_type, height, birth_place, color_id1/2, is_active, image_url, blog_url, insta_url, pv_movie_id）
    
- **hn_neta**: トークネタ帳（user_id, member_id, content, memo, status[stock, done, delete]）
    
- **hn_colors**: サイリウムカラー定義（color_name, color_code）
    
- **hn_favorites**: 推し登録（user_id, member_id）
    
- **hn_events**: イベントマスタ（event_name, event_date, category[1:Live, 2:MG, 3:RMG, 4:Rel, 5:Med, 6:SP, 99:Other], event_place, event_info, event_url）
    
- **hn_user_events_status**: 参戦状況（user_id, event_id, status[1:参加, 2:不参加, 3:検討, 4:当選, 5:落選]）
    
- **hn_event_members**: イベント出演者紐付け
    
- **hn_event_movies**: イベント関連動画紐付け
    

## 5. アプリ実装標準 (Coding Standards)

### ■ Model層 (BaseModel)

- **原則**: 全てのアプリModelは `BaseModel` を継承する。
    
- **自動隔離機能**: `$isUserIsolated = true` 設定時、`all()`, `find()`, `update()`, `delete()` のクエリに `user_id = :uid` を自動付加。
    
- **SQLルール**: `SELECT *` は禁止。`$fields` 配列で指定したカラムのみを取得する。
    

### ■ ディレクトリ構成

- `private/lib/`: `Database`, `Auth`, `Logger`, `Validator`, `SessionManager`, `Utils/`
    
- `private/apps/(App名)/`: `Controller/`, `Model/`, `Views/`
    
- `www/assets/`: `css/common.css`, `js/core.js` (共通Ajax/通知ロジック)
    

## 6. UI/UX 設計

### ■ 画面遷移

1. **ログイン** ↔ **ユーザー登録**
    
2. **ログイン成功** → **総合ポータル**
    
3. **総合ポータル** → **タスク管理** / **日向坂ポータル** / **ヤバい集中力ノート**
    
4. **共通サイドバー**: 全画面から各アプリへ遷移可能。
    

### ■ コンポーネント指針

- **モバイルファースト**: Tailwind CSS の responsive utilities を活用。
    
- **インタラクション**:
    
    - アコーディオンの状態保存（localStorage）。
        
    - 非同期保存時のローディング表示。
        
    - 完了済みタスクの透過表示（row-done）。
        

## 7. 開発プロトコル（AIへの厳守事項）

1. **合意形成**: コードを生成・修正する前に、必ず「修正方針」と「影響範囲」を提示し、ユーザーの合意を得ること。
    
2. **機能保護**: 実装済みの機能（特に画像アップロード、検索、フィルタ、詳細表示等）を破壊・削除しないよう、既存コードを完全に解析してから着手すること。
    
3. **エラー防止**: JavaScriptでは必ずデータ存在チェックを行い、`.replace()` 等でのクラッシュを回避する。
    
4. **勝手な推測の禁止**: DBカラム名やファイルパスが不明な場合は、必ずアップロードされた最新のソースコードを確認すること。また、ユーザへ質問をして確認をすること。
    
