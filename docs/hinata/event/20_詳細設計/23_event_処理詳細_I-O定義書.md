# イベント管理・セットリスト・ライブガイド (event) 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/events.php | EventController::index() | なし | HTML |
| GET | hinata/event_admin.php | EventController::admin() | なし | HTML |
| POST | hinata/api/save_event.php | EventController::save() | JSON body | JSON |
| POST | hinata/api/delete_event.php | EventController::delete() | JSON body | JSON |
| POST | hinata/api/save_event_status.php | (スタンドアロン) | JSON body | JSON |
| POST | hinata/api/save_event_seat_impression.php | (スタンドアロン) | JSON body | JSON |
| POST | hinata/api/toggle_attendance.php | (スタンドアロン) | JSON body | JSON |
| POST | hinata/api/save_event_applications.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/api/event_applications.php | (スタンドアロン) | ?event_id | JSON |
| POST | hinata/api/save_event_series.php | EventController::saveEventSeriesJson() | JSON body | JSON |
| POST | hinata/api/delete_event_series.php | EventController::deleteEventSeriesJson() | JSON body | JSON |
| GET | hinata/api/past_events.php | (スタンドアロン) | ?before&limit&offset&category | JSON |
| POST | hinata/api/geocode_event_place.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/setlist.php | SetlistController::show() | ?event_id | HTML |
| GET | hinata/setlist_edit.php | SetlistController::edit() | ?event_id | HTML |
| GET | hinata/api/get_event_setlist.php | (スタンドアロン) | ?event_id | JSON |
| POST | hinata/api/save_setlist.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/api/get_event_shadow_narration.php | (スタンドアロン) | ?event_id | JSON |
| POST | hinata/api/save_event_shadow_narration.php | (スタンドアロン) | JSON body | JSON |
| GET | hinata/live_guide.php | LiveGuideController::index() | なし | HTML |
| GET | hinata/live_guide_admin.php | LiveGuideController::admin() | なし | HTML |
| GET | hinata/api/get_live_guide.php | (スタンドアロン) | ?event_id | JSON |
| POST | hinata/api/save_event_guide_songs.php | (スタンドアロン) | JSON body | JSON |

## 2. 処理フロー詳細

### GET hinata/events.php (EventController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。未ログインなら `/login.php` へリダイレクト。

2. **データ収集**:
   - **イベント一覧**: `EventModel::getEventsForCalendar($start, $end)` で先月1日 ～ 2099-12-31 のイベントを取得。LEFT JOIN で `hn_event_series`（系列名）、`hn_user_events_status`（ユーザー別ステータス/座席/感想）、`hn_event_movies` + `com_media_assets`（YouTube video_key）、`hn_event_members`（出演メンバーCSV）を結合。
   - **MG/RMGスロット**: category=2,3 のイベントについて `MeetGreetModel::getSlotsByEventId()` またはフォールバックで `getSlotsByDate()` を呼び出し、`MeetGreetReportModel::sumTicketUsedBySlotIds()` でチケット消化数を集計。
   - **参戦済みイベントID**: `SetlistModel::getAttendedEventIds()` でログインユーザーの参戦イベント一覧を取得。
   - **次のイベント**: `EventModel::getNextEvent()` で直近未来イベント1件（残日数付き）を取得。

3. **レスポンス**:
   - `Views/event_index.php` をレンダリング。`$events`, `$eventSlots`, `$ticketUsedSums`, `$attendedEventIds`, `$nextEvent`, `$user` をViewに渡す。

### GET hinata/event_admin.php (EventController::admin)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')` により管理者権限を確認。権限不足なら `/hinata/` へリダイレクト。

2. **データ収集**:
   - **メンバー一覧**: `MemberModel::getAllWithColors()` で全メンバー（卒業生含む）取得。
   - **イベント一覧**: `EventModel::getEventsForCalendar()` で前年1月1日 ～ 翌年12月31日のイベントを取得し、今後のイベント（`$recentUpcoming`）と過去のイベント（`$recentPast`）に分離・ソート。
   - **ミニカレンダー用**: 同範囲のイベントを `$miniCalEvents` として保持。
   - **系列マスタ**: `EventSeriesModel::allByNameAsc()` で全系列を名前順で取得。

