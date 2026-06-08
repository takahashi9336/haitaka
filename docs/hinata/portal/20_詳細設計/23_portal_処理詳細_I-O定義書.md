# ポータル（Portal） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/index.php | HinataController::portal() | なし | HTML |
| GET | hinata/portal_info_admin.php | PortalInfoController::admin() | ?tab=topics\|announcements\|deadlines | HTML |
| POST | hinata/api/save_topic.php | (スタンドアロン) | JSON body | JSON |
| POST | hinata/api/save_announcement.php | (スタンドアロン) | JSON body | JSON |
| POST | hinata/api/upload_topic_image.php | (スタンドアロン) | multipart/form-data | JSON |
| POST | hinata/api/upload_announcement_image.php | (スタンドアロン) | multipart/form-data | JSON |

## 2. 処理フロー詳細

### GET hinata/index.php (HinataController::portal)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。未ログインなら `/login.php` へリダイレクト。

2. **データ収集** (各処理は try-catch で保護され、テーブル未作成時は空配列):
   - **ネタ統計**: `NetaModel::getGroupedNeta()` でグループ化されたネタを取得し、件数を集計
   - **次のイベント**: `EventModel::getNextEvent()` で直近の未来イベント1件を取得
   - **推しサマリ**: `FavoriteModel::getOshiPortalSummary()` で最推し/2推し/3推しの情報取得（名前、画像、期、ブログURL、Instagram URL、参加楽曲数、次の出演イベント）
   - **推し最新ブログ**: `BlogModel::getLatestOnePerMember($oshiMemberIds)` で推しメンバーごとの最新ブログ1件
   - **推し最新ニュース**: `NewsModel::getLatestByMemberAndCategory($mid, 'メディア', 1)` で推しメンバーごとの最新ニュース1件（カテゴリ=メディア）
   - **推し新着動画**: `MediaAssetModel::getLatestOneByMember($mid)` で推しメンバーごとの最新動画1件
   - **最新リリース**: `getLatestRelease()` で最新リリース1件（ジャケット、エディション、MV、収録曲含む）
   - **本日のミーグリ予定**: 次のイベントが当日（`days_left === 0`）の場合、`MeetGreetModel::getSlotsByDate($today)` で当日スロット取得
   - **最新ブログ(全体)**: `BlogModel::getLatestAll(20)` で全メンバーの最新ブログ20件
   - **次の誕生日**: `getUpcomingBirthdays()` で2週間以内の誕生日メンバー（直接SQL: `hn_members` + `hn_colors` + `hn_member_images`）
   - **今日は何の日**: `getTodayInHistory()` で同月同日の過去リリース/イベント（直接SQL: `hn_releases` + `hn_events`）
   - **TOPICS**: `TopicModel::getActiveTopics()` で表示中トピック
   - **お知らせ**: `AnnouncementModel::getActiveAnnouncements(15)` で公開中お知らせ15件
   - **応募締め切り**: `EventApplicationModel::getUpcomingDeadlines(7)` で7日以内の締め切り
   - **推しキャッシュ**: `FavoriteModel::cacheOshiToSession()` でセッションにキャッシュ

3. **レスポンス**:
   - `$user = $_SESSION['user']` をViewに渡す
   - `Views/portal.php` をレンダリング
   - 推しデータ(`$oshiByLevel`)、ブログ/ニュース/動画データをJSON埋め込みし、JS側で推し切り替え時にDOM更新

### GET hinata/portal_info_admin.php (PortalInfoController::admin)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。未ログインなら `/login.php`、権限不足なら `/hinata/` へリダイレクト。

2. **データ取得**:
   - `TopicModel::getAll()`: 全トピック一覧
   - `AnnouncementModel::getAll()`: 全お知らせ一覧
   - `EventModel::getAllUpcomingEvents()`: 今後のイベント一覧を取得し、カテゴリ 1/2/3（ライブ・ミーグリ・リアルミーグリ）でフィルタ

3. **レスポンス**:
   - `Views/portal_info_admin.php` をレンダリング
   - タブパラメータ (`$_GET['tab']`) により TOPICS / お知らせ / 応募締め切りタブを切り替え

### POST hinata/api/save_topic.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403 + JSON エラー返却。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | - | 更新時のみ。0または未指定で新規作成 |
   | title | string | 必須 | タイトル（空文字はバリデーションエラー） |
   | summary | string | - | 概要 |
   | url | string | - | リンクURL。空文字は NULL に変換 |
   | image_url | string | - | 画像URL。空文字は NULL に変換 |
   | topic_type | string | - | 種別。DEFAULT 'other' |
   | start_date | string (YYYY-MM-DD) | - | 表示開始日。空なら NULL |
   | end_date | string (YYYY-MM-DD) | - | 表示終了日。空なら NULL |
   | sort_order | int | - | 並び順。DEFAULT 0 |
   | is_active | int (0\|1) | - | 有効フラグ |

