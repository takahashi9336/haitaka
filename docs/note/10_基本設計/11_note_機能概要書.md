# メモ（Note） 機能概要書

## 1. 目的と背景
- MyPlatform の個人ポータル「haitaka」において、日常的に発生するアイデア・タスク・疑問・気づきなどを素早く記録し、後から見返せるメモ管理機能を提供する。
- Google Keep を参考にした直感的な UI で、テキストメモの作成・整理と、種別ごとに構造化されたリスト（やること、疑問・仮説、はじめて体験、おもろかったこと、書籍メモ、汎用リスト）の両方を一画面で管理できる。

## 2. 解決するペインポイント（課題）
- 思いついたことをすぐに書き留める場所がなく、散逸してしまう。
- メモとタスクリスト、読書記録、疑問メモなど性質の異なる情報を別々のツールで管理すると運用が煩雑になる。
- アーカイブ・ピン留め・背景色などの整理手段がないと、メモが増えた際に重要な情報が埋もれる。

## 3. コアバリュー（主要な提供価値）
- **クイックメモ**: タイトル任意・本文のみで即座に保存でき、タイトル未入力時は本文先頭30文字を自動生成する。
- **構造化リスト**: 6種別のリスト（todo / question / first_time / fun / book / generic_list）をJSON payloadで柔軟に管理し、種別ごとに最適化されたカード表示を提供する。
- **Google Keep風の整理機能**: ピン留め、背景色変更（10色）、アクティブ/アーカイブの状態管理、カードクリックによるモーダル編集。
- **レスポンシブ対応**: カラムレイアウト（1列〜4列）でデスクトップ・モバイル双方に対応。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` / `Core\Auth::check()` 前提）
- 関連する主要システム/外部API: なし（ローカルDB完結）
- 対象機能
  - **汎用メモ管理**: `/note/index.php`（一覧表示・クイックメモ追加・詳細モーダル編集）
  - **メモCRUD API**: `/note/api/save.php`, `/note/api/update.php`, `/note/api/delete.php`, `/note/api/toggle_pin.php`
  - **リストエントリ管理**: `/note/api/list_save.php`, `/note/api/list_update.php`, `/note/api/list_delete.php`, `/note/api/list_toggle_pin.php`
- 非スコープ
  - ダッシュボードからのクイックメモ呼び出し（ダッシュボード側設計で扱う）
  - Markdown レンダリング（DB設計上は想定しているが現時点では未実装）
  - メモの検索・全文検索機能
  - 他ユーザーとのメモ共有

## 5. 現状（実装）サマリ
- アプリキー: `note`（`$appKey = 'note'` でテーマ取得）
- エントリポイント: `www/note/index.php`
- Controller: `private/apps/Note/Controller/NoteController.php`
- View: `private/apps/Note/Views/note_index.php`
- Model:
  - `private/apps/Note/Model/NoteModel.php`（テーブル: `nt_notes`）
  - `private/apps/Note/Model/NoteListEntryModel.php`（テーブル: `nt_list_entries`）
- マイグレーション:
  - `migrations/done/001_create_nt_notes_table.sql`
  - `migrations/done/create_nt_list_entries.sql`

## 6. データの基本方針
- **ユーザー隔離**: 両テーブルとも `BaseModel::$isUserIsolated = true` により、`user_id` ベースでデータを隔離。
- **ステータス管理**: `nt_notes` は `active` / `archived` / `trash` の3状態、`nt_list_entries` は `active` / `archived` の2状態。
- **リストデータ構造**: `nt_list_entries.payload` カラムに JSON 型で種別ごとのデータを格納し、`NoteListEntryModel::normalizePayload()` でバリデーションと正規化を行う。
- **背景色**: 10色のプリセットカラーをカード単位で設定可能（デフォルト: `#ffffff`）。
