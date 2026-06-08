# Music（楽曲・リリース・アーティスト写真） 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/songs.php | SongController::index | ?tab=songs\|releases, ?group=, ?release_id= | HTML |
| GET | hinata/song.php | SongController::detail | ?id={song_id}, ?from=, ?release_id=, ?event_id= | HTML |
| GET | hinata/song_member_edit.php | SongController::memberEdit | ?song_id={song_id} | HTML |
| POST | hinata/api/save_song_members.php | SongController::saveMembers | JSON body | JSON |
| POST | hinata/api/save_song_streaming.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/release.php | ReleaseController::show | ?id={release_id} | HTML |
| GET | hinata/release_admin.php | ReleaseController::admin | なし | HTML |
| POST | hinata/api/save_release.php | ReleaseController::save | JSON body | JSON |
| POST | hinata/api/delete_release.php | ReleaseController::delete | JSON body | JSON |
| GET | hinata/api/detail_release.php | ReleaseController::detail | ?id={release_id} | JSON |
| POST | hinata/api/save_release_song_order.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/release_artist_photos.php | ReleaseController::artistPhotos | ?release_id={release_id} | HTML |
| POST | hinata/api/save_release_member_images.php | ReleaseController::saveReleaseMemberImages | JSON body | JSON |
| POST | hinata/api/upload_release_jacket.php | (スタンドアロン) | multipart/form-data | JSON |
| POST | hinata/api/upload_release_artist_photo.php | (スタンドアロン) | multipart/form-data | JSON |
| GET | hinata/artist_photos.php | ArtistPhotoController::index | ?tab=releases\|members, ?release_id=, ?member_id= | HTML |

## 2. 処理フロー詳細

---

### GET hinata/songs.php (SongController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。未ログインなら `/login.php` へリダイレクト。

2. **データ取得**:
   - `ReleaseModel::getAllReleasesWithSummary()` で全リリース一覧を取得（版別情報 `editions[]`、収録曲数 `song_count` 付き）。
   - クエリパラメータ `group` が `hinatazaka46` または `hiragana_keyaki` の場合、`group_name` でフィルタリング。
   - 各リリースに対して `SongModel::getTitleTrackCenterNames(release_id)` を呼び出し、表題曲のセンター名一覧を付与。
   - 全曲タブ用: フィルタ済みリリースを順に走査し、`SongModel::getAllSongsWithRelease(release_id)` で楽曲リストを取得。`hn_release_editions` から type_a ジャケット URL を優先取得（なければ先頭版のジャケット）。`release_id` パラメータ指定時は当該リリースのみに絞り込み。
   - 定数ロード: `ReleaseModel::RELEASE_TYPES`, `ReleaseModel::GROUP_NAMES`, `ReleaseEditionModel::EDITIONS`, `SongModel::TRACK_TYPES_DISPLAY`。

3. **レスポンス**:
   - `Views/song_index.php` をレンダリング。`$_GET['tab']` が `songs` なら全曲タブ、それ以外はリリースタブをデフォルト表示。
   - 推しメンバー（`$_SESSION['oshi']`）の参加曲を `bg-amber-50/60` でハイライト。

---

### GET hinata/song.php (SongController::detail)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。

2. **バリデーション**:
   - `$_GET['id']` が未指定または 0 の場合、`/hinata/songs.php` へリダイレクト。

3. **データ取得**:
   - `SongModel::getSongWithMembers(songId)` で楽曲情報 + 参加メンバー一覧（`hn_song_members` JOIN `hn_members` JOIN `hn_colors`）を取得。楽曲が見つからない場合は `/hinata/songs.php` へリダイレクト。
   - `ReleaseModel::find(release_id)` で収録リリース情報を取得。
   - `SongModel::getFormation(songId)` でフォーメーション情報（`row_1` ～ `row_5`, `other`）を取得。
   - `SongModel::getCenterMembers(songId)` でセンターメンバー（ダブルセンター対応）を取得。
   - `ReleaseMemberImageModel::getMapByReleaseId(release_id)` でリリース単位のアー写マップ（`member_id => image_url`）を取得。フォーメーション表示時にメンバー既定画像より優先される。画像は `object-cover object-top` で表示し、顔（頭部）が切れないようにする。
   - **フォーメーション表示判定**: 全メンバーの `position` が非NULL の場合のみ `$showFormation = true`。一人でも NULL なら非表示。
   - `SongModel::getMediaLinksBySongId(songId)` で楽曲に紐づく動画一覧を取得し、カテゴリ別にグループ化。MV を最優先ソート。
   - ライブ披露履歴: `hn_setlists` JOIN `hn_events` で `song_id` に該当するイベントを `event_date DESC` で最大 20 件取得（直接SQL）。

