<?= $this->extend('dashboard/layout') ?>

<!-- Muat script Midtrans Snap -->
<?= $this->section('head') ?>
    <?php $midtransConfig = new \Config\Midtrans(); // Buat instance config kustom ?>
    <script type="text/javascript"
            src="<?= $midtransConfig->isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' ?>"
            data-client-key="<?= $midtransConfig->clientKey ?>"></script>
<?= $this->endSection() ?>


<?= $this->section('content') ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-8">
            <div class="text-center">
                <i class="fa-solid fa-star text-5xl text-yellow-400 mb-4"></i>
                <h2 class="text-3xl font-bold text-white mb-2">Upgrade ke Premium</h2>
                <p class="text-lg text-gray-400 mb-6">Buka semua fitur canggih untuk bisnis Anda.</p>
            </div>

            <div class="bg-gray-800/50 p-6 rounded-lg border border-gray-700">
                <h3 class="text-xl font-semibold text-white mb-4">Apa yang Anda Dapatkan?</h3>
                <ul class="space-y-3 text-gray-300">
                    <li class="flex items-center space-x-3">
                        <i class="fa-solid fa-check-circle text-green-400 w-5"></i>
                        <span>Fitur Order Otomatis (via Midtrans)</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fa-solid fa-check-circle text-green-400 w-5"></i>
                        <span>Manajemen Stok Produk Digital</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fa-solid fa-check-circle text-green-400 w-5"></i>
                        <span>Prioritas Dukungan Teknis</span>
                    </li>
                    <li class="flex items-center space-x-3">
                        <i class="fa-solid fa-check-circle text-green-400 w-5"></i>
                        <span>Tombol "Premium" di Halaman Profil</span>
                    </li>
                </ul>
            </div>

            <div class="text-center mt-8">
                <p class="text-sm text-gray-500">Hanya dengan sekali bayar:</p>
                <p class="text-4xl font-extrabold text-white my-2">Rp 100.000</p>
                <p class="text-gray-400">(Lifetime Access)</p>

                <button id="pay-button" class="w-full max-w-sm mt-6 bg-gradient-to-br from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-gray-900 font-bold py-3 px-6 rounded-lg shadow-lg transition duration-300 text-lg">
                    Upgrade Sekarang
                </button>
                <div id="payment-error" class="text-red-400 text-sm mt-4 hidden"></div>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('pay-button').onclick = function(e) {
        e.preventDefault();
        
        const payButton = this;
        const errorContainer = document.getElementById('payment-error');
        
        payButton.disabled = true;
        payButton.textContent = 'Memproses...';
        errorContainer.classList.add('hidden');
        errorContainer.textContent = '';

        // 1. Panggil server Anda untuk mendapatkan Snap Token
        fetch('<?= url_to('PaymentController::payForPremium') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' // Penting untuk CI4
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.token) {
                // 2. Tampilkan popup pembayaran Midtrans
                window.snap.pay(data.token, {
                    onSuccess: function(result) {
                        /* Pembayaran sukses */
                        console.log('Success:', result);
                        window.location.href = "<?= url_to('DashboardController::index') ?>?payment=success";
                    },
                    onPending: function(result) {
                        /* Pembayaran pending */
                        console.log('Pending:', result);
                        window.location.href = "<?= url_to('DashboardController::index') ?>?payment=pending";
                    },
                    onError: function(result) {
                        /* Pembayaran error */
                        console.error('Error:', result);
                        errorContainer.textContent = 'Pembayaran gagal atau dibatalkan.';
                        errorContainer.classList.remove('hidden');
                        payButton.disabled = false;
                        payButton.textContent = 'Upgrade Sekarang';
                    },
                    onClose: function() {
                        /* Popup ditutup tanpa transaksi */
                        console.log('Popup closed');
                        payButton.disabled = false;
                        payButton.textContent = 'Upgrade Sekarang';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            errorContainer.textContent = 'Gagal memproses pembayaran: ' + error.message;
            errorContainer.classList.remove('hidden');
            payButton.disabled = false;
            payButton.textContent = 'Upgrade Sekarang';
        });
    };
</script>

<?= $this->endSection() ?>

