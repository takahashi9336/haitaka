<?php
/**
 * フラッシュメッセージをトースト表示
 * $_SESSION['flash_success'], $_SESSION['flash_error'] を読み取り表示後に unset
 */
$toastSuccess = $_SESSION['flash_success'] ?? null;
$toastError = $_SESSION['flash_error'] ?? null;
if ($toastSuccess) unset($_SESSION['flash_success']);
if ($toastError) unset($_SESSION['flash_error']);
?>
<div id="flash-toast-container" class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 max-w-sm w-full pointer-events-none" aria-live="polite"></div>
<style>
#flash-toast-container .flash-toast { pointer-events: auto; animation: flash-toast-in 0.25s ease; }
@keyframes flash-toast-in { from { opacity: 0; transform: translateX(1rem); } to { opacity: 1; transform: translateX(0); } }
</style>
<script>
(function() {
    var container = document.getElementById('flash-toast-container');
    if (!container) return;
    function showToast(msg, type) {
        var el = document.createElement('div');
        el.className = 'flash-toast px-4 py-3 rounded-lg shadow-lg text-sm font-medium ' +
            (type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white');
        el.textContent = msg;
        container.appendChild(el);
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transform = 'translateX(1rem)';
            el.style.transition = 'opacity 0.2s, transform 0.2s';
            setTimeout(function() { el.remove(); }, 200);
        }, 4000);
    }
    <?php if ($toastSuccess): ?>showToast(<?= json_encode($toastSuccess) ?>, 'success');<?php endif; ?>
    <?php if ($toastError): ?>showToast(<?= json_encode($toastError) ?>, 'error');<?php endif; ?>
})();
</script>
