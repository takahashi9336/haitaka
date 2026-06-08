# イベント管理・セットリスト・ライブガイド (event) 機能概要書

## 1. 目的と背景
- 日向坂46ポータル（Hinata）内で、ライブ・ミーグリ・リリース・メディアなど多種多様なイベントを一元管理する。
- セットリストや影ナレ情報を記録し、参戦記録と紐づけてライブ体験を振り返れるようにする。
- ライブ初参戦者向けに「出る可能性のある曲」と関連動画・音源へのリンクをまとめたガイドを提供する。

## 2. 解決するペインポイント（課題）
- イベント情報が公式サイト・SNS・個人メモに散在し、過去のイベント振り返りや今後のスケジュール確認が煩雑。
- セットリストの記録先がなく、参戦ライブの思い出を体系的に残せない。
- 初参戦者はどの曲が演奏されるか分からず、予習が困難。ペンライト色やコール動画への導線も不足している。

## 3. コアバリュー（主要な提供価値）
- **統合スケジュール管理**: カレンダー / タイムライン / ダッシュボード / マスタ・ディテールの4ビューモードで直感的にイベントを俯瞰できる。
- **参戦記録＋座席・感想**: ライブへの参戦トグル、座席情報、感想メモをイベント単位で記録できる。
- **セットリスト・影ナレ管理**: 曲順・アンコール区分・複数センター・MC/ブロック行を構造化して保存できる。
- **初参戦ライブガイド**: イベント別に候補曲を確度付きで登録し、MV/コール動画・Spotify/Apple Music 埋め込み・ハッシュタグ動画をまとめて提供する。
- **系列・応募締切管理**: 上位くくり（系列）によるイベント分類と、応募ラウンド別の締切管理が可能。

## 4. スコープ
- **対象ユーザー**: ログインユーザー（`Core\Auth::requireLogin()` 前提）。管理画面は `admin` / `hinata_admin` ロール限定。
- **関連する主要システム/外部API**:
  - Geocoding API（会場住所からの緯度経度取得、`geocode_event_place.php`）
  - YouTube Data API（イベント関連動画のサムネイル/埋め込み、`com_media_assets` 経由）
  - Spotify / Apple Music（埋め込みプレイヤー、楽曲テーブル `hn_songs` の URL を参照）
- **対象機能（event 配下）**:
  - **イベント管理**: イベント一覧（4ビューモード）、管理画面（CRUD）、系列マスタ、応募締切、ステータス・座席・感想、過去イベント無限スクロール
  - **セットリスト**: 閲覧画面、編集画面（曲/MC/ブロック、アンコール区分、複数センター）、参戦トグル、影ナレ
  - **ライブガイド**: ガイド閲覧画面（候補曲の確度別表示、動画/音源プレイヤー、コラボURL、ハッシュタグ動画）、楽曲管理画面
- **非スコープ**:
  - ミーグリ管理（MeetGreetController / MeetGreetReportController）は別設計書で扱う
  - 楽曲マスタ（SongModel / ReleaseModel）・メンバーマスタ（MemberModel）の CRUD 本体は別設計書で扱う
  - メディアアセット基盤（`com_media_assets` / `MediaAssetModel`）は共通基盤として別途定義

## 5. 現状（実装）サマリ
- **親アプリ（メニュー階層）**: `sys_apps.app_key = 'hinata'` 配下の event / setlist / live_guide
- **イベント一覧**:
  - エントリ: `www/hinata/events.php`
  - Controller: `private/apps/Hinata/Controller/EventController.php::index()`
  - View: `private/apps/Hinata/Views/event_index.php`（4ビューモード: calendar / timeline / dashboard / master-detail）
- **イベント管理**:
  - エントリ: `www/hinata/event_admin.php`
  - Controller: `EventController::admin()`
  - View: `private/apps/Hinata/Views/event_admin.php`（登録フォーム + ミニカレンダー + 最近の編集リスト）
- **セットリスト**:
  - 閲覧: `www/hinata/setlist.php` -> `SetlistController::show()`
  - 編集: `www/hinata/setlist_edit.php` -> `SetlistController::edit()`
- **ライブガイド**:
  - 閲覧: `www/hinata/live_guide.php` -> `LiveGuideController::index()`
  - 管理: `www/hinata/live_guide_admin.php` -> `LiveGuideController::admin()`
- **データの全ユーザー共通化**: イベント関連テーブルは `isUserIsolated = false`（BaseModel のユーザー分離を無効化）。ただし `hn_user_events_status` / `hn_event_attendance` はユーザー別データ。

## 6. 外部連携の基本仕様（要点）
### Geocoding API
- `geocode_event_place.php` で会場住所から緯度・経度・place_id を取得し、`hn_events` に保存する。
- LiveTrip 機能の「エリア」表示に利用される。

### YouTube（MediaAsset 経由）
- イベント関連リンクに YouTube URL が含まれる場合、`EventRelatedLinkService::syncYoutubeMovie()` で `com_media_assets` にアセットを登録し、`hn_event_movies` に紐づける。
- イベント一覧やセットリスト画面で YouTube 埋め込みプレイヤーを表示する。

### Spotify / Apple Music
- `hn_songs` テーブルの `spotify_url` / `apple_music_url` を利用し、ライブガイド画面で埋め込みプレイヤーを表示する。
- ストリーミングサービスへのデータ書き込みは行わない。
