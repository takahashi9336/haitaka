# メンバー（Member） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 認可 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| GET | hinata/members.php | MemberController::index | ログイン必須 | なし | HTML |
| GET | hinata/members.php?action=detail&id=X | MemberController::detail | ログイン必須 | クエリ id | JSON |
| GET | hinata/member_admin.php | MemberController::admin | admin/hinata_admin | ?member_id=X (任意) | HTML |
| POST | hinata/api/save_member.php | MemberController::save | admin/hinata_admin | multipart/form-data | JSON |
| POST | hinata/api/save_member_basic.php | MemberController::saveBasic | admin/hinata_admin | POST form | JSON |
| POST | hinata/api/save_member_basic_bulk.php | MemberController::saveBasicBulk | admin/hinata_admin | JSON body | JSON |
| POST | hinata/api/save_member_activity.php | MemberController::saveActivity | admin/hinata_admin | multipart/form-data | JSON |
| POST | hinata/api/delete_member_activity.php | MemberController::deleteActivity | admin/hinata_admin | JSON body | JSON |
| GET | hinata/oshi_settings.php | OshiController::settings | ログイン必須 | なし | HTML |
| GET | hinata/member.php?id=X | OshiController::memberPage | ログイン必須 | クエリ id | HTML |
| GET | hinata/oshi_member.php?id=X | (リダイレクト) | - | クエリ id | 301 → member.php?id=X |
| POST | hinata/api/toggle_favorite.php | TalkController::toggleFavorite | ログイン必須 | JSON body | JSON |
| GET | hinata/api/oshi_data.php | OshiController::oshiData | ログイン必須 | なし | JSON |
| GET | hinata/api/get_oshi_timeline.php | (スタンドアロン) | ログイン必須 | クエリ member_id, offset, limit | JSON |
| POST | hinata/api/oshi_image_upload.php | (スタンドアロン) | ログイン必須 | multipart/form-data | JSON |
| POST | hinata/api/oshi_image_delete.php | (スタンドアロン) | ログイン必須 | JSON body | JSON |
| POST | hinata/api/oshi_image_sort.php | (スタンドアロン) | ログイン必須 | JSON body | JSON |
| POST | hinata/api/save_member_profile_image.php | (スタンドアロン) | ログイン必須 | multipart/form-data | JSON |
| GET | hinata/api/get_members_for_select.php | (スタンドアロン) | ログイン必須 | なし | JSON |
| GET | hinata/penlight.php | PenlightController::index | ログイン必須 | なし | HTML |

## 2. 処理フロー詳細

### GET hinata/members.php (MemberController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。未ログインなら `/login.php` へリダイレクト。

2. **データ取得**:
   - `MemberModel::getMembersForBook()` で全メンバー一覧を取得。
   - 結合: `hn_members` LEFT JOIN `hn_colors` (c1, c2) LEFT JOIN `hn_favorites` (ユーザーの推しレベル)。
   - PV動画キーをサブクエリで取得（`hn_media_members` → `hn_media_metadata` → `com_media_assets` の SoloPV）。
   - `getMemberImagesMap()` で全メンバーの複数画像を一括取得し、先頭画像を `image_url` に設定。

3. **レスポンス**:
   - `Views/members.php` をレンダリング。`$members`, `$user` をViewに渡す。
   - JS側でフィルタ (期生/卒業)、ソート (期生順/名前順/生年月日順/身長順)、表示切替 (カード/リスト) を処理。

### GET hinata/members.php?action=detail&id=X (MemberController::detail)

1. **認証チェック**:
   - `$this->auth->check()` で確認。未認証なら HTTP 401 + JSON エラー。

2. **入力パラメータ**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | メンバーID。0 は有効（長濱ねる） |

3. **処理**:
   - `MemberModel::getMemberDetail((int)$id)` で詳細情報を取得。
   - カラー情報、PV動画キー・タイトル（サブクエリ）、ユーザーの推しレベル（サブクエリ）、複数画像を含む。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": { ...メンバー詳細... }}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/member_admin.php (MemberController::admin)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。権限不足なら `/hinata/` へリダイレクト。

