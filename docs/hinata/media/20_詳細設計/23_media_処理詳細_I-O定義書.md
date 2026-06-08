# Media（メディア管理） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

### 画面表示
| HTTP | 公開パス (www からの相対) | Controller / 処理 | 認可 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/media_list.php` | `MediaController::list` | ログインユーザー | HTML |
| GET | `hinata/media_register.php` | `MediaController::registerPage` | hinata_admin | HTML |
| GET | `hinata/media_member_admin.php` | `MediaController::mediaAdmin('member')` | hinata_admin | HTML |
| GET | `hinata/media_song_admin.php` | `MediaController::mediaAdmin('song')` | hinata_admin | HTML |
| GET | `hinata/media_settings_admin.php` | `MediaController::mediaAdmin('settings')` | hinata_admin | HTML |

### メディア閲覧 API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/load_more_media.php` | `MediaController::loadMore` | ログイン | `?offset=0&limit=25&platform=&category=&sort=newest&member_id=&generation=&media_type=` | JSON `{status, data[], has_more}` |

### メディア登録 API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/youtube_channel_videos.php` | `MediaController::youtubeChannelVideos` | hinata_admin | `?channel_id=&page_token=&max_results=25` | JSON `{status, data[], next_page_token, prev_page_token, total_results}` |
| GET | `hinata/api/youtube_search.php` | `MediaController::youtubeSearch` | hinata_admin | `?q=&channel_id=&page_token=&max_results=25` | JSON `{status, data[], next_page_token, prev_page_token, total_results}` |
| POST | `hinata/api/fetch_oembed.php` | `MediaController::fetchOembed` | hinata_admin | JSON `{urls: [string]}` (最大 50 件) | JSON `{status, data[]}` |
| POST | `hinata/api/preview_media.php` | `MediaController::preview` | hinata_admin | JSON `{raw_input, default_category}` | JSON `{status, data[], releases[], track_types}` |
| POST | `hinata/api/save_media_bulk.php` | `MediaController::bulkSave` | hinata_admin | JSON `{items: [{url, title, category, release_date, ...}]}` | JSON `{status, saved, skipped, message}` |
| POST | `hinata/api/bulk_register_media.php` | `MediaController::bulkRegister` | hinata_admin | JSON `{items: [{url, title, category, platform, media_key, sub_key, thumbnail_url, ...}]}` | JSON `{status, saved, skipped, auto_linked, message}` |

### メタデータ管理 API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| POST | `hinata/api/update_media_metadata.php` | `MediaController::updateMetadata` | hinata_admin | JSON `{meta_id, category, asset_id?, upload_date?, media_type?}` | JSON `{status, category, upload_date, media_type}` |
| POST | `hinata/api/delete_media.php` | `MediaController::deleteMedia` | hinata_admin | JSON `{meta_id}` | JSON `{status, message}` |

### カテゴリ管理 API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/list_media_categories.php` | `MediaController::listMediaCategories` | ログイン | なし | JSON `{status, data: [string]}` |
| POST | `hinata/api/create_media_category.php` | `MediaController::createMediaCategory` | hinata_admin | JSON `{name}` (64 文字以内) | JSON `{status, name}` |
| POST | `hinata/api/rename_media_category.php` | `MediaController::renameMediaCategory` | hinata_admin | JSON `{old_name, new_name}` | JSON `{status, name}` |

### メンバー紐付け API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/get_media_members.php` | `MediaController::getMediaMembers` | hinata_admin | `?meta_id=` | JSON `{status, data: [{id, name, kana, generation, is_active}]}` |
| POST | `hinata/api/save_media_members.php` | `MediaController::saveMediaMembers` | hinata_admin | JSON `{meta_id, member_ids: [int]}` | JSON `{status, saved}` |

