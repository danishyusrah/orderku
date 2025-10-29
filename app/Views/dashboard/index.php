<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-100"><?= esc($title ?? 'Dashboard') ?></h1>
        <p class="text-sm text-gray-400">Kelola produk Anda di sini.</p>
    </div>
    <a href="<?= route_to('product.new') ?>" class="w-full md:w-auto bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition duration-300 flex items-center justify-center space-x-2 shrink-0">
        <i class="fa-solid fa-plus"></i>
        <span>Tambah Produk</span>
    </a>
</div>

<!-- Search Form -->
<div class="mb-6">
    <?= form_open(route_to('dashboard'), ['method' => 'get', 'class' => 'flex items-center gap-2']) ?>
        <?= csrf_field() ?> <!-- Tambahkan CSRF Field untuk GET form jika filter diaktifkan untuk GET -->
        <input type="search" name="search" value="<?= esc($search ?? '', 'attr') ?>" placeholder="Cari nama produk..." class="flex-grow px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150">
            <i class="fa-solid fa-search mr-1 hidden sm:inline"></i> Cari
        </button>
         <?php if (!empty($search)): ?>
            <a href="<?= route_to('dashboard') ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150" title="Reset Pencarian">
                <i class="fa-solid fa-times"></i>
            </a>
        <?php endif; ?>
    <?= form_close() ?>
</div>


<!-- Container untuk tabel -->
<div class="bg-gray-800/50 border border-gray-700/60 rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700/50">
            <thead class="bg-gray-700/50">
                <tr>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ikon</th>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nama Produk</th>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Harga</th>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipe</th>
                    <th scope="col" class="px-5 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider hidden sm:table-cell">Stok</th>
                    <th scope="col" class="px-5 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider hidden md:table-cell">Status</th>
                    <th scope="col" class="relative px-5 py-3">
                        <span class="sr-only">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700/50">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-gray-500">
                            <?php if (!empty($search)): ?>
                                Produk dengan kata kunci "<?= esc($search) ?>" tidak ditemukan. <a href="<?= route_to('dashboard') ?>" class="text-indigo-400 hover:text-indigo-300 font-medium">Reset pencarian?</a>
                            <?php else: ?>
                                Anda belum memiliki produk. Silakan <a href="<?= route_to('product.new') ?>" class="text-indigo-400 hover:text-indigo-300 font-medium">tambah produk baru</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                        // Calculate the starting number for the current page
                        $startNumber = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($products as $index => $product): ?>
                        <tr class="hover:bg-gray-700/30 transition-colors duration-150">
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $startNumber + $index ?></td>
                            <td class="px-5 py-3 whitespace-nowrap align-middle">
                                <img src="<?= esc($product->icon_url, 'attr') ?>"
                                     alt="Ikon"
                                     class="w-10 h-10 rounded object-cover border border-gray-600"
                                     onerror="this.onerror=null; this.src='https://placehold.co/40x40/555/eee?text=?';"
                                     loading="lazy">
                            </td>
                            <td class="px-5 py-3 align-middle max-w-xs">
                                <div class="text-sm font-medium text-gray-100 truncate" title="<?= esc($product->product_name) ?>"><?= esc($product->product_name) ?></div>
                                <?php if ($product->order_type == 'manual' && !empty($product->target_url)): ?>
                                    <div class="text-xs text-gray-500 truncate" title="<?= esc($product->target_url) ?>"><?= esc($product->target_url) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-300 align-middle hidden md:table-cell">
                                <?= ($product->price ?? 0) > 0 ? 'Rp ' . number_format($product->price, 0, ',', '.') : '-' ?>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap align-middle">
                                <?php if ($product->order_type == 'manual'): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-cyan-600/20 text-cyan-300 items-center">
                                        <i class="fa-brands fa-whatsapp mr-1.5 text-base"></i> Manual
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-600/20 text-emerald-300 items-center">
                                        <i class="fa-solid fa-bolt mr-1.5 text-sm"></i> Otomatis
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-center font-semibold align-middle hidden sm:table-cell <?= ($product->order_type === 'auto' && isset($product->available_stock) && $product->available_stock == 0) ? 'text-red-400' : 'text-gray-300' ?>">
                                <?= $product->order_type === 'auto' ? ($product->available_stock ?? 'N/A') : '-' ?>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap align-middle hidden md:table-cell">
                                <?php if ($product->is_active): ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-600/20 text-blue-300">Aktif</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-600/30 text-gray-400">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3 align-middle">
                                <?php if ($product->order_type == 'auto'): ?>
                                    <a href="<?= route_to('product.stock.manage', $product->id) ?>" class="text-yellow-400 hover:text-yellow-300 transition-colors inline-block" title="Kelola Stok">
                                        <i class="fa-solid fa-boxes-stacked"></i> <span class="hidden lg:inline">Stok</span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= route_to('product.edit', $product->id) ?>" class="text-indigo-400 hover:text-indigo-300 transition-colors inline-block" title="Edit Produk">
                                    <i class="fa-solid fa-pen-to-square"></i> <span class="hidden lg:inline">Edit</span>
                                </a>
                                <?= form_open(route_to('product.delete', $product->id), ['class' => 'inline', 'onsubmit' => 'return confirm(\'Apakah Anda yakin ingin menghapus produk ini? Stok terkait (jika ada) juga akan dihapus.\')']) ?>
                                    <?= csrf_field() ?> <!-- Tambahkan CSRF Field -->
                                    <button type="submit" class="text-red-500 hover:text-red-400 transition-colors" title="Hapus Produk">
                                        <i class="fa-solid fa-trash-can"></i> <span class="hidden lg:inline">Hapus</span>
                                    </button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

     <!-- Pagination Links -->
     <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
        <div class="px-5 py-4 border-t border-gray-700/50">
             <?= $pager->links('products', 'default_full') // Use group name 'products' and a suitable template ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

