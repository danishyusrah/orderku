<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="mb-6">
    <a href="<?= route_to('dashboard') ?>" class="text-indigo-400 hover:text-indigo-300 text-sm">
        <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Daftar Produk
    </a>
</div>

<h1 class="text-2xl font-semibold text-gray-100 mb-2">Kelola Stok Varian</h1>
<p class="text-md text-gray-400 mb-6">Produk: <span class="font-medium text-white"><?= esc($product->product_name) ?></span></p>

<?php if (empty($variants)): ?>
    <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-300 px-4 py-3 rounded-lg text-sm" role="alert">
        Produk ini ditandai memiliki varian, tetapi belum ada varian yang ditambahkan. Silakan <a href="<?= route_to('product.edit', $product->id) ?>" class="font-bold underline hover:text-yellow-200">edit produk</a> untuk menambahkan varian.
    </div>
<?php else: ?>
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700/50">
                <thead class="bg-gray-800/70">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">No</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Nama Varian</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Stok Tersedia</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50" id="variant-stock-table-body">
                    <?php foreach ($variants as $index => $variant): ?>
                        <tr id="variant-row-<?= $variant->id ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $index + 1 ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white align-middle"><?= esc($variant->name) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">Rp <?= number_format($variant->price, 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm align-middle">
                                <!-- Hanya Tampilkan Jumlah Stok -->
                                <span id="stock-count-<?= $variant->id ?>" class="font-semibold text-white"><?= esc($variant->stock) ?></span>
                                <span class="text-xs text-gray-400 ml-1">(Item unik)</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm align-middle">
                                <?php if ($variant->is_active): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-500/20 text-blue-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-600 text-gray-300">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium align-middle">
                                <!-- Tombol untuk ke halaman kelola item stok -->
                                <a href="<?= route_to('product.variant.stock.items', $product->id, $variant->id) ?>"
                                   class="px-3 py-1.5 text-xs rounded bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-300 transition-colors font-medium">
                                    <i class="fa-solid fa-list-check mr-1"></i> Kelola Item Stok
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
     <div class="mt-4 text-center">
         <a href="<?= route_to('product.edit', $product->id) ?>" class="text-indigo-400 hover:text-indigo-300 text-sm">
             <i class="fa-solid fa-pencil mr-1"></i> Edit Varian (Nama, Harga, Status)
         </a>
     </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Tidak ada lagi fungsi update stok langsung di sini
    // Fungsi JavaScript sebelumnya (prepareUpdate, updateStock) bisa dihapus jika tidak diperlukan lagi
    // Pastikan setup CSRF token tetap ada jika ada request AJAX lain di halaman ini
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
     if (csrfTokenMeta) {
         fetch = (originalFetch => {
             return (...args) => {
                 if (args[1] && args[1].headers && typeof args[1].headers === 'object') {
                     args[1].headers['X-CSRF-TOKEN'] = csrfTokenMeta.getAttribute('content');
                 }
                 return originalFetch(...args);
             };
         })(fetch);
     } else {
         console.warn('CSRF meta tag not found. AJAX requests might fail.');
     }
</script>
<?= $this->endSection() ?>
