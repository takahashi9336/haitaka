# 設計：楽曲ページ（リリース一覧・全曲一覧・楽曲個別紹介）

日向坂ポータルに、**一般ユーザー向けの楽曲・リリース閲覧機能**を追加する。管理画面（release_admin）は既存のまま、公開用の「リリース一覧」「全曲一覧」「楽曲の個別紹介」の3画面を設計する。

---

## 1. 要件の整理

| 項目 | 内容 |
|------|------|
| 対象ユーザー | ログイン済みの一般ユーザー（管理者は release_admin で編集） |
| 画面 | ① リリース一覧 ② 全曲一覧 ③ 楽曲の個別紹介 |
| データソース | 既存の hn_releases / hn_release_editions / hn_songs / hn_song_members / hn_media_metadata + com_media_assets |
| 既存 | リリース管理（release_admin）、ReleaseModel / SongModel / ReleaseEditionModel はそのまま利用 |

---

## 2. 画面一覧とURL設計

| 画面 | URL（案） | 説明 |
|------|-----------|------|
| 楽曲トップ（タブ切り替え） | `/hinata/songs.php` | リリース一覧／全曲一覧をタブで切り替え。同一エントリで表示を分ける。 |
| リリース一覧 | `/hinata/songs.php` タブ「リリース」 | シングル・アルバム等をカード表示。ジャケット・タイトル・発売日・収録曲数など。 |
| 全曲一覧 | `/hinata/songs.php` タブ「全曲」 | 全楽曲を一覧（リリース名・曲名・トラック種別・発売日など）。検索・フィルタは将来拡張。 |
| 楽曲個別紹介 | `/hinata/song.php?id={id}` | 1曲の詳細：タイトル、収録リリース、クレジット、フォーメーション・センター、MVリンクなど。 |

- **エントリポイント**: `www/hinata/songs.php` → 楽曲トップ（リリース／全曲タブ）  
- **楽曲詳細**: `www/hinata/song.php` → 個別紹介（`id` 必須）

---

## 3. データ取得方針

### 3.1 リリース一覧

- **ReleaseModel::getAllReleases()** で一覧取得（既存）。
- ジャケット表示用に **ReleaseEditionModel::getEditionsByReleaseIds()** で版別情報を一括取得し、各リリースのメインジャケット（type_a の jacket_image_url）を付与。
- 収録曲数は **ReleaseModel::getReleaseWithSongs()** を全件使うと重いため、**COUNT で曲数だけ取得**するメソッドを ReleaseModel に追加するか、一覧用に「リリース＋曲数＋メインジャケットURL」を返す **getAllReleasesWithSummary()** のようなメソッドを1本用意するのがよい。

**推奨**: ReleaseModel に `getAllReleasesWithSummary()` を追加  
→ 返却: 各リリース + `jacket_url`（type_a の画像）+ `song_count`

### 3.2 全曲一覧

- **全楽曲**を「リリース名・発売日」付きで取得するメソッドが現状ない。
- **SongModel** に **getAllSongsWithRelease()** を追加する。  
  - `hn_songs` と `hn_releases` を JOIN し、`release_title`, `release_date`, `release_type` などを付与。  
  - 並び順: 発売日 DESC → track_number ASC → song id ASC。  
- 表示: 曲名、収録リリース、トラック種別、発売日。各行から楽曲詳細へリンク。

### 3.3 楽曲の個別紹介

- **SongModel::getSongWithMembers()** で楽曲＋参加メンバーは取得済み。
- 追加で必要な情報:
  - **収録リリース**: 1件なので ReleaseModel::find($release_id) で取得。
  - **MV（YouTube）**: `hn_songs.media_meta_id` → hn_media_metadata → com_media_assets で `media_key` 取得し、埋め込みURLを組み立て。
- **フォーメーション・センター**: SongModel::getFormation() / getCenterMembers() が既にあるのでそのまま利用。
- 作詞・作曲・編曲・振付・MV監督は **hn_songs** の lyricist, composer, arranger, choreographer, mv_director を表示。

---

## 4. 画面仕様（ワイヤーイメージ）

### 4.1 楽曲トップ（songs.php）— リリース一覧タブ

- ヘッダー: 「楽曲」タイトル、戻る（ポータルへ）、（管理者なら「リリース管理」リンク）。
- タブ: 「リリース」「全曲」。
- **リリースタブ**:
  - カード一覧（グリッドまたはリスト）。各カード:
    - ジャケット画像（メイン＝type_a）、なければ placeholder アイコン。
    - リリース種別ラベル（シングル／アルバム等）。
    - タイトル、リリース番号（1st 等）、発売日。
    - 収録曲数。
  - クリックで「リリース詳細」に遷移するか、その場で収録曲を展開するかは実装で選択（まずは「リリース詳細」専用ページを設けず、トップのカードから「収録曲を見る」で全曲一覧をリリースでフィルタする、またはモーダルで収録曲表示でも可）。  
  - **案**: カードクリックで **同一 songs.php の「全曲」タブに遷移し、クエリで `?release_id=xxx` を付与してフィルタ表示**するか、または **リリース詳細ページ `/hinata/release.php?id=xxx`** を用意して収録曲一覧を表示する。  
  - **推奨**: リリース詳細を **release.php?id=xxx** で用意し、そこに収録曲一覧＋各曲へのリンクを載せる。

