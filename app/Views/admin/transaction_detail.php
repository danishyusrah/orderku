<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<div class="max-w-4xl mx-auto">
     <div class="mb-6">
        <a href="<?= route_to('admin.transactions') ?>" class="text-indigo-400 hover:text-indigo-300 text-sm">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Daftar Transaksi
        </a>
    </div>

    <div class="bg-gray-900/80 border border-gray-700 rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-700">
            <h2 class="text-xl font-semibold text-white">Detail Transaksi</h2>
            <p class="text-sm text-gray-400 font-mono"><?= esc($transaction->order_id) ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
            <!-- Kolom Kiri: Info Dasar -->
            <div class="space-y-4 text-sm">
                <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Tanggal</label>
                    <p class="text-gray-100"><?= \CodeIgniter\I18n\Time::parse($transaction->created_at)->toLocalizedString('dd MMMM yyyy, HH:mm:ss') ?></p>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Tipe Transaksi</label>
                    <p class="text-gray-100">
                        <?php if ($transaction->transaction_type === 'product'): ?>
                            Pembelian Produk
                        <?php elseif ($transaction->transaction_type === 'premium'): ?>
                            Upgrade Premium
                        <?php else: ?>
                            <?= esc(ucfirst($transaction->transaction_type)) ?>
                        <?php endif; ?>
                    </p>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Jumlah</label>
                    <p class="text-lg font-semibold text-white">Rp <?= number_format($transaction->amount, 0, ',', '.') ?></p>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Status</label>
                     <?php
                        $statusClass = 'bg-gray-600 text-gray-300'; // Default (pending)
                        $statusText = ucfirst($transaction->status);
                        if (in_array($transaction->status, ['success', 'settlement', 'capture'])) {
                            $statusClass = 'bg-green-500/20 text-green-300'; $statusText = 'Berhasil';
                        } elseif (in_array($transaction->status, ['failed', 'expired', 'deny', 'cancel', 'failure'])) {
                            $statusClass = 'bg-red-500/20 text-red-300'; $statusText = 'Gagal/Expired';
                        } elseif ($transaction->status === 'challenge') {
                             $statusClass = 'bg-orange-500/20 text-orange-300';
                        }
                    ?>
                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                        <?= esc($statusText) ?>
                    </span>
                </div>
                 <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Gateway</label>
                    <p class="text-gray-100"><?= esc(ucfirst($transaction->payment_gateway)) ?></p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Snap Token</label>
                    <p class="text-gray-300 break-all font-mono text-xs"><?= esc($transaction->snap_token ?? '-') ?></p>
                </div>
            </div>

            <!-- Kolom Kanan: Info Pembeli & Penjual/Produk -->
            <div class="space-y-4 text-sm">
                 <div>
                    <label class="block text-xs font-medium text-gray-400 uppercase">Pembeli</label>
                    <p class="text-gray-100"><?= esc($transaction->buyer_name) ?></p>
                    <p class="text-gray-400 text-xs"><?= esc($transaction->buyer_email) ?></p>
                </div>
                 <hr class="border-gray-700">
                 <?php if ($transaction->transaction_type === 'product' && $transaction->product_id): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 uppercase">Produk</label>
                        <p class="text-gray-100"><?= esc($transaction->product_name ?? 'Produk Dihapus') ?></p>
                         <p class="text-gray-400 text-xs">(ID: <?= esc($transaction->product_id) ?>)</p>
                    </div>
                     <div>
                        <label class="block text-xs font-medium text-gray-400 uppercase">Penjual</label>
                         <p class="text-gray-100"><?= esc($transaction->seller_username ?? 'User Dihapus') ?></p>
                         <p class="text-gray-400 text-xs">(ID: <?= esc($transaction->user_id) ?>)</p>
                    </div>
                    <?php if ($stockData): ?>
                         <hr class="border-gray-700">
                         <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase">Data Stok Terkirim</label>
                            <pre class="mt-1 text-xs bg-gray-800 p-3 rounded border border-gray-600 text-gray-300 whitespace-pre-wrap break-words"><?= esc($stockData->stock_data) ?></pre>
                        </div>
                    <?php elseif (in_array($transaction->status, ['success', 'settlement', 'capture']) && $transaction->product_order_type === 'auto'): ?>
                         <hr class="border-gray-700">
                         <div>
                            <label class="block text-xs font-medium text-gray-400 uppercase">Data Stok Terkirim</label>
                            <p class="mt-1 text-xs text-orange-400 bg-orange-500/10 p-2 rounded border border-orange-500/30">Data stok tidak ditemukan (kemungkinan terhapus atau error saat pengiriman).</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($transaction->transaction_type === 'premium'): ?>
                     <div>
                        <label class="block text-xs font-medium text-gray-400 uppercase">User yang Upgrade</label>
                         <p class="text-gray-100"><?= esc($transaction->seller_username ?? 'User Dihapus') ?></p>
                         <p class="text-gray-400 text-xs">(ID: <?= esc($transaction->user_id) ?>)</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
