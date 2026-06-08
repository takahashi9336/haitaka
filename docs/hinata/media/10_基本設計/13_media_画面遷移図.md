# Media（メディア管理） 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> HinataPortal : 日向坂ポータルトップ

    HinataPortal --> MediaList : サイドバー「動画」

    MediaList --> MediaList : 無限スクロール（loadMore API）
    MediaList --> MediaList : フィルタ変更（platform/category/member/generation/media_type）
    MediaList --> ExternalPlayer : カード押下（YouTube/TikTok/Instagram 外部遷移）
    MediaList --> MediaRegister : 「メディア登録」リンク（管理者のみ）

    HinataPortal --> MediaRegister : サイドバー「メディア登録」（管理者のみ）

    state MediaRegister {
        [*] --> YouTubeSearchTab : デフォルト
        YouTubeSearchTab --> URLPasteTab : 「URL貼り付け」タブ
        URLPasteTab --> CSVImportTab : 「CSV/TSV」タブ
        CSVImportTab --> YouTubeSearchTab : 「YouTube検索」タブ
    }

    MediaRegister --> MediaList : 「動画一覧へ」リンク

    HinataPortal --> MediaAdmin : サイドバー「動画管理」（管理者のみ）

    state MediaAdmin {
        [*] --> MemberTab : デフォルト（メンバー紐付けタブ）
        MemberTab --> SongTab : 「楽曲紐付け」タブ
        SongTab --> SettingsTab : 「設定」タブ
        SettingsTab --> MemberTab : 「メンバー紐付け」タブ
    }

    MediaAdmin --> HinataPortal : 「ポータルへ戻る」
```

## 2. バッチジョブフロー

```mermaid
stateDiagram-v2
    [*] --> RunAll : cron 0 0,9,12,15,18,21 * * *

    state RunAll {
        [*] --> BlogLatest : 毎回
        BlogLatest --> NewsScrape : 毎回
        NewsScrape --> ScheduleScrape : 毎回
        ScheduleScrape --> YouTubeImport : 毎回
        YouTubeImport --> CheckWeekly

        state CheckWeekly <<choice>>
        CheckWeekly --> YouTubeRefresh : 日曜 09:00
        CheckWeekly --> [*] : それ以外
        YouTubeRefresh --> BlogMembers : 日曜 09:00
        BlogMembers --> [*]
    }

    state TikTokClient {
        [*] --> ClientApp : Windows クライアント
        ClientApp --> TikTokImport : POST /batch/tiktok_client_import
        TikTokImport --> [*]
    }
```

## 3. 遷移パラメータ

| 遷移元 | 遷移先 | パラメータ | 説明 |
| :--- | :--- | :--- | :--- |
| 動画一覧 | 動画一覧（追加ロード） | `?offset=&limit=25&category=&sort=newest&platform=&member_id=&generation=&media_type=` | 無限スクロール用。offset をインクリメント |
| ポータルトップ | メディア登録 | なし | 管理者ロールのみアクセス可 |
| ポータルトップ | 動画管理（統合） | `media_member_admin.php` / `media_song_admin.php` / `media_settings_admin.php` | 初期タブが member / song / settings に分岐 |
| 動画管理 | 紐付け管理用動画一覧 | `?q=&category=&platform=&media_type=&unlinked_only=&link_type=song&limit=100` | 動画管理画面内の動画検索 |
| バッチ統合ランナー | ブログスクレイプ | `[pages] [mode]`（CLI 引数） | mode: latest / members |
| バッチ統合ランナー | ニューススクレイプ | `[months]`（CLI 引数） | 対象月数（当月+翌月） |
| バッチ統合ランナー | スケジュールスクレイプ | `[months]`（CLI 引数） | 対象月数（当月+翌月） |
| バッチ統合ランナー | YouTube リフレッシュ | `[limit]`（CLI 引数） | 処理件数上限 |
| 外部クライアント | TikTok インポート | `POST { token, account, urls[], category }` | ヘッダ or ボディでトークン認証 |