3. **レスポンス**:
   - `Views/event_admin.php` をレンダリング。`$members`, `$recentUpcoming`, `$recentPast`, `$miniCalEvents`, `$eventSeriesList`, `$user` をViewに渡す。
   - 系列リストは `$hinataSeriesListJson` として JSON 埋め込み。
   - 最近の編集リストの各行は `EventRelatedLinkService::buildLegacyLinksForEditor()` で関連リンクを復元し、`data-ev` 属性にJSON格納。

### POST hinata/api/save_event.php (EventController::save)

1. **認証チェック**:
   - `Auth::check()` によりログイン確認。未ログインなら HTTP 401。
   - Controller 内で暗黙の管理者権限チェック（EventController コンストラクタでは行わず、admin 画面からの呼び出し前提）。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | - | 更新時のみ。未指定で新規作成 |
   | event_name | string | 必須 | イベント名 |
   | event_date | string (YYYY-MM-DD) | 必須 | 開催日 |
   | category | int | 必須 | カテゴリ (1-6, 99) |
   | series_id | int | - | 系列ID。0 または空で NULL |
   | mg_rounds | int | - | ミーグリ部数（category=2,3 のみ有効） |
   | event_place | string | - | 会場名 |
   | event_place_address | string | - | Maps用住所 |
   | latitude | string | - | 緯度 |
   | longitude | string | - | 経度 |
   | place_id | string | - | Google Places ID |
   | event_info | string | - | 詳細メモ |
   | event_hashtag | string | - | ハッシュタグ（#あり/なし両方受付、#を除去して保存） |
   | related_links | array | - | `[{url, kind?, manual_override?}, ...]` 関連リンク配列（最大20件） |
   | member_ids | array[int] | - | 出演メンバーID配列 |

3. **処理**:
   - トランザクション開始
   - `EventRelatedLinkService::normalizePayload()` で関連リンクを正規化:
     - 各 URL の https 化・ホスト小文字化・utm パラメータ除去
     - 種別自動判定（youtube/tokusetsu/collab/other）
     - event_url（先頭 tokusetsu）、collaboration_urls_json、related_links_json、first_youtube_normalized を算出
   - 系列ID のバリデーション（存在チェック）
   - `$input['id']` があれば `EventModel::update()`、なければ `EventModel::create()` + `lastInsertId()`
   - `hn_event_members` を event_id 単位で DELETE → INSERT（POKA_MEMBER_ID 除外、重複排除）
   - `EventRelatedLinkService::syncYoutubeMovie()` で `hn_event_movies` を同期:
     - event_id の全行 DELETE
     - first_youtube_normalized があれば `MediaAssetModel::findOrCreateAsset()` でアセット作成/取得、`hn_event_movies` に INSERT
   - トランザクションコミット
   - Logger::info でログ出力

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`（トランザクションロールバック）

### POST hinata/api/delete_event.php (EventController::delete)

1. **認証チェック**:
   - `Auth::check()` && `role` が `admin` / `hinata_admin` であること。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | 削除対象イベントID |

3. **処理**:
   - `EventModel::delete($id)` を呼び出し。関連テーブル（hn_event_members, hn_event_movies 等）は CASCADE または個別のデータ残存となる。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_event_status.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | status | int | 必須 | 0-5。0=クリア（DELETE）、1=参加、2=不参加、3=検討、4=当選、5=落選 |

3. **処理**:
   - status=0 の場合: `EventModel::deleteUserStatus($eventId)` で行 DELETE
   - status=1-5 の場合: `EventModel::saveUserStatus($eventId, $status)` で upsert（ON DUPLICATE KEY UPDATE）

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "new_status": <int>}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_event_seat_impression.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | seat_info | string | - | 座席情報（空文字は NULL に変換） |
   | impression | string | - | 感想（空文字は NULL に変換） |

3. **処理**:
   - `EventModel::saveUserSeatImpression($eventId, $seatInfo, $impression)` で upsert。status が未設定の場合はデフォルト 1 で INSERT される。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/toggle_attendance.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |

3. **処理**:
   - `SetlistModel::toggleAttendance($eventId)`:
     - `hn_event_attendance` に (user_id, event_id) が存在すれば DELETE → false を返却
     - 存在しなければ INSERT → true を返却

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "attended": <bool>, "message": "参戦を記録しました|参戦記録を解除しました"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_event_applications.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | rows | array | 必須 | `[{round_name, application_start?, application_deadline, announcement_date?, application_url?}, ...]` |

3. **処理**:
   - 各行の日時を正規化（`T` → 空白、末尾 `:00` 付加）
   - `application_deadline` が空の行はスキップ
   - `EventApplicationModel::replaceForEvent($eventId, $normalized)`:
     - トランザクション内で event_id の全行 DELETE → 順序付き INSERT

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/event_applications.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID（0以下なら空配列を返却） |

3. **処理**:
   - `EventApplicationModel::getByEventId($eventId)` で sort_order → application_deadline 順で取得。

4. **出力 (JSON)**:
   - `{"applications": [...]}`

### POST hinata/api/save_event_series.php (EventController::saveEventSeriesJson)

1. **認証チェック**:
   - `Auth::check()` && `HinataAuth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | name | string | 必須 | 系列名（空文字はバリデーションエラー） |

