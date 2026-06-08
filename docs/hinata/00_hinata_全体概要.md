# 日向坂ポータル (Hinata) 全体概要

## 1. 機能概要

日向坂46に関する情報を包括的に管理・閲覧するための個人ポータル機能。メンバー・楽曲・リリース・イベント・メディア（YouTube/TikTok/ブログ）・ミーグリ（お話し会）など、多岐にわたるドメインを統合管理する。

- 対象ユーザー: ログインユーザー（`HinataAuth` による認証。一部画面は `hinata_movie` ロールでもアクセス可）
- アプリキー: `hinata`（`sys_apps.app_key`）
- テーマカラー: 空色系（`#87CEEB` ベース）

## 2. サブドメイン分割と設計書索引

本機能は規模が大きいため、以下の6サブドメインに分割して設計書を管理する。

| # | サブドメイン | ディレクトリ | 概要 |
|---|------------|------------|------|
| 1 | [ポータル・お知らせ](portal/) | `docs/hinata/portal/` | ポータルダッシュボード、トピック・お知らせ管理、認証 |
| 2 | [メンバー・推し](member/) | `docs/hinata/member/` | メンバー一覧・詳細・管理、推し設定・タイムライン、ペンライト |
| 3 | [イベント・ライブ](event/) | `docs/hinata/event/` | イベント管理、セットリスト、ライブガイド、影ナレ |
| 4 | [ミーグリ・ネタ帳](meetgreet/) | `docs/hinata/meetgreet/` | ミーグリスロット・レポート、ネタ帳管理 |
| 5 | [メディア・バッチ](media/) | `docs/hinata/media/` | メディアアセット管理（YouTube/TikTok/ブログ）、バッチジョブ |
| 6 | [楽曲・リリース](music/) | `docs/hinata/music/` | 楽曲・歌唱メンバー、リリース・エディション、アー写 |

各サブドメインは標準の設計書構成（`10_基本設計/` + `20_詳細設計/` + `99_変更履歴`）に従う。

## 3. 技術構成

### ディレクトリ構成
```
www/hinata/
├── index.php              ← ポータルエントリ
├── *.php                  ← 各画面エントリ（30ファイル）
├── api/                   ← APIエンドポイント（84ファイル）
└── batch/                 ← バッチジョブ（8ファイル）

private/apps/Hinata/
├── Controller/            ← 15コントローラ
├── Model/                 ← 30モデル（+ スクレイパー2 + APIクライアント1）
├── Service/               ← 1サービス
├── Helper/                ← 1ヘルパー
└── Views/                 ← 30ビュー（+ partials 4 + inc 1）
```

### コントローラ一覧
| Controller | サブドメイン | 主な責務 |
|-----------|------------|---------|
| HinataController | portal | ポータルダッシュボード表示 |
| HinataAuth | portal | Hinata固有の認証・ロール判定 |
| PortalInfoController | portal | トピック・お知らせ管理 |
| MemberController | member | メンバーCRUD・プロフィール画像・活動記録 |
| OshiController | member | 推し設定・タイムライン・推しギャラリー |
| PenlightController | member | ペンライトカラー表示 |
| EventController | event | イベントCRUD・シリーズ・参加ステータス |
| SetlistController | event | セットリスト表示・編集・参戦管理 |
| LiveGuideController | event | ライブガイド・予習楽曲・影ナレ |
| MeetGreetController | meetgreet | ミーグリスロット・レポート管理 |
| TalkController | meetgreet | ネタ帳（ミーグリ用トーク素材） |
| MediaController | media | メディアアセットCRUD・カテゴリ・ハッシュタグ |
| SongController | music | 楽曲CRUD・歌唱メンバー・ストリーミング |
| ReleaseController | music | リリースCRUD・エディション・収録曲管理 |
| ArtistPhotoController | music | アーティスト写真（リリース別メンバー画像）表示 |

## 4. テーブル一覧（全43テーブル）

### マスタ系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_members` | メンバーマスタ | member |
| `hn_colors` | メンバーカラーマスタ | member |
| `hn_member_images` | メンバー画像 | member |

