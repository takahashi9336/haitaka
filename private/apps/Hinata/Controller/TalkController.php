<?php

namespace App\Hinata\Controller;

use App\Hinata\Model\MemberModel;
use App\Hinata\Model\NetaModel;
use App\Hinata\Model\FavoriteModel;
use App\Hinata\Model\EventModel;
use App\Hinata\Model\MeetGreetModel;
use Core\Auth;
use Core\Database;

/**
 * ミーグリネタ帳 コントローラ
 * 物理パス: haitaka/private/apps/Hinata/Controller/TalkController.php
 */
class TalkController {

    private Auth $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    /**
     * 一覧表示
     */
    public function index(): void {
        $this->auth->requireLogin();
        
        $memberModel = new MemberModel();
        $netaModel = new NetaModel();
        $eventModel = new EventModel();
        
        $members = $memberModel->getActiveMembersWithColors();
        $groupedNeta = $netaModel->getGroupedNeta();
        $tagSuggestions = $netaModel->listTagsForUser(50);
        $nextMgEvent = $eventModel->getNextMgEvent();
        $nextMgParticipantsText = null;
        if ($nextMgEvent && !empty($nextMgEvent['id'])) {
            $mgModel = new MeetGreetModel();
            $slots = $mgModel->getSlotsByEventId((int)$nextMgEvent['id']);
            $byMember = [];
            foreach ($slots as $s) {
                $name = (string)($s['member_name'] ?? $s['member_name_raw'] ?? '');
                $slotName = trim((string)($s['slot_name'] ?? ''));
                // 表示上は「第」を省略（例: 第1部→1部）
                $slotName = preg_replace('/^第/u', '', $slotName) ?? $slotName;
                if ($name === '' || $slotName === '') continue;
                if (!isset($byMember[$name])) $byMember[$name] = [];
                $byMember[$name][] = $slotName;
            }
            if (!empty($byMember)) {
                $parts = [];
                foreach ($byMember as $name => $slotNames) {
                    $slotNames = array_values(array_unique(array_filter(array_map('trim', $slotNames))));
                    if (empty($slotNames)) continue;
                    // 数値部（例: 1部）をレンジ圧縮して "1～3部" のように表記
                    $nums = [];
                    $others = [];
                    foreach ($slotNames as $sn) {
                        $normalized = preg_replace_callback('/[０-９]/u', fn($m) => (string)(mb_ord($m[0], 'UTF-8') - 0xFF10), $sn);
                        if (preg_match('/^(\d+)\s*部$/u', $normalized, $m2)) {
                            $nums[] = (int)$m2[1];
                        } else {
                            $others[] = $sn;
                        }
                    }
                    $nums = array_values(array_unique($nums));
                    sort($nums);

                    $compressed = [];
                    $i = 0;
                    $n = count($nums);
                    while ($i < $n) {
                        $start = $nums[$i];
                        $end = $start;
                        while ($i + 1 < $n && $nums[$i + 1] === $end + 1) {
                            $i++;
                            $end = $nums[$i];
                        }
                        if ($end > $start) $compressed[] = "{$start}～{$end}部";
                        else $compressed[] = "{$start}部";
                        $i++;
                    }

                    $labelParts = array_merge($compressed, $others);
                    $parts[] = $name . ' ' . implode(',', $labelParts);
                }
                if (!empty($parts)) $nextMgParticipantsText = implode(' / ', $parts);
            }
        }

        $memberMap = [];
        foreach ($members as $m) {
            if (!empty($m['id'])) $memberMap[(int)$m['id']] = $m;
        }

        $registeredMembers = [];
        $totalNetaCount = 0;
        $totalUsedCount = 0;
        foreach ($groupedNeta as $mid => $group) {
            $count = count($group['items'] ?? []);
            $totalNetaCount += $count;
            $usedCount = 0;
            foreach (($group['items'] ?? []) as $it) {
                if (($it['status'] ?? '') === 'done') $usedCount++;
            }
            $totalUsedCount += $usedCount;
            $mm = $memberMap[(int)$mid] ?? null;
            $favLevel = (int)($mm['favorite_level'] ?? ($group['favorite_level'] ?? 0));
            $favType = $favLevel >= 2 ? 'oshi' : ($favLevel === 1 ? 'kininaru' : 'other');
            $registeredMembers[] = [
                'member_id' => (int)$mid,
                'name' => (string)($group['member_name'] ?? ($mm['name'] ?? '')),
                'favorite_level' => $favLevel,
                'fav_type' => $favType,
                'generation' => (int)($mm['generation'] ?? 0),
                'image_url' => $mm['image_url'] ?? null,
                'color1' => $group['color1'] ?? null,
                'count' => $count,
                'used_count' => $usedCount,
            ];
        }

        usort($registeredMembers, function($a, $b) {
            if ($a['favorite_level'] !== $b['favorite_level']) return $b['favorite_level'] <=> $a['favorite_level'];
            if ($a['count'] !== $b['count']) return $b['count'] <=> $a['count'];
            return strcmp($a['name'], $b['name']);
        });
        
        require_once __DIR__ . '/../Views/talk.php';
    }

