<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- Kolom Kiri: Info Saldo & Form Withdraw -->
    <div class="lg:col-span-1">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Saldo Anda</h2>
            <p class="text-4xl font-bold text-green-400 mb-6">Rp <?= number_format($user->balance ?? 0, 0, ',', '.') ?></p>

            <hr class="border-gray-700/50 my-6">

            <h2 class="text-xl font-semibold text-white mb-4">Ajukan Penarikan</h2>

            <?php if (empty($user->bank_name) || empty($user->account_number) || empty($user->account_name)): ?>
                <div class="bg-yellow-500/10 border border-yellow-500/30 text-yellow-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                   Harap lengkapi <a href="<?= route_to('dashboard.settings') ?>" class="font-bold underline hover:text-yellow-200">Informasi Penarikan Dana</a> di Pengaturan Profil terlebih dahulu.
                </div>
            <?php else: ?>
                 <?= form_open(route_to('dashboard.withdraw.request')) ?>
                    <?= csrf_field() ?> <!-- Tambahkan CSRF Field -->
                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-300 mb-2">Jumlah Penarikan (Rp)</label>
                        <input type="number" name="amount" id="amount" value="<?= old('amount') ?>" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Min. Rp 10.000" required min="10000">
                         <p class="text-xs text-gray-500 mt-1">Minimal penarikan Rp 10.000.</p>
                    </div>

                    <div class="bg-gray-800/50 p-4 rounded-lg border border-gray-700 text-sm mb-4">
                        <p class="text-gray-400">Dana akan ditransfer ke:</p>
                        <p class="font-medium text-white"><?= esc($user->bank_name) ?></p>
                        <p class="font-medium text-white"><?= esc($user->account_number) ?></p>
                        <p class="font-medium text-white">a/n <?= esc($user->account_name) ?></p>
                        <a href="<?= route_to('dashboard.settings') ?>" class="text-xs text-indigo-400 hover:text-indigo-300 mt-1 inline-block">Ubah Rekening?</a>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-br from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-300">
                        Ajukan Penarikan
                    </button>
                <?= form_close() ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom Kanan: Riwayat Withdraw -->
    <div class="lg:col-span-2">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
             <h2 class="text-xl font-semibold text-white mb-0 p-6 border-b border-gray-700/50">Riwayat Penarikan</h2>
             <div class="overflow-x-auto">
                 <table class="min-w-full divide-y divide-gray-700/50">
                    <thead class="bg-gray-800/70 sticky top-0">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Jumlah</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Info Bank</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        <?php if (empty($withdrawals)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    Belum ada riwayat penarikan dana.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            // Calculate the starting number for the current page
                            $startNumber = ($currentPage - 1) * $itemsPerPage + 1;
                            ?>
                            <?php foreach ($withdrawals as $index => $wd): ?>
                                <tr class="hover:bg-gray-800/50 transition-colors">
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $startNumber + $index ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                        <?= \CodeIgniter\I18n\Time::parse($wd->created_at)->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white align-middle">
                                        Rp <?= number_format($wd->amount, 0, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm align-middle">
                                        <?php
                                            $statusClass = '';
                                            $statusText = ucfirst($wd->status);
                                            switch ($wd->status) {
                                                case 'pending':
                                                    $statusClass = 'bg-yellow-500/20 text-yellow-300';
                                                    break;
                                                case 'approved': // Ganti 'completed' jadi 'approved'
                                                    $statusClass = 'bg-green-500/20 text-green-300';
                                                    $statusText = 'Disetujui'; // Ubah teks
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-red-500/20 text-red-300';
                                                     $statusText = 'Ditolak';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-gray-500/20 text-gray-300';
                                            }
                                        ?>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-400 hidden md:table-cell align-middle">
                                         <?= esc($wd->bank_name) ?> <br> <?= esc($wd->account_number) ?> <br> a/n <?= esc($wd->account_name) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
             </div>
             <?php if (isset($pager) && $pager->getPageCount('withdraw') > 1): ?>
                <div class="p-4 border-t border-gray-700/50">
                    <?= $pager->links('withdraw', 'default_full') // Use 'withdraw' group ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
