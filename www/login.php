<?php
require_once __DIR__ . '/../private/vendor/autoload.php';
$auth = new \Core\Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login($_POST['id_name'], $_POST['password'])) {
        $target = $_SESSION['user']['default_route'] ?? '/index.php';
        header('Location: ' . $target);
        exit;
    } else {
        $error = 'IDまたはパスワードが正しくありません。';
    }
}
$isTimeout = isset($_GET['msg']) && $_GET['msg'] === 'timeout';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Inter', 'Noto Sans JP', sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] flex items-center justify-center min-h-screen p-4 text-slate-800">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm shadow-slate-200/50 overflow-hidden">
            <div class="px-8 pt-8 pb-2 text-center">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200 mx-auto mb-4">
                    <i class="fa-solid fa-layer-group text-xl"></i>
                </div>
                <h1 class="font-black text-slate-800 text-xl tracking-tighter">MyPlatform</h1>
                <p class="text-[10px] font-bold text-slate-400 tracking-wider mt-1">ログイン</p>
            </div>

            <div class="px-8 pb-8 pt-4">
                <?php if ($isTimeout): ?>
                <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium">
                    セッションが終了しました。再度ログインしてください。
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-medium"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="/login.php" autocomplete="on" class="space-y-4">
                    <div>
                        <label for="login-id_name" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">ユーザーID</label>
                        <input id="login-id_name" type="text" name="id_name" required autocomplete="off"
                            class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 focus:border-indigo-200 outline-none transition-all"
                            placeholder="半角英数字">
                    </div>
                    <div>
                        <label for="login-password" class="block text-[10px] font-black text-slate-400 tracking-wider mb-1">パスワード</label>
                        <input id="login-password" type="password" name="password" required autocomplete="current-password"
                            class="w-full border border-slate-200 rounded-xl h-12 px-4 text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 focus:border-indigo-200 outline-none transition-all"
                            placeholder="パスワード">
                    </div>
                    <button type="submit" class="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black tracking-wider rounded-xl shadow-md shadow-indigo-200/50 transition-all">
                        ログイン
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-[10px] text-slate-400 mt-4 font-bold tracking-wider">MyPlatform</p>
    </div>

</body>
</html>
