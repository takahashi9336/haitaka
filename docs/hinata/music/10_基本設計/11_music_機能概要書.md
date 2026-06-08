# Music（楽曲・リリース・アーティスト写真） 機能概要書

## 1. 目的と背景
- 日向坂46（およびけやき坂46）のディスコグラフィを体系的に管理し、リリース（シングル/アルバム等）単位の収録曲、参加メンバー、フォーメーション、ストリーミングURL、関連動画、アーティスト写真を一元化する。
- ライブイベントのセットリスト（`hn_setlists`）やメディア管理（`hn_media_metadata` / `com_media_assets`）と連携し、楽曲を軸にした横断的なデータ参照を可能にする。

## 2. 解決するペインポイント（課題）
- リリースごとの収録曲・メンバー構成・フォーメーションがバラバラに散在し、一望できない。
- 楽曲ごとのストリーミング配信リンク（Apple Music / Spotify）が管理されておらず、試聴導線がない。
- リリースごとのアーティスト写真（アー写）が管理されず、フォーメーション表示時にメンバーの正しいビジュアルを出せない。

## 3. コアバリュー（主要な提供価値）
- **リリース一覧と全曲一覧の二軸閲覧**: タブ切り替えでリリース単位のブラウズと全楽曲横断のブラウズを提供する。
- **フォーメーション可視化**: 楽曲ごとの列（row）・ポジション・センターを管理し、フォーメーション図としてビジュアル表示する。アー写画像を `object-cover object-top` で表示し、顔切れを防ぐ。
- **ストリーミング試聴**: Apple Music / Spotify の埋め込みプレイヤーによるワンクリック試聴。
- **管理者向け一括編集**: リリース・楽曲・メンバー構成・アー写をモーダルやドラッグ&ドロップで効率的に管理できる。

## 4. スコープ
- **対象ユーザー**: ログインユーザー全般（閲覧）、日向坂ポータル管理者（admin / hinata_admin）のみ（編集・登録・削除）
- **関連する主要システム/外部API**: なし（外部APIへの依存はない。ストリーミングは URL 埋め込みのみ）
- **対象機能（Music サブドメイン配下）**
  - **楽曲管理**: 楽曲トップ（リリース一覧 / 全曲一覧）、楽曲個別紹介、参加メンバー編集
  - **リリース管理**: リリース管理画面（CRUD）、リリース詳細（収録曲一覧）、楽曲順序編集、ストリーミングURL一括編集
  - **アーティスト写真**: リリース別アー写登録、アー写一覧閲覧（リリース別 / メンバー別タブ）
- **非スコープ**
  - メディア管理（`hn_media_metadata` / `com_media_assets`）の CRUD 自体（Music からは参照のみ）
  - ライブイベント・セットリスト管理（楽曲詳細画面からの参照リンクのみ）
  - メンバー帳（`hn_members`）の管理自体（Music からは参照のみ）

## 5. 現状（実装）サマリ
- **Controller**:
  - `SongController`（楽曲トップ / 楽曲詳細 / メンバー編集 / メンバー保存API）
  - `ReleaseController`（リリース管理 / リリース詳細 / アー写登録 / リリース保存・削除・詳細取得API）
  - `ArtistPhotoController`（アー写一覧閲覧）
- **Model**:
  - `SongModel`（`hn_songs`）、`SongMemberModel`（`hn_song_members`）
  - `ReleaseModel`（`hn_releases`）、`ReleaseEditionModel`（`hn_release_editions`）
  - `ReleaseMemberImageModel`（`hn_release_member_images`）
- **全テーブルで `isUserIsolated = false`**（全ユーザー共通データ）

## 6. データの基本方針
- **リリース**: `hn_releases` に種別（single/album/digital/ep/best）・グループ（hinatazaka46/hiragana_keyaki）・発売日等を保持。版別ジャケット画像は `hn_release_editions` で管理。
- **楽曲**: `hn_songs` はリリースに従属（`release_id` FK, ON DELETE CASCADE）。`track_type` で収録形態（表題曲/TYPE-A/TYPE-B 等）を区分。
- **参加メンバー**: `hn_song_members` で楽曲×メンバーの中間テーブル。`row_number`（1-5列）/ `position`（左端=1）/ `is_center` でフォーメーション情報を保持。
- **動画リンク**: `hn_song_media_links` で楽曲と `hn_media_metadata` を n:1 で紐付け（1曲に複数動画、1動画は1曲）。Music サブドメインが所有するが、メディアサブドメインのテーブルを参照する。
- **アーティスト写真**: `hn_release_member_images` でリリース×メンバーの画像URLを保持。フォーメーション表示時にメンバー既定画像より優先される。
