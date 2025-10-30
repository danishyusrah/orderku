<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? esc($title) . ' - ' : '' ?>Kontrol Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
         /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #1f2937; /* gray-800 */
             border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* gray-600 */
            border-radius: 10px;
            border: 2px solid #1f2937; /* gray-800 */
        }
         ::-webkit-scrollbar-thumb:hover {
            background-color: #6b7280; /* gray-500 */
        }
        /* Style for pagination */
        .pagination li a, .pagination li span {
            display: inline-block; padding: 0.5rem 1rem; margin: 0 0.125rem;
            border-radius: 0.375rem; background-color: #374151; color: #d1d5db;
            transition: background-color 150ms ease-in-out; font-size: 0.875rem; line-height: 1.25rem;
        }
        .pagination li a:hover { background-color: #4b5563; }
        .pagination li.active span { background-color: #4f46e5; color: #ffffff; font-weight: 600; }
        .pagination li.disabled span { background-color: #1f2937; color: #6b7280; cursor: not-allowed; }
        .pagination ul { display: flex; flex-wrap: wrap; justify-content: center; list-style: none; padding: 0; gap: 0.25rem; }
         /* Responsive Sidebar */
         @media (max-width: 768px) {
            #sidebar {
                position: fixed; top: 0; left: -16rem; /* Start hidden */ width: 16rem;
                height: 100vh; z-index: 50; transition: left 0.3s ease-in-out; overflow-y: auto;
            }
            #sidebar.open { left: 0; }
            #main-content { padding-left: 1rem; padding-right: 1rem; /* Adjust main content padding */ transition: padding-left 0.3s ease-in-out; }
            #sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.6); z-index: 40; }
            #sidebar-overlay.open { display: block; }
         }
    </style>

    <?= $this->renderSection('head') ?>
</head>
<body class="bg-gray-900 text-gray-200">

    <!-- Mobile Header -->
    <header class="md:hidden bg-gray-900/90 backdrop-blur-sm border-b border-gray-700/50 p-4 sticky top-0 z-30 flex items-center justify-between">
         <div class="flex items-center space-x-3">
             <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
                  <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M7 19V5l10 14V5"></path></svg>
             </div>
             <span class="text-lg font-bold text-white">Itsku ID</span>
         </div>
         <button id="menu-toggle-button" aria-label="Toggle Menu">
             <i class="fa-solid fa-bars text-xl text-gray-300"></i>
         </button>
    </header>


    <div class="flex min-h-screen">
         <!-- Sidebar Overlay for Mobile -->
        <div id="sidebar-overlay" class="md:hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-gray-900/90 backdrop-blur-sm border-r border-gray-700/50 flex-shrink-0 p-6 flex flex-col">
            <div class="hidden md:flex items-center space-x-3 mb-10">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M7 19V5l10 14V5"></path></svg>
                </div>
                <span class="text-xl font-bold text-white">Itsku ID</span>
            </div>
             <!-- Close button for mobile -->
            <button id="close-sidebar-button" class="md:hidden absolute top-4 right-4 text-gray-400 hover:text-white">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>


            <nav class="flex-grow space-y-2 mt-8 md:mt-0">
                 <?php
                    // Helper function to check active menu
                    function isActive($segment2 = '') {
                        $currentSegment1 = current_url(true)->getSegment(1);
                        $currentSegment2 = current_url(true)->getSegment(2);
                        return $currentSegment1 === 'dashboard' && $currentSegment2 === $segment2;
                    }
                ?>
                <a href="<?= route_to('dashboard') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isActive('') ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center"></i>
                    <span>Produk Saya</span>
                </a>
                 <a href="<?= route_to('dashboard.transactions') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isActive('transactions') ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-receipt w-5 text-center"></i>
                    <span>Riwayat Transaksi</span>
                </a>
                <a href="<?= route_to('product.new') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= strpos(current_url(true)->getPath(), 'product/new') !== false ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-plus w-5 text-center"></i>
                    <span>Tambah Produk</span>
                </a>
                 <a href="<?= route_to('dashboard.withdraw') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isActive('withdraw') ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-money-bill-transfer w-5 text-center"></i>
                    <span>Penarikan Dana</span>
                </a>
                 <a href="<?= route_to('dashboard.settings') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isActive('settings') ? 'bg-indigo-600 text-white font-semibold' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-sliders w-5 text-center"></i>
                    <span>Pengaturan Profil</span>
                </a>

                <!-- Link Upgrade Premium -->
                <?php if (isset($user) && !$user->is_premium): ?>
                <a href="<?= route_to('dashboard.upgrade') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isActive('upgrade') ? 'bg-yellow-500/30 text-yellow-100 font-semibold' : 'text-yellow-300 bg-yellow-500/10 hover:bg-yellow-500/20' ?> transition-colors mt-4">
                    <i class="fa-solid fa-star w-5 text-center"></i>
                    <span>Upgrade Premium</span>
                </a>
                <?php endif; ?>

                 <a href="<?= route_to('profile.public', session('username')) ?>" target="_blank" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors mt-4">
                    <i class="fa-solid fa-link w-5 text-center"></i>
                    <span>Halaman Publik</span>
                </a>
            </nav>

            <div class="mt-auto">
                <div class="border-t border-gray-700/50 pt-4">
                    <p class="text-sm text-gray-400">Login sebagai:</p>
                    <p class="font-medium text-white truncate"><?= esc(session('username')) ?></p>
                     <!-- Tampilkan role jika admin -->
                     <?php if (session('is_admin')): ?>
                         <a href="<?= route_to('admin.dashboard') ?>" class="text-xs px-2 py-0.5 rounded bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 font-medium inline-block mt-1">
                             <i class="fa-solid fa-shield-halved mr-1"></i> Ke Panel Admin
                         </a>
                    <?php endif; ?>
                    <a href="<?= route_to('logout') ?>" class="flex items-center justify-center w-full mt-4 px-4 py-2 rounded-lg bg-red-600/20 hover:bg-red-600/40 text-red-300 text-sm font-medium transition-colors">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main id="main-content" class="flex-1 p-6 md:p-10 overflow-auto">

            <!-- Judul Halaman Dinamis (Hanya di layar besar, sudah ada di mobile header) -->
            <h1 class="text-2xl md:text-3xl font-bold text-white mb-8 hidden md:block"><?= esc($title ?? 'Dashboard') ?></h1>

            <!-- Notifikasi (Flashdata) -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-6 flex justify-between items-center text-sm md:text-base animate-pulse-once" role="alert" id="success-alert">
                    <span><?= session()->getFlashdata('success') ?></span>
                     <button onclick="document.getElementById('success-alert').style.display='none'" class="text-green-200 hover:text-white text-xl ml-2">&times;</button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                 <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6 flex justify-between items-center text-sm md:text-base animate-pulse-once" role="alert" id="error-alert">
                    <span><?= session()->getFlashdata('error') ?></span>
                     <button onclick="document.getElementById('error-alert').style.display='none'" class="text-red-200 hover:text-white text-xl ml-2">&times;</button>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('errors')): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6 text-sm md:text-base animate-pulse-once" role="alert" id="errors-alert">
                     <div class="flex justify-between items-center mb-2">
                        <p class="font-bold">Terjadi Kesalahan:</p>
                         <button onclick="document.getElementById('errors-alert').style.display='none'" class="text-red-200 hover:text-white text-xl ml-2">&times;</button>
                     </div>
                    <ul class="list-disc pl-5 text-sm">
                    <?php foreach (session()->getFlashdata('errors') as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Render konten spesifik halaman -->
            <?= $this->renderSection('content') ?>

        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        const menuToggleButton = document.getElementById('menu-toggle-button');
        const closeSidebarButton = document.getElementById('close-sidebar-button');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const mainContent = document.getElementById('main-content');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('open');
        }

        if (menuToggleButton) menuToggleButton.addEventListener('click', toggleSidebar);
        if (closeSidebarButton) closeSidebarButton.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

        // Auto-close flash messages after a delay with fade-out
        function autoCloseFlashMessages() {
            const alerts = document.querySelectorAll('#success-alert, #error-alert, #errors-alert');
            alerts.forEach(alert => {
                 if (alert) {
                     setTimeout(() => {
                         alert.style.transition = 'opacity 0.5s ease-out';
                         alert.style.opacity = '0';
                         setTimeout(() => alert.style.display = 'none', 500); // Wait for fade out
                     }, 5000); // Start hiding after 5 seconds
                 }
            });
        }
        document.addEventListener('DOMContentLoaded', autoCloseFlashMessages);

    </script>
     <?= $this->renderSection('scripts') ?>

</body>
</html>

