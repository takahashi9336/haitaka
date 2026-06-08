# ポータル（Portal） 機能概要書

## 1. 目的と背景
- 日向坂ポータル（Hinata）アプリの中心画面として、各サブドメイン（イベント、ミーグリ、リリース、ブログ、楽曲、動画等）のデータを集約し、ダッシュボード形式で一望できるようにする。
- ユーザーが頻繁に確認する「推しメンバー情報」「次のイベント」「最新リリース」「トピック・お知らせ」等を1画面に統合し、各サブ機能画面への導線を提供する。
- 管理者向けに「お知らせ管理（PortalInfo）」画面を提供し、トピック・お知らせ・応募締め切りの登録/編集を行えるようにする。

## 2. 解決するペインポイント（課題）
- 各サブ機能（イベント、ミーグリ、リリース、ブログ等）が独立画面に分散しており、全体の最新状況を把握するために複数画面を巡回する必要がある。
- 「推し」に関する横断情報（次の出演、参加楽曲数、最新ブログ、最新ニュース、新着動画）が散在しており、一目で確認できない。
- ポータル上のトピックやお知らせ（イベント告知、グッズ情報等）を管理する仕組みがなく、画面に表示する情報を柔軟に管理できない。

## 3. コアバリュー（主要な提供価値）
- 推しメンバーの横断サマリ（次の出演・参加楽曲数・最新ブログ・最新ニュース・新着動画）を1エリアに集約し、最推し/2推し/3推しをワンクリックで切り替え可能。
- 次のイベントまでのカウントダウン、カレンダー連携（iCal/Googleカレンダー）、本日のミーグリ予定を即座に確認可能。
- 最新リリース情報（ジャケット/収録曲/MV/ストリーミング再生）をポータル上でインライン展開。
- TOPICS・お知らせ・応募締め切りを管理画面から柔軟に登録・制御し、ポータルのカルーセルに表示。
- 最新ブログ、YouTube/TikTok動画のカルーセル表示で最新コンテンツを即キャッチアップ。
- 「今日は何の日」（過去のリリース/イベント）と「次の誕生日メンバー」で日向坂ヒストリーを楽しめる。
- 各サブ機能（ミーグリネタ帳、ミーグリ予定、レポ登録、イベント、メンバー帳、楽曲、アー写、動画一覧）へのアプリカード導線。

## 4. スコープ
- 対象ユーザー:
  - **ポータルダッシュボード**: ログインユーザー全般（`Core\Auth::requireLogin()` 前提）
  - **ポータル情報管理**: 管理者のみ（`admin` または `hinata_admin` ロール、`HinataAuth::requireHinataAdmin()` による制御）
- 関連する主要システム/外部API:
  - **YouTube Data API**: ポータル上のYouTubeカルーセル表示（`/hinata/api/youtube_latest.php` 経由）
  - **TikTok**: ポータル上のTikTokカルーセル表示（`/hinata/api/tiktok_latest.php` 経由）
- 対象機能（portal サブドメイン配下）
  - **ポータルダッシュボード**: `/hinata/index.php`
  - **ポータル情報管理**: `/hinata/portal_info_admin.php`（トピック・お知らせ・応募締め切りの管理）
  - **API**: `/hinata/api/save_topic.php`, `/hinata/api/save_announcement.php`, `/hinata/api/upload_topic_image.php`, `/hinata/api/upload_announcement_image.php`
- 非スコープ（本設計書では扱わない/別サブドメイン設計で扱う）
  - イベント管理の詳細仕様（events サブドメイン）
  - ミーグリ管理の詳細仕様（meetgreet サブドメイン）
  - リリース管理の詳細仕様（release サブドメイン）
  - ブログ・ニュース・動画管理の詳細仕様（blog / news / media サブドメイン）
  - メンバー管理の詳細仕様（member サブドメイン）
  - 推し設定の詳細仕様（favorite サブドメイン）