3. **処理**:
   - title が空なら例外スロー
   - `$id > 0` なら `TopicModel::update($id, $data)`、そうでなければ `TopicModel::create($data)`

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_announcement.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | - | 更新時のみ |
   | title | string | 必須 | タイトル |
   | body | string | - | 本文 |
   | url | string | - | リンクURL |
   | image_url | string | - | 画像URL |
   | announcement_type | string | - | 種別。DEFAULT 'other' |
   | published_at | string (datetime-local形式) | - | 公開日時。`T` を空白に置換し `:00` を付加 |
   | expires_at | string (datetime-local形式) | - | 終了日時。同上 |
   | sort_order | int | - | 並び順 |
   | is_active | int (0\|1) | - | 有効フラグ |

3. **処理**:
   - title が空なら例外スロー
   - `$id > 0` なら `AnnouncementModel::update($id, $data)`、そうでなければ `AnnouncementModel::create($data)`

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/upload_topic_image.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403。

2. **入力 (multipart/form-data)**:
   | フィールド名 | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | file | ファイル | 必須 | 画像ファイル (jpg, jpeg, png, gif, webp) |

3. **処理**:
   - 拡張子チェック（許可: jpg, jpeg, png, gif, webp）
   - 保存先ディレクトリ `www/assets/img/topics/` を確認、なければ `mkdir` で作成
   - ファイル名生成: `topic_YYYYMMDDHHmmss_XXXXXXXX.{ext}` (ランダム8文字hex)
   - `move_uploaded_file()` で保存

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "image_url": "img/topics/topic_....jpg"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/upload_announcement_image.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403。

2. **入力 (multipart/form-data)**:
   | フィールド名 | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | file | ファイル | 必須 | 画像ファイル (jpg, jpeg, png, gif, webp) |

3. **処理**:
   - 拡張子チェック（許可: jpg, jpeg, png, gif, webp）
   - 保存先ディレクトリ `www/assets/img/announcements/` を確認、なければ `mkdir` で作成
   - ファイル名生成: `ann_YYYYMMDDHHmmss_XXXXXXXX.{ext}` (ランダム8文字hex)
   - `move_uploaded_file()` で保存

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "image_url": "img/announcements/ann_....jpg"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

## 3. ポータルダッシュボードのクライアントサイド処理

### OshiPortal (推し切り替え)
- **トリガー**: サブ推しサムネイルのクリック
- **処理**: `OshiPortal.switchMain(level)` が呼ばれ、JSONデータ (`$oshiByLevel`) からメイン推しエリアのDOM要素を更新
- **更新対象**: 名前、期生、画像、KPIカード（次の出演/参加楽曲/最新ブログ/最新ニュース/新着動画）、個別ページリンク、ブログ/Instagramリンク
- **API呼び出し**: なし（初期レンダリング時にJSONをページに埋め込み）

### YtCarousel (YouTubeカルーセル)
- **初期化**: `DOMContentLoaded` で `YtCarousel.init()` が2つのチャンネルを非同期ロード
- **API**: `/hinata/api/youtube_latest.php?channel_id=...`
- **表示**: 最大16件、2行グリッド横スクロール。スケルトンローディング -> 実データ差し替え

### TkCarousel (TikTokカルーセル)
- **初期化**: `DOMContentLoaded` で `TkCarousel.init()` が非同期ロード
- **API**: `/hinata/api/tiktok_latest.php`
- **表示**: 最大18件、2行グリッド横スクロール

### TopicCarousel (TOPICSカルーセル)
- **初期化**: `DOMContentLoaded` で `TopicCarousel.init()` が矢印ボタンの表示/非表示を初期化
- **スクロール**: 左右矢印クリックで2カード分スクロール

### ReleaseAccordion (最新リリースアコーディオン)
- **トリガー**: 「収録曲を見る」ボタンクリック
- **処理**: `.expanded` クラスのトグルで `max-height` を0 -> 3000pxに遷移

### ReleasePlayer (ストリーミング再生)
- **トリガー**: 収録曲行のApple Music / Spotifyアイコンクリック
- **処理**: iframeで埋め込みプレーヤーを表示。同じ曲の再クリックで閉じる

### ポータル内検索
- **トリガー**: 検索ボックスへの入力（80msデバウンス）
- **処理**: 画面内のカード/リンク要素のテキストを照合し、マッチしないものを `display: none`
- **クリア**: ESCキーで検索クリア
