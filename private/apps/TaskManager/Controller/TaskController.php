<?php

namespace App\TaskManager\Controller;

use App\TaskManager\Model\TaskModel;
use App\TaskManager\Model\CategoryModel;
use App\Hinata\Model\EventModel;
use Core\Auth;
use Core\Validator;
use Core\Logger;

class TaskController {
    
    public function index(): void {
        $auth = new Auth();
        $auth->requireLogin();
        $model = new TaskModel();
        $tasks = $model->getActiveTasks();
        $catModel = new CategoryModel();
        $categories = $catModel->all();
        
        // 日向坂イベントを取得
        $eventModel = new EventModel();
        $hinataEvents = $eventModel->getAllUpcomingEvents();
        
        $user = $_SESSION['user'];
        require_once __DIR__ . '/../Views/index.php';
    }

    public function store(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $validator = new Validator($input);
            $validator->rule('required', ['title']);
            if (!$validator->validate()) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'errors' => $validator->errors()]);
                return;
            }

            $categoryId = $this->handleCategory($input);
            $taskModel = new TaskModel();
            $taskModel->create([
                'category_id' => $categoryId,
                'title'       => $input['title'],
                'description' => $input['description'] ?? '',
                'priority'    => $input['priority'] ?? 2,
                'status'      => 'todo',
                'start_date'  => !empty($input['start_date']) ? $input['start_date'] : null,
                'due_date'    => !empty($input['due_date']) ? $input['due_date'] : null,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ]);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'System Error']);
        }
    }

    public function update(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (!isset($input['id'])) throw new \Exception('Missing ID');

            $taskModel = new TaskModel();
            $categoryId = $this->handleCategory($input);

            // ステータスのみ更新（完了チェック用）か、全体更新（編集用）かを判定
            $updateData = [];
            if (isset($input['status'])) {
                $updateData['status'] = $input['status'];
            } else {
                $updateData = [
                    'category_id' => $categoryId,
                    'title'       => $input['title'],
                    'description' => $input['description'] ?? '',
                    'priority'    => $input['priority'] ?? 2,
                    'start_date'  => !empty($input['start_date']) ? $input['start_date'] : null,
                    'due_date'    => !empty($input['due_date']) ? $input['due_date'] : null,
                ];
            }
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $taskModel->update($input['id'], $updateData);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function delete(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (!isset($input['id'])) throw new \Exception('Missing ID');
            $taskModel = new TaskModel();
            $taskModel->delete($input['id']);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function handleCategory($input): ?int {
        if (!empty($input['category_name'])) {
            $catModel = new CategoryModel();
            return $catModel->getOrCreate($input['category_name'], $input['category_color'] ?? '#4f46e5');
        }
        return null;
    }
}