3. **処理**:
   - `EventSeriesModel::createByName($name)`:
     - trim 後空文字チェック → `InvalidArgumentException`
     - `create()` で INSERT。UNIQUE制約違反（重複名）の場合は PDOException → `InvalidArgumentException` に変換

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "id": <int>, "name": "<string>"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/delete_event_series.php (EventController::deleteEventSeriesJson)

1. **認証チェック**:
   - `Auth::check()` && `HinataAuth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | 削除対象系列ID |

3. **処理**:
   - `EventSeriesModel::find($id)` で存在確認
   - `hn_events` の `series_id` 参照件数を COUNT。0件でなければ削除不可エラー
   - `DELETE FROM hn_event_series WHERE id = ?`

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "この系列はイベント N 件から参照されています。先にイベント側の系列を解除してください。"}`

### GET hinata/api/past_events.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | before | string (YYYY-MM-DD) | - | 基準日（未指定は今日） |
   | limit | int | - | 取得件数（1-50、初期値: 20） |
   | offset | int | - | オフセット（初期値: 0） |
   | category | int | - | カテゴリフィルタ（未指定は全カテゴリ） |

3. **処理**:
   - `EventModel::getPastEvents($before, $limit, $offset, $category)` で before 日より前のイベントを新しい順に取得。JOIN は `getEventsForCalendar()` と同構成。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": [...], "has_more": <bool>}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/geocode_event_place.php

1. **認証チェック**:
   - `Auth::check()` && `role` が `admin` / `hinata_admin`。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | イベントID |
   | event_place_address | string | 必須 | 会場住所（空文字はバリデーションエラー） |

3. **処理**:
   - `GeocodeService::geocode($address)` で Google Geocoding API を呼び出し、緯度・経度・place_id を取得。`SqlUsageLimiter` でAPI利用制限を管理。
   - `EventModel::update($eventId, {latitude, longitude, place_id, update_user})` で保存。`formatted_address` は保存しない（ユーザー入力優先）。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "geo": {latitude, longitude, place_id, ...}}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/setlist.php (SetlistController::show)

1. **認証チェック**:
   - `$this->auth->requireLogin()`。未ログインなら `/login.php` へリダイレクト。
   - `event_id` が 0 または未指定、または該当イベントが存在しない場合は `/hinata/events.php` へリダイレクト。

2. **データ収集**:
   - `EventModel::find($eventId)` でイベント情報取得。
   - `SetlistModel::getByEventId($eventId)` でセットリスト全行取得（JOIN: hn_songs, hn_releases, hn_members + hn_setlist_centers から複数センター取得）。
   - `EventShadowNarrationModel::getByEventId($eventId)` で影ナレ情報取得（メンバーID配列付き）。
   - 影ナレメンバーIDから `MemberModel::getActiveMembersWithColors()` で名前を解決。

3. **レスポンス**:
   - `Views/setlist_show.php` をレンダリング。`$event`, `$setlist`, `$shadow`, `$shadowMembers`, `$user` をViewに渡す。

### GET hinata/setlist_edit.php (SetlistController::edit)

1. **認証チェック**:
   - `$this->auth->requireLogin()` + `HinataAuth::requireHinataAdmin('/hinata/')`。
   - `event_id` 未指定/不正、または該当イベント不在時は `/hinata/events.php` へリダイレクト。

