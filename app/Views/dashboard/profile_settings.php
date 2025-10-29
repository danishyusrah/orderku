<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="max-w-xl mx-auto space-y-8">

    

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
         <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Informasi Profil & Kontak</h2>
         

<?= form_open_multipart(route_to('dashboard.profile.update')) // Use form_open_multipart for file uploads ?>
            <?= csrf_field() ?>
            <div class="space-y-4">
                 

<div class="flex items-center space-x-4">
                    <!-- Display Logo -->
                    <?php
                    $logoUrl = $user->logo_filename
                        ? base_url('uploads/logos/' . $user->logo_filename)
                        // Fallback to UI Avatars using username as default
                        : 'https://ui-avatars.com/api/?name=' . urlencode($user->username) . '&background=4f46e5&color=ffffff&size=64&bold=true';
                    ?>
                    <img src="<?= esc($logoUrl, 'attr') ?>"
                         alt="Logo Toko"
                         id="logoPreview"
                         class="w-16 h-16 rounded-full border-2 border-gray-600 object-cover bg-gray-700">
                    <div>
                         <label for="logo" class="block text-sm font-medium text-gray-300 mb-1">Logo Toko</label>
                         <input type="file" name="logo" id="logo" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" class="block w-full text-xs text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-500/10 file:text-indigo-300 hover:file:bg-indigo-500/20 file:cursor-pointer focus:outline-none" onchange="previewLogo(event)">
                         <p class="text-xs text-gray-500 mt-1">Ganti Logo (Opsional, Max 1MB).</p>
                         <?php if (session('errorsProfile.logo')): ?>
                            <p class="text-xs text-red-400 mt-1"><?= session('errorsProfile.logo') ?></p>
                         <?php endif ?>
                    </div>
                </div>

                

