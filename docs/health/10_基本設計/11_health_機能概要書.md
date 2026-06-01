# Health（ヘルスケア/健康管理） 機能概要書

## 1. 目的と背景
- MyPlatform 内に「ヘルスケア/健康管理」カテゴリを設け、日常生活の健康維持に関わる小さなユーティリティをまとめて提供する。
- 現時点では「食材ストック（キッチン在庫管理）」「トレーニングメニュー管理」「トレーニング実施履歴」の3機能を実装し、ポータル画面から各機能へアクセスできる。
- 将来的には「1週間の献立（AI提案）」など、在庫データと連動した機能拡張を想定している。

## 2. 解決するペインポイント（課題）
- 冷蔵庫/冷凍庫の食材在庫を手軽に把握できず、重複購入や食材の使い忘れが発生する。
- トレーニングメニュー（HIIT等）の種目構成・回数・時間を都度思い出す必要があり、記録も残らない。
- トレーニングの実施頻度を振り返る手段がなく、継続のモチベーション維持が難しい。

## 3. コアバリュー（主要な提供価値）
- 食材ストックの一覧管理と、Markdown形式でのコピー機能（AIへの献立提案など外部利用に便利）。
- トレーニングメニューの登録・管理と、YouTube参照動画の埋め込みによるフォーム確認。
- HIIT完了ボタンによるワンタップ記録と、ヒートマップによる実施状況の可視化（連続日数・月間回数等の統計表示）。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` 前提）
- 関連する主要システム/外部API: なし（外部API連携は現時点で未使用）
- 対象機能（Health配下）
  - **ポータル**: `/health/index.php` - Health機能群のトップ画面
  - **食材ストック**: `/health/kitchen_stock.php` - 食材の在庫一覧・追加・編集・削除
  - **トレーニングメニュー**: `/health/training_menu.php` - メニュー管理・HIIT一括記録
  - **トレーニング実施履歴**: `/health/training_history.php` - 実施記録の閲覧・追加・削除・ヒートマップ
- 非スコープ（本設計書では扱わない/別設計で扱う）
  - 1週間の献立AI提案（準備中として画面上に表示のみ）
  - sys_apps / sys_roles / sys_role_apps の汎用管理仕様（本書では参照関係のみ記載）

## 5. 現状（実装）サマリ
- 親アプリ（メニュー階層）: `sys_apps.app_key = 'health'`（食材ストック/トレーニングメニュー/トレーニング実施履歴を子としてぶら下げる）
  - 移行SQL: `migrations/done/add_health_to_sys_apps.sql`
- ポータル:
  - エントリ: `www/health/index.php`
  - Controller: `private/apps/Health/Controller/HealthController.php`
  - View: `private/apps/Health/Views/portal.php`
- 食材ストック:
  - エントリ: `www/health/kitchen_stock.php`
  - View: `private/apps/Health/Views/kitchen_stock.php`
  - Model: `private/apps/Health/Model/KitchenStockModel.php`
  - テーブル: `hl_kitchen_stock_items`
- トレーニングメニュー:
  - エントリ: `www/health/training_menu.php`
  - View: `private/apps/Health/Views/training_menu.php`
  - Model: `private/apps/Health/Model/TrainingMenuModel.php`
  - テーブル: `hl_training_menu_items`
- トレーニング実施履歴:
  - エントリ: `www/health/training_history.php`
  - View: `private/apps/Health/Views/training_history.php`
  - Model: `private/apps/Health/Model/TrainingLogModel.php`
  - テーブル: `hl_training_logs`

## 6. データの基本方針
- **ユーザー隔離**: 全テーブルで `isUserIsolated = true`（BaseModel）を適用し、`user_id` でデータを分離する。
- **スナップショット保存**: トレーニング実施履歴（`hl_training_logs`）では、記録時点のメニュー名・回数・時間をスナップショットとして保存する。メニューが後から編集・削除されても履歴データに影響しない。
- **HIIT一括記録**: 全登録メニューを1セッションとしてまとめて記録する。各種目のスナップショットを `menu_snapshot`（JSON）カラムに保持する。
