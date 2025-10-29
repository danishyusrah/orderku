<?= $this->extend('dashboard/layout') ?>

<?= $this->section('content') ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden p-6 md:p-8">

        <?= form_open_multipart($action) ?>
            <div class="space-y-6">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-300 mb-2">Nama Produk</label>
                    <input type="text" name="product_name" id="product_name" value="<?= old('product_name', $product->product_name ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Cth: Ebook Premium" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Deskripsi (Opsional)</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Deskripsi singkat produk..."><?= old('description', $product->description ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Tipe Order</label>
                    <div class="grid grid-cols-1 <?= $user->is_premium ? 'md:grid-cols-2' : '' ?> gap-4">
                        <label class="flex p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-700/50 transition-all has-[:checked]:ring-2 has-[:checked]:ring-indigo-500 has-[:checked]:border-indigo-500/50">
                            <input type="radio" name="order_type" value="manual" class="text-indigo-500 focus:ring-indigo-500 mt-0.5 shrink-0" <?= old('order_type', $product->order_type ?? 'manual') == 'manual' ? 'checked' : '' ?>>
                            <span class="ml-4 flex flex-col">
                                <span class="font-medium text-white">Manual via Whatsapp</span>
                                <span class="text-sm text-gray-400">Order akan diarahkan ke link WA Anda.</span>
                            </span>
                        </label>

                        <?php if ($user->is_premium): ?>
                            <label class="flex p-4 bg-gray-800 border border-gray-700 rounded-lg cursor-pointer hover:bg-gray-700/50 transition-all has-[:checked]:ring-2 has-[:checked]:ring-indigo-500 has-[:checked]:border-indigo-500/50">
                                <input type="radio" name="order_type" value="auto" class="text-indigo-500 focus:ring-indigo-500 mt-0.5 shrink-0" <?= old('order_type', $product->order_type ?? '') == 'auto' ? 'checked' : '' ?>>
                                <span class="ml-4 flex flex-col">
                                    <span class="font-medium text-white flex items-center">
                                        Otomatis (Stok/Varian)
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-yellow-500/20 text-yellow-300">Premium</span>
                                    </span>
                                    <span class="text-sm text-gray-400">Order via Midtrans (stok otomatis atau varian).</span>
                                </span>
                            </label>
                        <?php else: ?>
                            <div class="flex p-4 bg-gray-800/50 border border-gray-700/50 rounded-lg opacity-70">
                                <input type="radio" class="text-gray-600 mt-0.5 shrink-0" disabled>
                                <span class="ml-4 flex flex-col">
                                    <span class="font-medium text-gray-400 flex items-center">
                                        Otomatis (Stok/Varian)
                                        <a href="<?= route_to('dashboard.upgrade') ?>" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-yellow-500/20 text-yellow-300 hover:bg-yellow-500/30">Upgrade?</a>
                                    </span>
                                    <span class="text-sm text-gray-500">Hanya tersedia untuk akun Premium.</span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Opsi Varian (Hanya muncul jika tipe Otomatis dipilih) -->
                <div id="variant-option-section" class="hidden <?= $user->is_premium ? '' : 'hidden-force' ?>">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="has_variants" id="has_variants" value="1" class="h-5 w-5 rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-gray-900" <?= old('has_variants', $product->has_variants ?? 0) == 1 ? 'checked' : '' ?>>
                        <span class="ml-3 text-sm font-medium text-gray-300">Aktifkan Varian Produk</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-8">Jika dicentang, Anda dapat menambahkan varian seperti ukuran, warna, durasi dengan harga berbeda. Stok diatur otomatis berdasarkan data yang ditambahkan.</p>
                </div>

                <!-- Field Harga Utama (Disembunyikan jika varian aktif) -->
                <div id="price-field">
                    <label for="price" class="block text-sm font-medium text-gray-300 mb-2">Harga Utama (Rp)</label>
                    <input type="number" name="price" id="price" value="<?= old('price', $product->price ?? '0') ?>" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="Cth: 50000" min="0">
                    <p id="price-helper" class="text-xs text-gray-500 mt-1">Isi harga jika produk tidak memiliki varian. Wajib diisi (> 0) jika tipe Otomatis tanpa varian.</p>
                </div>

                <!-- Field Target URL (Hanya untuk tipe Manual) -->
                <div id="url-field" class="hidden">
                    <label for="target_url" id="url-label" class="block text-sm font-medium text-gray-300 mb-2">Target URL (Link Whatsapp)</label>
                    <input type="url" name="target_url" id="target_url" value="<?= old('target_url', $product->target_url ?? '') ?>" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" placeholder="https://wa.me/62...">
                    <p id="url-helper" class="text-xs text-gray-500 mt-1">Link Whatsapp untuk order manual (wajib diisi).</p>
                </div>

                <!-- Bagian Input Varian Dinamis (Hanya muncul jika varian diaktifkan) -->
                <div id="variants-section" class="hidden pt-4 border-t border-gray-700/50">
                    <h3 class="text-md font-semibold text-white mb-3">Daftar Varian</h3>
                    <div id="variants-container" class="space-y-3">
                        <?php
                        $oldVariants = old('variants', []);
                        $existingVariants = $variants ?? []; // $variants dikirim dari controller saat edit
                        // Modifikasi: Tidak perlu lagi mengambil 'stock' dari old data atau existing data
                        $displayVariants = !empty($oldVariants) ? $oldVariants : ($existingVariants ?: [['name' => '', 'price' => '']]); // Tampilkan setidaknya satu jika kosong, hapus 'stock'

                        foreach ($displayVariants as $index => $variant): ?>
                        <div class="variant-item flex items-start space-x-2 p-3 bg-gray-800/50 rounded border border-gray-700">
                            <input type="hidden" name="variants[<?= $index ?>][id]" value="<?= esc($variant->id ?? '', 'attr') ?>">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-400 mb-1">Nama Varian</label>
                                <input type="text" name="variants[<?= $index ?>][name]" value="<?= esc($variant->name ?? ($variant['name'] ?? ''), 'attr') ?>" class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="Merah / XL / 1 Bulan" required>
                            </div>
                            <div class="w-2/5"> <!-- Perlebar kolom harga -->
                                <label class="block text-xs font-medium text-gray-400 mb-1">Harga (Rp)</label>
                                <input type="number" name="variants[<?= $index ?>][price]" value="<?= esc($variant->price ?? ($variant['price'] ?? ''), 'attr') ?>" class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="50000" min="1" required>
                            </div>
                            <!-- Kolom Stok Dihilangkan -->
                            <button type="button" onclick="removeVariant(this)" class="mt-5 p-1 text-red-400 hover:text-red-300 transition-colors">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-variant-btn" class="mt-3 px-3 py-1.5 text-xs rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 font-medium transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i> Tambah Varian
                    </button>
                </div>

                <div>
                    <label for="product_icon" class="block text-sm font-medium text-gray-300 mb-2">Ikon Produk</label>
                    <input type="file" name="product_icon" id="product_icon" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/10 file:text-indigo-300 hover:file:bg-indigo-500/20 file:cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, GIF, WEBP. Maks 1MB.</p>
                    <?php if (isset($product->icon_url) && $product->icon_url): ?>
                        <div class="mt-3">
                            <span class="text-xs text-gray-500">Ikon saat ini:</span>
                            <img src="<?= esc($product->icon_url, 'attr') ?>" alt="Icon Preview" class="mt-1 w-12 h-12 rounded-md object-cover border border-gray-700">
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded bg-gray-700 border-gray-600 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-gray-900" <?= old('is_active', $product->is_active ?? 1) == 1 ? 'checked' : '' ?>>
                        <span class="ml-3 text-sm font-medium text-gray-300">Aktifkan Produk</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-8">Jika dicentang, produk akan tampil di halaman publik Anda.</p>
                </div>

            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-700/50 gap-4">
                <div>
                     <!-- Tombol kelola stok dihilangkan dari sini, pindah ke halaman index -->
                </div>
                <div class="flex space-x-4 w-full sm:w-auto">
                    <a href="<?= route_to('dashboard') ?>" class="flex-1 sm:flex-none text-center px-6 py-2 rounded-lg bg-gray-700/50 hover:bg-gray-700 text-gray-300 font-medium transition-colors text-sm">Batal</a>
                    <button type="submit" class="flex-1 sm:flex-none px-6 py-2 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold shadow-lg transition duration-300 text-sm">Simpan Produk</button>
                </div>
            </div>

        <?= form_close() ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeManualRadio = document.querySelector('input[name="order_type"][value="manual"]');
        const typeAutoRadio = document.querySelector('input[name="order_type"][value="auto"]');
        const urlField = document.getElementById('url-field');
        const urlInput = document.getElementById('target_url');
        const priceField = document.getElementById('price-field');
        const priceInput = document.getElementById('price');
        const priceHelper = document.getElementById('price-helper');
        const urlHelper = document.getElementById('url-helper');
        const variantOptionSection = document.getElementById('variant-option-section');
        const hasVariantsCheckbox = document.getElementById('has_variants');
        const variantsSection = document.getElementById('variants-section');
        const addVariantBtn = document.getElementById('add-variant-btn');
        const variantsContainer = document.getElementById('variants-container');
        const isPremium = <?= $user->is_premium ? 'true' : 'false' ?>;
        // Hitung index awal dari jumlah item varian yang sudah ada di DOM
        let variantIndex = variantsContainer.querySelectorAll('.variant-item').length;


        function toggleFieldsBasedOnTypeAndVariant() {
            const isAuto = typeAutoRadio && typeAutoRadio.checked && isPremium;
            const useVariants = hasVariantsCheckbox && hasVariantsCheckbox.checked && isAuto;

            // Tampilkan/sembunyikan opsi varian
            if (isAuto) {
                variantOptionSection.classList.remove('hidden');
            } else {
                variantOptionSection.classList.add('hidden');
                if(hasVariantsCheckbox) hasVariantsCheckbox.checked = false; // Nonaktifkan varian jika tipe manual
                 variantsSection.classList.add('hidden'); // Sembunyikan section varian
            }

            // Tampilkan/sembunyikan field URL (hanya untuk manual)
            if (!isAuto) {
                urlField.classList.remove('hidden');
                if(urlInput) urlInput.setAttribute('required', 'required');
                if(urlHelper) urlHelper.textContent = 'Link Whatsapp untuk order manual (wajib diisi untuk tipe "Manual").';
            } else {
                urlField.classList.add('hidden');
                if(urlInput) urlInput.removeAttribute('required');
                if(urlHelper) urlHelper.textContent = '';
            }

            // Atur field harga utama
            if (useVariants) {
                if(priceField) priceField.classList.add('hidden'); // Sembunyikan harga utama
                if(priceInput) priceInput.removeAttribute('required');
                if(priceHelper) priceHelper.textContent = '';
                if(variantsSection) variantsSection.classList.remove('hidden'); // Tampilkan section varian
                // Pastikan input varian required
                variantsContainer.querySelectorAll('input[name$="[name]"]').forEach(input => input.setAttribute('required', 'required'));
                variantsContainer.querySelectorAll('input[name$="[price]"]').forEach(input => input.setAttribute('required', 'required'));
                // Tidak ada lagi input stok
            } else {
                if(priceField) priceField.classList.remove('hidden'); // Tampilkan harga utama
                if(variantsSection) variantsSection.classList.add('hidden'); // Sembunyikan section varian
                 // Hapus required dari input varian (jika ada)
                 variantsContainer.querySelectorAll('input[name$="[name]"], input[name$="[price]"]').forEach(input => input.removeAttribute('required'));

                if (isAuto) {
                    if(priceInput) priceInput.setAttribute('required', 'required'); // Harga utama wajib jika auto tanpa varian
                    if(priceHelper) priceHelper.textContent = 'Isi harga jika produk tidak memiliki varian. Wajib diisi (> 0) jika tipe Otomatis tanpa varian.';
                } else {
                    if(priceInput) priceInput.removeAttribute('required'); // Harga utama tidak wajib jika manual
                    if(priceHelper) priceHelper.textContent = 'Isi harga produk (opsional untuk tipe "Manual", akan ditampilkan jika diisi).';
                }
            }

            // Fallback jika user non-premium memilih auto
            if (typeAutoRadio && typeAutoRadio.checked && !isPremium) {
                if(typeManualRadio) typeManualRadio.checked = true;
                toggleFieldsBasedOnTypeAndVariant(); // Re-run
            }
        }

        function addVariant() {
            const newVariantItem = document.createElement('div');
            newVariantItem.className = 'variant-item flex items-start space-x-2 p-3 bg-gray-800/50 rounded border border-gray-700';
            // Hilangkan bagian input stok dari innerHTML
            newVariantItem.innerHTML = `
                <input type="hidden" name="variants[${variantIndex}][id]" value="">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-400 mb-1">Nama Varian</label>
                    <input type="text" name="variants[${variantIndex}][name]" class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="Merah / XL / 1 Bulan" required>
                </div>
                <div class="w-2/5"> <!-- Sesuaikan lebar jika perlu -->
                    <label class="block text-xs font-medium text-gray-400 mb-1">Harga (Rp)</label>
                    <input type="number" name="variants[${variantIndex}][price]" class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="50000" min="1" required>
                </div>
                <button type="button" onclick="removeVariant(this)" class="mt-5 p-1 text-red-400 hover:text-red-300 transition-colors">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            `;
            variantsContainer.appendChild(newVariantItem);
            variantIndex++;
        }

        window.removeVariant = function(button) {
             const variantItem = button.closest('.variant-item');
             // Tandai untuk dihapus jika punya ID (sudah ada di DB)
             const idInput = variantItem.querySelector('input[name$="[id]"]');
             const nameInput = variantItem.querySelector('input[name$="[name]"]'); // Ambil input nama

            if (idInput && idInput.value) {
                 // Cek apakah ini satu-satunya varian yang terlihat
                 let visibleVariants = 0;
                 variantsContainer.querySelectorAll('.variant-item').forEach(item => {
                     if (item.style.display !== 'none') {
                         visibleVariants++;
                     }
                 });

                 // Jika hanya satu yang tersisa, jangan sembunyikan tapi beri peringatan
                 if (visibleVariants <= 1 && hasVariantsCheckbox && hasVariantsCheckbox.checked) {
                     alert('Minimal harus ada satu varian jika opsi varian diaktifkan.');
                     return; // Hentikan proses penghapusan
                 }


                 // Buat input hidden untuk menandai penghapusan di backend
                 const deleteMarkerInput = document.createElement('input');
                 deleteMarkerInput.type = 'hidden';
                 // Gunakan nama yang konsisten, misal 'deleted_variants[]'
                 deleteMarkerInput.name = `deleted_variants[]`;
                 deleteMarkerInput.value = idInput.value;
                 // Tambahkan ke form, BUKAN ke item yang disembunyikan
                 checkoutForm.appendChild(deleteMarkerInput); // Asumsi form punya id="checkoutForm" atau gunakan selector lain

                 variantItem.style.display = 'none'; // Sembunyikan item
                 // Hapus required dari input yang disembunyikan
                 variantItem.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => input.removeAttribute('required'));
            } else {
                 // Jika item baru (belum disimpan), langsung hapus dari DOM
                 // Tapi cek dulu apakah ini item terakhir
                  if (variantsContainer.querySelectorAll('.variant-item').length <= 1 && hasVariantsCheckbox && hasVariantsCheckbox.checked) {
                      alert('Minimal harus ada satu varian jika opsi varian diaktifkan.');
                      return; // Hentikan penghapusan
                  }
                 variantItem.remove(); // Hapus langsung jika belum ada di DB
            }
        }


        // Initial setup
        toggleFieldsBasedOnTypeAndVariant();

        // Event listeners
        if (typeManualRadio) typeManualRadio.addEventListener('change', toggleFieldsBasedOnTypeAndVariant);
        if (typeAutoRadio) typeAutoRadio.addEventListener('change', toggleFieldsBasedOnTypeAndVariant);
        if (hasVariantsCheckbox) hasVariantsCheckbox.addEventListener('change', toggleFieldsBasedOnTypeAndVariant);
        if (addVariantBtn) addVariantBtn.addEventListener('click', addVariant);
    });
</script>

<?= $this->endSection() ?>
