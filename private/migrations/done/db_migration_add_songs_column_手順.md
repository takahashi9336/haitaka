# hn_songs テーブル カラム追加・変更 移行手順

`db_migration_add_songs_column.sql` を既存テーブルに適用する際の手順です。

---

## 前提・注意

- **必ず本番実行前にバックアップを取得してください。**
- 既存の `track_type` が `db_migration_add_track_types.sql` 適用後（`coupling`, `album_only`, `kisei`, `unit`, `solo`, `other` 等）の場合、**いきなり新しい enum に MODIFY すると既存データが新しい値に存在せずエラーになります。** そのため「データ移行」を挟みます。
- 新規作成の空テーブルなら、`db_migration_add_songs_column.sql` をそのまま順に実行すれば問題ありません。

---

## 手順概要

1. バックアップ
2. 既存データの確認（任意だが推奨）
3. **track_type のデータ移行**（既存データがある場合）
4. マイグレーション SQL の実行
5. 実行後確認

---

## 1. バックアップ

```sql
-- 例: テーブルごとバックアップ
CREATE TABLE hn_songs_backup_YYYYMMDD AS SELECT * FROM hn_songs;
```

または mysqldump で `hn_songs` を取得してください。

---

## 2. 既存データの確認（推奨）

```sql
-- track_type の現状
SELECT track_type, COUNT(*) FROM hn_songs GROUP BY track_type;

-- 行数
SELECT COUNT(*) FROM hn_songs;
```

`track_type` に `title` 以外（`coupling`, `album_only`, `kisei`, `unit`, `solo`, `other` 等）が含まれている場合は、次の「3. データ移行」が必須です。

---

## 3. track_type のデータ移行（既存データがある場合のみ）

新しい `track_type` の enum は次のみです。  
`title`, `read`, `sub`, `type_a`, `type_b`, `type_c`, `type_d`, `normal`, `other`

いきなり MODIFY すると、既存の `coupling` / `album_only` / `kisei` / `unit` / `solo` 等でエラーになるため、**先に新しい enum に収まる値へ更新**します。

### 3-1. 一時的に enum を「旧＋新」で広げる

```sql
ALTER TABLE `hn_songs`
MODIFY COLUMN `track_type` enum(
    'title','coupling','album_only','bonus','kisei','unit','solo','other',  -- 既存
    'read','sub','type_a','type_b','type_c','type_d','normal'               -- 新
) DEFAULT 'other' COMMENT 'トラックタイプ（移行用）';
```

### 3-2. 旧値 → 新値へ UPDATE（必要に応じてマッピングを調整）

```sql
UPDATE hn_songs SET track_type = CASE
    WHEN track_type = 'title'       THEN 'title'
    WHEN track_type = 'coupling'    THEN 'normal'
    WHEN track_type = 'album_only'  THEN 'sub'
    WHEN track_type = 'bonus'       THEN 'normal'
    WHEN track_type = 'kisei'       THEN 'other'
    WHEN track_type = 'unit'        THEN 'other'
    WHEN track_type = 'solo'        THEN 'other'
    WHEN track_type = 'other'       THEN 'other'
    ELSE 'other'
END;
```

### 3-3. 新しい enum のみに MODIFY（本マイグレーションの 1 本目と同等）

```sql
ALTER TABLE `hn_songs`
MODIFY COLUMN `track_type` enum(
    'title','read','sub','type_a','type_b','type_c','type_d','normal','other'
) DEFAULT 'other' COMMENT 'トラックタイプ';
```

ここまでで `track_type` の移行は完了です。

---

## 4. マイグレーション SQL の実行

**3. を実行した場合**は、`db_migration_add_songs_column.sql` の **1 本目の ALTER（track_type の MODIFY）はスキップ**し、2 本目以降だけ実行します。

**3. を実行していない場合**（空テーブルや、もともと新 enum だけの状態）は、ファイルを先頭からそのまま実行して構いません。

### 実行する文（3. を実行した場合の例）

```sql
-- formation_type 追加
ALTER TABLE `hn_songs`
ADD `formation_type` enum(
    'all','kibetsu','senbatsu','solo','under','unit','other'
) DEFAULT 'other' COMMENT 'フォーメーションタイプ';

-- 新規カラム追加
ALTER TABLE hn_songs ADD generation tinyint DEFAULT NULL COMMENT 'hn_members.generation（期別曲の場合の期）' AFTER formation_type;
ALTER TABLE hn_songs ADD arranger varchar(50) DEFAULT NULL COMMENT 'アレンジャー' AFTER composer;
ALTER TABLE hn_songs ADD mv_director varchar(50) DEFAULT NULL COMMENT 'MV監督' AFTER arranger;
ALTER TABLE hn_songs ADD choreographer varchar(50) DEFAULT NULL COMMENT '振付師' AFTER mv_director;
```

---

## 5. 実行後確認

```sql
DESCRIBE hn_songs;

-- または
SHOW CREATE TABLE hn_songs;
```

次のカラム・定義になっていることを確認してください。

- `track_type` … 新しい enum（title, read, sub, type_a～d, normal, other）
- `formation_type` … 新規（all, kibetsu, senbatsu, solo, under, unit, other）
- `generation` … 新規（tinyint, NULL 許容）
- `arranger` … 新規（varchar(50)）
- `mv_director` … 新規（varchar(50)）
- `choreographer` … 新規（varchar(50)）

---

## 実施した修正（マイグレーション SQL 側）

- `tinyinto` → `tinyint` の typo 修正
- `track_type` / `formation_type` の enum に `'other'` を追加（`DEFAULT 'other'` と整合するように）

以上が既存テーブル向けの移行手順です。
