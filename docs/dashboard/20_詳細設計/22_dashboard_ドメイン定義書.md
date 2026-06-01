# ダッシュボード (dashboard) ドメイン・データモデル定義書

## 1. テーブル定義詳細

### dashboard_article_training
| カラム名 | 論理名 | 型 | 制約 | 備考・初期値 |
| :--- | :--- | :--- | :--- | :--- |
| id | ID | BIGINT UNSIGNED | PK, Auto Inc | |
| user_id | ユーザーID | INT | NOT NULL, FK (sys_users.id) | |
| article_url | 記事URL | VARCHAR(500) | NOT NULL | 対象記事のURL。http/httpsのみ許可 |
| article_title | 記事タイトル | VARCHAR(500) | NOT NULL | 空の場合はAPIがarticle_urlで補完 |
| praise_1 | ほめポイント1 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| praise_2 | ほめポイント2 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| praise_3 | ほめポイント3 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| tsukkomi_1 | ツッコミポイント1 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| tsukkomi_2 | ツッコミポイント2 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| tsukkomi_3 | ツッコミポイント3 | TEXT | NULL | 各500文字以内（アプリ側で制限） |
| created_at | 作成日時 | DATETIME | NOT NULL | DEFAULT CURRENT_TIMESTAMP |
| updated_at | 更新日時 | DATETIME | NOT NULL | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

**インデックス:**
| インデックス名 | 種類 | カラム | 用途 |
| :--- | :--- | :--- | :--- |
| PRIMARY | PK | id | 主キー |
| uk_user_article | UNIQUE | user_id, article_url | 同一ユーザー・同一URLで1レコード。UPSERT に使用 |
| idx_user | INDEX | user_id | ユーザー別の履歴一覧取得を高速化 |

**エンジン・文字セット:**
- ENGINE: InnoDB
- DEFAULT CHARSET: utf8mb4
- COLLATE: utf8mb4_0900_ai_ci

## 2. ステータス・区分値定義 (マジックナンバー)

### ダッシュボードにはDB上のステータスカラムは存在しない

ただし、以下の定数値がサービスクラス内で使用されている。

### DashboardFeedService 定数
| 定数名 | 値 | 意味 |
| :--- | :--- | :--- |
| CACHE_TTL_SECONDS | 3600 | RSSフィードキャッシュのTTL（1時間） |
| PALEO_MAX_ITEMS | 3 | パレオな男ブログの最大取得件数 |
| RECENT_DAYS_DEFAULT | 7 | フィード記事の「直近」フィルタ日数 |
| CURIOSITY_SHOWN_URL_MAX | 24 | 好奇心ブースト表示済みURL記録の最大件数 |

### YouTubeFocusChannelService 定数
| 定数名 | 値 | 意味 |
| :--- | :--- | :--- |
| PER_CHANNEL_LIMIT | 3 | 1チャンネルあたりの最大取得動画数 |
| PLAYLIST_PAGE_SIZE | 50 | playlistItems APIの1ページあたり最大件数 |
| MAX_PLAYLIST_PAGES | 5 | playlistItems 走査の最大ページ数 |
| FETCH_PARALLELISM | 2 | チャンネル間のplaylistItems並列取得数 |
| CACHE_TTL_SECONDS | 1800 | YouTube集中視聴キャッシュのTTL（30分） |

### 好奇心ブースト検索テーマ一覧 (CURIOSITY_SEARCH_QUERIES)
| インデックス | 検索クエリ |
| :--- | :--- |
| 0 | 宇宙 天文学 最新 |
| 1 | 心理学 認知科学 研究 |
| 2 | 脳科学 神経 研究 |
| 3 | デザイン 美学 |
| 4 | 建築 空間 デザイン |
| 5 | 食文化 料理 発見 |
| 6 | 伝統工芸 ものづくり |
| 7 | 言語 語源 文化 |
| 8 | 映画 音楽 カルチャー |
| 9 | アイドル エンタメ 業界 |

フォールバッククエリ: `科学 最新`

### YouTube集中視聴モード (parseEnvEntries)
| mode値 | モードラベル | 説明 |
| :--- | :--- | :--- |
| video | 通常動画 | Shorts以外の動画を取得（デフォルト） |
| short | Shorts | Shorts動画のみを取得。`short`, `shorts` いずれも受け付ける |

### RSSフィードバッジ種別 (dashboard_feed_badge_label)
| kind値 | バッジ表示 | バッジカラー |
| :--- | :--- | :--- |
| curiosity | 好 | border-amber-200 bg-amber-50 text-amber-800 |
| ai | AI | border-violet-200 bg-violet-50 text-violet-800 |
| paleo | パ | border-emerald-200 bg-emerald-50 text-emerald-800 |

## 3. キャッシュファイル構造

### RSSフィードキャッシュ (dashboard_feed_*.json)
```json
[
  {
    "title": "記事タイトル",
    "url": "https://example.com/article",
    "pubDate": "Sun, 01 Jun 2026 10:00:00 GMT"
  }
]
```

### 好奇心ブースト表示済みURL (dashboard_curiosity_shown_{userId}.json)
```json
[
  { "d": "2026-05-31", "u": "https://example.com/article1" },
  { "d": "2026-05-30", "u": "https://example.com/article2" }
]
```

### YouTube集中視聴キャッシュ (dashboard_youtube_focus_{hash}.json)
```json
{
  "configured": true,
  "api_configured": true,
  "cached": false,
  "channels": [
    {
      "input_spec": "@channelHandle",
      "mode": "video",
      "mode_label": "通常動画",
      "channel_id": "UC...",
      "channel_title": "チャンネル名",
      "error": null,
      "videos": [
        {
          "video_id": "xxxxx",
          "title": "動画タイトル",
          "thumbnail_url": "https://...",
          "published_at": "2026-05-31T10:00:00Z",
          "channel_title": "チャンネル名",
          "media_type": "video"
        }
      ]
    }
  ]
}
```
