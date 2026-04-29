# 設計検討：リリース版別ジャケット画像（シングル TYPE-A～D・通常版）

日向坂46のシングルは「初回限定 TYPE-A / B / C / D」「通常版」など版ごとにジャケット画像が異なるため、**版ごとの情報**を管理するテーブルを設ける。ジャケット画像はその1要素として扱う。

**設計方針**  
- **hn_release_editions** … リリースごとの「版（edition）」単位の情報管理テーブル。1行＝1リリースの1版。  
- 各版に **jacket_image_url** を持たせる（版ごとの情報の一要素）。将来、版別の説明・品番なども同じテーブルで拡張可能。  
- `hn_releases.jacket_image_url` は **廃止・削除**。現状データなしのため DROP のみ。メインジャケットは **原則 type_a** の行の `jacket_image_url` を使用する。  
- **リリース管理画面**で edition（版）も管理する。版の追加・編集・ジャケット画像の設定を同一画面で行う。  
- 楽曲の `track_type` と **edition を紐づけ**、どの版のジャケットを表示するかを決める。

---

## 1. 要件の整理

| 項目 | 内容 |
|------|------|
| 対象 | 主に **シングル** のディスコグラフィー |
| 版の種類 | 初回限定 TYPE-A、TYPE-B、TYPE-C、TYPE-D、通常版（最大5種） |
| やりたいこと | 版別にジャケット画像を登録し、一覧・詳細で表示したい |
| メインジャケット | **原則 type_a** の画像を使用（一覧・代表表示・OGP 等） |
| 既存カラム | `hn_releases.jacket_image_url` は **廃止・削除**（現状データなしのため DROP のみ） |
| 画面方針 | **リリース管理画面**で edition（版）も管理する（版の登録・編集・ジャケット画像の設定を同一画面で行う） |

※ アルバムも「初回限定」「通常版」などがある場合は、同じ仕組みを流用可能とする。

---

## 2. 現行スキーマの確認

- **hn_releases**  
  - `jacket_image_url` varchar(255) … **廃止**。現状データが入っていないため、カラムを DROP するのみでよい。
- **hn_songs**  
  - `track_type` に `title`, `read`, `sub`, `type_a`～`type_d`, `normal` 等が定義済み。  
  - **track_type と edition の対応**をアプリ／表示で用いる（後述）。

版（edition）の概念は楽曲の `track_type` と対応づけ、**リリース単位の画像**は版別テーブルで持つ。

---

## 3. 設計案の比較と採用方針

### 案A：版ごとの情報管理テーブル → **採用**

**テーブル**: `hn_release_editions`

| 観点 | 内容 |
|------|------|
| 役割 | **edition ごとの情報管理**。1行＝1リリースの1版。ジャケット画像はその中の1カラム（`jacket_image_url`）。 |
| 構造 | release_id + edition（type_a / type_b / type_c / type_d / normal）+ jacket_image_url ＋ 将来の版別項目 |
| メリット | 版単位で情報を拡張しやすい（説明・品番など）。ジャケット以外も同じテーブルで管理できる。メインは type_a で一元化できる。 |
| デメリット | テーブル・マイグレーションが1本増える。 |

**採用方針**: 案Aを採用する。リリース管理画面において、edition（版）もあわせて管理する（版の追加・編集・ジャケット画像の登録を同一画面で行う）。

### 案B：hn_releases にカラム追加（不採用）

**例**: `jacket_type_a_url`, `jacket_type_b_url`, … を5本追加

| 観点 | 内容 |
|------|------|
| メリット | 実装が単純。JOIN 不要。 |
| デメリット | 版が増えたときにスキーマ変更が必要。アルバムなど版がないリリースは NULL だらけになりがち。 |

### 案C：既存 jacket_image_url のみ運用で複数URLを格納（不採用）

**例**: JSON やカンマ区切りで複数 URL を1カラムに格納

| 観点 | 内容 |
|------|------|
| デメリット | どの URL がどの版か判別しづらい。検索・正規化が面倒。非推奨。 |

---

## 4. 採用案：版ごとの情報テーブル（hn_release_editions）の詳細

### 4.1 テーブル定義案

**コンセプト**: リリースの「版」ごとに1行。版に紐づく情報（ジャケット画像など）をまとめて管理する。ジャケット画像はその1要素。

```
テーブル名: hn_release_editions

カラム:
- id                  bigint UNSIGNED AUTO_INCREMENT  PK
- release_id          int NOT NULL                    リリースID（hn_releases.id）
- edition             enum('type_a','type_b','type_c','type_d','normal')  版
- jacket_image_url    varchar(255) DEFAULT NULL       当該版のジャケット画像URL（版情報の1要素）
- sort_order          tinyint DEFAULT 0               表示順（任意）
- created_at          datetime DEFAULT CURRENT_TIMESTAMP
- （将来）memo, catalog_number など版別の項目を追加可能

UNIQUE KEY (release_id, edition)  … 1リリース・1版につき1行
FOREIGN KEY (release_id) REFERENCES hn_releases(id) ON DELETE CASCADE
```

- **edition** は `hn_songs.track_type` と対応づける（4.3 参照）。「この版の情報（ジャケット等）」「この版の収録曲」を一貫して扱える。
- シングル以外（アルバム・デジタル等）も、同じテーブルで「初回」「通常」などを edition で区別して持てる（必要なら enum を拡張）。

