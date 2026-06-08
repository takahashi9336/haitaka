# イベント管理・セットリスト・ライブガイド (event) 機能一覧

## 1. 画面一覧

| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| イベント一覧画面 | `www/hinata/events.php` -> `EventController::index()` -> `Views/event_index.php` | 4ビューモード（カレンダー/タイムライン/ダッシュボード/マスタ・ディテール）でイベントを一覧表示する。カテゴリフィルタ、NEXT EVENT カウントダウン、過去イベント無限スクロール、ステータス設定、参戦トグル、座席・感想保存、セットリスト/影ナレ/ライブガイドへの導線を含む。 |
| イベント管理画面 | `www/hinata/event_admin.php` -> `EventController::admin()` -> `Views/event_admin.php` | 管理者向け。イベントの登録・編集・削除フォーム。カテゴリ選択、系列マスタ連携、出演メンバー選択（世代別/一括選択）、関連リンクのチップ入力（自動種別判定）、会場住所ジオコーディング、ミニカレンダー、最近の編集リストを含む。 |
| カレンダー画面 | `www/hinata/calendar.php` | イベントカレンダーの独立エントリポイント |
| セットリスト閲覧画面 | `www/hinata/setlist.php` -> `SetlistController::show()` -> `Views/setlist_show.php` | イベント別のセットリスト（曲順/アンコール/Wアンコール区分）と影ナレメンバーを表示する。曲名リンクから楽曲詳細への遷移、複数センター表示に対応。 |
| セットリスト編集画面 | `www/hinata/setlist_edit.php` -> `SetlistController::edit()` -> `Views/setlist_edit.php` | 管理者向け。セットリスト行の追加・削除・並替。曲/MC/ブロックの種別切替、アンコール区分、複数センター選択、影ナレメンバー・メモ編集を含む。 |
| ライブガイド閲覧画面 | `www/hinata/live_guide.php` -> `LiveGuideController::index()` -> `Views/live_guide.php` | 初参戦者向け。イベント選択後、候補曲を確度別（ほぼ確実/高確率/可能性あり）に一覧表示。MV/コール動画サムネイル、Spotify/Apple Music埋め込みプレイヤー、ペンライトカラー表リンク、コラボURL、ハッシュタグ付き動画（YouTube/TikTok分離）を含む。 |
| ライブガイド楽曲管理画面 | `www/hinata/live_guide_admin.php` -> `LiveGuideController::admin()` -> `Views/live_guide_admin.php` | 管理者向け。イベント別に候補曲を追加・削除し、出る確度（certain/high/possible）を設定する。リリース別の楽曲検索、確度変更、一括保存。 |

## 2. 機能・アクション一覧

| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| イベント一覧表示 | 画面 | ログインユーザーがイベントを4ビューモードで閲覧する |
| イベント登録 | API (POST) | 管理者がイベント情報を新規登録する（`save_event.php` -> `EventController::save()`） |
| イベント更新 | API (POST) | 管理者が既存イベントを更新する（同上、`id` 指定時） |
| イベント削除 | API (POST) | 管理者がイベントを削除する（`delete_event.php` -> `EventController::delete()`） |
| イベント複製 | 画面 | 管理画面で既存イベントの入力内容を保持したまま新規モードに切替（クライアント側操作） |
| 過去イベント取得 | API (GET) | 無限スクロール用に過去イベントをページネーション取得する（`past_events.php`） |
| 系列マスタ登録 | API (POST) | 管理者がイベント系列名を新規追加する（`save_event_series.php` -> `EventController::saveEventSeriesJson()`） |
| 系列マスタ削除 | API (POST) | 管理者が参照0件の系列を削除する（`delete_event_series.php` -> `EventController::deleteEventSeriesJson()`） |
| イベントステータス保存 | API (POST) | ユーザーがイベントへの参加予定/当選/落選等のステータスを保存する（`save_event_status.php`） |
| 座席・感想保存 | API (POST) | ユーザーがイベントの座席情報と感想を保存する（`save_event_seat_impression.php`） |
| 参戦トグル | API (POST) | ユーザーがライブイベントへの参戦を記録/解除する（`toggle_attendance.php`） |
| 応募締切管理 | API (POST) | 管理者がイベントの応募ラウンド（開始日/締切/当落日/URL/メモ）を一括保存する（`save_event_applications.php`） |
| 応募締切取得 | API (GET) | イベントの応募ラウンド一覧を取得する（`event_applications.php`） |
| 会場ジオコーディング | API (POST) | 会場住所から緯度・経度・place_id を取得し保存する（`geocode_event_place.php`） |
| セットリスト表示 | 画面 | ログインユーザーがイベントのセットリストと影ナレを閲覧する |
| セットリスト編集 | 画面/API | 管理者がセットリスト行を追加・並替・保存する |
| セットリスト保存 | API (POST) | セットリスト全行を一括保存する（`save_setlist.php`、delete-insert） |
| セットリスト取得 | API (GET) | イベントのセットリストを JSON で取得する（`get_event_setlist.php`） |
| 影ナレ取得 | API (GET) | イベントの影ナレ情報（メンバー一覧+メモ）を取得する（`get_event_shadow_narration.php`） |
| 影ナレ保存 | API (POST) | イベントの影ナレメンバーとメモを保存する（`save_event_shadow_narration.php`） |
| ライブガイド表示 | 画面 | ログインユーザーがイベント別の候補曲ガイドを閲覧する |
| ライブガイドデータ取得 | API (GET) | イベントの候補曲・動画・コラボURL・ハッシュタグメディアを取得する（`get_live_guide.php`） |
| ガイド楽曲保存 | API (POST) | 管理者がイベントの候補曲リストを一括保存する（`save_event_guide_songs.php`、delete-insert） |

## 3. 関連テーブル一覧

| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| `hn_events` | イベント | メインテーブル (CRUD すべて) |
| `hn_event_series` | イベント系列 | マスタテーブル (CRD) |
| `hn_event_members` | イベント出演メンバー | 中間テーブル (イベント保存時に delete-insert) |
| `hn_event_movies` | イベント動画 | 中間テーブル (関連リンク保存時に自動同期、delete-insert) |
| `hn_event_attendance` | ライブ参戦記録 | ユーザー別データ (toggle insert/delete) |
| `hn_event_applications` | イベント応募締切 | ラウンド別データ (一括 delete-insert) |
| `hn_event_guide_songs` | ライブガイド候補曲 | イベント別データ (一括 delete-insert) |
| `hn_event_shadow_narrations` | 影ナレ | イベント別 1:1 (upsert) |
| `hn_event_shadow_narration_members` | 影ナレメンバー | 中間テーブル (影ナレ保存時に delete-insert) |
| `hn_user_events_status` | ユーザーイベントステータス | ユーザー別データ (upsert / delete) |
| `hn_setlists` | セットリスト | イベント別データ (一括 delete-insert) |
| `hn_setlist_centers` | セットリストセンター | 中間テーブル (セットリスト保存時に delete-insert) |
| `hn_members` | メンバー | 参照のみ (出演メンバー・センター・影ナレ選択) |
| `hn_songs` | 楽曲 | 参照のみ (セットリスト・ガイド楽曲選択) |
| `hn_releases` | リリース | 参照のみ (楽曲のリリース情報) |
| `com_media_assets` | メディアアセット (共通基盤) | 参照/作成 (YouTube 動画の登録・サムネイル取得) |
| `hn_media_metadata` | メディアメタデータ | 参照のみ (ハッシュタグ動画検索) |
