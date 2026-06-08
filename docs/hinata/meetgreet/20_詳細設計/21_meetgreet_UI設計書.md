# ミーグリ（お話し会）& ネタ帳 UI設計書

## 1. 画面構成・レイアウト

### ミーグリ予定一覧画面 (meetgreet.php)
- **構成要素**:
  - 共通サイドバー（`components/sidebar.php`）
  - ヘッダー（戻るボタン → Hinataポータル、チケットアイコン、タイトル「ミーグリ予定」、「+ 予定を追加」ボタン）
  - KPIサマリセクション（2~3カラムGrid）:
    1. 直近の予定（あとN日後 + プログレスバー）
    2. 保有チケット枚数（今後の予定の合計）
    3. 推しの枚数（推しメンバー別の保有枚数。推しがいる場合のみ3カラム目として表示）
  - フィルタバー（「今後の予定」/「過去の予定」/「すべて」トグル、`mg-period-toggle`）
  - 日付別アコーディオンカード群（`meetgreet_slot_day_card.php` パーシャル。日付ヘッダー + スロット行テーブル）
  - インポートモーダル（`#importModal`、タブ切替: テキスト一括追加 / 手動1件追加）

- **KPIサマリ**: 推しメンバーがいる場合は `grid-cols-1 sm:grid-cols-3`、いない場合は `grid-cols-1 sm:grid-cols-2`。各KPIカードは `bg-white border border-slate-200 rounded-xl shadow-sm`
- **フィルタバー**: `bg-slate-100 border border-slate-200 rounded-xl` 内にボタンを配置。アクティブ状態は `is-active`（`bg-white text-slate-900 shadow`）
- **アコーディオンカード**: 日付ヘッダーをクリックでスロット行を展開/折りたたみ。開閉状態は `localStorage('mg_opened_dates')` に永続化。各スロット行にはメンバー名ピル（サイリウムカラー）、枚数、レポリンク、削除ボタンを表示
- **インポートモーダル**: Step1=テキスト入力（forTUNE meets当選結果貼り付け + 手動日付指定）→ Step2=プレビュー（テーブル形式で部/メンバー/枚数を確認、イベント自動紐付けチェックボックス）

### ミーグリ レポページ (meetgreet_report.php, slot_id指定時)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー（戻るボタン → ミーグリ予定一覧、メンバーアバター（クリックで変更）、メンバー名、日付・部名、「+ レポ追加」ボタン）
  - メモ欄（折りたたみ可。スロットの `report` カラムを保存）
  - レポカード群（各レポ = 1回のやり取り単位）:
    - レポヘッダー（レポ番号、使用枚数バッジ、ニックネーム表示、拡大表示・設定・削除ボタン）
    - チャットエリア（`#chat-{reportId}`）: LINE風バブルUI
    - 入力エリア（送信者タイプ切替ボタン + テキスト入力 + 送信ボタン + 挿入モードトグル）
  - モーダル群: レポメタ編集モーダル / レポ新規作成モーダル / メッセージ編集モーダル
  - HC全画面表示オーバーレイ（`#hcOverlay`）

- **チャットバブルスタイル**:
  - `bubble-member`: メンバーカラーを10%混合した背景 + 角丸（左上のみ2px）
  - `bubble-self`: テーマカラー背景 + 白テキスト + 角丸（右上のみ2px）
  - `bubble-self-thought`: テーマカラー8%混合背景 + ダッシュ破線ボーダー + イタリック
  - `bubble-narration`: `bg-slate-100` + 中央寄せ + 小フォント
- **挿入モード**: `insert-mode`クラス追加でメッセージ間に挿入ボタン（`insert-divider`）を表示。挿入位置選択後はインジケーターを表示し、次の送信メッセージをその位置に挿入
- **HC表示モード**: チャットエリアを全画面オーバーレイ（`hc-overlay`）で表示。編集UI（ペンアイコン・挿入ボタン）を非表示にした閲覧専用ビュー

### レポ新規作成フォーム (meetgreet_report_new.php, slot_id未指定時)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー（戻るボタン → ミーグリ予定一覧、アイコン、タイトル「レポ新規作成」）
  - フォームカード:
    - メンバー選択（セレクトボックス、`member_select_options.php` パーシャル）
    - 日付入力（date型、イベント選択時に自動入力）
    - イベント選択（任意、`mg_rounds > 0` 時は部チェックボックスリストを表示）
    - 部名入力（イベント未選択時 or `mg_rounds = 0` 時）
    - アクションボタン:
      - 「参加予定を登録」（`mg_rounds > 0` 時のみ表示、複数スロット一括作成 → 予定一覧へ遷移）
      - 「作成してレポを書く」（1スロット作成 → レポページへ遷移）
  - フッターリンク「スケジュールに戻る」

