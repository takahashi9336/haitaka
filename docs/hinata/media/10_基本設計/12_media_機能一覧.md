# Media（メディア管理） 機能一覧

## 1. 画面一覧
| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| 動画一覧画面 | `www/hinata/media_list.php` → `MediaController::list` → `Views/media_list.php` | 登録済みメディア（YouTube/TikTok/Instagram）をカード形式で一覧表示する。無限スクロール、プラットフォーム・カテゴリ・メンバー・世代・メディアタイプによるフィルタ、ソート（新しい順/古い順/タイトル順）に対応 |
| メディア登録画面 | `www/hinata/media_register.php` → `MediaController::registerPage` → `Views/media_register.php` | YouTube チャンネル動画検索・キーワード検索・URL 貼り付け（oEmbed 自動取得）・CSV/TSV 一括インポートの 4 導線でメディアを登録する（管理者専用） |
| 動画管理（統合）画面 | `www/hinata/media_member_admin.php` / `media_song_admin.php` / `media_settings_admin.php` → `MediaController::mediaAdmin` → `Views/media_admin.php` | メンバー紐付け・楽曲紐付け・カテゴリ/メタデータ設定の 3 タブを統合した管理画面（管理者専用） |

## 2. 機能・アクション一覧
| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| 動画一覧表示 | 画面 | メディアカードを無限スクロールで表示。プラットフォーム・カテゴリ・メンバー・世代・メディアタイプでフィルタ可能 |
| 動画追加ロード | API (GET JSON) | `api/load_more_media.php` → `MediaController::loadMore`。offset/limit/フィルタ条件でページネーション。limit+1 件取得で has_more を判定 |
| メディア登録画面表示 | 画面 | YouTube API 設定状態・プリセットチャンネル・カテゴリ一覧を準備して登録フォームを表示 |
| YouTube チャンネル動画一覧取得 | API (GET JSON) | `api/youtube_channel_videos.php` → `MediaController::youtubeChannelVideos`。チャンネルの uploads プレイリストから動画一覧を取得。登録済み判定付き |
| YouTube キーワード検索 | API (GET JSON) | `api/youtube_search.php` → `MediaController::youtubeSearch`。キーワード+チャンネル絞り込みで動画を検索（quota: 100 units/call） |
| oEmbed 情報取得 | API (POST JSON) | `api/fetch_oembed.php` → `MediaController::fetchOembed`。YouTube/TikTok/Instagram の URL リストから oEmbed 情報を一括取得。重複判定付き |
| CSV/TSV プレビュー | API (POST JSON) | `api/preview_media.php` → `MediaController::preview`。CSV/TSV テキストを解析し、重複チェック・シングル番号からの楽曲紐付け候補を返す |
| CSV/TSV 一括保存 | API (POST JSON) | `api/save_media_bulk.php` → `MediaController::bulkSave`。プレビュー結果をもとに com_media_assets + hn_media_metadata を一括登録 |
| URL 貼り付け一括登録 | API (POST JSON) | `api/bulk_register_media.php` → `MediaController::bulkRegister`。oEmbed 取得済みデータを一括登録。メンバー自動紐付け・TikTok サムネイル保存を実施 |
| メタデータ更新 | API (POST JSON) | `api/update_media_metadata.php` → `MediaController::updateMetadata`。カテゴリ・アップロード日・メディアタイプを更新 |
| メディア削除 | API (POST JSON) | `api/delete_media.php` → `MediaController::deleteMedia`。hn_media_metadata + 関連テーブルを削除。com_media_assets は保持 |
| カテゴリ一覧取得 | API (GET JSON) | `api/list_media_categories.php` → `MediaController::listMediaCategories`。DB 優先、未作成時は定数フォールバック |
| カテゴリ新規作成 | API (POST JSON) | `api/create_media_category.php` → `MediaController::createMediaCategory`。hn_media_categories に INSERT |
| カテゴリ名称変更 | API (POST JSON) | `api/rename_media_category.php` → `MediaController::renameMediaCategory`。hn_media_categories + hn_media_metadata を同時更新 |
| メンバー紐付け取得 | API (GET JSON) | `api/get_media_members.php` → `MediaController::getMediaMembers`。指定メディアに紐づくメンバー一覧を返す |
| メンバー紐付け保存 | API (POST JSON) | `api/save_media_members.php` → `MediaController::saveMediaMembers`。既存を全件削除して洗い替え |
| 楽曲紐付け取得 | API (GET JSON) | `api/get_media_linked_song.php` → `MediaController::getMediaLinkedSong`。指定メディアに紐づく楽曲を 1 件取得 |
| 楽曲紐付け保存 | API (POST JSON) | `api/save_media_song_link.php` → `MediaController::saveMediaSongLink`。hn_song_media_links を洗い替え |
| 楽曲メンバー取得 | API (GET JSON) | `api/get_song_members_for_media.php` → `MediaController::getSongMembersForMedia`。動画に紐づく楽曲の参加メンバーを取得 |
| 紐付け管理用動画一覧 | API (GET JSON) | `api/list_media_for_link.php` → `MediaController::listMediaForLink`。検索・カテゴリ・未紐付け絞り込み付き |
| ハッシュタグ取得 | API (GET JSON) | `api/get_media_hashtags.php`。指定メディアのハッシュタグ一覧を取得 |
| ハッシュタグ保存 | API (POST JSON) | `api/save_media_hashtags.php`。hn_media_hashtags を全件洗い替え |
| ハッシュタグ別メディア一覧 | API (GET JSON) | `api/list_media_by_hashtag.php`。指定ハッシュタグに紐づくメディア一覧を取得（最大 100 件） |
| サムネイルアップロード | API (POST multipart) | `api/upload_thumbnail.php`。画像ファイルをアップロードし URL を返す |
| ブログ画像取得 | API (GET JSON) | `api/get_blog_images.php`。ブログ記事の画像一覧を取得 |
| ブログ画像ダウンロード | API (POST JSON) | `api/download_blog_image.php`。外部ブログ画像をサーバにダウンロード保存 |
| ブログスクレイプ | Batch (CLI/HTTP) | `batch/blog_scrape.php`。公式サイトのブログ記事をスクレイピングし hn_blog_posts に UPSERT。latest / members の 2 モード |
| ニューススクレイプ | Batch (CLI/HTTP) | `batch/news_scrape.php`。公式サイトのニュースをスクレイピングし hn_news に UPSERT。メンバー自動紐付け |
| スケジュールスクレイプ | Batch (CLI/HTTP) | `batch/schedule_scrape.php`。公式サイトのスケジュールをスクレイピングし hn_schedule に UPSERT。メンバー自動紐付け（詳細ページも参照） |
| YouTube 新着インポート | Batch (CLI/HTTP) | `batch/youtube_import.php`。プリセットチャンネルの最新動画を取得し DB 登録。メンバー自動紐付け |
| YouTube メタデータリフレッシュ | Batch (CLI/HTTP) | `batch/youtube_refresh.php`。既存 YouTube 動画の title/thumbnail/description/media_type を最新化 |
| TikTok クライアントインポート | Batch (CLI/HTTP) | `batch/tiktok_client_import.php`。Windows クライアントアプリから URL リストを受信し一括登録。トークン認証 |
| TikTok サムネイルリフレッシュ | Batch (CLI/HTTP) | `batch/tiktok_thumbnail_refresh.php` → `MediaController::refreshTikTokThumbnails`。oEmbed からサムネイルを再取得しサーバ保存 |
| バッチ統合ランナー | Batch (CLI) | `batch/run_all.php`。上記バッチを cron（3 時間ごと）で一括実行。週次タスク（日曜 09:00）も制御 |

