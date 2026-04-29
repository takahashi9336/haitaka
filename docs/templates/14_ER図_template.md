<!--
新規機能設計書を生成するときの AI 指示:
- migrations と Model の $table/$fields を突き合わせ、現在の実装に合わせたエンティティ名を使う。
- システム横断テーブル（sys_* / hn_* を参照のみする場合）は関係線で「参照」とラベル。
- ER図には主要カラム型と PK/FK を Mermaid の entity 構文で書く。
-->
# [機能名] ER図

## 1. データモデル関係図
```mermaid
erDiagram
    TABLE_A ||--o{ TABLE_B : "has many"
    TABLE_A {
        bigint id PK
        varchar name
    }
    TABLE_B {
        bigint id PK
        bigint table_a_id FK
    }
```