<div>
                    <label for="store_name" class="block text-sm font-medium text-gray-300 mb-2">Nama Toko / Tampilan</label>
                    <input type="text" name="store_name" id="store_name" value="<?= old('store_name', $user->store_name ?? $user->username) ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsProfile.store_name') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Nama Toko Anda" required>
                    <p class="text-xs text-gray-500 mt-1">Nama ini akan ditampilkan di halaman publik Anda.</p>
                     <?php if (session('errorsProfile.store_name')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsProfile.store_name') ?></p>
                    <?php endif ?>
                </div>

                

<div>
                    <label for="profile_subtitle" class="block text-sm font-medium text-gray-300 mb-2">Subtitle Profil (Opsional)</label>
                    <input type="text" name="profile_subtitle" id="profile_subtitle" value="<?= old('profile_subtitle', $user->profile_subtitle ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsProfile.profile_subtitle') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Cth: Penjual Terpercaya ✨" maxlength="255">
                     <p class="text-xs text-gray-500 mt-1">Teks singkat di bawah nama toko (mendukung emoji).</p>
                     <?php if (session('errorsProfile.profile_subtitle')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsProfile.profile_subtitle') ?></p>
                    <?php endif ?>
                </div>

                

<div>
                    <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                    <input type="text" name="username" id="username" value="<?= old('username', $user->username ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsProfile.username') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Username unik Anda" required pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan underscore (_)">
                    <p class="text-xs text-gray-500 mt-1">Ini akan menjadi URL publik Anda (cth: domain.com/<?= esc(old('username', $user->username ?? 'UsernameAnda')) ?>). Hanya huruf, angka, underscore.</p>
                     <?php if (session('errorsProfile.username')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsProfile.username') ?></p>
                    <?php endif ?>
                </div>

                

<div>
                    <label for="whatsapp_link" class="block text-sm font-medium text-gray-300 mb-2">Link WhatsApp (Opsional)</label>
                    <input type="url" name="whatsapp_link" id="whatsapp_link" value="<?= old('whatsapp_link', $user->whatsapp_link ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsProfile.whatsapp_link') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="https://wa.me/62...">
                    <p class="text-xs text-gray-500 mt-1">Akan ditampilkan di halaman publik Anda.</p>
                     <?php if (session('errorsProfile.whatsapp_link')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsProfile.whatsapp_link') ?></p>
                    <?php endif ?>
                </div>

            </div>

            

<div class="flex justify-end mt-6 pt-6 border-t border-gray-700/50">
                <button type="submit" class="px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan Info Profil</button>
            </div>
             

<?php if (session('successProfile')): ?>
                <p class="text-xs text-green-400 mt-2 text-right"><?= session('successProfile') ?></p>
            <?php elseif (session('errorProfile')): ?>
                <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorProfile') ?></p>
            <?php endif; ?>
         <?= form_close() ?>
    </div>

    

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
         <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Informasi Penarikan Dana</h2>
         <p class="text-sm text-gray-400 -mt-4 mb-4">Pastikan data ini benar untuk kelancaran proses penarikan saldo Anda.</p>
         

<?= form_open(route_to('dashboard.bank.update')) ?>
            <?= csrf_field() ?>
            <div class="space-y-4">
                 

<div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-300 mb-2">Nama Bank</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= old('bank_name', $user->bank_name ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsBank.bank_name') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Cth: Bank Central Asia (BCA)">
                     <?php if (session('errorsBank.bank_name')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsBank.bank_name') ?></p>
                    <?php endif ?>
                </div>

                

<div>
                    <label for="account_number" class="block text-sm font-medium text-gray-300 mb-2">Nomor Rekening</label>
                    <input type="text" inputmode="numeric" name="account_number" id="account_number" value="<?= old('account_number', $user->account_number ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsBank.account_number') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Hanya angka">
                     <?php if (session('errorsBank.account_number')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsBank.account_number') ?></p>
                    <?php endif ?>
                </div>

                 

<div>
                    <label for="account_name" class="block text-sm font-medium text-gray-300 mb-2">Nama Pemilik Rekening</label>
                    <input type="text" name="account_name" id="account_name" value="<?= old('account_name', $user->account_name ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsBank.account_name') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Sesuai nama di buku tabungan">
                     <p class="text-xs text-gray-500 mt-1">Pastikan nama sesuai untuk kelancaran penarikan dana.</p>
                     <?php if (session('errorsBank.account_name')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsBank.account_name') ?></p>
                    <?php endif ?>
                </div>
            </div>

            

<div class="flex justify-end mt-6 pt-6 border-t border-gray-700/50">
                <button type="submit" class="px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan Info Bank</button>
            </div>
             

<?php if (session('successBank')): ?>
                <p class="text-xs text-green-400 mt-2 text-right"><?= session('successBank') ?></p>
            <?php elseif (session('errorBank')): ?>
                <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorBank') ?></p>
            <?php endif; ?>
         <?= form_close() ?>
    </div>

    

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
        <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Pengaturan API Key Midtrans (Opsional)</h2>
        <p class="text-sm text-gray-400 -mt-4 mb-4">Kosongkan jika ingin menggunakan API Key default sistem.</p>

        

<?= form_open(route_to('dashboard.midtrans.update')) ?>
            <?= csrf_field() ?>
            <div class="space-y-4">
                

<div>
                    <label for="midtrans_server_key" class="block text-sm font-medium text-gray-300 mb-2">Midtrans Server Key</label>
                    <input type="password" name="midtrans_server_key" id="midtrans_server_key" value="<?= old('midtrans_server_key', $user->midtrans_server_key ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsMidtrans.midtrans_server_key') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="SB-Mid-server-xxxx atau Mid-server-xxxx">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong untuk menggunakan kunci default.</p>
                    <?php if (session('errorsMidtrans.midtrans_server_key')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsMidtrans.midtrans_server_key') ?></p>
                    <?php endif ?>
                </div>
                

<div>
                    <label for="midtrans_client_key" class="block text-sm font-medium text-gray-300 mb-2">Midtrans Client Key</label>
                    <input type="text" name="midtrans_client_key" id="midtrans_client_key" value="<?= old('midtrans_client_key', $user->midtrans_client_key ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsMidtrans.midtrans_client_key') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="SB-Mid-client-xxxx atau Mid-client-xxxx">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong untuk menggunakan kunci default.</p>
                     <?php if (session('errorsMidtrans.midtrans_client_key')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsMidtrans.midtrans_client_key') ?></p>
                    <?php endif ?>
                </div>
                 

            </div>
            

<div class="flex justify-end mt-6 pt-6 border-t border-gray-700/50">
                <button type="submit" class="px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan API Key</button>
            </div>
             

<?php if (session('successMidtrans')): ?>
                <p class="text-xs text-green-400 mt-2 text-right"><?= session('successMidtrans') ?></p>
            <?php elseif (session('errorMidtrans')): ?>
                <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorMidtrans') ?></p>
            <?php endif; ?>
        <?= form_close() ?>
    </div>

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
  <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Pengaturan API Key Tripay (Opsional)</h2>
  <p class="text-sm text-gray-400 -mt-4 mb-4">Kosongkan jika ingin menggunakan API Key default sistem.</p>

  <?= form_open(route_to('dashboard.tripay.update')) ?>
    <?= csrf_field() ?>
    <div class="space-y-4">
      <div>
        <label for="tripay_api_key" class="block text-sm font-medium text-gray-300 mb-2">Tripay API Key</label>
        <input type="password" name="tripay_api_key" id="tripay_api_key"
               value="<?= esc($user->tripay_api_key ?? '', 'attr') ?>"
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white">
      </div>
      <div>
        <label for="tripay_private_key" class="block text-sm font-medium text-gray-300 mb-2">Tripay Private Key</label>
        <input type="password" name="tripay_private_key" id="tripay_private_key"
               value="<?= esc($user->tripay_private_key ?? '', 'attr') ?>"
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white">
      </div>
      <div>
        <label for="tripay_merchant_code" class="block text-sm font-medium text-gray-300 mb-2">Tripay Merchant Code</label>
        <input type="text" name="tripay_merchant_code" id="tripay_merchant_code"
               value="<?= esc($user->tripay_merchant_code ?? '', 'attr') ?>"
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white">
      </div>
      <div class="text-right">
        <button type="submit" class="px-4 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan Tripay</button>
      </div>
    </div>
  <?= form_close() ?>

  <?php if (session('successTripay')): ?>
    <p class="text-xs text-green-400 mt-2 text-right"><?= session('successTripay') ?></p>
  <?php elseif (session('errorsTripay')): ?>
    <p class="text-xs text-red-400 mt-2 text-right">
      <?= is_array(session('errorsTripay')) ? implode(', ', session('errorsTripay')) : session('errorsTripay') ?>
    </p>
  <?php elseif (session('errorTripay')): ?>
    <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorTripay') ?></p>
  <?php endif; ?>
</div>


    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
    <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Preferensi Gateway Pembayaran</h2>
    <p class="text-sm text-gray-400 -mt-4 mb-4">Pilih gateway mana yang ingin Anda prioritaskan untuk transaksi otomatis.</p>


    <?= form_open(route_to('dashboard.gateway.update')) ?>
        <?= csrf_field() ?>
        <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            <label class="text-sm font-medium text-gray-300 w-full sm:w-auto mb-2 sm:mb-0">Gateway Aktif:</label>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
            <label class="inline-flex items-center gap-2 text-gray-300 cursor-pointer">
                <input type="radio" name="gateway_active" value="system" <?= ($user->gateway_active ?? 'system') === 'system' ? 'checked' : '' ?> class="form-radio text-indigo-500 bg-gray-700 border-gray-600 focus:ring-indigo-500">
                <span>Default Sistem</span>
            </label>
            <label class="inline-flex items-center gap-2 text-gray-300 cursor-pointer">
                <input type="radio" name="gateway_active" value="midtrans" <?= ($user->gateway_active ?? '') === 'midtrans' ? 'checked' : '' ?> class="form-radio text-indigo-500 bg-gray-700 border-gray-600 focus:ring-indigo-500">
                <span>Midtrans</span>
            </label>
            <label class="inline-flex items-center gap-2 text-gray-300 cursor-pointer">
                <input type="radio" name="gateway_active" value="tripay" <?= ($user->gateway_active ?? '') === 'tripay' ? 'checked' : '' ?> class="form-radio text-indigo-500 bg-gray-700 border-gray-600 focus:ring-indigo-500">
                <span>Tripay</span>
            </label>
            <label class="inline-flex items-center gap-2 text-gray-300 cursor-pointer">
                 <input type="radio" name="gateway_active" value="orderkuota" <?= ($user->gateway_active ?? '') === 'orderkuota' ? 'checked' : '' ?> class="form-radio text-indigo-500 bg-gray-700 border-gray-600 focus:ring-indigo-500">
                 <span>Orderkuota</span>
            </label>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-1">Jika memilih gateway spesifik, pastikan API Key sudah terisi (jika diperlukan oleh gateway tersebut). Jika memilih "Default Sistem" atau API Key kosong, sistem akan menggunakan gateway default yang diatur di <code class="text-xs bg-gray-700 px-1 rounded">.env</code>.</p>
        <div class="flex justify-end mt-6 pt-6 border-t border-gray-700/50">
            <button type="submit" class="px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan Preferensi</button>
        </div>
        </div>
    <?= form_close() ?>

    <?php if (session('successGateway')): ?>
        <p class="text-xs text-green-400 mt-2 text-right"><?= session('successGateway') ?></p>
    <?php elseif (session('errorGateway')): ?>
        <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorGateway') ?></p>
    <?php endif; ?>
    </div>

    

<div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 sm:p-8">
        <h2 class="text-xl font-semibold text-white mb-6 border-b border-gray-700/50 pb-4">Ubah Password</h2>
        

<?= form_open(route_to('dashboard.password.update')) ?>
            <?= csrf_field() ?>
            <div class="space-y-4">
                 

<div>
                    <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" id="current_password" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsPassword.current_password') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
                     <?php if (session('errorsPassword.current_password')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsPassword.current_password') ?></p>
                    <?php endif ?>
                </div>
                 

<div>
                    <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">Password Baru</label>
                    <input type="password" name="new_password" id="new_password" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsPassword.new_password') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
                    <p class="text-xs text-gray-500 mt-1">Minimal 8 karakter, kombinasi huruf besar, huruf kecil, dan angka.</p>
                     <?php if (session('errorsPassword.new_password')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsPassword.new_password') ?></p>
                    <?php endif ?>
                </div>
                 

<div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 bg-gray-800 border <?= session('errorsPassword.confirm_password') ? 'border-red-500' : 'border-gray-700' ?> rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" required>
                     <?php if (session('errorsPassword.confirm_password')): ?>
                        <p class="text-xs text-red-400 mt-1"><?= session('errorsPassword.confirm_password') ?></p>
                    <?php endif ?>
                </div>
            </div>

            

<div class="flex justify-end mt-6 pt-6 border-t border-gray-700/50">
                <button type="submit" class="px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Ubah Password</button>
            </div>
             

<?php if (session('successPassword')): ?>
                 <p class="text-xs text-green-400 mt-2 text-right"><?= session('successPassword') ?></p>
            <?php elseif (session('errorPassword')): ?>
                 <p class="text-xs text-red-400 mt-2 text-right"><?= session('errorPassword') ?></p>
            <?php endif; ?>
        <?= form_close() ?>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Preview logo before upload
    function previewLogo(event) {
        const reader = new FileReader();
        const output = document.getElementById('logoPreview');
        reader.onload = function(){
            output.src = reader.result;
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            // Revert to default or previous logo if selection is cancelled
            output.src = '<?= esc($logoUrl, 'js') ?>'; // Use the same logic as initial display
        }
    }
</script>
<?= $this->endSection() ?>

