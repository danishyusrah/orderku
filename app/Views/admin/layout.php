<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? esc($title) . ' - ' : '' ?>Admin Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
         /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; /* gray-800 */ border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #4a5568; /* gray-600 */ border-radius: 10px; border: 2px solid #1f2937; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; /* gray-500 */ }
        /* Style for pagination */
        .pagination li a, .pagination li span {
            display: inline-block; padding: 0.5rem 1rem; margin: 0 0.125rem;
            border-radius: 0.375rem; background-color: #374151; color: #d1d5db;
            transition: background-color 150ms ease-in-out; font-size: 0.875rem; line-height: 1.25rem;
        }
        .pagination li a:hover { background-color: #4b5563; }
        .pagination li.active span { background-color: #ef4444; color: #ffffff; font-weight: 600; }
        .pagination li.disabled span { background-color: #1f2937; color: #6b7280; cursor: not-allowed; }
        .pagination ul { display: flex; flex-wrap: wrap; justify-content: center; list-style: none; padding: 0; gap: 0.25rem; }
        /* Responsive Sidebar */
         @media (max-width: 768px) {
            #admin-sidebar {
                position: fixed; top: 0; left: -16rem; /* Start hidden */ width: 16rem;
                height: 100vh; z-index: 50; transition: left 0.3s ease-in-out; overflow-y: auto;
            }
            #admin-sidebar.open { left: 0; }
             #admin-main-content { padding-left: 1rem; padding-right: 1rem; /* Adjust main content padding */ transition: padding-left 0.3s ease-in-out; }
            #admin-sidebar-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.6); z-index: 40; }
            #admin-sidebar-overlay.open { display: block; }
         }
         /* Add simple pulse animation for flashdata */
         .animate-pulse-once { animation: pulse 1s ease-out; }
         @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .7; } }
    </style>
     <?= $this->renderSection('head') ?>
</head>
<body class="bg-gray-800 text-gray-200">

     <!-- Mobile Header -->
    <header class="md:hidden bg-gray-900/90 backdrop-blur-sm border-b border-gray-700/50 p-4 sticky top-0 z-30 flex items-center justify-between">
         <div class="flex items-center space-x-3">
             <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gradient-to-br from-red-500 to-orange-600 shadow-lg">
                 <i class="fa-solid fa-shield-halved text-white text-lg"></i>
             </div>
             <span class="text-lg font-bold text-white">Admin Panel</span>
         </div>
         <button id="admin-menu-toggle-button" aria-label="Toggle Menu">
             <i class="fa-solid fa-bars text-xl text-gray-300"></i>
         </button>
    </header>

    <div class="flex min-h-screen">
         <!-- Sidebar Overlay for Mobile -->
        <div id="admin-sidebar-overlay" class="md:hidden"></div>

        <!-- Admin Sidebar -->
        <aside id="admin-sidebar" class="w-64 bg-gray-900/90 backdrop-blur-sm border-r border-gray-700/50 flex-shrink-0 p-6 flex flex-col">
            <div class="hidden md:flex items-center space-x-3 mb-10">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-red-500 to-orange-600 shadow-lg">
                    <i class="fa-solid fa-shield-halved text-white text-xl"></i>
                </div>
                <span class="text-xl font-bold text-white">Admin Panel</span>
            </div>
             <!-- Close button for mobile -->
            <button id="admin-close-sidebar-button" class="md:hidden absolute top-4 right-4 text-gray-400 hover:text-white">
                <i class="fa-solid fa-times text-2xl"></i>
            </button>

            <nav class="flex-grow space-y-2 mt-8 md:mt-0">
                 <?php
                    // Helper function to check active menu for admin
                    function isAdminActive($segment2 = '') {
                        $currentSegment1 = current_url(true)->getSegment(1);
                        $currentSegment2 = current_url(true)->getSegment(2);
                        return $currentSegment1 === 'admin' && $currentSegment2 === $segment2;
                    }
                ?>
                <a href="<?= route_to('admin.dashboard') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isAdminActive('') ? 'bg-red-600/50 text-white font-semibold' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= route_to('admin.users') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isAdminActive('users') ? 'bg-red-600/50 text-white font-semibold' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-users w-5 text-center"></i>
                    <span>Pengguna</span>
                </a>
                 <a href="<?= route_to('admin.transactions') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isAdminActive('transactions') ? 'bg-red-600/50 text-white font-semibold' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-receipt w-5 text-center"></i>
                    <span>Transaksi</span>
                </a>
                <a href="<?= route_to('admin.withdrawals') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= isAdminActive('withdrawals') ? 'bg-red-600/50 text-white font-semibold' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> transition-colors">
                    <i class="fa-solid fa-money-bill-transfer w-5 text-center"></i>
                    <span>Penarikan Dana</span>
                </a>
                 <a href="<?= route_to('dashboard') ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-400 border border-gray-700 hover:bg-gray-700 hover:text-white transition-colors mt-8">
                     <i class="fa-solid fa-arrow-left w-5 text-center"></i>
                    <span>Kembali ke User</span>
                </a>
            </nav>

            <div class="mt-auto">
                <div class="border-t border-gray-700 pt-4">
                    <p class="text-sm text-gray-400">Login sebagai Admin:</p>
                    <p class="font-medium text-white truncate"><?= esc(session('username')) ?></p>
                    <a href="<?= route_to('logout') ?>" class="flex items-center justify-center w-full mt-4 px-4 py-2 rounded-lg bg-red-600/20 hover:bg-red-600/40 text-red-300 text-sm font-medium transition-colors">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i>
                        Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Admin Content -->
        <main id="admin-main-content" class="flex-1 p-6 md:p-10 overflow-auto">

            <h1 class="text-2xl md:text-3xl font-bold text-white mb-8"><?= esc($title ?? 'Admin Area') ?></h1>

            <!-- Flash Messages -->
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


            <?= $this->renderSection('content') ?>

        </main>
    </div>

     <script>
        // Mobile sidebar toggle for admin
        const adminMenuToggleButton = document.getElementById('admin-menu-toggle-button');
        const adminCloseSidebarButton = document.getElementById('admin-close-sidebar-button');
        const adminSidebar = document.getElementById('admin-sidebar');
        const adminSidebarOverlay = document.getElementById('admin-sidebar-overlay');
        const adminMainContent = document.getElementById('admin-main-content');

        function toggleAdminSidebar() {
            adminSidebar.classList.toggle('open');
            adminSidebarOverlay.classList.toggle('open');
        }

        if (adminMenuToggleButton) adminMenuToggleButton.addEventListener('click', toggleAdminSidebar);
        if (adminCloseSidebarButton) adminCloseSidebarButton.addEventListener('click', toggleAdminSidebar);
        if (adminSidebarOverlay) adminSidebarOverlay.addEventListener('click', toggleAdminSidebar);

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

