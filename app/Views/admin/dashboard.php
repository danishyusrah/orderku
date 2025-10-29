<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

    <!-- Card Jumlah Pengguna -->
    <div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg p-6 flex items-center space-x-4">
        <div class="flex-shrink-0 bg-blue-500/20 text-blue-300 rounded-full p-3">
            <i class="fa-solid fa-users fa-xl w-6 h-6"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Total Pengguna</p>
            <p class="text-2xl font-semibold text-white"><?= esc($userCount ?? 0) ?></p>
        </div>
    </div>

     <!-- Card Penarikan Pending -->
    <div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg p-6 flex items-center space-x-4">
        <div class="flex-shrink-0 bg-yellow-500/20 text-yellow-300 rounded-full p-3">
            <i class="fa-solid fa-hourglass-half fa-xl w-6 h-6"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Penarikan Pending</p>
            <p class="text-2xl font-semibold text-white"><?= esc($pendingWithdrawals ?? 0) ?></p>
        </div>
    </div>

    <!-- Card Total Pendapatan Produk -->
    <div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg p-6 flex items-center space-x-4">
        <div class="flex-shrink-0 bg-green-500/20 text-green-300 rounded-full p-3">
            <i class="fa-solid fa-box-archive fa-xl w-6 h-6"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Pendapatan Produk</p>
             <p class="text-2xl font-semibold text-white">Rp <?= number_format($totalRevenue ?? 0, 0, ',', '.') ?></p>
        </div>
    </div>

     <!-- Card Total Pendapatan Premium -->
    <div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg p-6 flex items-center space-x-4">
        <div class="flex-shrink-0 bg-purple-500/20 text-purple-300 rounded-full p-3">
             <i class="fa-solid fa-star fa-xl w-6 h-6"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Pendapatan Premium</p>
             <p class="text-2xl font-semibold text-white">Rp <?= number_format($totalPremiumRevenue ?? 0, 0, ',', '.') ?></p>
        </div>
    </div>


</div>

<!-- You can add more dashboard elements here like charts or recent activity -->
<div class="mt-8 text-gray-400">
    <p>Selamat datang di Admin Panel. Gunakan menu di samping untuk mengelola data aplikasi.</p>
</div>

<?= $this->endSection() ?>