2. **データ取得**:
   - `MemberModel::getAllWithColors()`: 全メンバー一覧（カラー・複数画像付き）
   - `MemberModel::getColorMaster()`: カラーマスタ全件
   - `MemberActivityModel::getAllGroupedByMember(false)`: 全メンバーの個人活動（非アクティブ含む）を member_id => [activities] マップで取得
   - `MemberActivityModel::CATEGORIES`: カテゴリ定義定数

3. **レスポンス**:
   - `Views/member_admin.php` をレンダリング。
   - `$members`, `$colors`, `$activitiesByMember`, `$activityCategories`, `$user` をViewに渡す。

### POST hinata/api/save_member.php (MemberController::save)

1. **認証チェック**:
   - `Auth::check()` && ロールが `admin` または `hinata_admin` であることを確認。不正なら HTTP 403。

2. **入力パラメータ (multipart/form-data)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | メンバーID |
   | name | string | 必須 | メンバー名 |
   | kana | string | - | かな |
   | generation | int | - | 期生 (0-5) |
   | birth_date | string (YYYY-MM-DD) | - | 生年月日。空なら NULL |
   | blood_type | string | - | 血液型 |
   | height | float | - | 身長 (cm)。空なら NULL |
   | birth_place | string | - | 出身地 |
   | color_id1 | int | - | サイリウムカラー1。空なら NULL |
   | color_id2 | int | - | サイリウムカラー2。空なら NULL |
   | blog_url | string | - | ブログURL |
   | insta_url | string | - | Instagram URL |
   | twitter_url | string | - | X(Twitter) URL |
   | member_info | string | - | メンバー情報メモ |
   | is_active | int (0\|1) | 必須 | 現役フラグ |
   | pv_youtube_url | string | - | YouTube紹介動画URL |
   | image_file_0 ~ image_file_4 | file | - | 画像ファイル (最大5枚) |
   | image_existing[] | string[] | - | 既存画像ファイル名の配列 |

