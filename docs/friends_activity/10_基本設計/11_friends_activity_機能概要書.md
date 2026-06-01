# フレンズアクティビティ (FriendsActivity) 機能概要書

## 1. 目的と背景
- MyPlatform 内のエンタメ機能（映画・ドラマ・アニメ）において、友人やグループメンバーが視聴した作品を横断的に閲覧できるようにする。
- 管理者が登録した「友達ペア」または「ユーザーグループ」の関係に基づき、閲覧可能なユーザーの視聴履歴を一覧表示する。
- 各エンタメダッシュボード（映画・ドラマ・アニメ）にも友人視聴セクションを埋め込み、発見性を高める。

## 2. 解決するペインポイント（課題）
- 他のユーザーがどの作品を視聴済みか把握する手段がなく、作品発見の機会が限られている。
- 友人が見ている作品を知ることで「次に何を見るか」を決めるヒントが得られないまま、個人の視聴リストだけで運用している。
- 友人の視聴作品を見つけた際、その場で自分のリストに追加する導線が存在しない。

## 3. コアバリュー（主要な提供価値）
- 友人・グループメンバーの視聴済み作品を、アニメ・映画・ドラマの3ジャンル横断で一覧表示する。
- 種別フィルタ（アニメ/映画/ドラマ）とユーザーフィルタで絞り込みが可能。
- 一覧画面から直接、自分の視聴リストへワンクリックで作品を追加できる（見たい/見てる/見た）。
- 作品カードをクリックすると各アプリのプレビューモーダルが開き、詳細を確認できる。
- 自分が既に登録済みの作品にはチェックマークを表示し、重複追加を防止する。

## 4. スコープ
- 対象ユーザー: ログインユーザー（`Core\Auth::requireLogin()` 前提）
- 友達・グループの管理は管理者専用（`AdminController::friends()` / `AdminController::friendGroups()`）
- 関連する主要システム/外部API:
  - **アニメ**: Annict 由来のローカルキャッシュ（`an_works`, `an_user_works`）
  - **映画**: TMDB 由来のローカルキャッシュ（`mv_movies`, `mv_user_movies`）
  - **ドラマ**: TMDB 由来のローカルキャッシュ（`dr_series`, `dr_user_series`）
- 対象機能:
  - **フレンズアクティビティ一覧画面**: `/friends_activity.php`
  - **各ダッシュボードへの埋め込み**: 映画・ドラマ・アニメ各ダッシュボードの「友人の視聴」セクション
  - **管理者画面（友達管理）**: `/admin/friends.php`
  - **管理者画面（グループ管理）**: `/admin/friend_groups.php`
- 非スコープ（本設計書では扱わない）:
  - 各エンタメ機能（映画/ドラマ/アニメ）の視聴登録・検索・詳細の仕様自体（別設計書で管理）
  - ユーザー認証・認可の汎用仕様（`Core\Auth` は参照関係のみ記載）

## 5. 現状（実装）サマリ
- アプリ構成:
  - エントリ: `www/friends_activity.php`
  - Controller: `private/apps/FriendsActivity/Controller/FriendsActivityController.php`
  - Service: `private/apps/FriendsActivity/Service/FriendsActivityService.php`
  - Model: `private/apps/FriendsActivity/Model/FriendGroupModel.php`
  - View: `private/apps/FriendsActivity/Views/activity.php`
- 管理者用CRUD:
  - Model: `private/apps/Admin/Model/FriendGroupAdminModel.php`
  - Controller: `private/apps/Admin/Controller/AdminController.php`（`friends()`, `friendGroups()` メソッド）
  - View（友達管理）: `private/apps/Admin/Views/friends.php`
  - View（グループ管理）: `private/apps/Admin/Views/friend_groups.php`
- マイグレーション: `migrations/done/create_sys_user_friends_and_groups.sql`
- ダッシュボード統合:
  - 映画: `private/apps/Movie/Views/movie_dashboard.php` 内「友人の視聴」セクション（`filter=movie`, `limit=12`）
  - アニメ: `private/apps/Anime/Views/anime_dashboard.php` 内「友人の視聴」セクション（`filter=anime`, `limit=12`）
  - ドラマ: `private/apps/Drama/Views/drama_dashboard.php` 内「友人の視聴」セクション（`filter=drama`, `limit=12`）

## 6. データの基本方針
- **友達ペア（sys_user_friends）**: 管理者が2人のユーザーを1対1で相互可視関係として登録する。`user_id < friend_user_id` の正規化で格納し、双方向の参照を1レコードで実現する。
- **ユーザーグループ（sys_user_groups + sys_user_group_members）**: 管理者がグループを作成し、メンバーを登録する。同一グループ内の全メンバーが互いの視聴履歴を参照可能。
- **閲覧可能ユーザーの導出**: 友達ペアとグループメンバーをマージし、重複を排除して「閲覧可能なユーザーID一覧」を生成する（`FriendGroupModel::getViewableUserIds()`）。
- **視聴履歴の参照**: 閲覧可能ユーザーの `an_user_works`（status=watched）、`mv_user_movies`（status=watched）、`dr_user_series`（status=watched）を UNION 相当で取得し、日付降順でソートする。
- **登録済み判定**: 表示する各アイテムに対し、ログインユーザー自身の登録状況を突合し `_registered` フラグを付与する。
