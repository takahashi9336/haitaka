# フレンズアクティビティ (FriendsActivity) 画面遷移図

## 1. 画面遷移フロー

### 1.1 ユーザー向け画面遷移
```mermaid
stateDiagram-v2
    [*] --> AnimeDashboard : アニメダッシュボードアクセス
    [*] --> MovieDashboard : 映画ダッシュボードアクセス
    [*] --> DramaDashboard : ドラマダッシュボードアクセス

    AnimeDashboard --> FriendsActivityList : もっと見る（filter=anime）
    MovieDashboard --> FriendsActivityList : もっと見る（filter=movie）
    DramaDashboard --> FriendsActivityList : もっと見る（filter=drama）

    FriendsActivityList --> FriendsActivityList : 種別タブ切替（filter=anime/movie/drama/全て）
    FriendsActivityList --> FriendsActivityList : ユーザー絞り込み（user_id=N）
    FriendsActivityList --> AnimePreviewModal : アニメカード押下
    FriendsActivityList --> MoviePreviewModal : 映画カード押下
    FriendsActivityList --> DramaPreviewModal : ドラマカード押下

    AnimePreviewModal --> FriendsActivityList : モーダル閉じる
    MoviePreviewModal --> FriendsActivityList : モーダル閉じる
    DramaPreviewModal --> FriendsActivityList : モーダル閉じる

    FriendsActivityList --> Dashboard : 戻るボタン
```

### 1.2 管理者向け画面遷移（友達・グループ管理）
```mermaid
stateDiagram-v2
    [*] --> AdminPortal : 管理者ポータル

    AdminPortal --> FriendsAdmin : 友達管理リンク
    AdminPortal --> GroupsAdmin : グループ管理リンク

    FriendsAdmin --> FriendsAdmin : 友達ペア追加（POST）
    FriendsAdmin --> FriendsAdmin : 友達ペア削除（POST）
    FriendsAdmin --> AdminPortal : 戻る

    GroupsAdmin --> GroupsAdmin : グループ作成（POST）
    GroupsAdmin --> GroupEditForm : 編集ボタン（?edit=ID）
    GroupEditForm --> GroupsAdmin : 更新完了 / キャンセル
    GroupsAdmin --> GroupsAdmin : グループ削除（POST）
    GroupsAdmin --> AdminPortal : 戻る
```

## 2. URL/パラメータ一覧

| 画面 | URL | GETパラメータ | 説明 |
| :--- | :--- | :--- | :--- |
| 友人の視聴一覧 | `/friends_activity.php` | なし | 全ジャンル・全ユーザー表示 |
| 友人の視聴一覧（種別絞り込み） | `/friends_activity.php` | `filter=anime\|movie\|drama` | 指定ジャンルのみ表示 |
| 友人の視聴一覧（ユーザー絞り込み） | `/friends_activity.php` | `user_id=N` | 指定ユーザーの視聴のみ表示 |
| 友人の視聴一覧（複合フィルタ） | `/friends_activity.php` | `filter=anime&user_id=N` | ジャンル + ユーザーの複合絞り込み |
| 友達管理（管理者） | `/admin/friends.php` | なし | 友達ペア一覧・登録・削除 |
| グループ管理（管理者） | `/admin/friend_groups.php` | なし | グループ一覧・作成・削除 |
| グループ編集（管理者） | `/admin/friend_groups.php` | `edit=ID` | グループ名・メンバー編集フォーム表示 |
