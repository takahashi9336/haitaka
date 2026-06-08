# ミーグリ（お話し会）& ネタ帳 処理詳細・I/O定義書

## 1. エンドポイント / アクション一覧

### ミーグリ予定 (MeetGreetController)

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/meetgreet.php | MeetGreetController::index | ?focus_slot_id, ?focus_event_date | HTML |
| POST | hinata/api/meetgreet_import.php | MeetGreetController::import | JSON body | JSON |
| POST | hinata/api/meetgreet_create_slot.php | MeetGreetController::createSlotApi | JSON body | JSON |
| POST | hinata/api/meetgreet_delete.php | MeetGreetController::delete | JSON body | JSON |
| POST | hinata/api/meetgreet_link_event.php | (スタンドアロン: MeetGreetModel::linkSlotsToEvent) | JSON body | JSON |
| GET | hinata/api/meetgreet_event_slots.php | MeetGreetController::getSlotsByEventApi | ?event_id | JSON |
| POST | hinata/api/meetgreet_save_report.php | MeetGreetController::saveReport | JSON body | JSON |

### ミーグリ レポ (MeetGreetController)

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/meetgreet_report.php (slot_id指定) | MeetGreetController::reportPage | ?slot_id | HTML |
| GET | hinata/meetgreet_report.php (slot_id未指定) | MeetGreetController::reportPage | ?event_id（任意） | HTML |
| POST | hinata/api/meetgreet_report_create.php | MeetGreetController::createReportApi | JSON body | JSON |
| POST | hinata/api/meetgreet_report_update.php | MeetGreetController::updateReportApi | JSON body | JSON |
| POST | hinata/api/meetgreet_report_delete.php | MeetGreetController::deleteReportApi | JSON body | JSON |
| POST | hinata/api/meetgreet_report_messages_save.php | MeetGreetController::saveReportMessagesApi | JSON body | JSON |
| POST | hinata/api/meetgreet_avatar_upload.php | MeetGreetController::uploadAvatarApi | multipart/form-data | JSON |

### ネタ帳 (TalkController)

| HTTP | 公開パス (www からの相対) | Controller / 処理 | 入力 | 出力 |
| :--- | :--- | :--- | :--- | :--- |
| GET | hinata/talk.php | TalkController::index | なし | HTML |
| POST | hinata/api/save_neta.php | TalkController::store | JSON body | JSON |
| POST | hinata/api/update_neta.php | TalkController::update | JSON body | JSON |
| POST | hinata/api/delete_neta.php | TalkController::delete | JSON body | JSON |
| POST | hinata/api/update_neta_status.php | TalkController::updateStatus | JSON body | JSON |
| POST | hinata/api/update_neta_favorite.php | TalkController::updateNetaFavorite | JSON body | JSON |

## 2. 処理フロー詳細

### GET hinata/meetgreet.php (MeetGreetController::index)

1. **認証チェック**:
   - `$this->auth->requireLogin()` によりセッション確認。未ログインなら `/login.php` へリダイレクト。

2. **データ収集**:
   - `MeetGreetModel::getGroupedByDate()`: 全スロットを日付別にグループ化して取得（LEFT JOIN: hn_members, hn_colors, hn_events）。暗号化カラム（report）を復号。
   - `MemberModel::getActiveMembersWithColors()`: アクティブメンバー一覧（メンバーセレクト用）
   - `EventModel::getMgEventsForMatching()`: ミーグリ関連イベント一覧（インポート時のイベント紐付け候補）
   - `MeetGreetReportModel::countBySlotIds($allSlotIds)`: 全スロットIDに対するレポ件数を一括取得

3. **KPI算出**:
   - `$mgKpiNearestDate`: 今日以降で最も近い日付を走査
   - `$mgKpiNearestDays`: 今日からの日数差（0未満は0にクランプ）
   - `$mgKpiNearestProgressPct`: 14日を基準としたプログレス割合（最小8%、最大100%）
   - `$mgKpiTotalFutureTickets`: 今後の予定の `ticket_count` 合計
   - `$mgKpiTicketsByMember`: メンバー別チケット枚数マップ
   - `$mgKpiOshiBoxes`: 推しメンバー（`FavoriteModel::getOshiMembers()`）のうちチケットを持つメンバーの情報配列

4. **レスポンス**:
   - `Views/meetgreet.php` をレンダリング。`$groupedSlots`, `$members`, `$mgEvents`, `$reportCounts`, KPI変数群をViewに渡す。
   - View側で `$groupedFuture` / `$groupedPast` に分割してフィルタ制御。

### POST hinata/api/meetgreet_import.php (MeetGreetController::import)