3. **処理** (トランザクション内):
   1. **画像アップロード**: スロット0-4を順に処理。新規ファイルがあれば `member_{id}_{slot}.{ext}` で保存。既存画像はファイル名をそのまま維持。削除された旧画像は物理ファイルも `unlink`。
   2. **YouTube紹介動画**: `pv_youtube_url` が指定されている場合、`MediaAssetModel::parseUrl()` でURLを解析し、`findOrCreateAsset()` → `findOrCreateMetadata()` (category=SoloPV) → `hn_media_members` に INSERT IGNORE で紐付け。
   3. **データ更新**: `MemberModel::update($id, $data)` で `hn_members` を更新。`update_user` にセッションのユーザーID名を記録。
   4. **画像テーブル更新**: `MemberModel::saveMemberImages($id, $savedImages)` で hn_member_images を洗い替え。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}` (トランザクション rollback)

### POST hinata/api/save_member_basic.php (MemberController::saveBasic)

1. **認証チェック**: admin/hinata_admin のみ。

2. **入力パラメータ (POST form)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | メンバーID |
   | name | string | - | メンバー名（未指定時は現在値を維持） |
   | kana | string | - | かな |
   | generation | int | - | 期生 |
   | color_id1 | int | - | サイリウムカラー1 |
   | color_id2 | int | - | サイリウムカラー2 |
   | blog_url | string | - | ブログURL |
   | insta_url | string | - | Instagram URL |
   | twitter_url | string | - | X(Twitter) URL |
   | member_info | string | - | メンバー情報メモ |
   | is_active | int (0\|1) | - | 現役フラグ |

3. **処理**:
   - 現在のメンバー情報を `find()` で取得。未指定パラメータは現在値を維持。
   - `MemberModel::update($id, $data)` で更新。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_member_basic_bulk.php (MemberController::saveBasicBulk)

1. **認証チェック**: admin/hinata_admin のみ。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | items | array | 必須 | 各要素は save_member_basic と同じフィールドを持つオブジェクト |

3. **処理**:
   - `items` 配列をループし、各メンバーの `find()` + `update()` を実行。`id` が空、またはメンバーが見つからない場合はスキップ（エラーにしない）。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_member_activity.php (MemberController::saveActivity)

1. **認証チェック**: admin/hinata_admin のみ。

2. **入力パラメータ (multipart/form-data)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID |
   | activity_id | int | - | 更新時のみ。0または未指定で新規作成 |
   | category | string | - | カテゴリ。DEFAULT 'other' |
   | title | string | - | 活動名 |
   | description | string | - | 概要 |
   | url | string | - | 誘導先URL |
   | url_label | string | - | リンクボタンラベル |
   | image_existing | string | - | 既存サムネイル画像ファイル名 |
   | activity_image | file | - | 新規サムネイル画像ファイル |
   | is_active | int (0\|1) | - | 表示フラグ。DEFAULT 1 |
   | sort_order | int | - | 表示順。DEFAULT 0 |
   | start_date | string (YYYY-MM-DD) | - | 開始日 |
   | end_date | string (YYYY-MM-DD) | - | 終了日 |

3. **処理**:
   - 画像アップロード: 新規ファイルがあれば `activity_{memberId}_{timestamp}.{ext}` で保存。既存サムネイルが新ファイルで置き換わる場合は旧ファイルを `unlink`。
   - `MemberActivityModel::saveActivity($data)`: `activity_id > 0` なら UPDATE、そうでなければ INSERT。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "id": 保存後のID}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/delete_member_activity.php (MemberController::deleteActivity)

1. **認証チェック**: admin/hinata_admin のみ。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | 活動ID |

3. **処理**:
   - `MemberActivityModel::find($id)` で活動を取得。`image_url` がある場合は物理ファイルを `unlink`。
   - `MemberActivityModel::delete($id)` でレコード削除。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/oshi_settings.php (OshiController::settings)

1. **認証チェック**:
   - `$this->auth->requireLogin()`

2. **データ取得**:
   - `MemberModel::getActiveMembersWithColors()`: 現役メンバー一覧（カラー・推しレベル付き）
   - `FavoriteModel::getUserFavorites()`: ユーザーの全お気に入り（メンバー名・かな・期生・画像付き）
   - `FavoriteModel::getOshiMembers()`: 推し3名の詳細（カラー・ユーザー設定プロフィール画像付き）

3. **レスポンス**:
   - `Views/oshi_settings.php` をレンダリング。
   - `$members`, `$favorites`, `$oshiMembers`, `$user` をViewに渡す。

### GET hinata/member.php?id=X (OshiController::memberPage)

1. **認証チェック**:
   - `$this->auth->requireLogin()`。id パラメータ未指定時は `/hinata/` へリダイレクト。

2. **入力パラメータ**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | メンバーID。0 は有効 |

3. **データ取得** (各処理は try-catch で保護):
   - `MemberModel::getMemberDetail($memberId)`: メンバー詳細情報。存在しない場合は `/hinata/` へリダイレクト
   - `FavoriteModel::getMemberLevel($memberId)`: 推しレベル
   - `getMemberSongs($memberId)`: 参加楽曲一覧 (`hn_song_members` JOIN `hn_songs` JOIN `hn_releases`)。リリース日降順 → トラック番号昇順
   - `getMemberSoloVideos($memberId)`: ソロ出演動画（SoloPV カテゴリ、または紐づけメンバーが1名のみ。最大20件）
   - `getMemberYoutubeGroupVideos($memberId)`: YouTube参加動画（全件。最大20件）
   - `getMemberVideosByPlatform($memberId, 'instagram')`: Instagram動画（最大20件）
   - `getMemberVideosByPlatform($memberId, 'tiktok')`: TikTok動画（最大20件）
   - `getMemberEvents($memberId)`: 参加イベント一覧（最大20件、日付降順）
   - `getOshiImages($memberId)`: マイフォト画像（ユーザー+メンバー単位、sort_order昇順）
   - `getMemberNeta($memberId)`: ミーグリネタ（status!='delete'、content/memo を `Encryption::decrypt()` で復号）
   - `BlogModel::getLatestByMember($memberId, 10)`: 最新ブログ10件
   - `NewsModel::getLatestByMember($memberId, 5)`: 最新ニュース5件
   - `ScheduleModel::getUpcomingByMember($memberId, 5)`: 今後のスケジュール5件
   - `MemberActivityModel::getByMember($memberId, true)`: 個人活動（アクティブのみ）
   - `getUserMemberProfileImage($memberId)`: ユーザー固有プロフィール画像パス

4. **レスポンス**:
   - `Views/oshi_member.php` をレンダリング。
   - 全取得データ + `$user` をViewに渡す。

### POST hinata/api/toggle_favorite.php (TalkController::toggleFavorite)

1. **認証チェック**: `Auth::check()` で確認。未認証なら HTTP 401。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID。0 は有効 |
   | level | int | - | 指定レベル。未指定時はトグル動作（現在>0なら0、0なら1） |

3. **処理**:
   - `level` 未指定時: 現在レベルを取得し、0ならば1に、0以外ならば0にトグル。
   - `level` 指定時: `FavoriteModel::setLevel($memberId, $level)` で直接設定。
   - level 7-9 は排他制御: 同レベルの既存メンバーを自動解除し、`swapped_member_id` / `swapped_member_name` を返却。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "level": 設定後のレベル}`
   - 排他発生時: `{"status": "success", "level": ..., "swapped_member_id": ..., "swapped_member_name": "..."}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/oshi_data.php (OshiController::oshiData)

1. **認証チェック**: ログイン必須。

2. **処理**:
   - `FavoriteModel::getOshiPortalSummary()` で推し3名のサマリを取得。
   - 各メンバーに最新動画（`getLatestVideosByMembers`）、次イベント（`getNextEventsByMembers`）、参加楽曲数（`getSongCountsByMembers`）を付加。

3. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": [推しメンバーサマリ配列]}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/get_oshi_timeline.php

1. **認証チェック**: `Auth::check()` で確認。未認証なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID |
   | offset | int | - | 取得開始位置。DEFAULT 0 |
   | limit | int | - | 取得件数。DEFAULT 15、MAX 50 |

3. **処理**:
   - 5つのデータソースを UNION ALL で結合し、`event_date DESC` でソート:
     - **blog**: `hn_blog_posts` (member_id 直接)
     - **news**: `hn_news` JOIN `hn_news_members`
     - **schedule**: `hn_schedule` JOIN `hn_schedule_members` (当日以前のみ)
     - **event**: `hn_events` JOIN `hn_event_members` (当日以前のみ)
     - **video**: `hn_media_members` JOIN `hn_media_metadata` JOIN `com_media_assets` (extra に JSON_OBJECT で media_key, platform, sub_key, category, description を格納)

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": [タイムラインアイテム配列]}`
   - 各アイテム: `{type, id, title, thumbnail_url, event_date, url, extra}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/oshi_image_upload.php

1. **認証チェック**: ログイン必須。

2. **入力パラメータ (multipart/form-data)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID |
   | image | file | 必須 | 画像ファイル (JPEG, PNG, WebP) |
   | caption | string | - | キャプション |

3. **処理**:
   - MIME タイプチェック (`OshiImageModel::ALLOWED_TYPES`)
   - 枚数チェック (`countByMember()` >= 10 でエラー)
   - アップロードディレクトリ取得/作成 (`getUploadDir()`: `uploads/oshi/{user_id}/{member_id}`)
   - `OshiImageModel::resizeImage()` で長辺1200px以下にリサイズして保存
   - `OshiImageModel::saveImage()` でDBレコード挿入

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "id": 画像ID, "image_path": "uploads/oshi/..."}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/oshi_image_delete.php

1. **認証チェック**: ログイン必須。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | 画像ID |

3. **処理**:
   - `OshiImageModel::deleteImage($imageId)`: user_id チェック → 物理ファイル削除 → DBレコード削除。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "削除に失敗しました"}`