### 4.2 hn_releases.jacket_image_url の廃止

| 方針 | 説明 |
|------|------|
| **廃止** | `hn_releases.jacket_image_url` は **削除**する。ジャケットは **hn_release_editions.jacket_image_url** で管理する。 |
| メインジャケット | リリースの代表画像は **原則 type_a** とする。一覧・OGP・SNS 等では `hn_release_editions` の `edition = 'type_a'` の行の `jacket_image_url` を使用する。 |
| 移行 | **現状、hn_releases.jacket_image_url にはデータが入っていない**ため、カラムを DROP するだけでよい（データ移行は不要）。 |

### 4.3 hn_songs.track_type と edition の紐づけ

楽曲の「どの版に属するか」は `track_type` から次のルールで **edition** に変換する。表示時や「この楽曲のリリースで使うジャケット」の判定に利用する。

| track_type | edition（版） | 備考 |
|------------|----------------|------|
| `title` | **type_a** | 表題曲 → TYPE-A 版と紐づけ |
| `read` | **type_a** | アルバムリード曲 → TYPE-A 版と紐づけ |
| `sub` | **type_b** | 共通曲 → TYPE-B 版と紐づけ |
| `type_a` | type_a | そのまま |
| `type_b` | type_b | そのまま |
| `type_c` | type_c | そのまま |
| `type_d` | type_d | そのまま |
| `normal` | normal | そのまま |
| `other` | （未対応） | 必要に応じて type_a フォールバック等 |

- **アプリ側の扱い**: 上記マッピングを定数または DB で持ち、`track_type` から edition を算出する。楽曲詳細で「この曲が載っている版のジャケット」を出すときなどに使用する。
- **DB に edition を持たない場合**: `hn_songs` に `edition` カラムを追加せず、表示・API で上記ルールを適用するだけでよい。将来「楽曲ごとに明示的に版を切り替えたい」場合は `hn_songs.edition` を追加する拡張を検討する。

### 4.4 リリース管理画面での edition 管理

- **方針**: リリース管理画面（release_admin）で **edition（版）もあわせて管理**する。
- リリースの登録・編集時に、そのリリースに紐づく版（TYPE-A～D・通常版）の追加・編集・削除ができるようにする。
- 各版ごとに `jacket_image_url` を設定（入力またはアップロード結果 URL を保存）。**type_a は必須**（メインジャケットのため）。
- API は `release_id` に紐づく `hn_release_editions` を返す。フロントは版ラベルと各版の `jacket_image_url` を対応づけて表示する。

### 4.5 データの流れ

- リリース登録・編集画面で edition を管理し、各版の `jacket_image_url` を設定する。
- 一覧・代表表示では `edition = 'type_a'` の行の `jacket_image_url` を使用する。type_a が未登録の場合は表示用にプレースホルダーまたは「未設定」とする。

### 4.6 表示イメージ（参考）

- **一覧**: `hn_release_editions` の **type_a** の行の `jacket_image_url`（メインジャケット）。
- **詳細**: タブやセレクトで「TYPE-A」「TYPE-B」…「通常版」を切り替え、該当する版の `jacket_image_url` を表示。
- **楽曲に紐づくジャケット**: その楽曲の `track_type` を 4.3 のルールで edition に変換し、そのリリースのその edition の行の `jacket_image_url` を表示する。

---

## 5. 今後の拡張

- **版ごとの情報の追加**  
  - `hn_release_editions` は「版ごとの情報管理」なので、`memo`（版別備考）・`catalog_number`（品番）・`description` など、版単位の項目をカラム追加で対応できる。
- **アルバム**で「初回」「通常」など版を分ける場合  
  - `edition` に `first_press`, `normal` などを追加するか、別 enum を検討。
- **画像が複数**（表・裏・特典など）になる場合  
  - 別テーブルで「版＋画像種別」を管理するか、`hn_release_editions` に `jacket_back_url` などを追加する拡張が可能。

---

## 6. まとめ

| 項目 | 結論 |
|------|------|
| 採用案 | **版ごとの情報管理テーブル** `hn_release_editions`（1行＝1リリースの1版） |
| ジャケット | 版情報の1要素として `jacket_image_url` を持つ。将来、版別の説明・品番なども同一テーブルで拡張可能。 |
| edition | type_a / type_b / type_c / type_d / normal（hn_songs.track_type と対応） |
| メインジャケット | **原則 type_a** の行の `jacket_image_url`（一覧・OGP・代表表示） |
| 既存 | `hn_releases.jacket_image_url` は **廃止・削除**。現状データなしのため **DROP のみ**（データ移行不要）。 |
| 画面 | **リリース管理画面**で edition も管理する（版の追加・編集・ジャケット画像の設定を同一画面で行う）。 |
| track_type → edition | `title` / `read` → type_a、`sub` → type_b。その他 type_a～normal は同名の edition に対応。 |
| 実装順 | ① `hn_release_editions` テーブル追加 ② `hn_releases.jacket_image_url` 削除（DROP） ③ リリース管理画面で edition 管理・版情報（jacket_image_url 含む）の登録・取得（type_a 必須） ④ フロントで版別表示・楽曲に紐づくジャケット表示 |

この方針でコード修正に進む。必要なら「画像アップロード先（S3 / ローカル）」や「URL のみかファイル管理もするか」を別ドキュメントで整理する。
