<?= $this->extend('auth/layout_auth') // Gunakan layout auth jika ada, atau sesuaikan ?>

<?= $this->section('content') ?>
<div class="w-full max-w-sm sm:max-w-md bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-2xl p-6 sm:p-8">
     <div class="flex justify-center items-center space-x-3 mb-6">
       <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
           <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M7 19V5l10 14V5"></path></svg>
       </div>
       <span class="text-2xl font-bold text-white">Repo.ID</span>
   </div>
    <h1 class="text-xl font-bold text-center text-white mb-6">Reset Password Anda</h1>

    <!-- Tampilkan Notifikasi Error -->
    <?php if (session()->get('error')): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
            <?= session()->get('error') ?>
        </div>
    <?php endif; ?>
     <?php if (session()->get('errors')): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
            <ul class="list-disc pl-5">
            <?php foreach (session()->get('errors') as $error) : ?>
                <li><?= esc($error) ?></li>
            <?php endforeach ?>
            </ul>
        </div>
    <?php endif; ?>

    <?= form_open(route_to('reset.password.process', $token)) // Gunakan token di URL action ?>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>"> <!-- Bisa juga kirim token via hidden input -->

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-300 mb-2">Password Baru</label>
            <input type="password" name="password" id="password" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
            <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter.</p>
        </div>
        <div class="mb-6">
            <label for="password_confirm" class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirm" id="password_confirm" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
        </div>
        <div class="mb-4">
            <button type="submit" class="w-full bg-gradient-to-br from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-300">Reset Password</button>
        </div>
    <?= form_close() ?>
</div>
<?= $this->endSection() ?>
