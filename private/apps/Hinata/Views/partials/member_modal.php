<?php
/**
 * メンバー詳細モーダル（メンバー帳・楽曲フォーメーション等で共通利用）
 * 使用前に $user が定義されていること。JS: HinataMemberModal.init() 後に HinataMemberModal.open(id, event) で表示。
 */
if (!isset($user)) {
    $user = $_SESSION['user'] ?? [];
}
// 日向坂ポータル内での編集ボタン表示用（admin / hinata_admin）
$isAdmin = in_array(($user['role'] ?? ''), ['admin', 'hinata_admin'], true);
?>
<style>
    #memberModal { opacity: 0; }
    #memberModal.active { display: block !important; overflow-y: auto; -webkit-overflow-scrolling: touch; opacity: 1; }
    @keyframes backdropFadeIn { 0% { opacity: 0; } 100% { opacity: 1; } }
    @keyframes backdropFadeOut { 0% { opacity: 1; } 100% { opacity: 0; } }
    #memberModal.modal-opening { animation: backdropFadeIn 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
    #memberModal.modal-opening .modal-content { animation: modalExpandFromPoint 0.65s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards; }
    #memberModal.modal-closing { animation: backdropFadeOut 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards; }
    #memberModal.modal-closing .modal-content { animation: modalShrinkToPoint 0.3s cubic-bezier(0.55, 0.09, 0.68, 0.53) forwards; }
    @keyframes modalExpandFromPoint {
        0% { transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3); opacity: 0; }
        100% { transform: translate(0, 0) scale(1); opacity: 1; }
    }
    @keyframes modalShrinkToPoint {
        0% { transform: translate(0, 0) scale(1); opacity: 1; }
        100% { transform: translate(var(--modal-translate-x, 0), var(--modal-translate-y, 0)) scale(0.3); opacity: 0; }
    }
    .fav-btn-base { height: 2rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; transition: all 0.18s ease-out; padding: 0 0.5rem; gap: 0.25rem; white-space: nowrap; }
    .fav-btn-base:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25); }
    .fav-btn-inactive { background-color: rgba(15,23,42,0.55); color: rgba(255,255,255,0.7); }
    .fav-btn-active-top { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
    .fav-btn-active-2nd { background: linear-gradient(135deg, #ec4899, #db2777); color: #fff; }
    .fav-btn-active-3rd { background: linear-gradient(135deg, #f472b6, #e879a0); color: #fff; }
    .fav-btn-active-star { background: linear-gradient(135deg, #f59e0b, #eab308); color: #fff; }
    @keyframes favorite-pop { 0% { transform: scale(1); } 40% { transform: scale(1.25); } 100% { transform: scale(1); } }
    .favorite-pop { animation: favorite-pop 0.22s ease-out; }
</style>
<div id="memberModal" class="fixed inset-0 z-[100] hidden overflow-y-auto bg-slate-900/80 backdrop-blur-xl transition-all">
    <div class="relative w-full max-w-5xl mx-auto md:my-10 min-h-full flex items-center">
        <div class="modal-content bg-white w-full h-full md:h-auto md:max-h-[90vh] md:rounded-xl shadow-2xl relative flex flex-col md:flex-row md:overflow-hidden min-h-screen md:min-h-0">
            <button type="button" id="memberModalCloseBtn" class="fixed md:absolute top-4 right-4 md:top-6 md:right-6 w-12 h-12 rounded-full bg-slate-100/90 text-slate-500 flex items-center justify-center z-[110] hover:bg-white shadow-lg"><i class="fa-solid fa-xmark text-lg"></i></button>

            <div class="w-full md:w-[380px] shrink-0 border-r border-slate-50 flex flex-col bg-white">
                <div id="modalHeader" class="h-64 md:h-72 relative shrink-0 flex flex-col justify-end p-8 text-white overflow-hidden bg-slate-200">
                    <img id="modalImg" src="" alt="" class="absolute inset-0 w-full h-full object-cover hidden">
                    <div class="relative z-10 text-white drop-shadow-md h-full flex flex-col justify-between">
                        <span id="modalGen" class="text-[10px] font-black opacity-90 tracking-wider bg-black/20 px-2 py-0.5 rounded self-start"></span>
                        <div class="flex items-end justify-between gap-3 mt-auto">
                            <h2 id="modalName" class="text-3xl md:text-4xl font-black leading-tight break-words"></h2>
                            <div id="favButtonBar" class="flex items-center gap-1.5 flex-wrap justify-end">
                                <button type="button" id="favTopBtn" data-level="9" class="fav-btn-base fav-btn-inactive" title="最推し"><i class="fa-solid fa-crown"></i><span class="hidden sm:inline">最推し</span></button>
                                <button type="button" id="fav2ndBtn" data-level="8" class="fav-btn-base fav-btn-inactive" title="2推し"><i class="fa-solid fa-heart"></i><span class="hidden sm:inline">2推し</span></button>
                                <button type="button" id="fav3rdBtn" data-level="7" class="fav-btn-base fav-btn-inactive" title="3推し"><i class="fa-regular fa-heart"></i><span class="hidden sm:inline">3推し</span></button>
                                <button type="button" id="favStarBtn" data-level="1" class="fav-btn-base fav-btn-inactive" title="気になる"><i class="fa-regular fa-star"></i></button>
                                <?php if ($isAdmin): ?>
                                <a id="adminEditBtn" href="#" class="fav-btn-base bg-sky-500 text-white shadow-lg hover:bg-sky-600 transition hidden" title="このメンバーを編集" style="padding:0;width:2rem;"><i class="fa-solid fa-user-pen text-xs"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="modalImgThumbsWrap" class="hidden shrink-0 py-2 px-4 bg-white border-b border-slate-100 flex justify-center gap-2">
                    <div id="modalImgThumbs" class="flex gap-2 flex-wrap justify-center"></div>
                </div>

                <div class="pt-4 pb-8 px-8 space-y-6 overflow-y-auto custom-scroll">
                    <div id="modalInfoArea" class="hidden bg-sky-50/50 p-5 rounded-xl border border-sky-100/50">
                        <p class="text-[9px] font-black text-sky-400 mb-1 tracking-wider">紹介文</p>
                        <p id="modalInfo" class="text-sm font-medium text-slate-600 leading-relaxed whitespace-pre-wrap"></p>
                    </div>
                    <div id="penlightSection" class="hidden bg-white p-5 rounded-xl border border-slate-100/70">
                        <p class="text-[9px] font-black text-slate-400 mb-2 tracking-wider">サイリウムカラー</p>
                        <div class="flex items-center gap-3">
                            <div id="penlight1" class="flex-1 h-9 rounded-lg flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                            <div id="penlight2" class="flex-1 h-9 rounded-lg flex items-center justify-center text-[11px] font-bold shadow-sm border border-slate-100"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">血液型</p><p id="modalBlood" class="text-base font-black text-slate-700">--</p></div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">身長</p><p id="modalHeight" class="text-base font-black text-slate-700">--</p></div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">生年月日</p><p id="modalBirth" class="text-base font-black text-slate-700">--</p></div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-100"><p class="text-[9px] font-black text-slate-400 mb-1 tracking-wider">出身地</p><p id="modalPlace" class="text-base font-black text-slate-700">--</p></div>
                    </div>
                    <div id="snsLinks" class="grid grid-cols-1 gap-2">
                        <a id="blogBtn" href="#" target="_blank" class="h-12 bg-sky-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-sky-600 border border-sky-100/50 hover:bg-sky-100 transition-all hidden"><i class="fa-solid fa-blog"></i> 公式ブログ</a>
                        <a id="instaBtn" href="#" target="_blank" class="h-12 bg-pink-50 rounded-xl flex items-center px-4 gap-3 text-xs font-black text-pink-600 border border-pink-100/50 hover:bg-pink-100 transition-all hidden"><i class="fa-brands fa-instagram text-lg"></i> Instagram</a>
                    </div>
                    <div class="pb-10 md:pb-0">
                        <a id="memberDetailPageBtn" href="#" class="h-12 bg-slate-800 rounded-xl flex items-center justify-center px-4 gap-2 text-xs font-black text-white hover:bg-slate-700 transition-all shadow-sm"><i class="fa-solid fa-arrow-right"></i>メンバー詳細ページへ</a>
                    </div>
                </div>
            </div>

            <div id="videoContainer" class="flex-1 bg-slate-50 p-6 md:p-12 flex flex-col justify-center pb-32 md:pb-12 overflow-y-auto">
                <div id="videoArea" class="hidden w-full max-w-3xl mx-auto">
                    <p class="text-[10px] font-black text-slate-400 tracking-wider mb-4 text-center">紹介動画</p>
                    <div class="aspect-video w-full rounded-lg md:rounded-xl overflow-hidden bg-black shadow-2xl ring-4 md:ring-8 ring-white">
                        <iframe id="modalVideo" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
                <div id="noVideoMsg" class="text-center text-slate-300 py-20">
                    <i class="fa-brands fa-youtube text-6xl mb-4 opacity-20"></i>
                    <p class="text-[10px] font-bold tracking-wider">紹介動画はありません</p>
                </div>
            </div>
        </div>
    </div>
</div>