4. **戻り先URL決定**:
   - `from=release` & `release_id` 指定 → `release.php?id={release_id}`
   - `from=live_guide` & `event_id` 指定 → `live_guide.php?event_id={event_id}`
   - `from=songs` → `songs.php?tab=songs`
   - その他 → `songs.php`

5. **レスポンス**:
   - `Views/song_detail.php` をレンダリング。セクションカード群（基本情報、クレジット、試聴、メイン動画、関連動画、参加メンバー、フォーメーション、ライブ披露、メモ）を表示。

---

### GET hinata/song_member_edit.php (SongController::memberEdit)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限（`admin` または `hinata_admin`）を確認。権限不足なら `/hinata/` へリダイレクト。

2. **バリデーション**:
   - `$_GET['song_id']` が未指定または 0 の場合、`/hinata/songs.php` へリダイレクト。

3. **データ取得**:
   - `SongModel::find(songId)` で楽曲情報を取得。見つからない場合はリダイレクト。
   - `ReleaseModel::find(release_id)` で収録リリース情報。
   - `SongMemberModel::getBySongIdWithNames(songId)` で現在の参加メンバー一覧（メンバー名・画像URL付き）。
   - `MemberModel::getAllWithColors()` で全メンバー一覧（期・カラー付き）。
   - `ReleaseMemberImageModel::getMapByReleaseId(release_id)` でリリースのアー写マップを取得し、`$allMembers` の各メンバーの `image_url` をアー写で上書き（アー写未登録のメンバーは `null` とし、人アイコンを表示）。

4. **レスポンス**:
   - `Views/song_member_edit.php` をレンダリング。フォーメーション編集（D&D）タブとリスト編集タブの 2 タブ構成。

---

### POST hinata/api/save_song_members.php (SongController::saveMembers)

1. **認証チェック**:
   - `Auth::check()` && `HinataAuth::isHinataAdmin()` を確認。不正なら HTTP 403 + JSON エラー。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | song_id | int | 必須 | 対象楽曲ID |
   | members | array | - | メンバー配列（空配列で全員削除） |
   | members[].member_id | int | 必須 | メンバーID（0以下はスキップ） |
   | members[].is_center | bool | - | センターフラグ。ダブルセンター時は複数 true |
   | members[].row_number | int\|null | - | 列番号（1-5）。範囲外は null に正規化 |
   | members[].position | int\|null | - | 列内位置（左端=1）。1未満は null に正規化 |
   | members[].part_description | string\|null | - | パート説明 |

3. **処理**:
   - `SongModel::find(songId)` で楽曲存在確認。見つからなければ例外。
   - `SongMemberModel::bulkInsertMembers(songId, members)`: トランザクション内で既存メンバーを DELETE → 新規一括 INSERT（洗い替え方式）。
   - `Logger::info()` で操作ログ出力（song_id、件数、操作者）。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "inserted": N}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

---

### POST hinata/api/save_song_streaming.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | song_id | int | 必須 | 対象楽曲ID |
   | apple_music_url | string | - | Apple Music URL。空文字は NULL に変換 |
   | spotify_url | string | - | Spotify URL。空文字は NULL に変換 |

3. **処理**:
   - `SongModel::find(songId)` で楽曲存在確認。
   - `SongModel::update(songId, data)` で `apple_music_url`, `spotify_url`, `update_user` を更新。
   - `Logger::info()` で操作ログ出力。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "ストリーミングURLを保存しました"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

---

### GET hinata/release.php (ReleaseController::show)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。

2. **バリデーション**:
   - `$_GET['id']` が未指定または 0 の場合、`/hinata/songs.php` へリダイレクト。

3. **データ取得**:
   - `ReleaseModel::getReleaseWithSongs(releaseId)` でリリース情報 + 収録曲一覧 + 版別情報（editions）を取得。見つからない場合はリダイレクト。
   - 収録曲には `hn_media_metadata` / `com_media_assets` からの `media_key`, `thumbnail_url`, `category` が JOIN される。

