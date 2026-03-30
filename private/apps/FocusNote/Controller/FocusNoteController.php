<?php

namespace App\FocusNote\Controller;

use App\FocusNote\Model\MonthlyPageModel;
use App\FocusNote\Model\DailyTaskModel;
use App\FocusNote\Model\WeeklyPageModel;
use App\FocusNote\Model\WeeklyTaskPickModel;
use App\FocusNote\Model\QuestionActionModel;
use App\FocusNote\Model\GoalModel;
use App\FocusNote\Model\ActionGoalModel;
use App\FocusNote\Model\IfThenRuleModel;
use Core\Auth;

/**
 * Focus Note コントローラ
 * 物理パス: haitaka/private/apps/FocusNote/Controller/FocusNoteController.php
 */
class FocusNoteController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function dashboard(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        $today = date('Y-m-d');
        $weekStart = WeeklyPageModel::getWeekStart($today);
        $yearMonth = date('Y-m') . '-01';

        $weeklyPageModel = new WeeklyPageModel();
        $questionActionModel = new QuestionActionModel();

        $weeklyPage = $weeklyPageModel->findByWeekStart($weekStart);
        $todayActions = [];
        if ($weeklyPage) {
            $todayActions = $questionActionModel->getActionsByWeeklyPageId((int) $weeklyPage['id']);
        }

        $monthlyLink = '/focus_note/monthly.php?ym=' . date('Y-m');
        $weeklyLink = '/focus_note/weekly.php?week=' . $weekStart;

        require_once __DIR__ . '/../Views/dashboard.php';
    }

    public function goalSetting(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/goal_setting.php';
    }

    public function goalSettingForm(): void {
        $this->auth->requireLogin();

        $user = $_SESSION['user'];

        try {
            $goalModel = new GoalModel();
            $actionModel = new ActionGoalModel();
            $ruleModel = new IfThenRuleModel();

            $goal = $goalModel->findActive();
            $actionGoals = [];
            $ifThenRules = [];

            if ($goal) {
                $actionGoals = $actionModel->getByGoalId((int) $goal['id']);
                $ifThenRules = $ruleModel->getByGoalId((int) $goal['id']);
            }

            require_once __DIR__ . '/../Views/goal_setting_form.php';
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $isTableMissing = (strpos($msg, "doesn't exist") !== false || stripos($msg, 'exist') !== false);
            self::renderError($isTableMissing
                ? 'fn_goals 等のテーブルが存在しません。マイグレーション create_fn_goals.sql を実行してください。'
                : 'データベースエラー: ' . $msg);
        } catch (\Throwable $e) {
            self::renderError('エラー: ' . $e->getMessage() . ' (in ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
    }

    public function monthly(): void {
        $this->auth->requireLogin();

        try {
            $ym = $_GET['ym'] ?? date('Y-m') . '-01';
            if (!preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $ym)) {
                $ym = date('Y-m') . '-01';
            }
            if (strlen($ym) === 7) {
                $ym .= '-01';
            }

            $monthlyModel = new MonthlyPageModel();
            $dailyTaskModel = new DailyTaskModel();

            $page = $monthlyModel->findOrCreateForYearMonth($ym);
            $dailyTasks = [];
            if (!empty($page['id'])) {
                $dailyTasks = $dailyTaskModel->getByMonthlyPageId((int) $page['id']);
            }

            $prevMonth = date('Y-m-d', strtotime($ym . ' -1 month'));
            $nextMonth = date('Y-m-d', strtotime($ym . ' +1 month'));

            $user = $_SESSION['user'];
            require_once __DIR__ . '/../Views/monthly.php';
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $isTableMissing = (strpos($msg, "doesn't exist") !== false || strpos($msg, 'exist') !== false);
            self::renderError($isTableMissing
                ? 'fn_* テーブルが存在しません。マイグレーション create_fn_focus_note.sql を実行してください。'
                : 'データベースエラー: ' . $msg);
        } catch (\Throwable $e) {
            self::renderError('エラー: ' . $e->getMessage() . ' (in ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
    }

    public function weekly(): void {
        $this->auth->requireLogin();

        try {
            $weekParam = $_GET['week'] ?? '';
            $today = date('Y-m-d');
            $weekStart = $weekParam && preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekParam)
                ? WeeklyPageModel::getWeekStart($weekParam)
                : WeeklyPageModel::getWeekStart($today);

            $weeklyPageModel = new WeeklyPageModel();
            $weeklyTaskPickModel = new WeeklyTaskPickModel();
            $questionActionModel = new QuestionActionModel();
            $monthlyPageModel = new MonthlyPageModel();
            $dailyTaskModel = new DailyTaskModel();

            $weeklyPage = $weeklyPageModel->findOrCreateForWeek($weekStart);
            $picks = [];
            $questionActions = [];
            $availableDailyTasks = [];

            if (!empty($weeklyPage['id'])) {
                $picks = $weeklyTaskPickModel->getPicksWithTasks((int) $weeklyPage['id']);
                $questionActions = $questionActionModel->getActionsByWeeklyPageId((int) $weeklyPage['id']);

                $yearMonth = date('Y-m', strtotime($weekStart)) . '-01';
                $monthlyPage = $monthlyPageModel->findByYearMonth($yearMonth);
                if ($monthlyPage) {
                    $availableDailyTasks = $dailyTaskModel->getByMonthlyPageId((int) $monthlyPage['id']);
                }
            }

            $prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
            $nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));
            $user = $_SESSION['user'];
            $userName = $user['id_name'] ?? '私';

            require_once __DIR__ . '/../Views/weekly.php';
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $isTableMissing = (strpos($msg, "doesn't exist") !== false || strpos($msg, 'exist') !== false);
            self::renderError($isTableMissing
                ? 'fn_* テーブルが存在しません。マイグレーション create_fn_focus_note.sql を実行してください。'
                : 'データベースエラー: ' . $msg);
        } catch (\Throwable $e) {
            self::renderError('エラー: ' . $e->getMessage() . ' (in ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
    }

    private static function renderError(string $message): void {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>エラー</title></head><body style="font-family:sans-serif;padding:2rem;">';
        echo '<h1>Focus Note エラー</h1><p>' . htmlspecialchars($message) . '</p>';
        echo '<p><a href="/focus_note/">ダッシュボードへ戻る</a></p></body></html>';
    }
}