1. **認証チェック**:
   - エントリPHPで `Auth::check()` を確認。未認証なら HTTP 401 + JSON返却。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_date | string (YYYY-MM-DD) | 必須 | ミーグリ日付 |
   | slots | array | 必須 | スロット配列（各要素に slot_name, start_time, end_time, member_id, member_name_raw, ticket_count） |
   | event_id | int | - | 紐付けイベントID |

3. **処理**:
   - `event_date` の形式バリデーション（`/^\d{4}-\d{2}-\d{2}$/`）
   - `MeetGreetModel::bulkInsert($eventDate, $slots, $eventId)`: 各スロットをINSERT（user_idは BaseModel で自動付与）

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "N件のミーグリ予定を登録しました"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_create_slot.php (MeetGreetController::createSlotApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID |
   | event_date | string (YYYY-MM-DD) | 必須 | ミーグリ日付 |
   | slot_name | string | - | 部名（デフォルト: '1部'） |
   | event_id | int | - | 紐付けイベントID |
   | ticket_count | int | - | 枚数（デフォルト: 0） |

3. **処理**:
   - member_id のバリデーション（0不可）
   - event_date の形式バリデーション
   - `MeetGreetModel::bulkInsert()` で1件挿入
   - `MeetGreetModel::lastInsertId()` で挿入IDを取得

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "slot_id": N}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_delete.php (MeetGreetController::delete)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 択一 | スロットID（単体削除） |
   | event_date | string (YYYY-MM-DD) | 択一 | 日付（日付一括削除） |

   `event_date` が指定されている場合は日付一括削除、`id` のみの場合は単体削除。両方未指定は例外。

3. **処理**:
   - 日付一括: `MeetGreetModel::deleteByDate($eventDate)` → user_id スコープ付きDELETE
   - 単体: `MeetGreetModel::delete($id)` → BaseModel の汎用DELETE

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "message": "..."}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_link_event.php (スタンドアロン)

1. **認証チェック**: エントリPHPで `Auth::check()`。POSTメソッドのみ許可（405返却）。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | date | string (YYYY-MM-DD) | 必須 | 対象日付 |
   | event_id | int | 必須 | 紐付けイベントID |

3. **処理**:
   - `MeetGreetModel::linkSlotsToEvent($date, $eventId)`: 指定日付の全スロットの event_id を更新

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "updated": N}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/api/meetgreet_event_slots.php (MeetGreetController::getSlotsByEventApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (クエリ)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | event_id | int | 必須 | イベントID |

3. **処理**:
   - `MeetGreetModel::getSlotsByEventId($eventId)`: イベントに紐づくスロット一覧取得（LEFT JOIN: hn_members, hn_colors）
   - `MeetGreetReportModel::countBySlotIds($slotIds)`: 各スロットのレポ件数を一括取得

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "slots": [{"id":N, "slot_name":"...", "member_id":N, "member_name":"...", "report_count":N}, ...]}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_save_report.php (MeetGreetController::saveReport)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | スロットID |
   | report | string | 必須 | メモ内容（空文字で消去） |

3. **処理**:
   - `MeetGreetModel::update($id, ['report' => $report, 'updated_at' => now()])`: report カラム更新（暗号化は BaseModel の encryptedFields により自動）

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/meetgreet_report.php (MeetGreetController::reportPage)

1. **認証チェック**: `$this->auth->requireLogin()`。

2. **ルーティング分岐**:
   - `slot_id` 指定あり → レポページ表示
   - `slot_id` 未指定 → レポ新規作成フォーム（`Views/meetgreet_report_new.php`）

3. **レポページ表示の処理**:
   - `MeetGreetModel::find($slotId)`: スロット情報取得。存在しなければ `/hinata/meetgreet.php` へリダイレクト
   - `MemberModel::getMemberDetail($memberId)`: メンバー詳細情報取得
   - `MeetGreetReportAvatarModel::resolveAvatar($memberId, $member)`: アバター画像解決（4段階フォールバック）
   - `MeetGreetReportModel::getReportsBySlotId($slotId)`: レポ一覧取得（message_countサブクエリ付き）
   - `MeetGreetReportMessageModel::getMessagesByReportIds($reportIds)`: 全レポのメッセージを一括取得・復号・レポID別にグループ化

4. **レポ新規作成フォームの処理**:
   - `MemberModel::getAllMembersWithColors()`: 全メンバー一覧
   - `EventModel::getAllMgEvents()`: 全ミーグリイベント一覧
   - `event_id` クエリパラメータ指定時: 該当イベント情報を `EventModel::find()` で取得し、日付を初期値にセット

5. **レスポンス**: `Views/meetgreet_report.php` または `Views/meetgreet_report_new.php` をレンダリング。

