# Music（楽曲・リリース・アーティスト写真） 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> HinataPortal : 日向坂ポータルトップ

    HinataPortal --> SongIndex : サイドバー「楽曲」
    HinataPortal --> ArtistPhotosIndex : サイドバー「アー写」

    state SongIndex {
        [*] --> ReleasesTab : デフォルト
        ReleasesTab --> SongsTab : 「全曲」タブ
        SongsTab --> ReleasesTab : 「リリース」タブ
    }

    SongIndex --> ReleaseShow : リリースカード押下 ?id=
    SongIndex --> SongDetail : 全曲タブの曲名押下 ?id=&from=songs
    SongIndex --> ReleaseAdmin : 「リリース管理」ボタン（管理者のみ）

    ReleaseShow --> SongDetail : 収録曲名押下 ?id=&from=release&release_id=
    ReleaseShow --> ReleaseArtistPhotos : 「アーティスト写真」リンク（管理者のみ）
    ReleaseShow --> SongIndex : 「リリース一覧へ戻る」

    SongDetail --> SongMemberEdit : 「参加メンバー編集」ボタン（管理者のみ）
    SongDetail --> ReleaseShow : 「収録」リリースリンク
    SongDetail --> SongIndex : 「楽曲一覧へ戻る」

    SongMemberEdit --> SongDetail : 「戻る」ボタン

    ReleaseAdmin --> ReleaseAdmin : 新規登録モーダル / 編集モーダル / 削除
    ReleaseAdmin --> HinataPortal : 「ポータルへ戻る」

    ReleaseArtistPhotos --> ReleaseShow : 「リリース詳細へ戻る」

    ArtistPhotosIndex --> ReleaseShow : リリース詳細リンク
    ArtistPhotosIndex --> ReleaseAdmin : 「リリース管理」リンク（管理者のみ）
```

## 2. 遷移パラメータ

| 遷移元 | 遷移先 | パラメータ | 説明 |
| :--- | :--- | :--- | :--- |
| 楽曲トップ | リリース詳細 | `?id={release_id}` | リリースID |
| 楽曲トップ (全曲タブ) | 楽曲詳細 | `?id={song_id}&from=songs` | 戻り先判定用 |
| リリース詳細 | 楽曲詳細 | `?id={song_id}&from=release&release_id={release_id}` | 戻り先をリリース詳細に設定 |
| 楽曲詳細 | メンバー編集 | `?song_id={song_id}` | 編集対象の楽曲ID |
| リリース詳細 | アー写登録 | `?release_id={release_id}` | 対象リリースID |
| 楽曲トップ | 楽曲トップ | `?group={group_name}` | グループフィルタ（hinatazaka46 / hiragana_keyaki） |
| 楽曲トップ | 楽曲トップ | `?tab=songs&release_id={id}` | 特定リリースの曲だけ表示 |
| アー写一覧 | アー写一覧 | `?tab=releases` / `?tab=members` | 表示モード切替 |
| アー写一覧 | アー写一覧 | `?tab=members&member_id={id}` | 特定メンバーへスクロール |
