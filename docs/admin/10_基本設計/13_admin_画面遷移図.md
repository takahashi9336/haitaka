# 管理画面 (admin) 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> Portal : /admin/index.php

    Portal --> Users : ユーザー管理カード
    Portal --> Apps : アプリ管理カード
    Portal --> Roles : ロール管理カード
    Portal --> DbViewer : DBビューワカード
    Portal --> DbExport : DB一括抽出カード
    Portal --> Guides : ガイド管理カード
    Portal --> Improvement : 対応管理カード
    Portal --> Friends : 友達管理カード
    Portal --> FriendGroups : グループ管理カード
    Portal --> TextFiles : テキスト管理カード

    state "管理ポータル" as Portal
    state "ユーザー管理" as Users
    state "アプリ管理" as Apps
    state "ロール管理" as Roles
    state "DBビューワ" as DbViewer
    state "DB一括抽出" as DbExport
    state "ガイド一覧" as Guides
    state "ガイド編集" as GuideEdit
    state "対応管理" as Improvement
    state "友達管理" as Friends
    state "グループ管理" as FriendGroups
    state "テキスト管理" as TextFiles

    Users --> Users : ユーザー追加/ロール変更/PW リセット (モーダル+API)

    Apps --> Apps : 新規作成/編集/削除 (モーダル+POST→リダイレクト)

    Roles --> Roles : 新規作成/編集/削除 (モーダル+POST→リダイレクト)

    DbViewer --> DbViewer : ?table=xxx テーブル選択
    DbViewer --> DbViewer : ?table=xxx&page=N ページ切替
    DbViewer --> DbViewer : ?table=xxx&limit=N 表示件数変更

    DbExport --> DbExport : ?download=all_create (.sql DL)
    DbExport --> DbExport : ?download=schema_md (.md DL)
    DbExport --> DbExport : ?download=schema_json (.json DL)
    DbExport --> DbExport : ?download=all_data_csv_zip (.zip DL)

    Guides --> GuideEdit : ?id=N (編集)
    Guides --> GuideEdit : ?new=1 (新規)
    GuideEdit --> Guides : 保存完了→リダイレクト
    Guides --> Guides : 削除 (POST→リダイレクト)

    Improvement --> Improvement : 新規追加/編集/削除/フィルタ (POST→リダイレクト)

    Friends --> Friends : ペア登録/削除 (モーダル+POST→リダイレクト)

    FriendGroups --> FriendGroups : 作成/削除 (POST→リダイレクト)
    FriendGroups --> FriendGroups : ?edit=N (編集→インライン表示)

    TextFiles --> TextFiles : 一覧/取得/保存/削除 (全て API 経由で SPA 的に動作)
```

## 2. 横断的な遷移: 改善事項 FAB

改善事項 FAB (`admin_utility_fab.php`) はすべての管理画面を含むプラットフォーム全画面に配置される。FAB から直接 API (`/admin/api/save_improvement_item.php`) を呼び出して改善事項を登録でき、「対応管理へ」リンクから対応管理画面へ遷移することもできる。

```mermaid
stateDiagram-v2
    state "任意の画面" as AnyScreen
    state "改善事項FAB (パーシャル)" as FAB
    state "対応管理" as ImpList

    AnyScreen --> FAB : FABクリック
    FAB --> AnyScreen : 改善事項登録 (API POST)
    FAB --> ImpList : 「対応管理へ」リンク
```

## 3. 認可による遷移制御

- すべてのエントリポイントで `Auth::requireAdmin()` を実行する。
- admin ロール以外のユーザーは 403 リダイレクトとなり、管理画面配下にはアクセスできない。
- API エンドポイント（テキスト管理、改善事項 FAB、ガイド画像）でも `$_SESSION['user']['role'] === 'admin'` を検証し、403 JSON を返す。
