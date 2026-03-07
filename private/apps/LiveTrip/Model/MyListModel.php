<?php

namespace App\LiveTrip\Model;

use Core\BaseModel;

/**
 * 持ち物マイリスト（テンプレート）モデル
 */
class MyListModel extends BaseModel {
    protected string $table = 'lt_my_lists';
    protected array $fields = ['id', 'user_id', 'list_name', 'created_at', 'updated_at'];
    protected bool $isUserIsolated = true;

    public function getListsForSelect(): array {
        return $this->all();
    }

    public function getWithItems(): array {
        $lists = $this->all();
        $itemModel = new MyListItemModel();
        foreach ($lists as &$list) {
            $list['items'] = $itemModel->getByMyListId((int) $list['id']);
        }
        return $lists;
    }
}
