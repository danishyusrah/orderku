<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Search Form -->
<div class="mb-6">
    <?= form_open(route_to('admin.transactions'), ['method' => 'get', 'class' => 'flex items-center gap-2']) ?>
         <?= csrf_field() ?> <!-- Tambahkan CSRF Field untuk GET form jika filter diaktifkan untuk GET -->
        <input type="search" name="search" value="<?= esc($search ?? '', 'attr') ?>" placeholder="Cari Order ID, Pembeli, Produk, Penjual..." class="flex-grow px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150">
            <i class="fa-solid fa-search mr-1 hidden sm:inline"></i> Cari
        </button>
         <?php if (!empty($search)): ?>
            <a href="<?= route_to('admin.transactions') ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150" title="Reset Pencarian">
                <i class="fa-solid fa-times"></i>
            </a>
        <?php endif; ?>
    <?= form_close() ?>
</div>


<div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Penjual</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Pembeli</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Produk/Tipe</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-400">
                             <?php if (!empty($search)): ?>
                                Transaksi dengan kata kunci "<?= esc($search) ?>" tidak ditemukan.
                            <?php else: ?>
                                Tidak ada data transaksi.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                     <?php
                        $pager = \Config\Services::pager();
                        $currentPage = $pager->getCurrentPage('transactions'); // Use 'transactions' group
                        $perPage = $pager->getPerPage('transactions');       // Use 'transactions' group
                        $startNumber = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($transactions as $index => $tx): ?>
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $startNumber + $index ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white font-mono align-middle"><?= esc($tx->order_id) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden sm:table-cell align-middle"><?= esc($tx->seller_username ?? 'N/A') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                <div><?= esc($tx->buyer_name) ?></div>
                                <div class="text-xs text-gray-400 truncate max-w-[150px]"><?= esc($tx->buyer_email) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                <?php if ($tx->transaction_type === 'product'): ?>
                                    <span class="font-medium"><?= esc($tx->product_name ?? 'Produk Dihapus') ?></span>
                                <?php elseif ($tx->transaction_type === 'premium'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-500/20 text-yellow-300">Upgrade Premium</span>
                                <?php else: ?>
                                    <?= esc(ucfirst($tx->transaction_type)) ?>
                                <?php endif; ?>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden md:table-cell align-middle">
                                Rp <?= number_format($tx->amount, 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <?php
                                    $statusClass = 'bg-gray-600 text-gray-300'; // Default (pending)
                                    $statusText = ucfirst($tx->status);
                                    if (in_array($tx->status, ['success', 'settlement', 'capture'])) { // Group success statuses
                                        $statusClass = 'bg-green-500/20 text-green-300';
                                        $statusText = 'Berhasil';
                                    } elseif (in_array($tx->status, ['failed', 'expired', 'deny', 'cancel', 'failure'])) {
                                        $statusClass = 'bg-red-500/20 text-red-300';
                                        $statusText = 'Gagal/Expired';
                                    } elseif ($tx->status === 'challenge') {
                                         $statusClass = 'bg-orange-500/20 text-orange-300';
                                    }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                    <?= esc($statusText) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 hidden md:table-cell align-middle">
                                <?= \CodeIgniter\I18n\Time::parse($tx->created_at)->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                            </td>
                             <!-- Aksi (Lihat Detail) -->
                           <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium align-middle">
                                <a href="<?= route_to('admin.transactions.detail', $tx->id) ?>" class="text-indigo-400 hover:text-indigo-300 text-xs">
                                     <i class="fa-solid fa-eye mr-1"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
     <!-- Pagination Links -->
     <?php if (isset($pager) && $pager->getPageCount('transactions') > 1): ?>
        <div class="px-6 py-4 border-t border-gray-700 bg-gray-800/50">
             <?= $pager->links('transactions', 'default_full') // Use 'transactions' group and template ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

