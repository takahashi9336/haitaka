<?php

namespace App\Hinata\Model;

use Core\BaseModel;

/**
 * メンバー個人活動モデル
 * 物理パス: haitaka/private/apps/Hinata/Model/MemberActivityModel.php
 */
class MemberActivityModel extends BaseModel {
    protected string $table = 'hn_member_activities';

    protected array $fields = [
        'id', 'member_id', 'category', 'title', 'description',
        'url', 'url_label', 'image_url', 'is_active', 'sort_order',
        'start_date', 'end_date', 'created_at', 'updated_at'
    ];

    protected bool $isUserIsolated = false;

    public const CATEGORIES = [
        'radio'            => ['label' => 'ラジオ番組',           'icon' => 'fa-solid fa-radio',            'color' => 'text-orange-500', 'bg' => 'bg-orange-50',  'pill' => 'bg-orange-100 text-orange-700'],
        'podcast'          => ['label' => 'ポッドキャスト',       'icon' => 'fa-solid fa-podcast',          'color' => 'text-purple-500', 'bg' => 'bg-purple-50',  'pill' => 'bg-purple-100 text-purple-700'],
        'drama'            => ['label' => 'ドラマ・映画',         'icon' => 'fa-solid fa-film',             'color' => 'text-rose-500',   'bg' => 'bg-rose-50',    'pill' => 'bg-rose-100 text-rose-700'],
        'magazine'         => ['label' => '雑誌・モデル',         'icon' => 'fa-solid fa-book-open',        'color' => 'text-pink-500',   'bg' => 'bg-pink-50',    'pill' => 'bg-pink-100 text-pink-700'],
        'youtube_personal' => ['label' => 'YouTube個人',          'icon' => 'fa-brands fa-youtube',         'color' => 'text-red-500',    'bg' => 'bg-red-50',     'pill' => 'bg-red-100 text-red-700'],
        'cm'               => ['label' => 'CM',                   'icon' => 'fa-solid fa-tv',               'color' => 'text-blue-500',   'bg' => 'bg-blue-50',    'pill' => 'bg-blue-100 text-blue-700'],
        'stage'            => ['label' => '舞台',                 'icon' => 'fa-solid fa-masks-theater',    'color' => 'text-amber-500',  'bg' => 'bg-amber-50',   'pill' => 'bg-amber-100 text-amber-700'],
        'other'            => ['label' => 'その他',               'icon' => 'fa-solid fa-star',             'color' => 'text-slate-500',  'bg' => 'bg-slate-50',   'pill' => 'bg-slate-100 text-slate-700'],
    ];

    /**
     * メンバーの活動一覧（アクティブのみ）
     */
    public function getByMember(int $memberId, bool $activeOnly = true): array {
        $sql = "SELECT * FROM {$this->table} WHERE member_id = :mid";
        $params = ['mid' => $memberId];
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 全メンバーの活動をまとめて取得 (member_id => [activities])
     */
    public function getAllGroupedByMember(bool $activeOnly = true): array {
        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY member_id, sort_order ASC, id ASC";
        $rows = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['member_id']][] = $r;
        }
        return $map;
    }

    /**
     * 活動を保存（新規 or 更新）
     */
    public function saveActivity(array $data): int {
        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        if ($id > 0) {
            $this->update($id, $data);
            return $id;
        }
        $this->create($data);
        return (int)$this->lastInsertId();
    }
}