### 4.2 楽曲トップ（songs.php）— 全曲一覧タブ

- 表形式またはカード形式。列: 曲名、収録リリース（タイトル）、トラック種別、発売日、操作（「詳細」リンク→ song.php?id=xxx）。
- オプション: `?release_id=xxx` でリリース絞り込み（リリース一覧から「収録曲を見る」で遷移した場合）。

### 4.3 楽曲個別紹介（song.php?id=xxx）

- タイトル（曲名）、よみ（title_kana があれば表示）。
- 収録リリース: リンク付き（release.php または songs.php?release_id=xxx）。
- クレジット: 作詞・作曲・編曲・振付・MV監督（存在する項目のみ表示）。
- フォーメーション: 列（row）ごとのメンバー。センターはハイライト（既存 SongModel::getFormation / getCenterMembers を利用）。
- MV: media_meta_id があれば YouTube 埋め込みまたはリンク。
- メモ（memo）があれば表示。
- 戻る: 「全曲一覧へ」など。

---

## 5. ルーティング・コントローラ・ビュー対応

| 種類 | ファイル | 役割 |
|------|----------|------|
| エントリ | `www/hinata/songs.php` | SongController::index() → リリース一覧＋全曲一覧（タブ） |
| エントリ | `www/hinata/song.php` | SongController::detail() → 楽曲個別紹介 |
| エントリ | `www/hinata/release.php` | （推奨）ReleaseController の公開用 show() → リリース詳細＋収録曲一覧 |
| コントローラ | `SongController`（新規） | index(), detail()。必要なら listByRelease()。 |
| コントローラ | `ReleaseController`（拡張） | 公開用 show($id) を追加（現状は管理用のみ）。 |
| ビュー | `Views/song_index.php` | 楽曲トップ（リリース／全曲タブ） |
| ビュー | `Views/song_detail.php` | 楽曲個別紹介 |
| ビュー | `Views/release_show.php` | リリース詳細（収録曲一覧含む） |

- 管理用の **ReleaseController** は `release_admin` 用のまま、**公開用の show()** を同じコントローラに追加し、`www/hinata/release.php` から呼ぶ形でよい。

---

## 6. モデル追加・拡張

| モデル | 追加メソッド | 説明 |
|--------|----------------|------|
| ReleaseModel | getAllReleasesWithSummary() | 一覧用。各リリースに jacket_url（type_a）, song_count を付与。 |
| SongModel | getAllSongsWithRelease() | 全曲をリリース情報付きで取得。並び: release_date DESC, track_number ASC。 |
| （既存） | getSongWithMembers(), getFormation(), getCenterMembers() | 楽曲詳細で使用。 |
| （既存） | getReleaseWithSongs() | リリース詳細で使用。 |

---

## 7. ポータル・ナビへの追加

- **日向坂ポータル**（portal.php）に「楽曲」カードを追加し、`/hinata/songs.php` へリンク。
- サイドバーに hinata 配下のリンクがある場合は「楽曲」を追加（既存のイベント・メンバー帳などと同様）。

---

## 8. track_type / formation_type の表示用ラベル

- DB の enum: track_type = title, read, sub, type_a～d, normal, other。  
  SongModel::TRACK_TYPES は現状 coupling, album_only 等の旧値なので、**表示用に DB の enum に合わせたラベル配列**を用意する（SongModel に TRACK_TYPES_DISPLAY を追加、または既存 TRACK_TYPES を enum に合わせて更新）。
- formation_type: all, kibetsu, senbatsu, solo, under, unit, other。  
  表示用ラベルを SongModel または View 用ヘルパで定義する。

---

## 9. 実装順序の提案

1. **ReleaseModel::getAllReleasesWithSummary()** の追加  
2. **SongModel::getAllSongsWithRelease()** の追加  
3. **SongModel**: 表示用 TRACK_TYPES / FORMATION_TYPES を DB enum に合わせて定義  
4. **SongController** 新規: index(), detail()  
5. **Views/song_index.php**, **Views/song_detail.php** の作成  
6. **www/hinata/songs.php**, **www/hinata/song.php** のエントリ追加  
7. **ReleaseController::show()** と **Views/release_show.php**、**www/hinata/release.php** の追加（リリース詳細）  
8. ポータルに「楽曲」カード追加  

以上で、リリース一覧・全曲一覧・楽曲の個別紹介の設計と実装方針を揃えられる。
