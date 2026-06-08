# ミーグリ（お話し会）& ネタ帳 機能一覧

## 1. 画面一覧

| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| ミーグリ予定一覧 | `www/hinata/meetgreet.php` | スロットを日付ごとにグループ化して表示。KPIサマリ、今後/過去/すべてフィルタ、インポート・手動追加モーダルを含む |
| ミーグリ レポ | `www/hinata/meetgreet_report.php` (slot_id 指定時) | チャット形式のレポ作成・閲覧・編集画面。メモ欄、アバター管理、HC表示モードを含む |
| ミーグリ レポ新規作成 | `www/hinata/meetgreet_report.php` (slot_id 未指定時) | メンバー・日付・イベント・部を選択してスロットを作成し、レポページへ遷移する |
| ネタ帳 | `www/hinata/talk.php` | メンバーごとのネタをカード形式で管理。メンバー横スクロール選択、種類/タグ/お気に入りフィルタ、次回イベント情報表示 |

## 2. 機能・アクション一覧

### ミーグリ予定 (MeetGreetController)

| 機能名 | 種類 | エンドポイント | 概要 |
| :--- | :--- | :--- | :--- |
| 予定一覧表示 | 画面 | GET `meetgreet.php` | 日付別グループ化スロット一覧 + KPIサマリを表示 |
| テキスト一括取り込み | API (JSON) | POST `api/meetgreet_import.php` | forTUNE meets 当選テキストをパースしたスロットデータを一括登録 |
| 手動スロット追加 | API (JSON) | POST `api/meetgreet_import.php` | 手動追加フォームからの1件または複数件のスロット登録 |
| スロット作成 (レポ用) | API (JSON) | POST `api/meetgreet_create_slot.php` | レポ新規作成画面からスロットを1件作成し、IDを返す |
| スロット削除 | API (JSON) | POST `api/meetgreet_delete.php` | スロット単体または日付一括の削除 |
| イベント紐付け | API (JSON) | POST `api/meetgreet_link_event.php` | 指定日付のスロット全件を指定イベントに紐付け |
| イベント別スロット取得 | API (JSON) | GET `api/meetgreet_event_slots.php` | 指定イベントIDに紐づくスロット一覧をレポ件数付きで返す |
| レポ(メモ)保存 | API (JSON) | POST `api/meetgreet_save_report.php` | スロットのメモ（report カラム）を更新 |

### ミーグリ レポ (MeetGreetController)

| 機能名 | 種類 | エンドポイント | 概要 |
| :--- | :--- | :--- | :--- |
| レポページ表示 | 画面 | GET `meetgreet_report.php?slot_id=N` | 指定スロットのチャット形式レポ一覧を表示 |
| レポ新規作成フォーム | 画面 | GET `meetgreet_report.php` (slot_id なし) | スロット未指定時の新規作成フォームを表示 |
| レポ作成 | API (JSON) | POST `api/meetgreet_report_create.php` | 新しいレポ（やり取り単位）を作成 |
| レポ更新 | API (JSON) | POST `api/meetgreet_report_update.php` | レポの使用枚数・ニックネームを更新 |
| レポ削除 | API (JSON) | POST `api/meetgreet_report_delete.php` | レポとそのメッセージを削除（CASCADE） |
| メッセージ一括保存 | API (JSON) | POST `api/meetgreet_report_messages_save.php` | レポ内の全メッセージを DELETE+INSERT で一括保存 |
| アバター画像アップロード | API (FormData) | POST `api/meetgreet_avatar_upload.php` | メンバー別アバター画像をリサイズして保存（UPSERT） |

### ネタ帳 (TalkController)

| 機能名 | 種類 | エンドポイント | 概要 |
| :--- | :--- | :--- | :--- |
| ネタ帳表示 | 画面 | GET `talk.php` | メンバー別ネタ一覧をカード形式で表示。次回イベント情報・登録メンバーサイドバーを含む |
| ネタ保存（新規/更新） | API (JSON) | POST `api/save_neta.php` | ネタの新規作成または既存更新。タグの差し替えも同時に行う |
| ネタ更新（内容のみ） | API (JSON) | POST `api/update_neta.php` | ネタ内容のみを更新する簡易エンドポイント |
| ネタ削除 | API (JSON) | POST `api/delete_neta.php` | ネタを削除（タグ紐付けもCASCADE削除） |
| ステータス切り替え | API (JSON) | POST `api/update_neta_status.php` | ネタの使用済み/未使用ステータスを切り替え |
| お気に入り更新 | API (JSON) | POST `api/update_neta_favorite.php` | ネタのお気に入りフラグを更新 |

## 3. 関連テーブル一覧

| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別） |
| :--- | :--- | :--- |
| hn_meetgreet_slots | ミーグリスロット | メインテーブル (CRUD すべて) |
| hn_meetgreet_reports | ミーグリレポ | スロットに紐づくレポ単位 (CRUD すべて) |
| hn_meetgreet_report_messages | ミーグリレポメッセージ | レポ内のチャットメッセージ (CRUD すべて) |
| hn_meetgreet_report_avatars | ミーグリレポアバター | メンバー別アバター画像 (CR, Update=UPSERT) |
| hn_neta | ネタ | ネタ帳メインテーブル (CRUD すべて) |
| hn_tags | タグマスタ | ユーザー別タグ定義 (CR, UPSERT) |
| hn_neta_tags | ネタ-タグ中間 | ネタとタグの多対多紐付け (CR, DELETE+INSERT) |
| hn_members | メンバーマスタ | 参照のみ (R) -- メンバー名・画像・カラー |
| hn_colors | カラーマスタ | 参照のみ (R) -- サイリウムカラー |
| hn_events | イベント | 参照のみ (R) -- イベント名・日付・カテゴリ・部数 |
| hn_favorites | お気に入り | 参照のみ (R) -- 推しレベル判定 |
| hn_user_member_profiles | ユーザー別メンバープロフィール | 参照のみ (R) -- アバター解決時のフォールバック |
