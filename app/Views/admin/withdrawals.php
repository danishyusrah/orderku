<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Search Form -->
<div class="mb-6">
    <?= form_open(route_to('admin.withdrawals'), ['method' => 'get', 'class' => 'flex items-center gap-2']) ?>
        <?= csrf_field() ?> <!-- Tambahkan CSRF Field untuk GET form jika filter diaktifkan untuk GET -->
        <input type="search" name="search" value="<?= esc($search ?? '', 'attr') ?>" placeholder="Cari username, bank, no rek..." class="flex-grow px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150">
            <i class="fa-solid fa-search mr-1 hidden sm:inline"></i> Cari
        </button>
         <?php if (!empty($search)): ?>
            <a href="<?= route_to('admin.withdrawals') ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150" title="Reset Pencarian">
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Rekening Tujuan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Tgl Request</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                 <?php
                     // Use the correct variable name passed from controller
                     $items = $withdrawals ?? [];
                     $pager = \Config\Services::pager();
                     $currentPage = $pager->getCurrentPage('withdrawals'); // Use 'withdrawals' group
                     $perPage = $pager->getPerPage('withdrawals');       // Use 'withdrawals' group
                     $startNumber = ($currentPage - 1) * $perPage + 1;
                 ?>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                             <?php if (!empty($search)): ?>
                                Permintaan penarikan dengan kata kunci "<?= esc($search) ?>" tidak ditemukan.
                            <?php else: ?>
                                Tidak ada permintaan penarikan dana.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $index => $wd): ?>
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $startNumber + $index ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                <?= esc($wd->username ?? 'N/A') ?> <span class="text-xs text-gray-500">(ID: <?= esc($wd->user_id) ?>)</span>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                Rp <?= number_format($wd->amount, 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden sm:table-cell align-middle">
                                <div><?= esc($wd->bank_name) ?></div>
                                <div class="text-xs text-gray-400"><?= esc($wd->account_number) ?></div>
                                <div class="text-xs text-gray-400">a/n <?= esc($wd->account_name) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <?php
                                    $statusClass = 'bg-orange-500/20 text-orange-300'; // Default (pending)
                                    $statusText = ucfirst($wd->status);
                                    if ($wd->status === 'approved') {
                                        $statusClass = 'bg-green-500/20 text-green-300';
                                        $statusText = 'Disetujui';
                                    } elseif ($wd->status === 'rejected') {
                                        $statusClass = 'bg-red-500/20 text-red-300';
                                        $statusText = 'Ditolak';
                                    }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                    <?= esc($statusText) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 hidden md:table-cell align-middle">
                                <?= \CodeIgniter\I18n\Time::parse($wd->created_at)->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 align-middle">
                                <?php if ($wd->status === 'pending'): ?>
                                    <!-- Form Setuju -->
                                    <?= form_open(route_to('admin.withdrawals.approve', $wd->id), ['class' => 'inline', 'onsubmit' => "this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Memproses...';"]) ?>
                                        <?= csrf_field() ?> <!-- Tambahkan CSRF Field -->
                                        <button type="submit" class="text-green-400 hover:text-green-300 px-2 py-1 rounded bg-green-500/10 hover:bg-green-500/20 text-xs disabled:opacity-50" onclick="return confirm('Anda yakin ingin MENYETUJUI penarikan ini?')"><i class="fa-solid fa-check mr-1"></i> Setujui</button>
                                    <?= form_close() ?>

                                    <!-- Form Tolak -->
                                     <?= form_open(route_to('admin.withdrawals.reject', $wd->id), ['class' => 'inline', 'onsubmit' => "this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Memproses...';"]) ?>
                                        <?= csrf_field() ?> <!-- Tambahkan CSRF Field -->
                                        <button type="submit" class="text-red-400 hover:text-red-300 px-2 py-1 rounded bg-red-500/10 hover:bg-red-500/20 text-xs disabled:opacity-50" onclick="return confirm('Anda yakin ingin MENOLAK penarikan ini? Saldo akan dikembalikan ke user.')"><i class="fa-solid fa-times mr-1"></i> Tolak</button>
                                    <?= form_close() ?>
                                <?php else: ?>
                                    <span class="text-gray-500 text-xs italic">
                                        <?= $wd->processed_at ? \CodeIgniter\I18n\Time::parse($wd->processed_at)->toLocalizedString('dd MMM yyyy') : 'Selesai' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
     <!-- Pagination Links -->
     <?php if (isset($pager) && $pager->getPageCount('withdrawals') > 1): ?>
        <div class="px-6 py-4 border-t border-gray-700 bg-gray-800/50">
             <?= $pager->links('withdrawals', 'default_full') // Use 'withdrawals' group and template ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