4. **レスポンス**:
   - `Views/release_show.php` をレンダリング。基本情報カード + 収録曲一覧 + 管理者専用セクション（楽曲順序編集、ストリーミングURL一括編集）。

---

### GET hinata/release_admin.php (ReleaseController::admin)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。

2. **データ取得**:
   - `ReleaseModel::getAllReleases()` で全リリース一覧（リリース日降順）。
   - `ReleaseEditionModel::getEditionsByReleaseIds(releaseIds)` で全リリースの版別情報をまとめて取得。
   - `MemberModel::getAllWithColors()` で全メンバー一覧。

3. **レスポンス**:
   - `Views/release_admin.php` をレンダリング。一覧テーブル + 新規登録/編集モーダル。

---

### POST hinata/api/save_release.php (ReleaseController::save)

1. **認証チェック**:
   - エントリ PHP で `Auth::check()` + ロール確認（`admin` / `hinata_admin`）。不正なら HTTP 401 / 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | - | 更新時のみ。未指定で新規作成 |
   | release_type | enum | - | 種別 (single/album/digital/ep/best)。DEFAULT 'single' |
   | group_name | enum | - | グループ名。無効値は 'hinatazaka46' にフォールバック |
   | release_number | string | - | リリース番号 ('1st', '2nd' 等) |
   | title | string | 必須 | タイトル（空文字は例外スロー） |
   | title_kana | string | - | よみがな |
   | release_date | date (YYYY-MM-DD) | - | 発売日 |
   | description | string | - | 説明・備考 |
   | editions | array | - | 版別情報配列 |
   | editions[].edition | enum | 必須 | 版種別 (type_a/type_b/type_c/type_d/normal) |
   | editions[].jacket_image_url | string | - | ジャケット画像URL |
   | editions[].sort_order | int | - | 表示順 |
   | songs | array | - | 収録曲配列（キーが存在する場合のみ同期） |
   | songs[].id | int | - | 既存楽曲の更新時のみ |
   | songs[].title | string | 必須 | 曲名（空文字はスキップ） |
   | songs[].title_kana | string | - | よみがな |
   | songs[].track_type | enum | - | トラック種別。無効値は 'other' にフォールバック |
   | songs[].track_number | int | - | トラック番号 |
   | songs[].media_meta_id | int | - | メディアメタID |
   | songs[].lyricist | string | - | 作詞 |
   | songs[].composer | string | - | 作曲 |
   | songs[].duration | int | - | 再生時間（秒） |
   | songs[].memo | string | - | 備考 |
   | songs[].apple_music_url | string | - | Apple Music URL |
   | songs[].spotify_url | string | - | Spotify URL |
   | songs[].members | array | - | 楽曲参加メンバー（存在時は bulkInsertMembers で洗い替え） |

3. **処理**:
   - トランザクション開始。
   - `id` 指定時: `ReleaseModel::update(id, data)` で更新。未指定時: `ReleaseModel::create(data)` で新規作成し `lastInsertId()` 取得。
   - `editions` 配列がある場合: `ReleaseEditionModel::saveForRelease(releaseId, editions)` で洗い替え保存。
   - `songs` キーが存在する場合:
     - 各楽曲を走査。`songs[].id` がある場合は既存楽曲の更新（リリースID不一致は例外）。ない場合は新規作成。
     - `songs[].members` がある場合は `SongMemberModel::bulkInsertMembers()` で参加メンバーも保存。
     - 更新モード（`id` 指定）の場合: 送信されなかった既存楽曲は DELETE（楽曲の同期削除）。
   - トランザクションコミット。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "release_id": N, "message": "リリース情報を保存しました"}`
   - 失敗: ロールバック + `{"status": "error", "message": "エラーメッセージ"}`

---

### POST hinata/api/delete_release.php (ReleaseController::delete)

1. **認証チェック**:
   - エントリ PHP で `Auth::check()` + ロール確認。不正なら HTTP 401 / 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | 削除対象のリリースID |

3. **処理**:
   - `ReleaseModel::delete(releaseId)` を実行。`hn_releases` → `hn_songs` は ON DELETE CASCADE のため、収録曲も連鎖削除される。さらに `hn_song_members` も CASCADE で削除。
   - `Logger::info()` で操作ログ出力。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "削除しました"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

---

### GET hinata/api/detail_release.php (ReleaseController::detail)

1. **認証チェック**:
   - エントリ PHP で `Auth::check()` + ロール確認。不正なら HTTP 401 / 403。

2. **入力パラメータ (GET クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | リリースID |

3. **処理**:
   - `ReleaseModel::getReleaseWithSongs(releaseId)` でリリース情報 + 収録曲一覧 + 版別情報を取得。見つからなければ例外。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": { ...リリース情報, "songs": [...], "editions": [...] }}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

---

### POST hinata/api/save_release_song_order.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` を確認。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | release_id | int | 必須 | 対象リリースID |
   | songs | array | 必須 | 楽曲順序配列（空配列は例外） |
   | songs[].song_id | int | 必須 | 楽曲ID |
   | songs[].track_number | int | - | トラック番号。0以下は NULL に変換 |