### POST hinata/api/meetgreet_report_create.php (MeetGreetController::createReportApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | slot_id | int | 必須 | スロットID |
   | ticket_used | int | - | 使用枚数（デフォルト: 1、最小: 1） |
   | my_nickname | string | - | ニックネーム |

3. **処理**:
   - `MeetGreetReportModel::createReport($slotId, $ticketUsed, $nickname)`: `sort_order` を MAX+1 で自動採番し、レポを作成。暗号化対象の `my_nickname` は BaseModel の `encryptedFields` で自動暗号化。

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "id": N}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_report_update.php (MeetGreetController::updateReportApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | レポID |
   | ticket_used | int | - | 使用枚数（最小: 1） |
   | my_nickname | string\|null | - | ニックネーム（`array_key_exists` で存在チェック。null許容） |

3. **処理**:
   - `MeetGreetReportModel::update($id, $data)`: 指定フィールドのみ更新。`updated_at` を自動付与。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_report_delete.php (MeetGreetController::deleteReportApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | レポID |

3. **処理**:
   - `MeetGreetReportModel::delete($id)`: レポ削除。`hn_meetgreet_report_messages` は ON DELETE CASCADE で自動削除。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_report_messages_save.php (MeetGreetController::saveReportMessagesApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | report_id | int | 必須 | レポID |
   | messages | array | 必須 | メッセージ配列（各要素に sender_type, content, sort_order） |

3. **処理**:
   - `MeetGreetReportMessageModel::bulkSave($reportId, $messages)`:
     - 既存メッセージを全DELETE（`report_id` + `user_id` スコープ）
     - 新規メッセージを順次INSERT。`content` は `Core\Encryption::encrypt()` で暗号化してから挿入
     - `sender_type` のデフォルトは `'self'`、`sort_order` のデフォルトはループインデックス

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "count": N}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/meetgreet_avatar_upload.php (MeetGreetController::uploadAvatarApi)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (multipart/form-data)**:
   | フィールド名 | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | member_id | int | 必須 | メンバーID（$_POST） |
   | avatar | ファイル | 必須 | 画像ファイル（JPEG/PNG/WebP） |

3. **処理**:
   - MIMEタイプ検証（`MeetGreetReportAvatarModel::ALLOWED_TYPES`）
   - 拡張子決定（MIME → jpg/png/webp）
   - `MeetGreetReportAvatarModel::getUploadDir($memberId)`: アップロードディレクトリの取得・作成（`uploads/mg_avatar/{user_id}/{member_id}/`）
   - `MeetGreetReportAvatarModel::resizeImage($src, $dest, $mime)`: 最大400pxにリサイズ。アルファチャンネル対応（PNG/WebP）
   - `MeetGreetReportAvatarModel::saveAvatar($memberId, $imagePath)`: UPSERT（`ON DUPLICATE KEY UPDATE`）

4. **出力 (JSON)**:
   - 成功: `{"status": "success", "image_path": "/uploads/mg_avatar/..."}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### GET hinata/talk.php (TalkController::index)

1. **認証チェック**: `$this->auth->requireLogin()`。

2. **データ収集**:
   - `MemberModel::getActiveMembersWithColors()`: アクティブメンバー一覧（横スクロール選択用）
   - `NetaModel::getGroupedNeta()`: メンバー別グループ化ネタ一覧。LEFT JOIN: hn_members, hn_colors, hn_favorites。status != 'delete' 条件。タグ情報を一括取得して結合。暗号化カラム（content, memo）を復号。
   - `NetaModel::listTagsForUser(50)`: ユーザーのタグ一覧（datalist候補、最大50件、更新日時降順）
   - `EventModel::getNextMgEvent()`: 次のミーグリイベント取得
   - 次のイベントのスロット情報: `MeetGreetModel::getSlotsByEventId()` でスロット取得 → メンバー名と部名をレンジ圧縮して `$nextMgParticipantsText` を生成（例: 「髙橋未来虹 1～3部 / 正源司陽子 1部,3部」）

3. **登録済みメンバー算出**:
   - `$groupedNeta` からネタのあるメンバーを抽出
   - 各メンバーのネタ件数、使用済み件数、推しレベル、期生、サイリウムカラーを集計
   - ソート: 推しレベル降順 → ネタ件数降順 → 名前昇順

4. **レスポンス**: `Views/talk.php` をレンダリング。

### POST hinata/api/save_neta.php (TalkController::store)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | - | 更新時のみ。未指定で新規作成 |
   | member_id | int | 必須 | メンバーID |
   | content | string | 必須 | ネタ内容 |
   | neta_type | string\|null | - | ネタ種類（question/impression/joke/null）。空文字・'none'はNULLに変換 |
   | tags | string(JSON)\|array | - | タグ配列。JSON文字列の場合はデコード |

3. **処理**:
   - neta_type のバリデーション（許可値: question, impression, joke, null）
   - tags の正規化（文字列→配列変換）
   - 新規作成: `NetaModel::create($data)` + `status='stock'`, `is_favorite=0`
   - 更新: `NetaModel::update($id, $data)`
   - `NetaModel::replaceNetaTags($netaId, $tags)`: タグの差し替え（DELETE+UPSERT+INSERT IGNORE）

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/update_neta.php (TalkController::update)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | ネタID |
   | content | string | 必須 | ネタ内容 |

3. **処理**:
   - `NetaModel::update($id, ['content' => $content, 'updated_at' => now()])`: 内容のみ更新

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: `{"status": "error", "message": "エラーメッセージ"}`（HTTP 200で返却）

### POST hinata/api/delete_neta.php (TalkController::delete)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | ネタID |

3. **処理**:
   - `NetaModel::delete($id)`: 物理削除。hn_neta_tags は ON DELETE CASCADE で自動削除。

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/update_neta_status.php (TalkController::updateStatus)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | ネタID |
   | status | string | 必須 | 新しいステータス（'stock' or 'done'） |

3. **処理**:
   - `NetaModel::update($id, ['status' => $status])`: ステータス更新

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

### POST hinata/api/update_neta_favorite.php (TalkController::updateNetaFavorite)

1. **認証チェック**: エントリPHPで `Auth::check()`。

2. **入力パラメータ (JSON body)**:
   | パラメータ | 型 | 必須 | 説明 |
   | :--- | :--- | :--- | :--- |
   | id | int | 必須 | ネタID |
   | is_favorite | int (0\|1) | 必須 | お気に入りフラグ |

3. **処理**:
   - `NetaModel::update($id, ['is_favorite' => $isFav, 'updated_at' => now()])`: お気に入りフラグ更新

4. **出力 (JSON)**:
   - 成功: `{"status": "success"}`
   - 失敗: HTTP 500 + `{"status": "error", "message": "エラーメッセージ"}`

## 3. クライアントサイド処理

### テキストインポートパーサー (MG._parseFortuneFormat)

- **トリガー**: インポートモーダルで「取り込む」ボタンクリック
- **入力**: forTUNE meets の当選結果テキスト
- **処理**: 「当選」/「落選」を区切りに5行1ブロック（ステータス/日付/部名/メンバー名/枚数）としてパース。落選ブロックはスキップ。全角数字→半角変換、「枚」前の数字を枚数として取得。日付・会場を自動検出。メンバー名は `memberList` と照合して `member_id` をマッチ。
- **出力**: `{slots: [], detectedDate, detectedVenue, warnings}`

### テキストインポートパーサー (MG._parseTabFormat)

- **トリガー**: 同上（テキストにforTUNE形式のキーワードがない場合のフォールバック）
- **入力**: タブ区切りテキスト（部名\tメンバー名\t枚数）
- **処理**: `第N部 HH:MM～HH:MM` 形式の時刻付きパースにも対応

### チャット描画 (MGR.renderChat)

- **トリガー**: メッセージ送信/編集/削除後
- **処理**: `reportMessages[reportId]` 配列から全メッセージのHTMLを再構築。バブルスタイルを sender_type に応じて切替。挿入モード時は各メッセージ間に挿入ボタンを配置。
- **API呼び出し**: `MGR.saveMessages(reportId)` → `App.post('/hinata/api/meetgreet_report_messages_save.php', ...)` でサーバーに一括保存

### ネタ帳 種類トグル・タグ入力

- **種類トグル**: `setFormNetaType(type)` でボタンのアクティブ状態を制御、`#form_neta_type` hidden input を更新
- **タグ入力**: Enter/カンマ/スペースで `addTag()` → `currentTags` 配列に追加 → `renderTags()` でバッジ表示を更新 → `#form_tags` hidden input をJSON文字列として更新。Backspaceで末尾タグ削除。ブラー時に未確定テキストを自動追加。`#` プレフィクスは自動除去。
- **タグ正規化**: `normalizeTag()` で `#` 除去・空白除去・トリム

### ネタ帳 次回イベントの参加予定テキスト生成

- **処理場所**: `TalkController::index()` のサーバーサイド
- **ロジック**: 次のミーグリイベントに紐づくスロットをメンバー別にグループ化。部名の数値部分をレンジ圧縮（例: 1部,2部,3部 → 1～3部）。全角数字は半角に変換。結果を「メンバー名 部名 / メンバー名 部名」形式の文字列として生成。
