# ポータル（Portal） 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> PortalDashboard : /hinata/index.php

    state "ポータルダッシュボード" as PortalDashboard {
        [*] --> MainView
        MainView --> OshiSwitch : 推しサムネイルクリック
        OshiSwitch --> MainView : JS切り替え（画面遷移なし）
        MainView --> SearchFilter : 検索ボックス入力
        SearchFilter --> MainView : ESC/クリア
        MainView --> ReleaseExpand : 収録曲を見るボタン
        ReleaseExpand --> MainView : アコーディオン折りたたみ
    }

    PortalDashboard --> PortalInfoAdmin : 管理者FABクリック\n/hinata/portal_info_admin.php
    PortalDashboard --> EventDetail : イベントカード・次のイベントクリック\n/hinata/events.php?event_id=N
    PortalDashboard --> MemberDetail : 推し個別ページへ\n/hinata/member.php?id=N
    PortalDashboard --> OshiSettings : 推し設定\n/hinata/oshi_settings.php
    PortalDashboard --> MeetGreetReport : レポリンク\n/hinata/meetgreet_report.php
    PortalDashboard --> Calendar : iCalに追加\n/hinata/calendar.php
    PortalDashboard --> ReleaseDetail : 最新リリース詳細\n/hinata/release.php?id=N
    PortalDashboard --> SongDetail : 収録曲リンク\n/hinata/song.php?id=N
    PortalDashboard --> ExternalBlog : ブログカード\n(外部URL)
    PortalDashboard --> MediaList : もっと見る\n/hinata/media_list.php
    PortalDashboard --> VideoModal : 動画カードクリック\n(モーダル表示)

    state "ポータル情報管理" as PortalInfoAdmin {
        [*] --> TopicTab
        TopicTab --> AnnouncementTab : タブ切り替え
        AnnouncementTab --> DeadlineTab : タブ切り替え
        DeadlineTab --> TopicTab : タブ切り替え
    }

    PortalInfoAdmin --> PortalDashboard : ポータルリンク\n/hinata/
    PortalInfoAdmin --> SaveTopicAPI : トピック保存\nPOST /hinata/api/save_topic.php
    PortalInfoAdmin --> SaveAnnouncementAPI : お知らせ保存\nPOST /hinata/api/save_announcement.php
    PortalInfoAdmin --> UploadTopicImageAPI : 画像アップロード\nPOST /hinata/api/upload_topic_image.php
    PortalInfoAdmin --> UploadAnnouncementImageAPI : 画像アップロード\nPOST /hinata/api/upload_announcement_image.php

    SaveTopicAPI --> PortalInfoAdmin : 成功時リロード
    SaveAnnouncementAPI --> PortalInfoAdmin : 成功時リロード

    state "サブ機能各画面（非スコープ）" as SubApps {
        EventDetail
        MemberDetail
        OshiSettings
        MeetGreetReport
        Calendar
        ReleaseDetail
        SongDetail
        MediaList
    }
```

## 2. アプリカードからの遷移先一覧
ポータルダッシュボードの「アプリ」セクションから以下の画面へ遷移する。

| アプリカード | 遷移先 |
| :--- | :--- |
| ミーグリネタ帳 | /hinata/talk.php |
| ミーグリ予定 | /hinata/meetgreet.php |
| レポ登録 | /hinata/meetgreet_report.php |
| イベント | /hinata/events.php |
| メンバー帳 | /hinata/members.php |
| 楽曲 | /hinata/songs.php |
| アー写 | /hinata/artist_photos.php |
| 動画一覧 | /hinata/media_list.php |

## 3. 管理者限定ツール（管理ツールdetails内）
| ツール | 遷移先 |
| :--- | :--- |
| リリース管理 | /hinata/release_admin.php |
| ポータル情報管理 | /hinata/portal_info_admin.php |
| 動画・メンバー紐付け | /hinata/media_member_admin.php |
| 動画・楽曲紐付け | /hinata/media_song_admin.php |
| 動画設定 | /hinata/media_settings_admin.php |
| メディア登録 | /hinata/media_register.php |