3. **処理**:
   - トランザクション開始。
   - 各楽曲について `SongModel::find(songId)` で存在確認し、`release_id` が一致しない場合はスキップ（別リリースの楽曲は更新不可）。
   - `SongModel::update(songId, { track_number, update_user })` でトラック番号を更新。
   - トランザクションコミット。`Logger::info()` で操作ログ出力。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "N件の順序を保存しました", "updated": N}`
   - 失敗: ロールバック + HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

---

### GET hinata/release_artist_photos.php (ReleaseController::artistPhotos)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。

2. **バリデーション**:
   - `$_GET['release_id']` が未指定または 0 の場合、`/hinata/release_admin.php` へリダイレクト。

3. **データ取得**:
   - `ReleaseModel::getReleaseWithSongs(releaseId)` でリリース情報。見つからない場合はリダイレクト。
   - `MemberModel::getAllWithColors()` で全メンバー一覧（期別グループ化用）。
   - `ReleaseMemberImageModel::getMapByReleaseId(releaseId)` で既存のメンバー写真マップ（`member_id => image_url`）。

4. **レスポンス**:
   - `Views/release_artist_photos.php` をレンダリング。メンバーごとの URL 入力欄 + ファイル選択ボタンを期別グループ化して表示。

---

### POST hinata/api/save_release_member_images.php (ReleaseController::saveReleaseMemberImages)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | release_id | int | 必須 | 対象リリースID |
   | members | array | - | メンバー写真配列 |
   | members[].member_id | int | 必須 | メンバーID（0以下はスキップ） |
   | members[].image_url | string | - | アー写URL。空文字のメンバーは「登録しない」扱い（フィルタで除外） |

3. **処理**:
   - `image_url` が空文字のメンバーをフィルタで除外。
   - `ReleaseMemberImageModel::saveForRelease(releaseId, rows)`: 既存レコードを DELETE → 新規一括 INSERT（洗い替え方式）。`sort_order` は配列順で 0 から自動採番。
   - `Logger::info()` で操作ログ出力。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "アーティスト写真を保存しました"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

---

### POST hinata/api/upload_release_jacket.php

1. **認証チェック**:
   - `Auth::check()` + ロール確認（`admin` / `hinata_admin`）。不正なら HTTP 401 / 403。

2. **入力 (multipart/form-data)**:
   | フィールド名 | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | file | ファイル | 必須 | 画像ファイル (jpg, jpeg, png, gif, webp) |

3. **処理**:
   - 拡張子チェック（許可: jpg, jpeg, png, gif, webp）。
   - 保存先ディレクトリ `www/assets/img/releases/` を確認、なければ `mkdir` で作成。
   - ファイル名生成: `jacket_YYYYMMDDHHmmss_XXXXXXXX.{ext}`（ランダム 8 文字 hex）。
   - `move_uploaded_file()` で保存。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "url": "/assets/img/releases/jacket_....jpg"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

---

### POST hinata/api/upload_release_artist_photo.php

1. **認証チェック**:
   - `Auth::check()` + ロール確認（`admin` / `hinata_admin`）。不正なら HTTP 401 / 403。

2. **入力 (multipart/form-data)**:
   | フィールド名 | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | file | ファイル | 必須 | アー写画像ファイル (jpg, jpeg, png, gif, webp) |

3. **処理**:
   - 拡張子チェック（許可: jpg, jpeg, png, gif, webp）。
   - 保存先ディレクトリ `www/assets/img/releases/artist/` を確認、なければ `mkdir` で作成。
   - ファイル名生成: `artist_YYYYMMDDHHmmss_XXXXXXXX.{ext}`（ランダム 8 文字 hex）。
   - `move_uploaded_file()` で保存。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "url": "/assets/img/releases/artist/artist_....jpg"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

---

### GET hinata/artist_photos.php (ArtistPhotoController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。

2. **データ取得**:
   - `ReleaseModel::getAllReleasesWithSummary()` で全リリース一覧（版別情報付き）。
   - `MemberModel::getAllWithColors()` で全メンバー一覧。
   - `ReleaseMemberImageModel::getRowsByReleaseIds(releaseIds)` でリリース別のアー写マップ（`release_id => [member_id => image_url]`）。
   - `ReleaseMemberImageModel::getRowsByMemberIds(memberIds)` でメンバー別のアー写マップ（`member_id => [release_id => image_url]`）。

3. **レスポンス**:
   - `Views/artist_photos_index.php` をレンダリング。
   - `$_GET['tab']` が `members` ならメンバー別タブ、それ以外はリリース別タブ。
   - アー写画像は `object-cover object-top` で表示し、顔（頭部）が切れないようにする。
   - `$_GET['member_id']` 指定時はメンバー別タブで該当メンバーへスクロール。

## 3. クライアントサイド処理

### フォーメーション編集 (song_member_edit.php)

- **D&D モード**: 左側のメンバーパネル（期別グループ化）からフォーメーション列（1-5列のドロップゾーン）へドラッグ&ドロップでメンバーを配置。列内の並び順が `position`（左端=1）に対応。
- **リスト編集モード**: テーブル形式で `member_id`, `row_number`, `position`, `is_center`, `part_description` を直接入力。メンバー追加フォームで新規メンバーを選択追加可能。
- **保存**: 「保存」ボタン押下で `App.post('/hinata/api/save_song_members.php', payload)` を呼び出し。`payload` にはフォーメーション情報を JSON 化して送信。

### リリースモーダル (release_admin.php)

- **新規登録/編集**: モーダルを開き、フォームに入力後「保存」で `App.post('/hinata/api/save_release.php', payload)` を送信。編集時は事前に `detail_release.php?id=N` で既存データを取得しフォームにバインド。
- **削除**: 確認ダイアログ後に `App.post('/hinata/api/delete_release.php', { id })` を送信。
- **ジャケット画像アップロード**: ファイル選択時に `upload_release_jacket.php` へ multipart 送信し、返却URLをフォームにセット。プレビュー表示。
- **収録曲動的追加**: 「曲を追加」ボタンで収録曲入力行を動的に追加。削除ボタンで行を除去。

### ストリーミングURL一括編集 (release_show.php)

- **トリガー**: 管理者専用セクション「ストリーミングURL」の折りたたみを展開。
- **保存**: 各楽曲の Apple Music / Spotify URL を入力し、個別に `App.post('/hinata/api/save_song_streaming.php', { song_id, apple_music_url, spotify_url })` で保存。

### 楽曲順序編集 (release_show.php)

- **トリガー**: 管理者専用セクション「楽曲順序」の折りたたみを展開。
- **保存**: トラック番号入力後「保存」で `App.post('/hinata/api/save_release_song_order.php', { release_id, songs })` を送信。

### アー写登録 (release_artist_photos.php)

- **URL入力**: メンバーごとの URL 入力欄に直接入力。
- **ファイルアップロード**: ファイル選択ボタンで `upload_release_artist_photo.php` へ multipart 送信し、返却URLをフォームにセット。
- **保存**: 「保存」ボタンで `App.post('/hinata/api/save_release_member_images.php', { release_id, members })` を送信。

### アー写一覧 (artist_photos_index.php)

- **タブ切替**: リリース別 / メンバー別タブを切り替え。URLパラメータ `?tab=releases|members` で状態を保持。
- **画像拡大**: 画像クリックでライトボックス（モーダル）表示。`max-w-md` サイズ。画像は `object-cover object-top` で顔切れを防止。
- **メンバー詳細モーダル**: メンバー名クリックで `partials/member_modal.php` によるメンバー詳細モーダルを表示。
