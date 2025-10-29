<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
    <h2 class="text-xl font-semibold text-white p-6 border-b border-gray-700/50">Riwayat Transaksi Penjualan</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700/50">
            <thead class="bg-gray-800/70">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Order ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Produk/Tipe</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Pembeli</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/50">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                            Belum ada riwayat transaksi.
                        </td>
                    </tr>
                <?php else: ?>
                     <?php
                        // Calculate the starting number for the current page
                        $startNumber = ($currentPage - 1) * $itemsPerPage + 1;
                    ?>
                    <?php foreach ($transactions as $index => $tx): ?>
                        <tr class="hover:bg-gray-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?= $startNumber + $index ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white font-mono"><?= esc($tx->order_id) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?= \CodeIgniter\I18n\Time::parse($tx->created_at)->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php if ($tx->transaction_type === 'product'): ?>
                                    <span class="font-medium"><?= esc($tx->product_name ?? 'Produk Dihapus') ?></span>
                                <?php elseif ($tx->transaction_type === 'premium'): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-500/20 text-yellow-300">Upgrade Premium</span>
                                <?php else: ?>
                                    <?= esc(ucfirst($tx->transaction_type)) ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden sm:table-cell">
                                <div><?= esc($tx->buyer_name) ?></div>
                                <div class="text-xs text-gray-400"><?= esc($tx->buyer_email) ?></div>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-400">
                                + Rp <?= number_format($tx->amount, 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $statusClass = 'bg-gray-600 text-gray-300'; // Default (pending)
                                    $statusText = ucfirst($tx->status);
                                    if ($tx->status === 'success') {
                                        $statusClass = 'bg-green-500/20 text-green-300';
                                        $statusText = 'Berhasil';
                                    } elseif (in_array($tx->status, ['failed', 'expired', 'deny', 'cancel'])) {
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination Links -->
    <?php if ($pager): ?>
        <div class="p-4 border-t border-gray-700/50">
            <?= $pager->links('transactions', 'default_full') // Use group 'transactions' ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
