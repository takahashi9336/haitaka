# フレンズアクティビティ (FriendsActivity) ドメイン・データモデル定義書

## 1. テーブル定義詳細

### 1.1 sys_user_friends (友達ペア)

管理者が登録する1対1の相互可視関係。`user_id < friend_user_id` で正規化格納し、双方向の参照を1レコードで実現する。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(10) unsigned | PK, Auto Inc | |
| user_id | ユーザーID (小さい方) | int(11) | NOT NULL, FK→sys_users(id) ON DELETE CASCADE | ペアのうちIDが小さい方を格納 |
| friend_user_id | 友達ユーザーID (大きい方) | int(11) | NOT NULL, FK→sys_users(id) ON DELETE CASCADE | ペアのうちIDが大きい方を格納 |
| created_by | 登録者ID | int(11) | NOT NULL | 登録した管理者の user_id |
| created_at | 登録日時 | datetime | DEFAULT CURRENT_TIMESTAMP | |

**インデックス・制約**:
- UNIQUE `uq_user_friend` (`user_id`, `friend_user_id`) — 同一ペアの重複登録を防止
- INDEX `idx_friend_user` (`friend_user_id`) — 逆引き検索の高速化

**マイグレーション**: `migrations/done/create_sys_user_friends_and_groups.sql`

### 1.2 sys_user_groups (ユーザーグループ)

管理者が作成する視聴共有用グループ。削除時は CASCADE で `sys_user_group_members` も連動削除される。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(10) unsigned | PK, Auto Inc | |
| name | グループ名 | varchar(100) | NOT NULL | |
| created_by | 作成者ID | int(11) | NOT NULL | 作成した管理者の user_id |
| created_at | 作成日時 | datetime | DEFAULT CURRENT_TIMESTAMP | |

**インデックス**:
- INDEX `idx_created_by` (`created_by`)

### 1.3 sys_user_group_members (グループメンバー)

グループとユーザーの中間テーブル。同一グループ内の全メンバーが互いの視聴履歴を参照可能。

| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | int(10) unsigned | PK, Auto Inc | |
| group_id | グループID | int(10) unsigned | NOT NULL, FK→sys_user_groups(id) ON DELETE CASCADE | グループ削除時に連動削除 |
| user_id | ユーザーID | int(11) | NOT NULL, FK→sys_users(id) ON DELETE CASCADE | ユーザー削除時に連動削除 |
| created_at | 追加日時 | datetime | DEFAULT CURRENT_TIMESTAMP | |

**インデックス・制約**:
- UNIQUE `uq_group_user` (`group_id`, `user_id`) — 同一グループへの重複メンバー登録を防止
- INDEX `idx_user_id` (`user_id`) — ユーザーID検索の高速化

### 1.4 参照テーブル (他機能が所有)

本機能は以下のテーブルを参照のみ行う。テーブル定義の詳細は各機能の設計書を参照。

| テーブル名 | 所有機能 | 本機能での参照目的 |
| :--- | :--- | :--- |
| sys_users | Core (認証) | `id`, `id_name` の取得 (ユーザー名表示) |
| an_user_works | Anime | 友人の視聴済みアニメ取得 (`status = 'watched'`) |
| an_works | Anime | アニメ作品タイトル・画像URL取得 |
| mv_user_movies | Movie | 友人の視聴済み映画取得 (`status = 'watched'`) |
| mv_movies | Movie | 映画作品タイトル・ポスター・TMDB ID取得 |
| dr_user_series | Drama | 友人の視聴済みドラマ取得 (`status = 'watched'`) |
| dr_series | Drama | ドラマ作品タイトル・ポスター・TMDB ID取得 |

## 2. ステータス・区分値定義 (マジックナンバー)

### 2.1 視聴ステータス (他機能管理、本機能参照)

本機能が参照する `status` カラムの値はジャンルごとに異なる。

**an_user_works.status** (アニメ):
- `wanna_watch` = 見たい
- `watching` = 見てる
- `watched` = 見た
- 本機能は `watched` のレコードのみ取得対象

**mv_user_movies.status** (映画):
- `watchlist` = 見たい
- `watched` = 見た
- 本機能は `watched` のレコードのみ取得対象

**dr_user_series.status** (ドラマ):
- `wanna_watch` = 見たい
- `watching` = 見てる
- `watched` = 見た
- 本機能は `watched` のレコードのみ取得対象

### 2.2 リスト追加時のステータス値 (カードから直接追加)

一覧画面の追加ボタンから各APIを呼ぶ際に送信するステータス値:

| 種別 | API | 送信パラメータ | 送信可能な値 |
| :--- | :--- | :--- | :--- |
| アニメ | POST `/anime/api/set_status.php` | `kind` | `wanna_watch`, `watching`, `watched` |
| 映画 | POST `/movie/api/add.php` | `status` | `watchlist`, `watched` |
| ドラマ | POST `/drama/api/add.php` | `status` | `wanna_watch`, `watching`, `watched` |

### 2.3 フィルタ値 (GETパラメータ `filter`)

- `anime` = アニメのみ表示
- `movie` = 映画のみ表示
- `drama` = ドラマのみ表示
- 未指定 (null) = 全ジャンル表示
- 上記以外の値が指定された場合は `null` にフォールバック

### 2.4 統合アイテムの `type` フィールド

`FriendsActivityService::getFriendsWatchedItems()` が返す各アイテムの `type` は、ソーステーブルに基づき以下のいずれか:
- `anime` = `an_user_works` + `an_works` 由来
- `movie` = `mv_user_movies` + `mv_movies` 由来
- `drama` = `dr_user_series` + `dr_series` 由来

## 3. 閲覧可能ユーザー導出ロジック

`FriendGroupModel::getViewableUserIds(int $currentUserId)` が実行する導出:

1. `sys_user_friends` から `user_id` または `friend_user_id` が自分のレコードを取得し、相手側のIDを抽出する (`getFriendUserIds`)
2. `sys_user_group_members` から自分と同一グループに属する他ユーザーIDを取得する (`getGroupMemberUserIds`)
3. 上記2つの結果を `array_unique` でマージし、自分のIDを除外して返却する

## 4. 登録済み判定ロジック

`FriendsActivityService::enrichWithRegistered()` が各アイテムに付与するフラグ:

| 種別 | 判定方法 | 付与フィールド |
| :--- | :--- | :--- |
| anime | 自分の `an_user_works.annict_work_id` の一覧に `item_id` が含まれるか | `_registered` (bool) |
| movie | 自分の `mv_user_movies` (JOINで `mv_movies.tmdb_id`) に `tmdb_id` が存在するか | `_registered` (bool), `user_status`, `user_movie_id` |
| drama | 自分の `dr_user_series` (JOINで `dr_series.tmdb_id`) に `tmdb_id` が存在するか | `_registered` (bool), `user_status`, `user_series_id` |
