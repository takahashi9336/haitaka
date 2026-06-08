# メンバー (Member) 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    direction LR

    state "Hinataポータル\n/hinata/" as Portal
    state "メンバー帳\n/hinata/members.php" as MemberBook
    state "メンバー詳細モーダル\n(members.php?action=detail)" as MemberModal
    state "メンバー管理\n/hinata/member_admin.php" as MemberAdmin
    state "推し設定\n/hinata/oshi_settings.php" as OshiSettings
    state "メンバー個別ページ\n/hinata/member.php?id=X" as OshiMember
    state "ペンライトカラー表\n/hinata/penlight.php" as Penlight
    state "旧URL互換\n/hinata/oshi_member.php?id=X" as OshiMemberOld

    Portal --> MemberBook : メンバー帳リンク
    Portal --> OshiSettings : 推し設定リンク
    Portal --> Penlight : ペンライトリンク
    Portal --> OshiMember : 推しメンバーカード

    MemberBook --> MemberModal : メンバーカード/行クリック
    MemberModal --> OshiMember : 詳細ページへ遷移
    MemberBook --> MemberAdmin : 管理ボタン (admin/hinata_admin)

    MemberAdmin --> MemberBook : 戻るボタン

    OshiSettings --> MemberModal : メンバーカードクリック
    OshiSettings --> Portal : 戻るボタン

    OshiMember --> Portal : 戻る (推し設定済み)
    OshiMember --> MemberBook : 戻る (推し未設定)

    Penlight --> Portal : 戻るボタン

    OshiMemberOld --> OshiMember : 301リダイレクト
```

## 2. API 呼び出しフロー

```mermaid
flowchart LR
    subgraph 画面
        A[メンバー帳]
        B[メンバー管理]
        C[推し設定]
        D[メンバー個別ページ]
    end

    subgraph API
        A1[members.php?action=detail]
        B1[api/save_member.php]
        B2[api/save_member_basic.php]
        B3[api/save_member_basic_bulk.php]
        B4[api/save_member_activity.php]
        B5[api/delete_member_activity.php]
        C1[api/toggle_favorite.php]
        D1[api/get_oshi_timeline.php]
        D2[api/oshi_image_upload.php]
        D3[api/oshi_image_delete.php]
        D4[api/oshi_image_sort.php]
        D5[api/save_member_profile_image.php]
        D6[api/save_neta.php]
    end

    A --> A1
    B --> B1
    B --> B2
    B --> B3
    B --> B4
    B --> B5
    C --> C1
    D --> D1
    D --> D2
    D --> D3
    D --> D4
    D --> D5
    D --> C1
    D --> D6
```

## 3. 遷移条件の補足

| 遷移 | 条件 |
| :--- | :--- |
| メンバー帳 → メンバー管理 | ユーザーロールが `admin` または `hinata_admin` の場合のみ管理ボタン表示 |
| メンバー個別ページ → 戻り先 | 推しレベル > 0 の場合は `/hinata/` へ、それ以外は `/hinata/members.php` へ |
| ペンライト → 戻り先 | `?from=live_guide&event_id=X` パラメータがある場合はライブガイドへ、それ以外はポータルへ |
| 旧URL互換 | `/hinata/oshi_member.php?id=X` は `/hinata/member.php?id=X` へ 301 リダイレクト |
| メンバー管理のクエリ指定 | `?member_id=X` で直接メンバーを選択状態にして表示 |
