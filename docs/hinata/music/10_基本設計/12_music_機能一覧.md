# Music（楽曲・リリース・アーティスト写真） 機能一覧

## 1. 画面一覧
| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| 楽曲トップ（リリース一覧 / 全曲一覧） | `www/hinata/songs.php` → `SongController::index` → `Views/song_index.php` | リリース一覧タブと全曲一覧タブを切り替えて楽曲を閲覧する。グループフィルタ（日向坂46 / けやき坂46）対応 |
| 楽曲個別紹介 | `www/hinata/song.php` → `SongController::detail` → `Views/song_detail.php` | 楽曲の詳細情報（クレジット、試聴、MV、フォーメーション、参加メンバー、ライブ披露履歴）を表示する |
| 参加メンバー編集（管理者専用） | `www/hinata/song_member_edit.php` → `SongController::memberEdit` → `Views/song_member_edit.php` | 楽曲の参加メンバー・フォーメーションをドラッグ&ドロップまたはリスト形式で編集する |
| リリース詳細（収録曲一覧） | `www/hinata/release.php` → `ReleaseController::show` → `Views/release_show.php` | リリースの基本情報と収録曲一覧を表示する。管理者は楽曲順序編集・ストリーミングURL一括編集が可能 |
| リリース管理（管理者専用） | `www/hinata/release_admin.php` → `ReleaseController::admin` → `Views/release_admin.php` | リリースの一覧表示・新規登録・編集・削除をモーダルダイアログで行う |
| リリース別アー写登録（管理者専用） | `www/hinata/release_artist_photos.php` → `ReleaseController::artistPhotos` → `Views/release_artist_photos.php` | リリースごとのメンバーアーティスト写真（URL入力またはファイルアップロード）を登録する |
| アー写一覧（閲覧） | `www/hinata/artist_photos.php` → `ArtistPhotoController::index` → `Views/artist_photos_index.php` | 全リリースのアーティスト写真をリリース別 / メンバー別タブで閲覧する。画像拡大・メンバー詳細モーダル対応 |

## 2. 機能・アクション一覧
| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| 楽曲トップ表示 | 画面 | リリース一覧（ジャケット・収録曲数・表題センター付き）と全曲一覧（リリース単位グルーピング）を切替表示。推しメンバーの参加曲をハイライト |
| 楽曲個別紹介表示 | 画面 | 楽曲詳細（クレジット、ストリーミング埋め込み、MV再生、関連動画、フォーメーション図、ライブ披露履歴、メモ）を表示 |
| 参加メンバー編集 | 画面/処理 | フォーメーション編集（D&D）とリスト編集の2タブ。メンバーの列（1-5）・ポジション・センターフラグを設定 |
| 参加メンバー保存 | API (POST JSON) | `api/save_song_members.php` → `SongController::saveMembers`。楽曲の参加メンバーを全件洗い替え |
| ストリーミングURL保存 | API (POST JSON) | `api/save_song_streaming.php`。楽曲個別の Apple Music / Spotify URLを保存 |
| リリース詳細表示 | 画面 | リリース基本情報 + 収録曲一覧（ストリーミングアイコン付き）を表示 |
| 楽曲順序保存 | API (POST JSON) | `api/save_release_song_order.php`。リリース内の楽曲トラック番号を一括更新 |
| リリース管理表示 | 画面 | リリース一覧テーブル（種別バッジ・グループ・版情報・操作ボタン）を表示 |
| リリース保存 | API (POST JSON) | `api/save_release.php` → `ReleaseController::save`。リリース情報・版別ジャケット・収録曲をトランザクションで一括保存（新規/更新） |
| リリース削除 | API (POST JSON) | `api/delete_release.php` → `ReleaseController::delete`。リリースを削除（CASCADE で楽曲も削除） |
| リリース詳細取得 | API (GET JSON) | `api/detail_release.php` → `ReleaseController::detail`。編集モーダルへの初期データ投入用 |
| ジャケット画像アップロード | API (POST multipart) | `api/upload_release_jacket.php`。画像ファイルをアップロードしURLを返す |
| アー写登録画面表示 | 画面 | リリースに対するメンバー別アー写一覧をフォーム表示（期別グループ化） |
| アー写保存 | API (POST JSON) | `api/save_release_member_images.php` → `ReleaseController::saveReleaseMemberImages`。リリースのメンバー写真を全件洗い替え |
| アー写画像アップロード | API (POST multipart) | `api/upload_release_artist_photo.php`。アー写ファイルをアップロードしURLを返す |
| アー写一覧表示 | 画面 | リリース別タブ（期別グループ化）/ メンバー別タブ（リリース横断）でアー写を閲覧。画像拡大ライトボックス付き |

## 3. 関連テーブル一覧
| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| hn_releases | リリースマスタ | メインテーブル (CRUD) |
| hn_release_editions | リリース版別情報 | リリースに従属 (CRUD)。版ごとのジャケット画像URL |
| hn_songs | 楽曲マスタ | メインテーブル (CRUD)。リリースに従属 |
| hn_song_members | 楽曲参加メンバー | 中間テーブル (CRUD)。楽曲×メンバーのフォーメーション |
| hn_song_media_links | 楽曲-動画紐付け | 中間テーブル (Read)。Music が所有するが Media サブドメインと接続 |
| hn_release_member_images | リリース別アー写 | メインテーブル (CRUD)。リリース×メンバーの画像URL |
| hn_members | メンバーマスタ | 参照のみ (Read)。名前・画像・期・色情報 |
| hn_colors | カラーマスタ | 参照のみ (Read)。メンバーカラー |
| hn_media_metadata | メディアメタデータ | 参照のみ (Read)。楽曲に紐づく動画のカテゴリ |
| com_media_assets | メディアアセット | 参照のみ (Read)。YouTube等の動画情報 |
| hn_setlists | セットリスト | 参照のみ (Read)。楽曲のライブ披露履歴 |
| hn_events | イベント | 参照のみ (Read)。セットリスト経由でイベント名・日付を表示 |
