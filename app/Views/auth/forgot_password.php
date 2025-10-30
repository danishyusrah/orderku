<?= $this->extend('auth/layout_auth') // Gunakan layout auth jika ada, atau sesuaikan ?>

<?= $this->section('content') ?>
<div class="w-full max-w-sm sm:max-w-md bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-2xl p-6 sm:p-8">
    <div class="flex justify-center items-center space-x-3 mb-6">
       <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg">
           <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M7 19V5l10 14V5"></path></svg>
       </div>
       <span class="text-2xl font-bold text-white">Itsku ID</span>
   </div>
    <h1 class="text-xl font-bold text-center text-white mb-6">Lupa Password</h1>
    <p class="text-sm text-gray-400 text-center mb-6">Masukkan email Anda. Kami akan mengirimkan instruksi untuk mereset password Anda.</p>

    <!-- Tampilkan Notifikasi -->
    <?php if (session()->get('success')): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
            <?= session()->get('success') ?>
        </div>
    <?php endif; ?>
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


    <?= form_open(route_to('forgot.password')) ?>
        <?= csrf_field() ?>
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email Terdaftar</label>
            <input type="email" name="email" id="email" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" value="<?= old('email') ?>" required>
        </div>
        <div class="mb-4">
            <button type="submit" class="w-full bg-gradient-to-br from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-300">Kirim Instruksi Reset</button>
        </div>
    <?= form_close() ?>

     <p class="text-center text-sm text-gray-400 mt-6">
        Ingat password Anda? <a href="<?= route_to('login') ?>" class="font-medium text-indigo-400 hover:text-indigo-300">Login di sini</a>
    </p>
</div>
<?= $this->endSection() ?>