2. **データ収集**:
   - `EventModel::find($eventId)` でイベント情報取得。
   - `SetlistModel::getByEventId($eventId)` で既存セットリスト取得。
   - `SongModel::getAllSongsWithRelease()` で全楽曲一覧（リリース情報付き）取得。JSON 埋め込み用に整形。
   - `MemberModel::getActiveMembersWithColors()` で現役メンバー一覧取得。JSON 埋め込み用に整形。

3. **レスポンス**:
   - `Views/setlist_edit.php` をレンダリング。`$event`, `$setlist`, `$allSongs`, `$allMembers`, `$user` をViewに渡す。
   - `$allSongs`, `$allMembers` は `<script>` タグ内に JSON 埋め込み。

### GET hinata/api/get_event_setlist.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |

3. **処理**:
   - `SetlistModel::getByEventId($eventId)` でセットリスト取得。
   - `SetlistModel::isAttended($eventId)` でログインユーザーの参戦状態を取得。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": {"setlist": [...], "attended": <bool>}}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_setlist.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | items | array | 必須 | セットリスト行配列 |

   items 各要素:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | entry_type | string | 必須 | 'song' / 'mc' / 'block' |
   | sort_order | int | 必須 | 表示順 |
   | song_id | int | 条件付必須 | entry_type='song' の場合に必須 |
   | encore | int | - | 0=本編, 1=EN, 2=W EN。entry_type='song' のみ有効 |
   | label | string | - | MC/ブロックのラベルテキスト |
   | block_kind | string | - | ブロック種別 |
   | center_member_id | int | - | レガシー単一センターID |
   | center_member_ids | array[int] | - | 複数センターID配列 |
   | memo | string | - | メモ |

3. **処理**:
   - `SetlistModel::saveForEvent($eventId, $items)`:
     - 既存行の ID を取得し、`hn_setlist_centers` の該当行を一括 DELETE
     - `hn_setlists` の event_id 行を全件 DELETE
     - 各 item を INSERT。entry_type のバリデーション（song/mc/block 以外は 'song' にフォールバック）
     - song 行で song_id 未指定の場合は例外スロー
     - song 行以外は song_id を NULL に強制
     - INSERT 後に lastInsertId で setlist_id を取得
     - song 行の場合: center_member_ids があれば `hn_setlist_centers` に INSERT。なければ center_member_id からフォールバック

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "保存しました"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/get_event_shadow_narration.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |

3. **処理**:
   - `EventShadowNarrationModel::getByEventId($eventId)` で影ナレ本体 + メンバーID配列を取得。
   - メンバーIDから `MemberModel::getAllWithColors()` で名前を解決。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": {"event_id": <int>, "member_ids": [...], "members": [{id, name}, ...], "memo": <string|null>}}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_event_shadow_narration.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | member_ids | array[int] | - | 影ナレメンバーID配列（重複排除・0以下除外済みで処理） |
   | memo | string | - | メモ（空文字は NULL に変換） |

