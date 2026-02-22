<?php
/**
 * 推し設定ページ View
 * 物理パス: haitaka/private/apps/Hinata/Views/oshi_settings.php
 */
$appKey = 'hinata';
require_once __DIR__ . '/../../../components/theme_from_session.php';

use App\Hinata\Model\FavoriteModel;
$levelLabels = FavoriteModel::LEVEL_LABELS;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>推し設定 - Hinata Portal</title>
    <?php require_once __DIR__ . '/../../../components/head_favicon.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Noto+Sans+JP:wght@400;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
        .sidebar { transition: width 0.3s; width: 240px; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; transform: translateX(-100%); z-index: 100; height: 100%; width: 240px !important; }
            .sidebar.mobile-open { transform: translateX(0); }
        }
        .oshi-card { transition: all 0.3s; }
        .oshi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -6px rgba(0,0,0,0.12); }
        .oshi-slot-empty { border: 2px dashed #e2e8f0; }
        .member-grid-card { transition: all 0.2s; cursor: pointer; }
        .member-grid-card:hover { transform: scale(1.03); }
        .member-grid-card.selected { ring: 3px; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800 <?= $bodyBgClass ?>"<?= $bodyStyle ? ' style="' . htmlspecialchars($bodyStyle) . '"' : '' ?>>

    <?php require_once __DIR__ . '/../../../components/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white/80 backdrop-blur-md border-b <?= $headerBorder ?> flex items-center justify-between px-6 shrink-0 sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <button id="mobileMenuBtn" class="md:hidden text-slate-400 p-2"><i class="fa-solid fa-bars text-lg"></i></button>
                <a href="/hinata/" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-lg <?= $headerIconBg ?>"<?= $headerIconStyle ? ' style="' . htmlspecialchars($headerIconStyle) . '"' : '' ?>>
                    <i class="fa-solid fa-heart text-sm"></i>
                </div>
                <h1 class="font-black text-slate-700 text-xl tracking-tighter">推し設定</h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scroll">
            <div class="max-w-4xl mx-auto">

                <!-- 現在の推し表示 -->
                <section class="mb-10">
                    <h2 class="text-sm font-black text-slate-500 mb-4 tracking-wider"><i class="fa-solid fa-crown text-amber-500 mr-2"></i>あなたの推し</h2>
                    <div id="oshiSlots" class="grid grid-cols-3 gap-4">
                        <?php
                        $slots = [
                            ['level' => 9, 'label' => '最推し', 'color' => 'amber', 'icon' => 'crown', 'gradient' => 'from-amber-400 to-amber-500'],
                            ['level' => 8, 'label' => '2推し', 'color' => 'pink', 'icon' => 'heart', 'gradient' => 'from-pink-400 to-pink-500'],
                            ['level' => 7, 'label' => '3推し', 'color' => 'rose', 'icon' => 'heart', 'gradient' => 'from-rose-300 to-rose-400'],
                        ];
                        foreach ($slots as $slot):
                            $member = null;
                            foreach ($oshiMembers as $om) {
                                if ((int)$om['level'] === $slot['level']) { $member = $om; break; }
                            }
                        ?>
                        <div class="oshi-card rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden cursor-pointer" data-level="<?= $slot['level'] ?>"<?php if ($member): ?> data-member-id="<?= $member['member_id'] ?>"<?php endif; ?>>
                            <div class="h-2 bg-gradient-to-r <?= $slot['gradient'] ?>"></div>
                            <?php if ($member): ?>
                            <div class="p-4 text-center">
                                <div class="w-20 h-20 mx-auto rounded-full overflow-hidden bg-slate-100 mb-3 ring-2 ring-<?= $slot['color'] ?>-200">
                                    <?php if ($member['image_url']): ?>
                                    <img src="/assets/img/members/<?= htmlspecialchars($member['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                    <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user text-2xl"></i></div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[10px] font-black text-<?= $slot['color'] ?>-500 mb-1"><i class="fa-solid fa-<?= $slot['icon'] ?> mr-1"></i><?= $slot['label'] ?></p>
                                <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($member['name']) ?></p>
                                <p class="text-[10px] text-slate-400"><?= htmlspecialchars($member['generation']) ?>期生</p>
                                <button onclick="OshiSettings.clearLevel(<?= $slot['level'] ?>, <?= $member['member_id'] ?>)" class="mt-2 text-[10px] text-slate-400 hover:text-red-500 transition"><i class="fa-solid fa-xmark mr-1"></i>解除</button>
                            </div>
                            <?php else: ?>
                            <div class="p-6 text-center">
                                <div class="w-20 h-20 mx-auto rounded-full bg-slate-50 border-2 border-dashed border-slate-200 flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-plus text-slate-300 text-xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-<?= $slot['color'] ?>-400 mb-1"><i class="fa-solid fa-<?= $slot['icon'] ?> mr-1"></i><?= $slot['label'] ?></p>
                                <p class="text-xs text-slate-400">下のメンバーから選択</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- メンバー一覧 -->
                <section>
                    <h2 class="text-sm font-black text-slate-500 mb-4 tracking-wider"><i class="fa-solid fa-users mr-2"></i>メンバー一覧</h2>
                    <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3">
                        <?php foreach ($members as $m):
                            $favLevel = (int)($m['favorite_level'] ?? 0);
                            $levelLabel = $levelLabels[$favLevel] ?? '';
                            $ringClass = '';
                            if ($favLevel === 9) $ringClass = 'ring-2 ring-amber-400';
                            elseif ($favLevel === 8) $ringClass = 'ring-2 ring-pink-400';
                            elseif ($favLevel === 7) $ringClass = 'ring-2 ring-rose-300';
                            elseif ($favLevel === 1) $ringClass = 'ring-2 ring-amber-200';
                        ?>
                        <div class="member-grid-card bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-center p-2 <?= $ringClass ?>" data-member-id="<?= $m['id'] ?>" data-current-level="<?= $favLevel ?>">
                            <div class="w-14 h-14 mx-auto rounded-full overflow-hidden bg-slate-100 mb-2">
                                <?php
                                $imgs = $m['images'] ?? [];
                                $imgUrl = $imgs[0] ?? $m['image_url'] ?? null;
                                ?>
                                <?php if ($imgUrl): ?>
                                <img src="/assets/img/members/<?= htmlspecialchars($imgUrl) ?>" class="w-full h-full object-cover" alt="" loading="lazy">
                                <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fa-solid fa-user"></i></div>
                                <?php endif; ?>
                            </div>
                            <p class="text-[10px] font-bold text-slate-700 leading-tight mb-1"><?= htmlspecialchars($m['name']) ?></p>
                            <?php if ($levelLabel): ?>
                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded-full
                                <?php if ($favLevel === 9): ?> bg-amber-100 text-amber-600
                                <?php elseif ($favLevel === 8): ?> bg-pink-100 text-pink-600
                                <?php elseif ($favLevel === 7): ?> bg-rose-100 text-rose-500
                                <?php elseif ($favLevel === 1): ?> bg-amber-50 text-amber-500
                                <?php endif; ?>
                            "><?= $levelLabel ?></span>
                            <?php endif; ?>
                            <div class="mt-2">
                                <select onchange="OshiSettings.setLevel(<?= $m['id'] ?>, parseInt(this.value))" class="w-full text-[10px] border border-slate-200 rounded-lg py-1 px-1 text-slate-600 bg-white">
                                    <option value="0" <?= $favLevel === 0 ? 'selected' : '' ?>>--</option>
                                    <option value="9" <?= $favLevel === 9 ? 'selected' : '' ?>>最推し</option>
                                    <option value="8" <?= $favLevel === 8 ? 'selected' : '' ?>>2推し</option>
                                    <option value="7" <?= $favLevel === 7 ? 'selected' : '' ?>>3推し</option>
                                    <option value="1" <?= $favLevel === 1 ? 'selected' : '' ?>>気になる</option>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/member_modal.php'; ?>

    <script src="/assets/js/core.js"></script>
    <script src="/assets/js/hinata-member-modal.js?v=<?= time() ?>"></script>
    <script>
    HinataMemberModal.init({ detailApiUrl: '/hinata/members.php', imgCacheBust: '<?= time() ?>', isAdmin: <?= $isAdmin ? 'true' : 'false' ?> });

    document.querySelectorAll('.member-grid-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('select')) return;
            var memberId = card.dataset.memberId;
            if (memberId) HinataMemberModal.open(parseInt(memberId, 10), e);
        });
    });

    document.querySelectorAll('.oshi-card[data-member-id]').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.closest('button')) return;
            HinataMemberModal.open(parseInt(card.dataset.memberId, 10), e);
        });
    });

    var OshiSettings = {
        setLevel: function(memberId, level) {
            fetch('/hinata/api/toggle_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ member_id: memberId, level: level })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status === 'success') {
                    if (res.swapped_member_name) {
                        OshiSettings.showToast(res.swapped_member_name + ' の推しランクが解除されました');
                    }
                    location.reload();
                } else {
                    OshiSettings.showToast('エラー: ' + (res.message || '更新に失敗しました'));
                }
            });
        },

        clearLevel: function(level, memberId) {
            this.setLevel(memberId, 0);
        },

        showToast: function(msg) {
            var el = document.createElement('div');
            el.textContent = msg;
            el.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:8px 20px;border-radius:8px;font-size:12px;font-weight:700;z-index:9999;opacity:0;transition:opacity 0.3s;';
            document.body.appendChild(el);
            requestAnimationFrame(function() { el.style.opacity = '1'; });
            setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 2500);
        }
    };

    document.getElementById('mobileMenuBtn').onclick = function() {
        document.getElementById('sidebar').classList.add('mobile-open');
    };
    </script>
</body>
</html>
