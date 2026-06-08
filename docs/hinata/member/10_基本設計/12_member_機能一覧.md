# メンバー (Member) 機能一覧

## 1. 画面一覧

| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| メンバー帳 | `www/hinata/members.php` → `Views/members.php` | メンバー一覧をカード/リスト形式で表示。期生フィルタ・ソート・卒業切替対応 |
| メンバー管理 (Admin) | `www/hinata/member_admin.php` → `Views/member_admin.php` | 管理者向け。詳細編集タブと一覧編集タブの2モード |
| メンバー個別ページ | `www/hinata/member.php` → `Views/oshi_member.php` | メンバーの詳細情報・タイムライン・楽曲・動画・イベント・マイフォト・ネタ |
| 推し設定 | `www/hinata/oshi_settings.php` → `Views/oshi_settings.php` | 最推し/2推し/3推し/気になるの設定画面 |
| ペンライトカラー表 | `www/hinata/penlight.php` → `Views/penlight.php` | 全メンバーのサイリウムカラーを表形式で表示。名前検索・期生フィルタ対応 |
| 旧URL互換リダイレクト | `www/hinata/oshi_member.php` | `/hinata/member.php?id=X` へ 301 リダイレクト |

## 2. 機能・アクション一覧

### メンバー管理 (MemberController)

| 機能名 | 種類 | 概要 |
| :--- | :--- | :--- |
| メンバー帳表示 | 画面 | 全メンバー（現役+卒業）のカード/リスト表示 |
| メンバー詳細API | API (GET/JSON) | メンバーIDを指定して詳細情報をJSON取得（モーダル表示用） |
| メンバー情報保存 | API (POST/JSON) | メンバーの全項目保存（画像5枚・PV動画URL・基本情報） |
| メンバー簡易保存 | API (POST/JSON) | 一覧編集用の基本項目のみ保存（画像・PV除く） |
| メンバー一括保存 | API (POST/JSON) | 複数メンバーの基本項目を一括更新 |
| メンバー個人活動保存 | API (POST/JSON) | メンバーの個人活動（ラジオ・ドラマ等）を新規登録/更新 |
| メンバー個人活動削除 | API (POST/JSON) | メンバーの個人活動を削除（画像ファイルも連動削除） |

### 推し機能 (OshiController)

| 機能名 | 種類 | 概要 |
| :--- | :--- | :--- |
| 推し設定画面 | 画面 | 推しスロット表示・メンバー選択でレベル設定 |
| 推しメンバーページ | 画面 | タイムライン・楽曲・動画・ブログ・イベント・マイフォト・ネタを統合表示 |
| 推しレベル設定 | API (POST/JSON) | メンバーの推しレベルをトグル/設定（排他制御付き） |
| 推しデータAPI | API (GET/JSON) | ポータル用サマリ（推し3名の最新動画・次イベント・楽曲数） |
| 推しタイムラインAPI | API (GET/JSON) | ブログ・ニュース・スケジュール・イベント・動画を統合タイムラインとして返却 |
| 推し画像アップロード | API (POST/multipart) | マイフォト画像の追加（リサイズ処理付き、上限10枚/メンバー） |
| 推し画像削除 | API (POST/JSON) | マイフォト画像の削除（ファイル連動） |
| 推し画像並び順変更 | API (POST/JSON) | マイフォト画像の表示順を更新 |
| プロフィール画像変更 | API (POST/multipart) | ユーザー固有のメンバープロフィール画像を設定 |
| メンバー一覧API (セレクト用) | API (GET/JSON) | メンバーID/名前/推しレベル/期生のリスト返却（FABフォーム用） |

### ペンライト (PenlightController)

| 機能名 | 種類 | 概要 |
| :--- | :--- | :--- |
| ペンライトカラー表 | 画面 | メンバー名・カラー1・カラー2を表形式で表示。最新シングルのアー写付き |

## 3. 関連テーブル一覧

| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| `hn_members` | メンバーマスタ | メインマスタ (CRUD) |
| `hn_colors` | カラーマスタ | サイリウムカラーの参照テーブル (R) |
| `hn_member_images` | メンバー画像 | メンバーごと最大5枚の画像管理 (CRD) |
| `hn_member_activities` | メンバー個人活動 | ラジオ・ドラマ等の活動管理 (CRUD) |
| `hn_favorites` | お気に入り (推し) | ユーザーごとの推しレベル管理 (CRD) |
| `hn_oshi_images` | マイフォト | ユーザーごとの推し画像管理 (CRD) |
| `hn_user_member_profiles` | ユーザー固有プロフィール画像 | ユーザーが独自設定したメンバー画像 (CU) |
| `hn_song_members` | 楽曲参加メンバー | メンバーの参加楽曲 (R) |
| `hn_media_members` | メディア出演メンバー | YouTube/Instagram/TikTok 動画の紐づけ (R, 一部CU) |
| `hn_media_metadata` | メディアメタデータ | 動画メタ情報 (R) |
| `com_media_assets` | メディアアセット共通 | YouTube/Instagram/TikTok のアセット情報 (R) |
| `hn_events` / `hn_event_members` | イベント | メンバー参加イベント (R) |
| `hn_blog_posts` | ブログ記事 | メンバー最新ブログ (R) |
| `hn_news` / `hn_news_members` | ニュース | メンバー関連ニュース (R) |
| `hn_schedule` / `hn_schedule_members` | スケジュール | メンバー関連スケジュール (R) |
| `hn_neta` | ミーグリネタ | ネタ帳（推しページからの簡易操作） (R) |
| `hn_releases` | リリース | 最新シングル情報（ペンライト表のアー写取得用） (R) |
| `hn_release_member_images` | リリースメンバーアー写 | シングルごとのメンバーアー写 (R) |
