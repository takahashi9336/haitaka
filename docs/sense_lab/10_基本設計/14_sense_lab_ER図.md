# Sense Lab（センスラボ） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sl_sense_entries ||--o{ sl_sense_quick_entries : "linked_entry_id"

    sl_sense_entries {
        bigint_unsigned id PK "AUTO_INCREMENT"
        int user_id "NOT NULL / ユーザーID"
        varchar_255 title "NOT NULL / 直感的なタイトル"
        varchar_500 image_path "NULL / 画像の相対URLパス"
        varchar_50 category "NOT NULL / food|design|daily|other"
        text reason_1 "NULL / なぜ良いと思ったか 1つ目"
        text reason_2 "NULL / なぜ良いと思ったか 2つ目"
        text reason_3 "NULL / なぜ良いと思ったか 3つ目"
        datetime created_at "DEFAULT CURRENT_TIMESTAMP"
        datetime updated_at "DEFAULT CURRENT_TIMESTAMP ON UPDATE"
    }

    sl_sense_quick_entries {
        bigint_unsigned id PK "AUTO_INCREMENT"
        int user_id "NOT NULL / ユーザーID"
        varchar_50 app_key "NULL / 起点アプリのapp_key"
        varchar_255 page_title "NULL / 起点ページタイトル"
        varchar_500 source_url "NULL / 起点URL パス+クエリ"
        varchar_50 category_hint "NULL / カテゴリラベル候補"
        text note "NOT NULL / ラフメモ"
        varchar_500 image_path "NULL / 画像の相対URLパス"
        text reason_1 "NULL / なぜ良いと思ったか 1つ目"
        text reason_2 "NULL / なぜ良いと思ったか 2つ目"
        text reason_3 "NULL / なぜ良いと思ったか 3つ目"
        bigint_unsigned linked_entry_id FK "NULL / 本番スクラップへの紐付き"
        datetime created_at "DEFAULT CURRENT_TIMESTAMP"
        datetime updated_at "DEFAULT CURRENT_TIMESTAMP ON UPDATE"
    }
```

## 2. インデックス定義

### sl_sense_entries
| インデックス名 | カラム | 用途 |
| :--- | :--- | :--- |
| PRIMARY | `id` | 主キー |
| `idx_user` | `user_id` | ユーザー別の一覧取得 |
| `idx_category` | `category` | カテゴリ別フィルタ・集計 |
| `idx_created_at` | `created_at` | 作成日順ソート |

### sl_sense_quick_entries
| インデックス名 | カラム | 用途 |
| :--- | :--- | :--- |
| PRIMARY | `id` | 主キー |
| `idx_user_created` | `user_id, created_at` | ユーザー別・日時順の一覧取得 |
| `idx_app` | `app_key` | 起点アプリ別の絞り込み |
| `idx_linked` | `linked_entry_id` | 本番スクラップとの紐付き検索 |

## 3. マイグレーション履歴
| ファイル名 | 概要 |
| :--- | :--- |
| `create_sl_sense_lab.sql` | `sl_sense_entries` と `sl_sense_quick_entries` の初期作成、`sys_apps` への登録 |
| `alter_sl_sense_entries_image_path_nullable.sql` | `sl_sense_entries.image_path` を NULL許可に変更 |
| `fix_sl_sense_entries_image_path_to_uploads.sql` | 画像パスを `/upload/` から `/uploads/` に統一 |
| `add_sl_sense_quick_entries_image_path.sql` | `sl_sense_quick_entries` に `image_path` カラムを追加 |
| `add_sl_sense_quick_entries_reasons.sql` | `sl_sense_quick_entries` に `reason_1` 〜 `reason_3` カラムを追加 |