### 楽曲紐付け API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/get_media_linked_song.php` | `MediaController::getMediaLinkedSong` | hinata_admin | `?meta_id=` | JSON `{status, data: {id, release_id, title, ...} or null}` |
| POST | `hinata/api/save_media_song_link.php` | `MediaController::saveMediaSongLink` | hinata_admin | JSON `{meta_id, song_id or null}` | JSON `{status}` |
| GET | `hinata/api/get_song_members_for_media.php` | `MediaController::getSongMembersForMedia` | hinata_admin | `?meta_id=` | JSON `{status, song, members[]}` |
| GET | `hinata/api/list_media_for_link.php` | `MediaController::listMediaForLink` | hinata_admin | `?q=&category=&platform=&media_type=&unlinked_only=&link_type=&limit=100` | JSON `{status, data[]}` |

### ハッシュタグ API
| HTTP | 公開パス | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | `hinata/api/get_media_hashtags.php` | 直接 DB 処理 | hinata_admin | `?meta_id=` | JSON `{status, data: [string]}` |
| POST | `hinata/api/save_media_hashtags.php` | 直接 DB 処理 | hinata_admin | JSON `{meta_id, hashtags: [string]}` | JSON `{status}` |
| GET | `hinata/api/list_media_by_hashtag.php` | 直接 DB 処理 | ログイン | `?hashtag=` | JSON `{status, data[]}` (最大 100 件) |

### バッチ
| 実行方法 | 公開パス | 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| CLI / HTTP | `hinata/batch/blog_scrape.php` | BlogScraper + BlogModel | CLI or hinata_admin | CLI: `[pages] [mode]`、HTTP: `?pages=&mode=` | JSON/stdout `{stats}` |
| CLI / HTTP | `hinata/batch/news_scrape.php` | NewsScraper + NewsModel | CLI or hinata_admin | CLI: `[months] [past]`、HTTP: `?months=&past=` | JSON/stdout `{stats}` |
| CLI / HTTP | `hinata/batch/schedule_scrape.php` | ScheduleScraper + ScheduleModel | CLI or hinata_admin | CLI: `[months] [past]`、HTTP: `?months=&past=` | JSON/stdout `{stats}` |
| CLI / HTTP | `hinata/batch/youtube_import.php` | YouTubeApiClient + MediaAssetModel | CLI or hinata_admin | なし | JSON/stdout `{stats}` |
| CLI / HTTP | `hinata/batch/youtube_refresh.php` | YouTubeApiClient + MediaAssetModel | CLI or hinata_admin | CLI: `[limit]`、HTTP: `?limit=50` | JSON/stdout `{stats}` |
| CLI / HTTP | `hinata/batch/tiktok_client_import.php` | MediaAssetModel | CLI or トークン認証 | CLI: `urls.txt [category]`、HTTP: JSON `{token, account, urls[], category?}` | JSON/stdout `{created, skipped, errors}` |
| CLI / HTTP | `hinata/batch/tiktok_thumbnail_refresh.php` | MediaController::refreshTikTokThumbnails | CLI or hinata_admin | CLI: `[limit]`、HTTP: `?limit=50` | JSON/stdout `{stats}` |
| CLI | `hinata/batch/run_all.php` | 上記バッチを子プロセス実行 | CLI のみ | なし | stdout |

## 2. 処理フロー詳細

### loadMore (動画一覧追加ロード)
1. **リクエスト受け取り・バリデーション**: offset / limit（上限 100）/ フィルタパラメータを取得
2. **WHERE 条件構築**: platform / category / member_id（EXISTS サブクエリ）/ generation（EXISTS サブクエリ）/ media_type を動的に組み立て
3. **SQL 実行**: hn_media_metadata JOIN com_media_assets、ORDER BY は COALESCE(upload_date, created_at)、limit+1 件取得で has_more 判定
4. **サムネイル補完**: YouTube で thumbnail_url が空の場合、media_key から `https://img.youtube.com/vi/{key}/mqdefault.jpg` を生成
5. **レスポンス**: `{status, data[], has_more}`

