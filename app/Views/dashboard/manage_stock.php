<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="flex flex-col lg:flex-row gap-8">

    <!-- Kolom Kiri: Form Tambah Stok -->
    <div class="lg:w-1/3">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl p-6 sticky top-8">
            <h2 class="text-xl font-semibold text-white mb-4">Tambah Stok Baru (Format JSON)</h2>
            <p class="text-sm text-gray-400 mb-4">Masukkan data item stok dalam format JSON, satu objek JSON per baris. Field <code class="text-xs bg-gray-700 px-1 rounded">email</code> dan <code class="text-xs bg-gray-700 px-1 rounded">password</code> wajib ada. Field lain opsional.</p>

             <!-- Contoh Format JSON -->
             <div class="mb-4 p-3 bg-gray-800 border border-gray-700 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <p class="text-xs font-medium text-gray-400">Contoh Format JSON:</p>
                    <button type="button" onclick="copyExampleFormat(this)" data-clipboard-target="#jsonExampleFormat" class="px-2 py-0.5 text-xs rounded bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 font-medium">
                        <i class="fa-solid fa-copy mr-1"></i> <span class="copy-text">Salin Contoh</span>
                    </button>
                </div>
                <pre id="jsonExampleFormat" class="text-xs text-gray-400 font-mono overflow-x-auto">{"email": "akun1@mail.com", "password": "pass1", "2fa": "kode Rahasia1", "gdrive_link": "https://link1.com"}
{"email": "akun2@mail.com", "password": "pass2"}</pre>
            </div>


            <?= form_open(route_to('product.stock.add', $product->id)) ?>
                 <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="stock_data" class="block text-sm font-medium text-gray-300 mb-2">Data Stok (JSON per Baris)</label>
                    <textarea name="stock_data" id="stock_data" rows="8" class="font-mono w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-gray-500 text-sm" placeholder='Paste JSON di sini, satu objek per baris...' required></textarea>
                    <p class="text-xs text-gray-500 mt-1">Pastikan format JSON valid untuk setiap baris.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300">
                        <i class="fa-solid fa-plus mr-1.5"></i> Tambah Stok
                    </button>
                </div>
            <?= form_close() ?>
        </div>
    </div>

    <!-- Kolom Kanan: Daftar Stok (Kode Tampilan Tabel sama seperti sebelumnya) -->
     <div class="lg:w-2/3">
         <h2 class="text-2xl font-semibold text-gray-300 mb-6">Daftar Stok untuk: <?= esc($product->product_name) ?></h2>
         <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden mb-6">
             <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-700/50">
                     <thead class="bg-gray-800/70">
                         <tr>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-[5%]">No</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider w-[40%]">Data Stok</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Pembeli</th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider hidden md:table-cell">Tanggal Ditambah</th>
                             <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                         </tr>
                     </thead>
                     <tbody class="divide-y divide-gray-700/50">
                         <?php if (empty($stocks)): ?>
                             <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">Belum ada stok.</td></tr>
                         <?php else: ?>
                             <?php
                                 $pager = \Config\Services::pager();
                                 $currentPage = $pager->getCurrentPage('stock');
                                 $perPage = $pager->getPerPage('stock');
                                 $startNumber = ($currentPage - 1) * $perPage + 1;
                             ?>
                             <?php foreach ($stocks as $index => $stock): ?>
                                 <?php
                                     $stockDisplay = esc($stock->stock_data); // Fallback
                                     $decodedData = json_decode($stock->stock_data);
                                     if (json_last_error() === JSON_ERROR_NONE && is_object($decodedData)) {
                                         $displayParts = [];
                                         if (isset($decodedData->email)) $displayParts[] = "Email: " . esc($decodedData->email);
                                         if (isset($decodedData->password)) $displayParts[] = "Pass: ***"; // Sembunyikan pass
                                         if (isset($decodedData->{'2fa'}) && !empty($decodedData->{'2fa'})) $displayParts[] = "2FA: Yes";
                                         if (isset($decodedData->gdrive_link) && !empty($decodedData->gdrive_link)) $displayParts[] = "Link: Yes";
                                         // Tambahkan field lain jika perlu ditampilkan di tabel ringkasan
                                         $stockDisplay = implode('<br>', $displayParts);
                                     } elseif (json_last_error() !== JSON_ERROR_NONE) {
                                          $stockDisplay = '<span class="text-red-400 text-xs italic">Format JSON tidak valid!</span><br><pre class="text-xs">' . esc($stock->stock_data) . '</pre>';
                                     }
                                 ?>
                                 <tr class="hover:bg-gray-800/50 transition-colors <?= $stock->is_used ? 'opacity-60' : '' ?>">
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 align-top"><?= $startNumber + $index ?></td>
                                     <td class="px-6 py-4 align-top text-xs"><?= $stockDisplay ?></td>
                                     <td class="px-6 py-4 whitespace-nowrap align-top">
                                         <?php if ($stock->is_used): ?>
                                             <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-500/20 text-red-300"><i class="fa-solid fa-check mr-1.5"></i> Terpakai</span>
                                         <?php else: ?>
                                             <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-500/20 text-green-300"><i class="fa-solid fa-box mr-1.5"></i> Tersedia</span>
                                         <?php endif; ?>
                                     </td>
                                     <td class="px-6 py-4 text-sm text-gray-400 break-all hidden md:table-cell align-top">
                                         <?= esc($stock->buyer_email ?? '-') ?>
                                         <?php if ($stock->updated_at && $stock->is_used): ?>
                                              <span class="block text-xs text-gray-500"><?= \CodeIgniter\I18n\Time::parse($stock->updated_at)->humanize() ?></span>
                                         <?php endif; ?>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 hidden md:table-cell align-top">
                                         <?= \CodeIgniter\I18n\Time::parse($stock->created_at)->toLocalizedString('dd MMM yyyy, HH:mm') ?>
                                     </td>
                                     <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium align-top">
                                         <?= form_open(route_to('product.stock.delete', $product->id, $stock->id), ['class' => 'inline', 'onsubmit' => 'return confirm(\'Hapus item stok ini?\')']) ?>
                                             <?= csrf_field() ?>
                                             <button type="submit" class="text-red-400 hover:text-red-300 disabled:opacity-50 disabled:cursor-not-allowed" title="Hapus Stok" <?= $stock->is_used ? 'disabled' : '' ?>>
                                                  <i class="fa-solid fa-trash-can"></i>
                                             </button>
                                         <?= form_close() ?>
                                     </td>
                                 </tr>
                             <?php endforeach; ?>
                         <?php endif; ?>
                     </tbody>
                 </table>
             </div>
         </div>
         <div class="mt-6">
             <?php if (isset($pager) && $pager->getPageCount('stock') > 1): ?>
                  <?= $pager->links('stock', 'default_full') ?>
             <?php endif; ?>
         </div>
         <div class="mt-8">
             <a href="<?= route_to('dashboard') ?>" class="text-indigo-400 hover:text-indigo-300 text-sm"><i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali</a>
         </div>
     </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function copyExampleFormat(button) {
        const targetId = button.getAttribute('data-clipboard-target');
        const textToCopy = document.querySelector(targetId).innerText;
        const copyTextSpan = button.querySelector('.copy-text');

        // Gunakan document.execCommand untuk kompatibilitas lebih luas di iframe
        const textarea = document.createElement('textarea');
        textarea.value = textToCopy;
        textarea.style.position = 'fixed'; // Hindari scroll jump
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                copyTextSpan.textContent = 'Tersalin!';
                button.classList.add('text-green-400');
                setTimeout(() => {
                    copyTextSpan.textContent = 'Salin Contoh';
                    button.classList.remove('text-green-400');
                }, 1500); // Reset text setelah 1.5 detik
            } else {
                 console.error('Gagal menyalin teks (execCommand)');
                 copyTextSpan.textContent = 'Gagal';
            }
        } catch (err) {
            console.error('Gagal menyalin teks:', err);
            copyTextSpan.textContent = 'Gagal';
        }
        document.body.removeChild(textarea);

        // Fallback atau alternatif menggunakan Clipboard API (mungkin tidak bekerja di iframe)
        /*
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                copyTextSpan.textContent = 'Tersalin!';
                button.classList.add('text-green-400');
                setTimeout(() => {
                    copyTextSpan.textContent = 'Salin Contoh';
                    button.classList.remove('text-green-400');
                }, 1500);
            }).catch(err => {
                console.error('Async: Could not copy text: ', err);
                copyTextSpan.textContent = 'Gagal';
            });
        } else {
            // Fallback for older browsers or insecure contexts
            // Maybe show an alert or different message
            console.warn('Clipboard API not available.');
            copyTextSpan.textContent = 'Error API';
        }
        */
    }
</script>
<?= $this->endSection() ?>