## 3. 関連テーブル一覧
| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| com_media_assets | メディアアセット（共通） | メインテーブル (CRUD)。platform + media_key で一意。YouTube/TikTok/Instagram の動画情報を格納 |
| hn_media_metadata | メディアメタデータ（日向坂固有） | メインテーブル (CRUD)。com_media_assets に対する 1:1 拡張。カテゴリ分類の起点 |
| hn_media_members | メディア・メンバー紐付け | 中間テーブル (CRUD)。メディア×メンバーの多対多 |
| hn_media_categories | メディアカテゴリマスタ | マスタテーブル (CRUD)。カテゴリ名と表示順を管理 |
| hn_media_hashtags | メディアハッシュタグ | 中間テーブル (CRUD)。メディア×ハッシュタグの多対多 |
| hn_blog_posts | ブログ記事 | メインテーブル (CR/U)。公式サイトからスクレイピングした記事データ |
| hn_news | ニュース | メインテーブル (CR/U)。公式サイトからスクレイピングしたニュースデータ |
| hn_news_members | ニュース・メンバー紐付け | 中間テーブル (CRUD)。ニュース×メンバーの多対多 |
| hn_schedule | スケジュール | メインテーブル (CR/U)。公式サイトからスクレイピングしたスケジュールデータ |
| hn_schedule_members | スケジュール・メンバー紐付け | 中間テーブル (CRUD)。スケジュール×メンバーの多対多 |
| hn_song_media_links | 楽曲-動画紐付け | 中間テーブル (CRUD)。Music サブドメインとの接続点 |
| hn_members | メンバーマスタ | 参照のみ (Read)。名前・画像・期・カラー情報。メンバー自動紐付けのソース |
| hn_songs | 楽曲マスタ | 参照のみ (Read)。楽曲紐付け時の参照 |
| hn_releases | リリースマスタ | 参照のみ (Read)。楽曲紐付け時のリリース情報参照 |