    /**
     * 新規保存・更新 (save_neta.php用)
     */
    public function store(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $model = new NetaModel();

            $allowedTypes = ['question', 'impression', 'joke'];
            $netaType = $input['neta_type'] ?? null; // null means 未登録
            if ($netaType === '' || $netaType === 'none') $netaType = null;
            if ($netaType !== null && !in_array($netaType, $allowedTypes, true)) {
                throw new \Exception('ネタ種類が不正です');
            }
            $tagsRaw = $input['tags'] ?? null;
            $tags = [];
            if (is_string($tagsRaw) && $tagsRaw !== '') {
                $decoded = json_decode($tagsRaw, true);
                if (is_array($decoded)) $tags = $decoded;
            } elseif (is_array($tagsRaw)) {
                $tags = $tagsRaw;
            }
            
            $data = [
                'member_id' => $input['member_id'],
                'content'   => $input['content'],
                'neta_type' => $netaType,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!empty($input['id'])) {
                $model->update((int)$input['id'], $data);
                $netaId = (int)$input['id'];
            } else {
                $data['status'] = 'stock';
                $data['is_favorite'] = 0;
                $data['created_at'] = date('Y-m-d H:i:s');
                $model->create($data);
                $netaId = (int)$model->lastInsertId();
            }
            if (!empty($netaId)) {
                $model->replaceNetaTags($netaId, $tags);
            }
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * ネタの更新専用 (update_neta.php用)
     */
    public function update(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id']) || empty($input['content'])) {
                throw new \Exception('必要なパラメータが不足しています');
            }

            $model = new NetaModel();
            $model->update((int)$input['id'], [
                'content'    => $input['content'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 完了・未完了の切り替え (update_neta_status.php用)
     */
    public function updateStatus(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $model = new NetaModel();
            $model->update((int)$input['id'], ['status' => $input['status']]);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * お気に入り更新 (update_neta_favorite.php用)
     */
    public function updateNetaFavorite(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new \Exception('ネタIDが指定されていません');
            $isFav = !empty($input['is_favorite']) ? 1 : 0;

            $model = new NetaModel();
            $model->update($id, [
                'is_favorite' => $isFav,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 推し（お気に入り）登録の切り替え (toggle_favorite.php用)
     * level 7-9 は排他制御付き（ユーザーにつき各1名のみ）
     */
    public function toggleFavorite(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $memberId = $input['member_id'] ?? null;
            // id=0 は有効。!$memberId は 0 を弾いてしまう。
            if ($memberId === null || $memberId === '') {
                throw new \Exception('メンバーIDが指定されていません');
            }
            $level = isset($input['level']) ? (int)$input['level'] : null;

            $favModel = new FavoriteModel();

            if ($level === null) {
                $current = $favModel->getMemberLevel((int)$memberId);
                $newLevel = $current > 0 ? 0 : 1;
                $result = $favModel->setLevel((int)$memberId, $newLevel);
            } else {
                $result = $favModel->setLevel((int)$memberId, $level);
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 削除
     */
    public function delete(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            (new NetaModel())->delete((int)$input['id']);
            echo json_encode(['status' => 'success']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}