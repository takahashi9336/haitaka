# live_trip 画面遷移図

## 1. 画面遷移フロー

```mermaid
flowchart LR
  subgraph nav [認可後]
    Index["index.php\n遠征一覧"]
    Show["show.php?id=\n遠征詳細"]
    Create["create.php\n新規"]
    Edit["edit.php?id=\n編集"]
    MyList["my_list.php\nマイリスト"]
    LtEv["lt_event_create.php\n汎用イベント"]
  end
  Index -->|行クリック| Show
  Index -->|新規| Create
  Create -->|保存| Show
  Create -->|キャンセル| Index
  Show -->|編集| Edit
  Edit -->|更新| Show
  Edit -->|削除| Index
  Show -->|持ち物テンプレ| MyList
  Index --> MyList
  Show -->|汎用イベント追加| LtEv
  LtEv --> Show
```

注:

- 費用・宿泊等の **部分更新** は詳細画面内 POST → リダイレクトで `show.php` に戻る典型形。詳細は [23](../20_詳細設計/23_live_trip_処理詳細_I-O定義書.md)。
- **しおり**（`shiori.php`）は印刷向けの別画面。一覧・詳細からの遷移矢印は設けず、`?id=` を付けて直接アクセスする想定。
