# ドキュメント体系 インデックス (00_Index.md)

本ディレクトリは、システムの各機能における仕様・設計を管理するためのドキュメント群です。
AIアシスタント（Cursor等）は、機能改修や仕様把握を行う際、**必ず本ファイルと対象機能ディレクトリ内の設計書を優先的に読み込み**、プロジェクトの意図と制約を理解した上で処理を行ってください。

## 共通機能設計書 (docs/common)

全機能で共有する UI・CSS・JavaScript・PHP コアのリファレンスです。**機能単位の設計書を読む前に、該当箇所（共通レイアウト／`Core\`／`assets` 等）が共通ドキュメントに触れている場合はこれらを参照**してください。

| ファイル | 内容 |
|---------|------|
| [docs/common/31_共通UIコンポーネント.md](common/31_共通UIコンポーネント.md) | `private/components/` のサイドバー、favicon/CSS、フラッシュ、`theme_from_session`、`guide_display` 等 |
| [docs/common/32_デザインシステム・CSS.md](common/32_デザインシステム・CSS.md) | Tailwind / Font Awesome、`common.css`、アプリテーマ（`sys_apps` / セッション） |
| [docs/common/33_共通JS・ユーティリティ.md](common/33_共通JS・ユーティリティ.md) | `www/assets/js/core.js`（`App.*`）、その他共通スクリプトの索引 |
| [docs/common/34_コアライブラリ(PHP).md](common/34_コアライブラリ(PHP).md) | `Core\` Bootstrap / Auth / BaseModel / Database / Logger 等 |

索引・レビューチェックリスト: [docs/common/README.md](common/README.md)

## ドキュメントディレクトリ構成（機能単位）
各機能ごとにディレクトリを作成し、以下の連番プレフィックスに従ってドキュメントを配置します。

* **10_基本設計/** : 全体像とフローの把握
    * `11_[機能名]_機能概要書.md`：目的、背景、解決する課題
    * `12_[機能名]_機能一覧.md`：画面・機能・関連DBのインデックス
    * `13_[機能名]_画面遷移図.md`：画面間の遷移フロー (Mermaid記法)
    * `14_[機能名]_ER図.md`：対象機能のデータモデル関係図 (Mermaid記法)
* **20_詳細設計/** : 実装に必要な詳細仕様
    * `21_[機能名]_UI設計書.md`：画面構成、コンポーネント、状態定義
    * `22_[機能名]_ドメイン定義書.md`：テーブルの詳細カラム、型、制約、マジックナンバー
    * `23_[機能名]_処理詳細_I-O定義書.md`：各エンドポイント・処理ごとの入出力とビジネスロジック
* **80_補足資料/** : (任意) 外部API連携仕様など
* **99_変更履歴_設計決定録.md** : アーキテクチャの変更履歴と、その「背景・理由 (ADR)」

### 機能別設計書一覧

| 機能 | ディレクトリ | 概要 |
|------|------------|------|
| 管理画面 | [docs/admin/](admin/) | アプリ・ロール・ユーザー・ガイド・改善管理 等 |
| ダッシュボード | [docs/dashboard/](dashboard/) | ポータルトップ、記事トレーニング、YouTube集中視聴 |
| エンタメ統合 | [docs/entame/](entame/) | 映画・ドラマ・アニメの統合ダッシュボードと各メディア機能 |
| 映画 | [docs/movie/](movie/) | 映画管理の詳細設計（TMDB連携、ガチャ、レコメンド 等） |
| フォーカスノート | [docs/focus_note/](focus_note/) | 月次・週次・日次の振り返り計画、WOOP目標設定 |
| フレンズアクティビティ | [docs/friends_activity/](friends_activity/) | 友人の映画/ドラマ/アニメ視聴履歴の閲覧・管理 |
| ヘルスケア | [docs/health/](health/) | キッチンストック、トレーニングメニュー、トレーニング履歴 |
| 遠征管理 | [docs/live_trip/](live_trip/) | ライブ遠征プラン・イベント・持ち物チェックリスト |
| メモ | [docs/note/](note/) | Google Keep風メモ、構造化リスト（6種別） |
| センスラボ | [docs/sense_lab/](sense_lab/) | スクラップ収集（本番・クイック）、カテゴリ管理 |
| タスク管理 | [docs/task_manager/](task_manager/) | タスクCRUD、カテゴリ、カレンダービュー |
| ユーザー設定 | [docs/users_settings/](users_settings/) | パスワード変更、ユーザー作成、ロール管理（管理者） |
| **日向坂ポータル** | [docs/hinata/](hinata/) | **6サブドメインに分割**（下記参照） |
| 　├ ポータル・お知らせ | [docs/hinata/portal/](hinata/portal/) | ポータルダッシュボード、トピック・お知らせ管理、認証 |
| 　├ メンバー・推し | [docs/hinata/member/](hinata/member/) | メンバー管理、推し設定・タイムライン、ペンライト |
| 　├ イベント・ライブ | [docs/hinata/event/](hinata/event/) | イベント管理、セットリスト、ライブガイド、影ナレ |
| 　├ ミーグリ・ネタ帳 | [docs/hinata/meetgreet/](hinata/meetgreet/) | ミーグリスロット・レポート、ネタ帳管理 |
| 　├ メディア・バッチ | [docs/hinata/media/](hinata/media/) | メディアアセット管理（YouTube/TikTok/ブログ）、バッチジョブ |
| 　└ 楽曲・リリース | [docs/hinata/music/](hinata/music/) | 楽曲・歌唱メンバー、リリース・エディション、アー写 |

## 新規ドキュメントの生成ルール
AIアシスタントが新しい設計書を生成・更新する場合は、必ず `docs/templates/` 配下にある該当のテンプレートフォーマットを踏襲し、各テンプレート先頭の **HTML コメント（`<!-- ... -->`）** による指示に従って出力すること（索引は [docs/templates/README.md](templates/README.md)）。