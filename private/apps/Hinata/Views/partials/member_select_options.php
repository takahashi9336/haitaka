<?php
/**
 * メンバー選択用 <select> の <option> を期別でグルーピング（optgroup）して出力
 * 使用例: <select>...<?php require __DIR__.'/member_select_options.php'; ?></select>
 *
 * 必要な変数: $members (必須)
 * オプション: $memberSelectBlankFirst, $memberSelectBlankLabel, $memberSelectSelectedId,
 *   $memberSelectShowGraduate, $memberSelectFormatOption
 */
use App\Hinata\Helper\MemberGroupHelper;

if (empty($members) || !is_array($members)) return;

$blankFirst = $memberSelectBlankFirst ?? true;
$blankLabel = $memberSelectBlankLabel ?? '選択';
$selectedId = $memberSelectSelectedId ?? null;
$showGraduate = $memberSelectShowGraduate ?? false;
$formatOption = $memberSelectFormatOption ?? null;

$grouped = MemberGroupHelper::group($members);

if ($blankFirst) {
    echo '<option value="">' . htmlspecialchars($blankLabel) . '</option>';
}
foreach ($grouped['order'] as $g) {
    if (empty($grouped['active'][$g])) continue;
    $label = MemberGroupHelper::getGenLabel($g);
    echo '<optgroup label="' . htmlspecialchars($label) . '">';
    foreach ($grouped['active'][$g] as $m) {
        $mid = (int)($m['id'] ?? 0);
        $sel = ($selectedId !== null && $mid === (int)$selectedId) ? ' selected' : '';
        $txt = $formatOption ? $formatOption($m) : htmlspecialchars($m['name'] ?? '');
        if ($showGraduate && !($m['is_active'] ?? 1)) $txt .= ' (卒)';
        echo '<option value="' . $mid . '"' . $sel . '>' . $txt . '</option>';
    }
    echo '</optgroup>';
}
if (!empty($grouped['graduates'])) {
    echo '<optgroup label="卒業生">';
    foreach ($grouped['graduates'] as $m) {
        $mid = (int)($m['id'] ?? 0);
        $sel = ($selectedId !== null && $mid === (int)$selectedId) ? ' selected' : '';
        $txt = $formatOption ? $formatOption($m) : htmlspecialchars($m['name'] ?? '');
        echo '<option value="' . $mid . '"' . $sel . '>' . $txt . '</option>';
    }
    echo '</optgroup>';
}
