# Focus Note（フォーカスノート） 機能一覧

## 1. 画面一覧
| 画面名 (論理名) | ファイルパス | 概要 |
| :--- | :--- | :--- |
| ダッシュボード | `www/focus_note/index.php` → `Views/dashboard.php` | 今週の質問型アクション一覧表示、完了チェック・所要時間記録、各画面へのナビゲーション |
| マンスリーページ | `www/focus_note/monthly.php` → `Views/monthly.php` | 月次の報酬感覚プランニング（ターゲット/重要度チェック/具象イメージング/リバースプランニング/デイリータスク設定）、自動保存対応 |
| ウィークリーページ | `www/focus_note/weekly.php` → `Views/weekly.php` | 週次のデイリータスク選択（3〜5個）、障害コントラスト/障害フィックス記入、質問型アクション（時間・場所設定と実施意図文の自動生成） |
| 目標設定の考え方 | `www/focus_note/goal_setting.php` → `Views/goal_setting.php` | MAC原則/WOOP/If-Thenプランニング/プロセス目標/目標設定の落とし穴に関する解説ページ（読み取り専用） |
| 目標・行動目標設定フォーム | `www/focus_note/goal_setting_form.php` → `Views/goal_setting_form.php` | WOOP（願望/成果/障害/計画/ありたい姿）入力、行動目標（MAC原則）の登録、If-Thenルールの登録、目標の削除 |

## 2. 機能・アクション一覧
| 機能名 | 種類 (画面/API/Batch) | 概要 |
| :--- | :--- | :--- |
| ダッシュボード表示 | 画面 | 今週のウィークリーページに紐づく質問型アクションを一覧表示する |
| マンスリーページ表示・自動保存 | 画面/API | 月次プランニングの表示と、入力後1.5秒の自動保存（テキストエリア＋デイリータスク一括置換） |
| ウィークリーページ表示 | 画面 | 週次ページの表示。デイリータスク選択、障害対策、質問型アクション設定を統合表示する |
| マンスリー保存 | API (POST) | `api/save_monthly.php` - マンスリーページの本文（target/importance_check/concrete_imaging/reverse_planning）とデイリータスク配列を一括保存 |
| ウィークリー保存 | API (POST) | `api/save_weekly.php` - ウィークリーページの障害コントラスト・障害フィックスを保存 |
| タスク選択保存 | API (POST) | `api/save_picks.php` - ウィークリーで選んだデイリータスク（3〜5個）のIDを一括保存 |
| 質問型アクション保存 | API (POST) | `api/save_question_actions.php` - 各ピックに対する時間・場所の設定と実施意図文の生成・保存 |
| アクション完了トグル | API (POST) | `api/toggle_done.php` - 質問型アクションの完了/未完了状態を切り替え |
| 所要時間記録 | API (POST) | `api/save_duration.php` - 質問型アクションの完了と所要時間（分）を記録 |
| WOOP保存 | API (POST) | `api/goal_save.php` - 目標（WOOP: wish/outcome/obstacle/plan/being）の新規作成または更新 |
| 行動目標一括保存 | API (POST) | `api/action_goals_save.php` - 目標に紐づく行動目標（content/measurement/is_process_goal）を一括置換 |
| If-Thenルール一括保存 | API (POST) | `api/if_then_rules_save.php` - 目標に紐づくIf-Thenルール（if_condition/then_action）を一括置換 |
| 目標削除 | API (POST) | `api/goal_delete.php` - 目標を削除（紐づくaction_goals/if_then_rulesはCASCADEで連鎖削除） |

## 3. 関連テーブル一覧
| テーブル物理名 | テーブル論理名 | 役割（CRUDの種別など） |
| :--- | :--- | :--- |
| fn_monthly_pages | マンスリーページ | 月単位の報酬感覚プランニング。月初アクセス時に自動作成 (CRUD) |
| fn_daily_tasks | デイリータスク | マンスリーページに紐づく日次タスク一覧。一括削除＋再挿入で更新 (CRD) |
| fn_weekly_pages | ウィークリーページ | 週単位（月曜起点）の障害対策ページ。週初アクセス時に自動作成 (CRUD) |
| fn_weekly_task_picks | ウィークリータスク選択 | ウィークリーで選んだデイリータスクの参照。一括削除＋再挿入 (CRD) |
| fn_question_actions | 質問型アクション | タスク×時間×場所の実施意図。完了・所要時間を記録 (CRUD) |
| fn_goals | 目標（WOOP） | 目標設定（Wish/Outcome/Obstacle/Plan/Being）。is_activeで現在の目標を管理 (CRUD) |
| fn_action_goals | 行動目標（MAC） | 目標に紐づく行動目標。一括削除＋再挿入 (CRD) |
| fn_if_then_rules | If-Thenルール | 目標に紐づくIf-Thenルール。一括削除＋再挿入 (CRD) |
| sys_apps | アプリ登録 | app_key='focus_note' として登録（参照のみ） |
