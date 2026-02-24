<?php

namespace App\Hinata\Helper;

/**
 * メンバー一覧のグルーピング用ヘルパー
 * 現役は期別（1期生〜5期生、ポカ）、卒業生は下にまとめる
 */
class MemberGroupHelper {

    public const POKA_MEMBER_ID = 99;

    /**
     * メンバーを現役（期別）と卒業生にグルーピング
     *
     * @param array $members メンバー配列（id, name, kana, generation, is_active 等）
     * @return array ['active' => [gen => [members]], 'graduates' => [members], 'order' => [gen,...]]
     */
    public static function group(array $members): array {
        $active = [];
        $graduates = [];

        foreach ($members as $m) {
            if (!($m['is_active'] ?? 1)) {
                $graduates[] = $m;
                continue;
            }
            $gen = ((int)($m['id'] ?? 0) === self::POKA_MEMBER_ID) ? 'poka' : (int)($m['generation'] ?? 0);
            if (!isset($active[$gen])) $active[$gen] = [];
            $active[$gen][] = $m;
        }

        $order = [1, 2, 3, 4, 5];
        if (isset($active[0])) array_unshift($order, 0);
        if (isset($active['poka'])) $order[] = 'poka';

        usort($graduates, fn($a, $b) =>
            ($a['generation'] ?? 0) <=> ($b['generation'] ?? 0)
            ?: strcmp($a['kana'] ?? $a['name'] ?? '', $b['kana'] ?? $b['name'] ?? '')
        );

        return ['active' => $active, 'graduates' => $graduates, 'order' => $order];
    }

    /**
     * 期キーから表示ラベルを取得
     */
    public static function getGenLabel($gen): string {
        if ($gen === 'poka') return 'ポカ';
        if ($gen === 0 || $gen === '0') return '期別なし';
        return $gen . '期生';
    }
}
