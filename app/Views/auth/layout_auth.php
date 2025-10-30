<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?? 'Autentikasi' ?> - Itsku ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Animasi Latar Belakang (Contoh) */
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
        <?= $this->renderSection('content') ?>
    </div>
</body>
</html>
