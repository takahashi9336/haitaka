# Sense Lab（センスラボ） 機能概要書

## 1. 目的と背景
- 日常で「いいな」と感じたもの（食事・デザイン・風景など）を画像とテキストで記録し、自分のセンス（審美眼・選ぶ力）を言語化・蓄積するための個人向けスクラップ機能。
- 直感的な「いいな」を、「なぜいいと思ったか」という理由（最大3つ）に分解して記録することで、感性のトレーニングと自己分析を目的とする。
- どの画面からでも素早くメモを残せる「クイックスクラップ」と、じっくり理由を整理する「本番スクラップ」の2段階構成で運用する。

## 2. 解決するペインポイント（課題）
- 日常で感じた「いいな」を後から振り返る手段がなく、感覚が流れてしまう。
- 「なぜ良いと思ったか」を言語化する習慣がなく、センスの成長を実感しにくい。
- 思いついた瞬間にメモを残す導線がなく、記録のハードルが高い。

## 3. コアバリュー（主要な提供価値）
- 画像 + 理由3つ（感覚・テクニカル・文脈）のフォーマットで、感性を構造的に言語化できる。
- クイックスクラップ（FABボタン）により、MyPlatform内のどの画面からでも1行メモを即座に保存できる。
- カテゴリ別の集計により、自分がどの分野に感度が高いかを俯瞰できる。
- 将来的にAIフィードバック・深掘り質問の拡張を想定した設計になっている。

## 4. スコープ
- 対象ユーザー: 管理者のみ（`Core\Auth::requireAdmin()` 前提、`sys_apps.admin_only = 1`）
- 関連する主要システム/外部API: なし（外部API連携なし、ローカルDB完結）
- 対象機能（Sense Lab配下）
  - **スクラップ一覧**: `/sense_lab/index.php` — 本番スクラップ + クイックスクラップの統合一覧、カテゴリ別集計
  - **スクラップ新規登録**: `/sense_lab/new.php` → `/sense_lab/create.php`
  - **スクラップ詳細表示**: `/sense_lab/show.php?id=`
  - **スクラップ編集**: `/sense_lab/edit.php?id=` → `/sense_lab/update.php`
  - **スクラップ削除**: `/sense_lab/delete.php`（POST）
  - **クイックスクラップAPI**: `/sense_lab/api/quick_save.php`（JSON/FormData対応）
  - **クイックスクラップ編集**: `/sense_lab/quick_edit.php?id=` → `/sense_lab/quick_update.php`
  - **ユーティリティFAB**: 全画面共通の右下フローティングボタン（`sense_lab_utility_fab.php`）
- 非スコープ（本設計書では扱わない/別設計で扱う）
  - AIフィードバック機能（詳細画面にプレースホルダのみ存在、将来拡張予定）
  - クイックスクラップから本番スクラップへの昇格（`linked_entry_id` カラムは用意済みだが未実装）
  - sys_apps / sys_roles の汎用管理仕様

## 5. 現状（実装）サマリ
- アプリ登録: `sys_apps.app_key = 'sense_lab'`（アイコン: `fa-wand-magic-sparkles`、テーマ: `violet`）
  - 登録SQL: `migrations/done/create_sl_sense_lab.sql`
- Controller: `private/apps/SenseLab/Controller/SenseLabController.php`
- Model:
  - `private/apps/SenseLab/Model/SenseEntryModel.php`（本番スクラップ）
  - `private/apps/SenseLab/Model/SenseQuickEntryModel.php`（クイックスクラップ）
- View:
  - `private/apps/SenseLab/Views/index.php`（一覧）
  - `private/apps/SenseLab/Views/new.php`（新規作成フォーム）
  - `private/apps/SenseLab/Views/show.php`（詳細表示）
  - `private/apps/SenseLab/Views/edit.php`（編集フォーム）
  - `private/apps/SenseLab/Views/quick_edit.php`（クイックスクラップ編集）
  - `private/apps/SenseLab/Views/partials/sense_lab_utility_fab.php`（FABコンポーネント）
- 画像アップロード先: `www/uploads/sense_lab/`
- クライアント圧縮JS: `www/assets/js/sense_lab_image_compress.js`

## 6. データの基本方針
- **本番スクラップ**: `sl_sense_entries` — タイトル・カテゴリ・画像・理由1〜3を構造的に保存
- **クイックスクラップ**: `sl_sense_quick_entries` — 起点アプリ情報（app_key、page_title、source_url）付きのラフメモを高速保存し、後から理由・カテゴリを追記可能
- 画像は `/uploads/sense_lab/` にファイル保存し、DBにはURLパスを格納（JPG/PNG/GIF、最大2MB、超過時はブラウザ側でJPEG圧縮）