### コンテンツ系（楽曲・リリース）
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_releases` | リリース（シングル/アルバム等） | music |
| `hn_release_editions` | リリースエディション（通常盤/初回盤等） | music |
| `hn_release_member_images` | リリース別アーティスト写真 | music |
| `hn_songs` | 楽曲 | music |
| `hn_song_members` | 楽曲×歌唱メンバー | music |
| `hn_song_media_links` | 楽曲×メディア紐付け | music |

### メディア系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `com_media_assets` | メディアアセット共通（YouTube/TikTok） | media |
| `hn_media_metadata` | Hinata固有メディアメタデータ | media |
| `hn_media_members` | メディア×出演メンバー | media |
| `hn_media_categories` | メディアカテゴリ | media |
| `hn_media_hashtags` | メディアハッシュタグ | media |

### イベント系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_events` | イベント | event |
| `hn_event_series` | イベントシリーズ | event |
| `hn_event_members` | イベント×出演メンバー | event |
| `hn_event_movies` | イベント×関連動画 | event |
| `hn_event_attendance` | イベント参戦記録 | event |
| `hn_event_applications` | イベント応募管理 | event |
| `hn_event_guide_songs` | ライブガイド楽曲 | event |
| `hn_event_shadow_narrations` | 影ナレーション | event |
| `hn_event_shadow_narration_members` | 影ナレ×担当メンバー | event |
| `hn_user_events_status` | ユーザー×イベント参加ステータス | event |

### セットリスト系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_setlists` | セットリスト | event |
| `hn_setlist_centers` | セットリストセンター | event |

### ミーグリ系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_meetgreet_slots` | ミーグリスロット | meetgreet |
| `hn_meetgreet_reports` | ミーグリレポート | meetgreet |
| `hn_meetgreet_report_messages` | レポートメッセージ | meetgreet |
| `hn_meetgreet_report_avatars` | レポートアバター | meetgreet |

### ネタ帳系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_neta` | ネタ（トーク素材） | meetgreet |
| `hn_tags` | タグマスタ | meetgreet |
| `hn_neta_tags` | ネタ×タグ | meetgreet |

### ユーザー系（推し・お気に入り）
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_favorites` | お気に入り（推しメンバー） | member |
| `hn_oshi_images` | 推しギャラリー画像 | member |
| `hn_user_member_profiles` | ユーザー×メンバープロフィール | member |
| `hn_member_activities` | メンバー活動記録 | member |

### 情報収集系（スクレイピング対象）
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_blog_posts` | ブログ記事（スクレイピング） | media |
| `hn_news` | ニュース | media |
| `hn_news_members` | ニュース×関連メンバー | media |
| `hn_schedule` | スケジュール | media |
| `hn_schedule_members` | スケジュール×関連メンバー | media |

### お知らせ系
| テーブル | 論理名 | サブドメイン |
|---------|--------|------------|
| `hn_topics` | トピック | portal |
| `hn_announcements` | お知らせ | portal |

## 5. バッチジョブ一覧

| ジョブ | ファイル | 対象テーブル | 実行方式 |
|--------|---------|------------|---------|
| ブログスクレイピング | `batch/blog_scrape.php` | `hn_blog_posts` | cron/手動 |
| ニューススクレイピング | `batch/news_scrape.php` | `hn_news`, `hn_news_members` | cron/手動 |
| スケジュールスクレイピング | `batch/schedule_scrape.php` | `hn_schedule`, `hn_schedule_members` | cron/手動 |
| YouTube取り込み | `batch/youtube_import.php` | `com_media_assets`, `hn_media_metadata` | cron/手動 |
| YouTubeリフレッシュ | `batch/youtube_refresh.php` | 同上 | cron/手動 |
| TikTok取り込み | `batch/tiktok_client_import.php` | 同上 | cron/手動 |
| TikTokサムネ更新 | `batch/tiktok_thumbnail_refresh.php` | `com_media_assets` | cron/手動 |
| 全バッチ実行 | `batch/run_all.php` | （上記をまとめて実行） | cron |

## 6. 外部連携

| サービス | 用途 | 認証方式 |
|---------|------|---------|
| YouTube Data API v3 | 動画検索・チャンネル動画取得 | APIキー (`YOUTUBE_API_KEY`) |
| TikTok | 動画情報取得 | oEmbed（認証不要） |
| 公式ブログ | ブログ記事スクレイピング | なし（HTML解析） |
| 公式サイト | ニュース・スケジュールスクレイピング | なし（HTML解析） |
| Geocoding API | イベント会場の緯度経度取得 | APIキー |
