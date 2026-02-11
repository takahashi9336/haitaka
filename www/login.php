<?php
require_once __DIR__ . '/../private/vendor/autoload.php';
$auth = new \Core\Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->login($_POST['id_name'], $_POST['password'])) {
        header('Location: /index.php');
        exit;
    } else {
        $error = 'IDまたはパスワードが正しくありません。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Login - MyPlatform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-6 text-center text-indigo-600">MyPlatform Login</h1>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'timeout'): ?>
            <div class="bg-yellow-50 text-yellow-700 p-3 rounded mb-4 text-sm border border-yellow-200">
                セッションが終了しました。再度ログインしてください。
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm border border-red-200"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">USER ID</label>
                <input type="text" name="id_name" class="w-full border rounded p-2 outline-none focus:ring-2 focus:ring-indigo-500/20" required>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-1">PASSWORD</label>
                <input type="password" name="password" class="w-full border rounded p-2 outline-none focus:ring-2 focus:ring-indigo-500/20" required>
            </div>
            <button class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700 transition">LOGIN</button>
        </form>
    </div>
</body>
</html>