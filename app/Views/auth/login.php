<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Itsku ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Animasi Latar Belakang */
        @keyframes float { 0% { transform: translate(0, 0) rotate(0deg); } 50% { transform: translate(20px, 30px) rotate(180deg); } 100% { transform: translate(0, 0) rotate(360deg); } }
        .circle-1 { animation: float 25s infinite ease-in-out; }
    </style>
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="fixed inset-0 w-full h-full overflow-hidden z-0">
        <div class="absolute top-1/4 left-1/4 w-72 h-72 sm:w-96 sm:h-96 bg-indigo-600 rounded-full filter blur-3xl opacity-20 circle-1"></div>
        <div class="absolute bottom-10 right-10 w-60 h-60 sm:w-80 sm:h-80 bg-purple-600 rounded-full filter blur-3xl opacity-15 circle-1 animation-delay-[-5s]"></div>
    </div>
    <div class="relative min-h-screen flex items-center justify-center p-4 z-10">
        <div class="w-full max-w-sm sm:max-w-md bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-2xl p-6 sm:p-8">
             <div class="flex justify-center items-center space-x-3 mb-6">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M7 19V5l10 14V5"></path></svg>
                </div>
                <span class="text-2xl font-bold text-white">Itsku ID</span>
            </div>
            <h1 class="text-2xl font-bold text-center text-white mb-6">Login ke Akun Anda</h1>

            <!-- Tampilkan Notifikasi Error/Success -->
            <?php if (session()->get('success')): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?= session()->get('success') ?>
                </div>
            <?php endif; ?>
            <?php if (session()->get('error')): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?= session()->get('error') ?>
                </div>
            <?php endif; ?>
            <?php if (session()->get('errors')): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <ul class="list-disc pl-5">
                    <?php foreach (session()->get('errors') as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?= form_open('login') ?>
                <?= csrf_field() ?> <!-- Tambahkan CSRF Field -->
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" name="email" id="email" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" value="<?= old('email') ?>" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                    <input type="password" name="password" id="password" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
                </div>
                <div class="text-right mb-6">
                     <a href="<?= route_to('forgot.password') ?>" class="text-sm text-indigo-400 hover:text-indigo-300 hover:underline">Lupa Password?</a>
                </div>
                <div class="mb-4">
                    <button type="submit" class="w-full bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-300">Login</button>
                </div>
            <?= form_close() ?>

            <p class="text-center text-sm text-gray-400 mt-6">
                Belum punya akun? <a href="<?= url_to('AuthController::register') ?>" class="font-medium text-indigo-400 hover:text-indigo-300">Daftar di sini</a>
            </p>
        </div>
    </div>
</body>
</html>

