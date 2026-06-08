# ミーグリ（お話し会）& ネタ帳 機能概要書

## 1. 目的と背景
- 日向坂46のミーグリ（お話し会）に関する予定管理・レポ記録・トーク準備をワンストップで行う機能群を提供する。
- ミーグリは「forTUNE meets」プラットフォーム経由で当選結果が通知されるため、その当選テキストをそのまま貼り付けて一括登録できる仕組みを設けている。
- レポ記録はチャット形式（メンバー発言 / 自分発言 / 内心 / ナレーション）でやり取りを再現でき、後から振り返りやすい。
- ネタ帳はミーグリ前のトーク準備を支援し、メンバーごとに質問・感想・ネタを分類管理する。

## 2. 解決するペインポイント（課題）
- ミーグリの当選結果が通知テキストのみで、日程・メンバー・枚数の管理を手作業で行う必要がある。
- ミーグリのやり取りを「何を話したか」「何を言われたか」を時系列で記録する手段がない。
- 次回のミーグリに向けて「何を話すか」を事前に整理・ストックしておく仕組みがない。
- メンバーごとの使用済みネタと未使用ネタの把握が困難である。

## 3. コアバリュー（主要な提供価値）
- **当選テキスト一括取り込み**: forTUNE meets の当選結果テキストを貼り付けるだけで、日付・部名・メンバー・枚数を自動パースして登録する。
- **チャット形式レポ**: メンバーとの会話を LINE 風のバブル UI で記録・閲覧でき、アバター画像のカスタマイズや挿入モードによる途中追記にも対応する。
- **KPI ダッシュボード**: 直近の予定までの日数、保有チケット枚数、推しメンバー別枚数をサマリ表示する。
- **ネタ帳**: メンバーごとに質問・感想・ネタを分類し、お気に入りやタグで整理する。使用済み/未使用のフィルタで次回に話すネタを素早く準備できる。
- **次回イベント連携**: ネタ帳画面から次のミーグリイベント情報と参加予定メンバーを即座に確認できる。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` 前提）
- 関連する主要システム/外部API: なし（外部API連携は行わない。forTUNE meets のテキストはクライアントサイドでパースする）
- 対象機能（meetgreet サブドメイン配下）
  - **ミーグリ予定一覧**: `/hinata/meetgreet.php`（スロット一覧、KPI、インポート、手動追加、イベント紐付け）
  - **ミーグリ レポ**: `/hinata/meetgreet_report.php`（チャット形式レポ作成・閲覧・編集、メモ、アバター管理）
  - **レポ新規作成**: `/hinata/meetgreet_report.php`（slot_id 未指定時のフォーム画面）
  - **ネタ帳**: `/hinata/talk.php`（メンバー選択、ネタ CRUD、種類・タグ・お気に入り管理）
- 非スコープ（本設計書では扱わない）
  - イベント管理（hn_events）自体の CRUD（別機能として管理）
  - メンバーマスタ（hn_members）の管理
  - お気に入り/推し設定（hn_favorites）の管理（TalkController に toggleFavorite があるが、これは共通機能の呼び出し）

## 5. 現状（実装）サマリ
- 親アプリ（メニュー階層）: `sys_apps.app_key = 'hinata_meetgreet'`（hinata の子アプリ）
- コントローラ:
  - `MeetGreetController` -- ミーグリ予定・レポの全操作
  - `TalkController` -- ネタ帳の全操作
- モデル:
  - `MeetGreetModel`（hn_meetgreet_slots）
  - `MeetGreetReportModel`（hn_meetgreet_reports）
  - `MeetGreetReportMessageModel`（hn_meetgreet_report_messages）
  - `MeetGreetReportAvatarModel`（hn_meetgreet_report_avatars）
  - `NetaModel`（hn_neta / hn_tags / hn_neta_tags）
- 暗号化対象フィールド:
  - `hn_meetgreet_slots.report`（メモ）
  - `hn_meetgreet_reports.my_nickname`（ニックネーム、TEXT に変更済み）
  - `hn_meetgreet_report_messages.content`（メッセージ本文）
  - `hn_neta.content`, `hn_neta.memo`（ネタ内容・メモ）

## 6. データの基本方針
- **ユーザー分離**: 全テーブルに `user_id` カラムを持ち、`BaseModel` の `isUserIsolated` によりログインユーザーのデータのみを操作する。
- **暗号化**: `Core\Encryption` を利用し、個人の感想やメッセージなどのプライバシー性の高いデータを暗号化して保存する。暗号化後のデータは base64(iv+tag+ciphertext) 形式となるため、対象カラムは `TEXT` 型に変更済み。
- **カスケード削除**: レポ削除時にメッセージが自動削除される（`ON DELETE CASCADE`）。スロット削除時にレポも自動削除される。ネタ削除時にタグ紐付けも自動削除される。
- **イベント紐付け**: `hn_meetgreet_slots.event_id` により `hn_events` と連携可能。紐付けは任意。
