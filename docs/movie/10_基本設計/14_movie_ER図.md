# 映画（Movie） ER図

## 1. データモデル関係図

```mermaid
erDiagram
    sys_users ||--o{ mv_user_movies : "has many"
    mv_movies ||--o{ mv_user_movies : "has many"
    mv_movies ||--o{ mv_movie_credits : "has many"

    sys_users {
        int id PK
        varchar id_name
        varchar display_name
    }

    mv_movies {
        bigint id PK
        int tmdb_id UK "NULLは仮登録"
        varchar title "日本語タイトル"
        varchar original_title "原題"
        text overview "あらすじ"
        varchar poster_path "TMDBポスター画像パス"
        varchar backdrop_path "TMDB背景画像パス"
        date release_date "公開日"
        decimal vote_average "TMDB平均評価"
        int vote_count "TMDB評価数"
        varchar genres "ジャンル(JSON配列)"
        int runtime "上映時間(分)"
        json watch_providers "配信サービス情報"
        datetime watch_providers_updated_at "配信情報取得日時"
        datetime created_at
        datetime updated_at
    }

    mv_user_movies {
        bigint id PK
        int user_id FK "sys_users.id"
        bigint movie_id FK "mv_movies.id"
        varchar status "watchlist/watched"
        tinyint rating "個人評価(1-10)"
        text memo "個人メモ・感想"
        date watched_date "視聴日"
        json tags "ユーザー定義タグ"
        datetime created_at
        datetime updated_at
    }

    mv_movie_credits {
        bigint id PK
        bigint movie_id FK "mv_movies.id"
        int tmdb_movie_id "mv_movies.tmdb_id"
        varchar role_kind "cast/director/writer"
        tinyint rank_no "作品内の順序"
        int person_tmdb_id "TMDB person id"
        varchar person_name "表示名"
        varchar character_name "役名(castのみ)"
        varchar job_name "job(crewのみ)"
        varchar department "department(crewのみ)"
        datetime created_at
        datetime updated_at
    }
```

## 2. テーブル間の関係説明

| 関係 | 説明 |
| :--- | :--- |
| `sys_users` → `mv_user_movies` | 1ユーザーが複数の映画をリストに持てる（1:N）。`user_id` + `movie_id` でユニーク制約 |
| `mv_movies` → `mv_user_movies` | 1つの映画キャッシュに対して複数ユーザーがエントリを持てる（1:N）。ON DELETE CASCADE |
| `mv_movies` → `mv_movie_credits` | 1つの映画に対して複数の出演者情報を持つ（1:N）。ON DELETE CASCADE |

## 3. インデックス構成

| テーブル | インデックス名 | カラム | 種類 |
| :--- | :--- | :--- | :--- |
| `mv_movies` | `PRIMARY` | `id` | PK |
| `mv_movies` | `uq_tmdb_id` | `tmdb_id` | UNIQUE (NULL許容) |
| `mv_user_movies` | `PRIMARY` | `id` | PK |
| `mv_user_movies` | `uq_user_movie` | `user_id, movie_id` | UNIQUE |
| `mv_user_movies` | `idx_user_status` | `user_id, status` | INDEX |
| `mv_movie_credits` | `PRIMARY` | `id` | PK |
| `mv_movie_credits` | `uq_mv_movie_credits_movie_role_person` | `movie_id, role_kind, person_tmdb_id` | UNIQUE |
| `mv_movie_credits` | `idx_mv_movie_credits_movie_role_rank` | `movie_id, role_kind, rank_no` | INDEX |
| `mv_movie_credits` | `idx_mv_movie_credits_person` | `person_tmdb_id` | INDEX |
| `mv_movie_credits` | `idx_mv_movie_credits_role` | `role_kind` | INDEX |
