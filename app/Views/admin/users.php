<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>

<!-- Search Form -->
<div class="mb-6">
    <?= form_open(route_to('admin.users'), ['method' => 'get', 'class' => 'flex items-center gap-2']) ?>
        <?= csrf_field() ?> <!-- Tambahkan CSRF Field untuk GET form jika filter diaktifkan untuk GET -->
        <input type="search" name="search" value="<?= esc($search ?? '', 'attr') ?>" placeholder="Cari username atau email..." class="flex-grow px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150">
            <i class="fa-solid fa-search mr-1 hidden sm:inline"></i> Cari
        </button>
         <?php if (!empty($search)): ?>
            <a href="<?= route_to('admin.users') ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition duration-150" title="Reset Pencarian">
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden sm:table-cell">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Saldo</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Bergabung</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Peran</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                             <?php if (!empty($search)): ?>
                                Pengguna dengan kata kunci "<?= esc($search) ?>" tidak ditemukan.
                            <?php else: ?>
                                Tidak ada data pengguna.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                        $pager = \Config\Services::pager();
                        $currentPage = $pager->getCurrentPage('users'); // Use 'users' group
                        $perPage = $pager->getPerPage('users');       // Use 'users' group
                        $startNumber = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($users as $index => $user): ?>
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-middle"><?= $startNumber + $index ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 align-middle">
                                <a href="<?= route_to('profile.public', $user->username) ?>" target="_blank" class="hover:underline" title="Lihat Halaman Publik">
                                    <?= esc($user->username) ?> <i class="fa-solid fa-external-link-alt text-xs text-gray-500 ml-1"></i>
                                </a>
                                <div class="text-xs text-gray-500 sm:hidden"><?= esc($user->email) ?></div> <!-- Show email on small screen below username -->
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden sm:table-cell align-middle"><?= esc($user->email) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <?php if ($user->is_premium): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-500/20 text-yellow-300">Premium</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-600 text-gray-300">Biasa</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 hidden md:table-cell align-middle">
                                Rp <?= number_format($user->balance ?? 0, 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 hidden md:table-cell align-middle">
                                <?= \CodeIgniter\I18n\Time::parse($user->created_at)->toLocalizedString('dd MMM yyyy') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap align-middle">
                                <?php if ($user->is_admin): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/20 text-red-300">Admin</span>
                                <?php else: ?>
                                     <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-500/20 text-blue-300">User</span>
                                <?php endif; ?>
                            </td>
                             <!-- Aksi -->
                             <td class="px-6 py-4 whitespace-nowrap text-xs font-medium space-x-2 align-middle">
                                <!-- Toggle Premium -->
                                <?= form_open(route_to('admin.users.toggle_premium', $user->id), ['class' => 'inline']) ?>
                                    <?= csrf_field() ?>
                                    <button type="submit" class="px-2 py-1 rounded <?= $user->is_premium ? 'bg-yellow-500/10 text-yellow-400 hover:bg-yellow-500/20 hover:text-yellow-300' : 'bg-gray-600/50 text-gray-300 hover:bg-gray-500/50 hover:text-gray-100' ?>" onclick="return confirm('Ubah status premium untuk <?= esc($user->username) ?>?')">
                                        <i class="fa-solid fa-star"></i> <?= $user->is_premium ? 'Cabut' : 'Beri' ?> Premium
                                    </button>
                                <?= form_close() ?>

                                <!-- Toggle Admin -->
                                <?php if ($user->id !== session()->get('user_id')): // Jangan tampilkan tombol untuk diri sendiri ?>
                                    <?= form_open(route_to('admin.users.toggle_admin', $user->id), ['class' => 'inline']) ?>
                                        <?= csrf_field() ?>
                                        <button type="submit" class="px-2 py-1 rounded <?= $user->is_admin ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300' : 'bg-gray-600/50 text-gray-300 hover:bg-gray-500/50 hover:text-gray-100' ?>" onclick="return confirm('Ubah status admin untuk <?= esc($user->username) ?>?')">
                                            <i class="fa-solid fa-shield-halved"></i> <?= $user->is_admin ? 'Cabut' : 'Jadikan' ?> Admin
                                        </button>
                                    <?= form_close() ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
     <!-- Pagination Links -->
     <?php if (isset($pager) && $pager->getPageCount('users') > 1): ?>
        <div class="px-6 py-4 border-t border-gray-700 bg-gray-800/50">
             <?= $pager->links('users', 'default_full') // Use 'users' group and template ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