## 5. 現状（実装）サマリ
- ポータルダッシュボード:
  - エントリ: `www/hinata/index.php`
  - Controller: `private/apps/Hinata/Controller/HinataController.php`
  - View: `private/apps/Hinata/Views/portal.php`
  - 集約元モデル: NetaModel, EventModel, FavoriteModel, MeetGreetModel, ReleaseModel, ReleaseEditionModel, BlogModel, MemberModel, TopicModel, AnnouncementModel, EventApplicationModel, NewsModel, MediaAssetModel
- ポータル情報管理:
  - エントリ: `www/hinata/portal_info_admin.php`
  - Controller: `private/apps/Hinata/Controller/PortalInfoController.php`
  - View: `private/apps/Hinata/Views/portal_info_admin.php`
- 認証:
  - `private/apps/Hinata/Controller/HinataAuth.php`（`Core\Auth` を委譲ラップし、`isHinataAdmin()` / `requireHinataAdmin()` を提供）
  - `private/lib/Auth.php` の `isHinataAdmin()` メソッド: `admin` または `hinata_admin` ロールを管理者として判定
- 所有テーブル: `hn_topics`, `hn_announcements`
- 移行SQL: `migrations/done/create_hn_topics_announcements_event_applications.sql`

## 6. データ集約の基本方針
ポータルダッシュボードはデータの「集約・表示」に特化し、各テーブルへの書き込みは行わない（読み取り専用参照）。書き込み操作はポータル情報管理画面とそのAPIのみが行う。

集約する主要データと参照元:

| セクション | 参照元モデル/テーブル | 取得メソッド |
| :--- | :--- | :--- |
| 推しサマリ | FavoriteModel (hn_favorites, hn_members) | getOshiPortalSummary() |
| 推し最新ブログ | BlogModel (hn_blogs) | getLatestOnePerMember() |
| 推し最新ニュース | NewsModel (hn_news) | getLatestByMemberAndCategory() |
| 推し新着動画 | MediaAssetModel (com_media_assets) | getLatestOneByMember() |
| ネタ統計 | NetaModel (hn_neta) | getGroupedNeta() |
| 次のイベント | EventModel (hn_events) | getNextEvent() |
| 本日のミーグリ | MeetGreetModel (hn_meetgreet_slots) | getSlotsByDate() |
| 最新リリース | ReleaseModel, ReleaseEditionModel (hn_releases, hn_release_editions, hn_songs) | 直接SQL |
| 最新ブログ(全体) | BlogModel (hn_blogs) | getLatestAll() |
| 次の誕生日 | hn_members, hn_colors, hn_member_images | 直接SQL |
| 今日は何の日 | hn_releases, hn_events | 直接SQL |
| TOPICS | TopicModel (hn_topics) | getActiveTopics() |
| お知らせ | AnnouncementModel (hn_announcements) | getActiveAnnouncements() |
| 応募締め切り | EventApplicationModel (hn_event_applications) | getUpcomingDeadlines() |

## 7. 認証モデル
### Core\Auth
- `requireLogin()`: セッションにユーザー情報がない場合ログイン画面へリダイレクト
- `check()`: ログイン済みかどうかを返す
- `isHinataAdmin()`: `$_SESSION['user']['role']` が `admin` または `hinata_admin` のとき `true`

### HinataAuth（Hinata固有の認証ラッパー）
- `Core\Auth` をコンストラクタ注入で受け取り委譲
- `isHinataAdmin()`: `Core\Auth::isHinataAdmin()` を委譲
- `requireHinataAdmin(string $redirectUrl)`: 未ログインならログイン画面、ログイン済みだが管理者でなければ指定URLへリダイレクト

### ロール体系
| ロール | ポータル閲覧 | ポータル情報管理 | 管理ツール表示 |
| :--- | :--- | :--- | :--- |
| admin | 可 | 可 | 可 |
| hinata_admin | 可 | 可 | 可 |
| hinata_movie | 可 | 不可 | 不可 |
| (その他) | 可 | 不可 | 不可 |

- `hinata_movie` ロールはサイドバーで一部メニューの表示制御に使用されるが、ポータルダッシュボードの閲覧自体はログインユーザーであれば可能。