- **イベント選択時の動的制御**:
  - `mg_rounds > 0`: 部チェックボックスリスト表示（既存スロットは `checked disabled`、レポ済みバッジ付き）
  - `mg_rounds = 0`: 既存スロットリスト表示（クリックで該当スロットのレポページへ遷移）+ 部名入力欄表示

### ネタ帳画面 (talk.php)
- **構成要素**:
  - 共通サイドバー
  - ヘッダー（戻るボタン → Hinataポータル、電球アイコン、タイトル「推し活ネタ帳」、スマホ用「+ 追加」ボタン）
  - メンバー横スクロール選択セクション（`#memberStrip`）:
    - メンバーフィルタバー（すべて / 推し / 気になる / ネタあり）
    - 丸アイコン付きメンバーチップ（w-136px、クリックで選択しネタをフィルタ）
  - ネタ登録・編集フォーム（`#netaFormContainer`）:
    - 左: メンバーアバター（選択中メンバーの画像・名前を動的表示）
    - 右: テキストエリア + 種類トグル（未登録/質問/感想/ネタ）+ タグ入力（SNS風、Enter/カンマ/スペースで追加）+ 追加ボタン
  - メインエリア（左右分割、`lg:basis-[70%]` / `lg:basis-[30%]`）:
    - 左: ネタフィルタバー（未使用/質問/感想/ネタ/お気に入り/すべて）+ ネタカード群（2カラムGrid、`neta-cards-columns`）
    - 右サイドバー:
      - 次のイベント情報カード（ガラスモーフィズム風、あとN日 + イベント名 + 参加予定メンバー・部情報）
      - 登録済みメンバー一覧（推し/気になる/その他でグループ化、進捗バー付き）

- **ネタカード**: `neta-card` クラス。左ボーダーにメンバーカラー（`border-l-4`）、種類ラベルバッジ（左上）、使用済バッジ（条件付き表示）、ネタ内容テキスト、タグバッジ群、アクションボタン群（お気に入り/編集/使用済み）。使用済みカードは `done` クラスで `opacity: 0.45`
- **アクションボタン（SVGアイコン）**: `iconbtn` クラス。28x28px、角丸10px、半透明白背景。ホバー時に `translateY(-1px)` + シャドウ強化。`data-state="on"` でお気に入り星アイコン黄色塗り、`data-state="done"` でチェックアイコン緑色

## 2. 共通コンポーネントの利用
- `components/sidebar.php`: 共通サイドバー（全画面で使用）
- `components/head_favicon.php`: ファビコン（全画面で使用）
- `components/theme_from_session.php`: テーマカラー変数の初期化（全画面で使用）
- `Views/partials/member_select_options.php`: メンバー選択セレクトボックスのオプション生成（予定一覧の手動追加、レポ新規作成で使用）
- `Views/partials/meetgreet_slot_day_card.php`: 日付別スロットカードのパーシャル（予定一覧で使用）
- `Views/inc/meetgreet_member_pill_style.inc.php`: メンバー名ピルのテキスト色算出ユーティリティ
- `components/guide_display.php`: ガイド表示コンポーネント（テキストインポートの説明表示）
- `assets/js/core.js`: 共通JS（`App.post`, `App.toast`, `App.goBack` 等）

## 3. 状態と表示制御 (State & Conditional Rendering)

### ミーグリ予定一覧
- **KPI 3カラム目（推しの枚数）**: `$mgKpiOshiBoxes` が空でない場合のみ表示。推しのサイリウムカラーで名前・枚数をカラーリング
- **フィルタ切替**: `MG.setFilter(mode)` で `future` / `past` / `all` を切替。`#mgSectionFuture` / `#mgSectionPast` の `hidden` クラスを制御。過去セクションのサブヘッダー（`#mgPastSubheader`）は `all` モード時のみ表示
- **アコーディオン開閉**: 最初の今後の日付のみデフォルト展開。`localStorage('mg_opened_dates')` から復元。`focus_slot_id` / `focus_event_date` クエリパラメータによる自動スクロール・展開
- **インポートモーダル タブ**: `MG.switchAddTab('import'|'manual')` でテキスト入力 / 手動追加フォームを切替
- **手動追加 イベント選択時**: イベント選択でイベント名から日付自動入力、部選択を単一セレクトから部ごと枚数入力（`#manualRoundTickets`）に切替、時刻入力は非表示