### POST hinata/api/oshi_image_sort.php

1. **認証チェック**: ログイン必須。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | order | int[] | 必須 | 画像IDの配列。インデックスが sort_order になる |

3. **処理**:
   - `order` 配列をループし、各画像IDの `sort_order` を UPDATE（`user_id` チェック付き）。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "order 配列が必要です"}`

### POST hinata/api/save_member_profile_image.php

1. **認証チェック**: ログイン必須。

2. **入力パラメータ (multipart/form-data)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID |
   | image | file | 必須 | 画像ファイル (JPEG, PNG, WebP) |

3. **処理**:
   - MIME タイプチェック
   - アップロードディレクトリ: `uploads/member_profile/{user_id}/`
   - `OshiImageModel::resizeImage()` でリサイズして保存
   - 既存レコードがある場合は旧画像を物理削除
   - `hn_user_member_profiles` に `INSERT ... ON DUPLICATE KEY UPDATE` で UPSERT

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "image_path": "uploads/member_profile/..."}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/get_members_for_select.php

1. **認証チェック**: `Auth::check()` で確認。未認証なら HTTP 401。

2. **処理**:
   - `MemberModel::getActiveMembersWithColors()` で現役メンバー一覧を取得。
   - 各メンバーから `id`, `name`, `favorite_level`, `generation` のみを抽出して返却。

3. **出力 (JSON)**:
   - 成功: `{"status": "success", "members": [{id, name, favorite_level, generation}, ...]}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/penlight.php (PenlightController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()`

2. **データ取得**:
   - `MemberModel::getMembersForBook()`: メンバー一覧（カラー付き）
   - ポカ（`MemberGroupHelper::POKA_MEMBER_ID`）をフィルタ除外
   - `ReleaseModel::getAllReleases()` から最新シングルの `release_id` を特定
   - `ReleaseMemberImageModel::getMapByReleaseId($releaseId)` でメンバーアー写マップを取得
   - 各メンバーに `latest_single_artist_image` を付与

3. **レスポンス**:
   - `Views/penlight.php` をレンダリング。`$members`, `$user` をViewに渡す。

## 3. クライアントサイド処理

### メンバー帳 (members.php)

- **フィルタ/ソート**: JS側で `currentGen`, `viewMode`, `showGraduates`, `currentSortOrder`, `isAscending` の状態管理。DOM操作でカード/リストの表示/非表示を切り替え。
- **メンバー詳細モーダル**: カードクリックで `fetch('/hinata/members.php?action=detail&id=X')` を呼び出し、モーダル内にメンバー詳細を描画。
- **メンバーグループJS** (`hinata-member-groups.js`): 期生グループヘッダーの表示制御。
- **メンバーモーダルJS** (`hinata-member-modal.js`): モーダルの表示・非表示・データバインド処理。

### メンバー管理 (member_admin.php)

- **タブ切替**: 詳細編集タブと一覧編集タブの表示切替。
- **メンバー選択**: 右カラムのメンバーリストクリックでフォームにデータをバインド。初期状態はフォーム無効 (`opacity-30 pointer-events-none`)。
- **詳細保存**: FormDataを構築し `fetch('/hinata/api/save_member.php')` へ POST (multipart/form-data)。
- **一覧個別保存**: FormDataを構築し `fetch('/hinata/api/save_member_basic.php')` へ POST。
- **一覧一括保存**: 全行のデータを JSON 配列化し `fetch('/hinata/api/save_member_basic_bulk.php')` へ POST。
- **個人活動CRUD**: FormData(multipart)で `save_member_activity.php` へ POST。削除は JSON で `delete_member_activity.php` へ POST。

### 推し設定 (oshi_settings.php)

- **レベル選択**: セレクトボックス変更時に `fetch('/hinata/api/toggle_favorite.php')` へ JSON POST（`{member_id, level}`）。
- **排他通知**: `swapped_member_name` が返却された場合、トースト通知「XXX の推しランクが解除されました」を表示。
- **推しスロット更新**: 設定変更後にスロット表示をDOM更新。

### メンバー個別ページ (member.php / oshi_member.php)

- **タイムライン**: 初回表示時と「もっと見る」クリック時に `fetch('/hinata/api/get_oshi_timeline.php?member_id=X&offset=N&limit=15')` を呼び出し、タイムラインアイテムを追加描画。
- **マイフォト**: ドラッグ&ドロップ / ファイル選択で `oshi_image_upload.php` へ multipart POST。削除は `oshi_image_delete.php` へ JSON POST。並び替えは `oshi_image_sort.php` へ JSON POST。
- **プロフィール画像変更**: ホバーで「変更」ボタン表示。ファイル選択で `save_member_profile_image.php` へ multipart POST。
- **推しレベル変更**: `toggle_favorite.php` へ JSON POST。
- **ミーグリネタ**: インライン追加フォームで `save_neta.php` へ POST。ステータス切替はチェックボックス変更で API 呼び出し。
