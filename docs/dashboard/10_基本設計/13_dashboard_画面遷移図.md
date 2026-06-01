# ダッシュボード (dashboard) 画面遷移図

## 1. 画面遷移フロー

```mermaid
stateDiagram-v2
    [*] --> Login : 未認証アクセス
    Login --> Dashboard : ログイン成功

    state "メインダッシュボード\n(www/index.php)" as Dashboard
    state "記事トレーニング URL入力\n(article_training.php)" as ATSetup
    state "記事トレーニング 実施\n(article_training.php?url=...)" as ATWork
    state "記事トレーニング 履歴\n(article_training_history.php)" as ATHistory
    state "YouTube 集中視聴\n(youtube_focus.php)" as YTFocus

    Dashboard --> ATSetup : 記事トレ一覧カード\nまたは新しい記事でトレーニング
    Dashboard --> ATWork : 気になる記事の\nトレーニングボタン
    Dashboard --> YTFocus : YouTube集中カード\n(管理者のみ表示)
    Dashboard --> ATHistory : 記事トレ一覧カード

    ATSetup --> ATWork : URL入力して送信\n(?url=...&title=...)
    ATSetup --> ATHistory : 履歴へリンク
    ATSetup --> Dashboard : ダッシュボードリンク

    ATWork --> ATSetup : 別の記事のURLを入力する
    ATWork --> ATHistory : 履歴リンク
    ATWork --> Dashboard : ダッシュボードに戻る

    ATHistory --> ATWork : 履歴の記事を選択\n(?url=...&title=...)
    ATHistory --> ATSetup : 新しい記事でトレーニング

    YTFocus --> Dashboard : ダッシュボードボタン
```

## 2. 遷移パラメータ

| 遷移元 | 遷移先 | パラメータ | 説明 |
| :--- | :--- | :--- | :--- |
| ダッシュボード（気になる記事） | 記事トレーニング実施 | `?url={記事URL}&title={記事タイトル}` | RSSフィードの記事URLとタイトルを引き継ぐ |
| 記事トレーニングURL入力 | 記事トレーニング実施 | `?url={入力URL}&title={入力タイトル}` | フォームからGETで遷移。スキーム省略時は `https://` を自動付与 |
| 記事トレーニング履歴 | 記事トレーニング実施 | `?url={article_url}&title={article_title}` | 履歴レコードのURLとタイトルを引き継ぐ |
| YouTube集中視聴 | YouTube集中視聴 | `?view=all` or `?view=grouped` | 表示モード切替（投稿日時順 / チャンネル別） |
| YouTube集中視聴 | YouTube集中視聴 | `?refresh=1` | 強制再取得フラグ |