### bulkRegister (URL 貼り付け一括登録)
1. **リクエスト受け取り・バリデーション**: items 配列（platform, media_key, title 必須）
2. **メンバーマスタ取得**: hn_members 全件を事前ロード（自動紐付け用）
3. **トランザクション開始**
4. **各アイテム処理**:
   a. title を 255 文字に切り詰め、description の UTF-8 正規化
   b. TikTok/Instagram のサムネイル: 外部 URL をサーバにダウンロードし `/uploads/thumbnails/` に保存
   c. `MediaAssetModel::findOrCreateAsset` で com_media_assets を UPSERT
   d. `MediaAssetModel::findOrCreateMetadata` で hn_media_metadata を UPSERT
   e. タイトル + 説明文からメンバー名を自動検出し hn_media_members に INSERT（既存紐付けは保持）
5. **トランザクションコミット**
6. **レスポンス**: `{status, saved, skipped, auto_linked, message}`

### blog_scrape (ブログスクレイプバッチ)
1. **モード判定**: `latest`（全体最新）または `members`（メンバー別）
2. **latest モード**: BlogScraper::scrapeLatest で公式サイト一覧ページを pages 分スクレイピング
3. **members モード**: BlogModel::getCtToMemberIdMap で DB の ct マッピングを取得。空の場合は BlogScraper::discoverMemberCts で公式サイトから ct 値を自動検出
4. **各記事処理**: 記事ごとにメンバー名→member_id マッピング、BlogModel::upsertArticle で article_id ベースの UPSERT
5. **待機**: リクエスト間に 0.5 秒の usleep（レート制限対策）

### youtube_import (YouTube 新着インポートバッチ)
1. **プリセットチャンネル巡回**: YouTubeApiClient::PRESET_CHANNELS（3 チャンネル）を順に処理
2. **各チャンネル**: getUploadsPlaylistId → getPlaylistItems で最新 25 件取得
3. **各動画**: findOrCreateAsset / findOrCreateMetadata で DB 登録。media_type からカテゴリを自動分類（short→SoloPV、live→Live、video→Variety）
4. **メンバー自動紐付け**: 既存紐付けがない場合のみ、タイトル+説明文からメンバー名を検出して INSERT IGNORE
5. **待機**: チャンネル間に 0.5 秒の usleep

### tiktok_client_import (TikTok クライアントインポートバッチ)
1. **認証**: HTTP ヘッダ `X-Hinata-Tiktok-Token` または JSON ボディの `token` フィールドを環境変数 `HINATA_TIKTOK_CLIENT_TOKEN` と hash_equals で比較
2. **URL 解析**: MediaAssetModel::parseUrl で TikTok URL をパース。TikTok 以外はスキップ
3. **登録済み判定**: is_already_registered で com_media_assets + hn_media_metadata の存在チェック
4. **oEmbed 取得**: TikTok oEmbed API でタイトル・サムネイル・公開日時を取得。公開日時は Snowflake ID からの推定
5. **サムネイル保存**: TikTok CDN からサーバにダウンロード（ホットリンク対策）
6. **DB 登録**: findOrCreateAsset（media_type='short'）→ findOrCreateMetadata
7. **メンバー自動紐付け**: oEmbed タイトルからメンバー名検出

### renameMediaCategory (カテゴリ名称変更)
1. **バリデーション**: old_name / new_name 必須、64 文字以内、同名チェック
2. **トランザクション開始**
3. **hn_media_categories 更新**: name カラムを変更。rowCount=0 の場合はロールバック
4. **hn_media_metadata 同期更新**: category カラムの旧名称を新名称に一括更新
5. **トランザクションコミット**

### saveMediaMembers (メンバー紐付け保存)
1. **バリデーション**: meta_id 必須、member_ids は配列
2. **member_ids 正規化**: intval → 正値フィルタ → 重複排除
3. **トランザクション開始**
4. **全件削除**: DELETE FROM hn_media_members WHERE media_meta_id = ?
5. **再挿入**: 各 member_id を INSERT
6. **トランザクションコミット**

### deleteMedia (メディア削除)
1. **バリデーション**: meta_id 必須、対象メディアの存在確認
2. **トランザクション開始**
3. **関連テーブル削除**: hn_song_media_links → hn_media_members → hn_media_metadata の順に DELETE
4. **com_media_assets は保持**: 他機能（イベント動画等）から参照される可能性があるため削除しない
5. **トランザクションコミット**
