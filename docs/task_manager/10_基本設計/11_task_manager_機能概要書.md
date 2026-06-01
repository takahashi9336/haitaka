# タスク管理 機能概要書

## 1. 目的と背景
- MyPlatform 内で個人のタスクを一元管理できる機能を提供する。
- 日常的な「やること」を登録・分類・追跡し、未着手/進行中/保留/完了のワークフローで進捗を可視化する。
- カレンダー表示やガントチャートによる時系列の俯瞰、カンバンボードによるステータス別管理など、複数のビューを切り替えて利用できる。

## 2. 解決するペインポイント（課題）
- タスクの散在: メモ帳や複数のアプリにタスクが分散し、全体の把握が困難になる。
- 進捗の不可視: 何がどの段階にあるかを一目で確認する手段がない。
- 日程管理との断絶: タスクの期限とカレンダーの予定（日向坂イベント等）が別管理で、スケジュール調整が煩雑になる。

## 3. コアバリュー（主要な提供価値）
- 4種類のビュー（リスト/ボード/ガントチャート/カレンダー）でタスクを多角的に確認できる。
- カテゴリ・優先度・ステータスで柔軟に分類・フィルタリングできる。
- カレンダービューでは日向坂46のイベント予定と自分のタスク期限を重ねて確認できる。
- カンバンボード上でドラッグ&ドロップによるステータス変更が可能。
- クイック追加フォームにより最小限の入力でタスクを素早く登録できる。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` 前提）
- 関連する主要システム/外部API:
  - **FullCalendar**: カレンダービューの描画（CDN: fullcalendar@6.1.10）
  - **SortableJS**: カンバンボードのドラッグ&ドロップ操作（CDN: sortablejs@1.15.3）
  - **日向坂イベントモデル**: `App\Hinata\Model\EventModel` から今後のイベント一覧を取得し、カレンダービューに表示
- 対象機能
  - タスクの CRUD（作成・参照・更新・削除）
  - リストビュー（優先度順/カテゴリ順ソート、検索、フィルタリング）
  - ボードビュー（カンバン形式、ドラッグ&ドロップによるステータス変更）
  - ガントチャートビュー（開始日〜期日のバー表示）
  - カレンダービュー（FullCalendar による月表示、日向坂イベント重畳表示）
  - カテゴリの自動作成（名前指定で存在しなければ新規作成）
  - 統計サマリ表示（件数・ステータス別内訳・プログレスバー）
- 非スコープ（本設計書では扱わない）
  - 複数ユーザー間でのタスク共有・アサイン機能
  - 繰り返しタスク（リカーリング）の自動生成
  - sys_apps / sys_roles の汎用管理仕様（参照関係のみ記載）

## 5. 現状（実装）サマリ
- エントリポイント: `www/task_manager/index.php`
- Controller: `private/apps/TaskManager/Controller/TaskController.php`
- View: `private/apps/TaskManager/Views/index.php`
- Model:
  - `private/apps/TaskManager/Model/TaskModel.php`（テーブル: `tm_tasks`）
  - `private/apps/TaskManager/Model/CategoryModel.php`（テーブル: `tm_categories`）
- API エンドポイント:
  - `www/task_manager/api/save.php`（タスク新規作成）
  - `www/task_manager/api/update.php`（タスク更新・ステータス変更）
  - `www/task_manager/api/delete.php`（タスク削除）
- 外部依存（CDN経由）:
  - Tailwind CSS
  - Font Awesome 6.5.1
  - FullCalendar 6.1.10
  - SortableJS 1.15.3
- 共通コンポーネント:
  - `private/components/theme_from_session.php`（テーマカラー取得）
  - `private/components/sidebar.php`（共通サイドバー）
  - `private/components/head_favicon.php`（ファビコン）
  - `www/assets/js/core.js`（共通ユーティリティ: `App.post()`, `App.toast()`, `App.calculateRemaining()`）