### ミーグリ レポページ
- **メモ欄**: `report` が記入済みの場合は展開状態で表示、空かつレポが存在する場合は折りたたみ
- **レポ空状態**: レポが0件の場合は空状態UI（コメントアイコン + 「チャット形式のレポもあります」 + 追加ボタン）を表示
- **送信者タイプ**: `MGR.setSender(reportId, type)` でボタンのアクティブ状態を制御。メンバー=メンバーカラー、自分/内心=テーマカラー、ナレーション=slate
- **挿入モード**: `MGR.toggleInsertMode(reportId)` でチャットエリアに `insert-mode` クラスを付与。挿入ボタンのクリック位置を `insertPosition[reportId]` に記憶し、次の送信で該当位置に挿入
- **ニックネーム表示**: レポの `my_nickname` が設定されている場合、自分の発言バブルの上にニックネームを表示。デフォルトニックネームは `localStorage('mg_default_nickname')` に永続化

### レポ新規作成フォーム
- **イベント選択連動**: `onEventSelect(sel)` でイベント選択時に日付自動入力、`mg_rounds > 0` で部チェックボックスリスト表示（`#roundsSection`）、`mg_rounds = 0` で既存スロットリスト表示（`#existingSlotsSection`） + 部名入力欄
- **部チェックボックス**: 既存スロットは `checked disabled`。レポ済みスロットは「レポ済」バッジ + レポページリンク、登録済みスロットは「登録済み」バッジ + 「レポを書く」リンク
- **ボタン切替**: `mg_rounds > 0` 時は「参加予定を登録」ボタン表示・「作成してレポを書く」非表示。それ以外は逆

### ネタ帳
- **メンバー選択**: `syncSelectedMember(memberId)` で横スクロールチップのハイライト + フォームのメンバー更新 + ネタカードのフィルタ表示 + 右サイドバーのメンバーハイライトを連動。初期値は `sessionStorage('hinata_selected_member_id')` から復元、なければ最推しメンバー
- **メンバーフィルタ**: `applyMemberFilter(mode)` で横スクロールチップの表示/非表示を制御（`all` / `oshi`=favorite_level>=2 / `fav`=favorite_level===1 / `has`=ネタありメンバーのみ）
- **ネタフィルタ**: `applyMemoFilters()` で `memoQuickMode` に応じてネタカードの表示/非表示を制御。デフォルトは `unused`（未使用全種類）
- **編集モード**: `editNeta(item)` でフォームに既存データをセット、送信ボタンを「変更を保存」に変更、キャンセルボタンを表示
- **スマホ対応**: フォーム領域は `form-hidden` クラスでスマホ時に非表示。ヘッダーの「+ 追加」ボタンでトグル

## 4. スタイル・デザインルール
- **テーマカラー**: CSS変数 `--mg-theme`（`$themePrimaryHex`）を全画面で使用。ボタン背景、アクティブ状態、リング色に適用
- **メンバーカラー**: CSS変数 `--member-color`（レポページのみ）。バブル背景のmix、アバターボーダー、送信者ボタンのアクティブ色に使用
- **フォント**: `'Inter', 'Noto Sans JP', sans-serif`
- **カードUI**: `bg-white border border-slate-200 rounded-2xl shadow-sm`（レポカード）、`rounded-xl`（KPIカード、日付カード）
- **モーダル**: `modal-backdrop`（`rgba(0,0,0,0.4) + backdrop-filter: blur(2px)`）、コンテンツは `bg-white rounded-2xl shadow-2xl max-w-lg`
- **レスポンシブ**: サイドバーはデスクトップで固定幅240px、モバイルではハンバーガーメニューによるオーバーレイ表示。ネタ帳は `lg:flex-row`（左70%/右30%）、SP時は縦積み。ネタカードは `grid-cols-2`、SP時は `grid-cols-1`
- **メンバー名ピル**: `meetgreet_member_pill_style.inc.php` の `hinata_meetgreet_member_text_color()` でサイリウムカラーの輝度に応じてテキスト色を自動調整（白系カラーの場合はslate-400フォールバック）