3. **処理**:
   - `EventShadowNarrationModel::saveForEvent($eventId, $memberIds, $memo)`:
     - `hn_event_shadow_narrations` に upsert（ON DUPLICATE KEY UPDATE で memo / update_user を更新）
     - `hn_event_shadow_narration_members` の event_id 行を全件 DELETE → member_ids を INSERT

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "保存しました"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/live_guide.php (LiveGuideController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()`

2. **データ収集**:
   - `EventModel::getUpcomingLiveEventsForGuide()` で過去1ヶ月 ～ 未来1年のライブイベント（category=1）を取得。

3. **レスポンス**:
   - `Views/live_guide.php` をレンダリング。`$events`, `$user` をViewに渡す。
   - イベント詳細は `get_live_guide.php` API で非同期取得。

### GET hinata/live_guide_admin.php (LiveGuideController::admin)

1. **認証チェック**:
   - `HinataAuth::requireHinataAdmin('/hinata/')`

2. **データ収集**:
   - `EventModel::getUpcomingLiveEventsForGuide()` でライブイベント一覧取得。
   - `ReleaseModel::getAllReleases()` + `getReleaseWithSongs()` で全リリース＋収録曲を取得。楽曲のないリリースは除外。
   - `EventGuideSongModel::LIKELIHOOD_LABELS` で確度ラベル定数取得。
   - `SongModel::TRACK_TYPES_DISPLAY` でトラック種別表示名取得。

3. **レスポンス**:
   - `Views/live_guide_admin.php` をレンダリング。`$events`, `$releasesWithSongs`, `$likelihoodLabels`, `$trackTypesDisplay`, `$user` をViewに渡す。

### GET hinata/api/get_live_guide.php

1. **認証チェック**:
   - `Auth::check()` 必須。不正なら HTTP 403。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID（0ならイベント=null の空レスポンス） |

3. **処理**:
   - `EventModel::find($eventId)` でイベント情報取得。
   - `EventGuideSongModel::getByEventIdGroupedByLikelihood($eventId)` で確度別候補曲取得（JOIN: hn_songs, hn_releases）。
   - 各曲について `SongModel::getMediaLinksBySongId($songId, ['MV', 'Call'])` で MV/コール動画を取得し、`videos` として付加。
   - `collaboration_urls`: イベントの `collaboration_urls` JSON をデコード。
   - `hashtag_media`: `event_hashtag` が設定されている場合、`hn_media_metadata` + `com_media_assets` をタイトル/説明の LIKE 検索（最大50件、upload_date DESC）。YouTube のサムネイルがなければフォールバック URL を生成。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "data": {"event": {...}, "songs_by_likelihood": {"certain": [...], "high": [...], "possible": [...]}, "collaboration_urls": [...], "hashtag_media": [...]}}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/save_event_guide_songs.php

1. **認証チェック**:
   - `Auth::check()` && `Auth::isHinataAdmin()` 必須。不正なら HTTP 403。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |
   | items | array | 必須 | `[{song_id: int, likelihood: string, sort_order?: int}, ...]` |

3. **処理**:
   - `EventGuideSongModel::saveForEvent($eventId, $items)`:
     - event_id の全行 DELETE
     - 各 item を INSERT。likelihood のバリデーション（certain/high/possible 以外は 'possible' にフォールバック）
     - sort_order は指定値を使用、未指定の場合は 1-indexed の連番

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 400 + `{"status": "error", "message": "エラーメッセージ"}`

## 3. サービスクラスの処理詳細

### EventRelatedLinkService

#### normalizePayload(array $incoming, MediaAssetModel $mediaModel): array
- 入力: クライアントから送信された関連リンク配列（`[{url, kind?, manual_override?}, ...]`）
- 処理:
  1. 配列フィルタ・件数チェック（最大 MAX_LINKS=20 件）
  2. 各 URL を `normalizeUrl()` で正規化（https化、ホスト小文字化、末尾スラッシュ除去、utm_* クエリ除去）
  3. `manual_override=true` の場合は指定 kind を使用、false の場合は `classifyKind()` で自動判定
  4. 結果から以下を算出:
     - `event_url`: 先頭の kind='tokusetsu' の URL
     - `collaboration_urls_json`: kind='collab' / 'other' の URL 配列 JSON（空なら NULL）
     - `related_links_json`: 全リンクの JSON
     - `first_youtube_normalized`: 先頭の YouTube URL
- 出力: `{links, event_url, collaboration_urls_json, related_links_json, first_youtube_normalized}`

#### syncYoutubeMovie(PDO $pdo, int $eventId, ?string $firstYoutubeNormalized, string $eventTitle, MediaAssetModel $mediaModel): void
- 処理:
  1. `hn_event_movies` の event_id 行を全件 DELETE
  2. firstYoutubeNormalized が null/空なら終了
  3. `MediaAssetModel::parseUrl()` で platform='youtube' を確認
  4. `MediaAssetModel::findOrCreateAsset()` でアセット作成/取得
  5. `MediaAssetModel::findOrCreateMetadata()` でメタデータ作成/取得
  6. `hn_event_movies` に (event_id, movie_id) を INSERT

#### buildLegacyLinksForEditor(array $eventRow, MediaAssetModel $mediaModel): array
- 処理:
  1. `related_links` カラムに JSON が存在すればデコードして返却
  2. JSON がない場合はレガシーカラムから復元: event_url → tokusetsu、video_key → youtube、collaboration_urls → classifyKind() で判定
  3. 最大 MAX_LINKS 件で切り詰め
