<?php

namespace App\Controllers;

// Default CI
use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait; // Diperlukan untuk response AJAX
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Services;
use Throwable; // Import Throwable

// Models
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\TransactionModel;
use App\Models\ProductStockModel;

// Payment Gateway Specific
use Config\Midtrans as MidtransConfig;
use Midtrans\Config as MidtransApiConfig;
use Midtrans\Snap;
use Midtrans\Notification;
use App\Libraries\TripayClient; // Tripay
use Config\Tripay as TripayConfig; // Tripay System Config
use App\Libraries\ZeppelinClient; // Orderkuota/Zeppelin
use Config\Orderkuota as OrderkuotaConfig; // Orderkuota System Config

class PaymentController extends BaseController
{
    use ResponseTrait; // Gunakan trait untuk response API/AJAX

    // Models
    protected UserModel $userModel;
    protected ProductModel $productModel;
    protected ProductVariantModel $productVariantModel;
    protected TransactionModel $transactionModel;
    protected ProductStockModel $productStockModel;

    // Gateway Configs & Clients
    protected MidtransConfig $defaultMidtransConfig;
    protected TripayConfig $defaultTripayConfig;
    protected OrderkuotaConfig $defaultOrderkuotaConfig;
    protected ZeppelinClient $zeppelinClient; // Zeppelin Client Instance

    // Helpers
    protected $helpers = ['url', 'text', 'number', 'security'];

    public function __construct()
    {
        // Init Models
        $this->userModel        = new UserModel();
        $this->productModel     = new ProductModel();
        $this->productVariantModel = new ProductVariantModel();
        $this->transactionModel = new TransactionModel();
        $this->productStockModel = new ProductStockModel();

        // Init Default Gateway Configs
        $this->defaultMidtransConfig = new MidtransConfig();
        $this->defaultTripayConfig = new TripayConfig();
        $this->defaultOrderkuotaConfig = new OrderkuotaConfig();

        // Init Zeppelin Client (using system default config initially)
        // Corrected constructor call based on ZeppelinClient.php
        $this->zeppelinClient = new ZeppelinClient($this->defaultOrderkuotaConfig);

        // Set initial Midtrans config (default)
        $this->configureMidtrans();
    }

    /**
     * Konfigurasi Midtrans API berdasarkan kunci yang diberikan atau default.
     */
    private function configureMidtrans(?string $serverKey = null, ?string $clientKey = null, ?bool $isProduction = null)
    {
        MidtransApiConfig::$serverKey    = $serverKey ?? $this->defaultMidtransConfig->serverKey;
        MidtransApiConfig::$clientKey    = $clientKey ?? $this->defaultMidtransConfig->clientKey;
        MidtransApiConfig::$isProduction = $isProduction ?? $this->defaultMidtransConfig->isProduction;
        MidtransApiConfig::$isSanitized  = true;
        MidtransApiConfig::$is3ds        = true;
        log_message('debug', '[Midtrans Config] Set Keys. Server Key ending: ' . substr(MidtransApiConfig::$serverKey, -5) . ', Is Production: ' . (MidtransApiConfig::$isProduction ? 'Yes' : 'No'));
    }

    // =========================================================================
    // A. GATEWAY RESOLUTION HELPERS
    // =========================================================================

    /**
     * Memilih gateway prioritas untuk penjual berdasarkan preferensi dan ketersediaan kunci.
     * @param object $seller Data user (penjual) dari database.
     * @return string 'midtrans', 'tripay', 'orderkuota', atau 'system'.
     */
    private function resolveGatewayForSeller(object $seller): string
    {
        // pilihan seller
        $pref = isset($seller->gateway_active) ? $seller->gateway_active : 'system'; // PHP < 7.0 compatibility

        if ($pref === 'midtrans') {
            return (!empty($seller->midtrans_server_key) && !empty($seller->midtrans_client_key)) ? 'midtrans' : 'system';
        }
        if ($pref === 'tripay') {
            return (!empty($seller->tripay_api_key) && !empty($seller->tripay_private_key) && !empty($seller->tripay_merchant_code)) ? 'tripay' : 'system';
        }
        if ($pref === 'orderkuota') {
            // Orderkuota saat ini hanya pakai config sistem, jadi langsung return jika dipilih
            // Pastikan config sistem orderkuota valid
            if(!empty($this->defaultOrderkuotaConfig->authUsername) && !empty($this->defaultOrderkuotaConfig->authToken)){
                return 'orderkuota';
            } else {
                 log_message('warning', '[Gateway Resolution] Seller prefers Orderkuota, but system config is incomplete. Falling back to system default.');
                 return 'system'; // Fallback jika config sistem tidak lengkap
            }
        }
        // system default
        return 'system';
    }

    /**
     * Mengembalikan gateway default sistem (dari env) jika preferensi penjual 'system'.
     */
    private function systemDefaultGateway(): string
    {
        $def = strtolower((string) env('PAYMENT_DEFAULT_GATEWAY', 'midtrans'));
        // Validasi apakah gateway default sistem valid dan terkonfigurasi
        if ($def === 'midtrans' && (!empty($this->defaultMidtransConfig->serverKey) && !empty($this->defaultMidtransConfig->clientKey))) {
            return 'midtrans';
        }
        if ($def === 'tripay' && (!empty($this->defaultTripayConfig->apiKey) && !empty($this->defaultTripayConfig->privateKey) && !empty($this->defaultTripayConfig->merchantCode))) {
            return 'tripay';
        }
         if ($def === 'orderkuota' && (!empty($this->defaultOrderkuotaConfig->authUsername) && !empty($this->defaultOrderkuotaConfig->authToken))) {
            return 'orderkuota';
        }

        // Fallback jika default tidak valid atau tidak terkonfigurasi, coba Midtrans lagi
        if (!empty($this->defaultMidtransConfig->serverKey) && !empty($this->defaultMidtransConfig->clientKey)) {
             log_message('warning', "[System Default Gateway] Configured default '{$def}' is invalid or keys missing. Falling back to Midtrans.");
             return 'midtrans';
        }
        // Jika Midtrans juga tidak ada, log error
        log_message('error', "[System Default Gateway] No valid default payment gateway is configured properly in .env or config files.");
        return 'midtrans'; // Kembalikan midtrans sebagai last resort, meskipun mungkin error nanti
    }

    // =========================================================================
    // B. TRIPAY & ZEPPELIN CLIENT BUILDERS
    // =========================================================================

    /**
     * Membangun TripayClient, menggunakan kunci penjual jika tersedia, atau kembali ke kunci konfigurasi default.
     * @param object|null $seller Data user (penjual/pembeli premium) atau null.
     * @return \App\Libraries\TripayClient
     */
    private function buildTripayClient(?object $seller = null): TripayClient
    {
        $cfgUser = [
            'apiKey'       => isset($seller->tripay_api_key) ? $seller->tripay_api_key : '', // PHP < 7.0 compatibility
            'privateKey'   => isset($seller->tripay_private_key) ? $seller->tripay_private_key : '', // PHP < 7.0 compatibility
            'merchantCode' => isset($seller->tripay_merchant_code) ? $seller->tripay_merchant_code : '', // PHP < 7.0 compatibility
            'isProduction' => (bool) $this->defaultTripayConfig->isProduction, // Ambil mode produksi dari config default
        ];

        $useUser = !empty($cfgUser['apiKey']) && !empty($cfgUser['privateKey']) && !empty($cfgUser['merchantCode']);

        if (!$useUser) {
            // Fallback ke config sistem
            $cfgUser = [
                'apiKey'       => $this->defaultTripayConfig->apiKey,
                'privateKey'   => $this->defaultTripayConfig->privateKey,
                'merchantCode' => $this->defaultTripayConfig->merchantCode,
                'isProduction' => $this->defaultTripayConfig->isProduction,
            ];
        }

        return new TripayClient($cfgUser);
    }

    /**
     * Membangun ZeppelinClient. Saat ini selalu menggunakan config sistem.
     * @param object|null $seller (Tidak digunakan saat ini, tapi ada untuk konsistensi)
     * @return \App\Libraries\ZeppelinClient
     */
    private function buildZeppelinClient(?object $seller = null): ZeppelinClient
    {
        // Selalu gunakan instance yang sudah dibuat di constructor yang pakai config default
        return $this->zeppelinClient;
    }

    /**
     * Helper to parse Zeppelin expiry date string to MySQL DATETIME format.
     * Assumes format "DD MMM YYYY, HH:mm:ss" like "30 Oct 2025, 14:35:00"
     * @param string|null $expiryString
     * @return string|null MySQL DATETIME format or null
     */
    private function parseZeppelinExpiry(?string $expiryString): ?string
    {
        if (empty($expiryString)) {
            return null;
        }
        try {
            // Try parsing with CodeIgniter Time (handles localization better)
            // Need to map Indonesian month names if locale is ID
            // Simple mapping for now
            $monthMap = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'Mei' => 'May', 'Jun' => 'Jun',
                'Jul' => 'Jul', 'Agu' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'
            ];
            $parts = explode(' ', $expiryString); // e.g., ["30", "Okt", "2025,", "14:35:00"]
            if (count($parts) >= 4) {
                 $day = $parts[0];
                 $monthEng = isset($monthMap[$parts[1]]) ? $monthMap[$parts[1]] : $parts[1]; // Map month // PHP < 7.0 compatibility
                 $year = rtrim($parts[2], ',');
                 $time = $parts[3];
                 $dateTimeString = "{$day} {$monthEng} {$year} {$time}";
                 return Time::parse($dateTimeString, 'Asia/Jakarta')->setTimezone('UTC')->toDateTimeString(); // Convert to UTC for DB
            }
            // Fallback parsing if Time::parse fails or format differs
            $timestamp = strtotime($expiryString);
            if ($timestamp === false) return null;
            return date('Y-m-d H:i:s', $timestamp); // Convert to MySQL DATETIME

        } catch (\Exception $e) {
            log_message('error', "[Parse Zeppelin Expiry] Failed to parse '{$expiryString}'. Error: " . $e->getMessage());
            return null;
        }
    }


    // =========================================================================
    // C. PAY FOR PREMIUM (MODIFIED FOR ALL GATEWAYS)
    // =========================================================================

    public function payForPremium()
    {
        // ... (Initial checks) ...
        if (!$this->request->isAJAX()) {
            log_message('error', '[PaymentController] payForPremium accessed non-AJAX.');
            return $this->response->setStatusCode(403, 'Forbidden Action.');
        }

        $userId = session()->get('user_id');
        if (!$userId) {
            log_message('error', '[PaymentController] payForPremium: User ID not found in session.');
            return $this->response->setJSON(['error' => 'Sesi tidak valid. Silakan login ulang.'])->setStatusCode(401);
        }
        $user = $this->userModel->find($userId); // $user will be used as $seller for gateway resolution

        if (!$user) {
            log_message('error', '[PaymentController] payForPremium: User not found. ID: ' . $userId);
            return $this->response->setJSON(['error' => 'User not found.'])->setStatusCode(404);
        }

        if ($user->is_premium) {
            log_message('notice', '[PaymentController] payForPremium: User already premium. ID: ' . $userId);
            return $this->response->setJSON(['error' => 'Anda sudah menjadi pengguna premium.'])->setStatusCode(400);
        }

        // --- GATEWAY RESOLUTION START ---
        // Untuk premium, kita anggap selalu pakai gateway default sistem karena tidak ada seller spesifik
        $resolved = $this->systemDefaultGateway();

        // Premium always uses default keys for Midtrans if selected
        if ($resolved === 'midtrans') {
            $this->configureMidtrans(); // Use default config for Midtrans
        }
        // --- GATEWAY RESOLUTION END ---


        $premiumPrice = 100000;
        $orderId = 'PREM-' . $userId . '-' . time() . '-' . strtoupper(random_string('alnum', 4));

        $transactionDetails = ['order_id' => $orderId, 'gross_amount' => $premiumPrice];
        $itemDetails = [['id' => 'UPGRADE_PREMIUM_' . $userId, 'price' => $premiumPrice, 'quantity' => 1, 'name' => 'Upgrade Akun Premium']];
        $customerDetails = ['first_name' => $user->username, 'email' => $user->email];

        $db = \Config\Database::connect();
        $db->transBegin();

        $txData = [
            'order_id'         => $orderId,
            'user_id'          => $userId, // User yang melakukan upgrade
            'product_id'       => null,
            'variant_id'       => null,
            'variant_name'     => null,
            'buyer_name'       => $user->username,
            'buyer_email'      => $user->email,
            'transaction_type' => 'premium',
            'amount'           => $premiumPrice,
            'status'           => 'pending',
            'payment_gateway'  => $resolved,
            'midtrans_key_source' => 'default', // Selalu default untuk premium
            'quantity'         => 1,
            // Field Tripay & Orderkuota akan null awalnya
            'tripay_reference' => null,
            'tripay_pay_url'   => null,
            'tripay_raw'       => null,
            // Corrected Zeppelin field names based on migration
            'zeppelin_reference_id'  => null,
            'zeppelin_qr_url'  => null,
            'zeppelin_paid_amount' => null,
            'zeppelin_expiry_date' => null, // Changed from expiry_str
            'zeppelin_raw_response'     => null, // Changed from raw
        ];

        $saveResult = $this->transactionModel->save($txData);
        $txId = $this->transactionModel->getInsertID();

        if (!$saveResult || !$txId) {
            $db->transRollback();
            log_message('error', '[PaymentController] payForPremium: Failed to save initial premium transaction to DB. Order ID: ' . $orderId . ' Errors: ' . print_r($this->transactionModel->errors(), true));
            return $this->response->setJSON(['error' => 'Gagal mencatat transaksi awal.'])->setStatusCode(500);
        }

        // --- MIDTRANS BLOCK ---
        if ($resolved === 'midtrans') {
            $payload = [
                'transaction_details' => $transactionDetails,
                'item_details'        => $itemDetails,
                'customer_details'    => $customerDetails,
                'callbacks' => ['finish' => route_to('dashboard') . '?payment_attempt=' . $orderId]
            ];

            try {
                $snapToken = Snap::getSnapToken($payload);
                $updateTokenResult = $this->transactionModel->update($txId, ['snap_token' => $snapToken]);

                if (!$updateTokenResult) {
                    $db->transRollback();
                    log_message('error', '[PaymentController] payForPremium (Midtrans): Failed to update snap_token for Order ID: ' . $orderId);
                    return $this->response->setJSON(['error' => 'Gagal memperbarui token pembayaran.'])->setStatusCode(500);
                }

                if ($db->transStatus() === false) {
                    $db->transRollback();
                    log_message('error', '[PaymentController] payForPremium (Midtrans): Transaction failed before sending token. Order ID: ' . $orderId);
                    return $this->response->setJSON(['error' => 'Terjadi kesalahan database.'])->setStatusCode(500);
                } else {
                    $db->transCommit();
                    log_message('info', '[PaymentController] payForPremium (Midtrans): Snap Token generated successfully for Order ID: ' . $orderId);
                    return $this->response->setJSON(['token' => $snapToken, 'gateway' => 'midtrans']); // Tambahkan gateway ke response
                }

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', '[Midtrans Error - payForPremium] Order ID: ' . $orderId . ' Error: ' . $e->getMessage());
                return $this->response->setJSON(['error' => 'Gagal membuat token pembayaran Midtrans. Detail: ' . $e->getMessage()])->setStatusCode(500);
            } finally {
                $this->configureMidtrans(); // Reset back to default keys after request
            }
        }

        // --- TRIPAY BLOCK ---
        if ($resolved === 'tripay') {
            $client = $this->buildTripayClient(null); // Pakai null -> default config

            $method = 'QRIS'; // contoh default
            $callbackUrl = base_url('payment/tripay/notify');
            $returnUrl   = base_url('dashboard/transactions');

            $payload = [
                'method'         => $method,
                'merchant_ref'   => $orderId,
                'amount'         => (int) $premiumPrice,
                'customer_name'  => $user->username,
                'customer_email' => $user->email,
                'order_items'    => [[
                    'sku'      => $itemDetails[0]['id'],
                    'name'     => substr($itemDetails[0]['name'], 0, 50),
                    'price'    => (int) $premiumPrice,
                    'quantity' => 1
                ]],
                'return_url'  => $returnUrl,
                'callback_url'=> $callbackUrl,
            ];

            try {
                $resp = $client->createTransaction($payload);
                if (!$resp['success']) {
                    throw new \RuntimeException(isset($resp['message']) ? $resp['message'] : 'Unknown Tripay error'); // PHP < 7.0 compatibility
                }
                $data = isset($resp['data']) ? $resp['data'] : []; // PHP < 7.0 compatibility
                $this->transactionModel->update($txId, [
                    'tripay_reference' => isset($data['reference']) ? $data['reference'] : null, // PHP < 7.0 compatibility
                    'tripay_pay_url'   => isset($data['checkout_url']) ? $data['checkout_url'] : null, // PHP < 7.0 compatibility
                    'tripay_raw'       => json_encode($resp),
                ]);
                $db->transCommit();

                return $this->response->setJSON([
                    'gateway' => 'tripay',
                    'status'  => 'ok',
                    'reference'   => isset($data['reference']) ? $data['reference'] : null, // PHP < 7.0 compatibility
                    'checkoutUrl' => isset($data['checkout_url']) ? $data['checkout_url'] : null, // PHP < 7.0 compatibility
                    'qrUrl'       => isset($data['qr_url']) ? $data['qr_url'] : null, // PHP < 7.0 compatibility
                    'orderId'     => $orderId,
                ]);
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Tripay Create - Premium] ' . $e->getMessage() . ' Payload: ' . json_encode($payload));
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Tripay untuk Premium.'])->setStatusCode(500);
            }
        }

        // --- ORDERKUOTA/ZEPPELIN BLOCK ---
        if ($resolved === 'orderkuota') {
            $client = $this->buildZeppelinClient(null); // Pakai null -> default config
            $refId = $orderId; // Gunakan orderId sebagai reference_id untuk Zeppelin
            // Mengambil expiry time dari config Orderkuota
            $expiryMinutes = $this->defaultOrderkuotaConfig->expiryTime;

            try {
                // Mengirim expiry time ke API
                $resp = $client->createPayment($refId, (int)$premiumPrice); // expiry time now handled inside client
                if (!$resp['success']) {
                    throw new \RuntimeException(isset($resp['message']) ? $resp['message'] : 'Unknown Zeppelin error'); // PHP < 7.0 compatibility
                }
                $data = isset($resp['data']) ? $resp['data'] : []; // PHP < 7.0 compatibility
                $qrisData = isset($data['qris']) ? $data['qris'] : []; // PHP < 7.0 compatibility

                // Corrected field names
                $expiryDate = $this->parseZeppelinExpiry(isset($data['expired_date_str']) ? $data['expired_date_str'] : null); // PHP < 7.0 compatibility // Parse the date string

                $this->transactionModel->update($txId, [
                    'zeppelin_reference_id'  => isset($data['reference_id']) ? $data['reference_id'] : null, // PHP < 7.0 compatibility
                    'zeppelin_qr_url'  => isset($qrisData['qris_image_url']) ? $qrisData['qris_image_url'] : null, // PHP < 7.0 compatibility
                    'zeppelin_paid_amount' => isset($data['paid_amount']) ? $data['paid_amount'] : null, // PHP < 7.0 compatibility
                    'zeppelin_expiry_date' => $expiryDate, // Save parsed date
                    'zeppelin_raw_response' => json_encode($resp), // Corrected field name
                ]);

                $db->transCommit();

                return $this->response->setJSON([
                    'gateway'     => 'orderkuota',
                    'status'      => 'ok',
                    'reference'   => isset($data['reference_id']) ? $data['reference_id'] : null, // PHP < 7.0 compatibility
                    'qrUrl'       => isset($qrisData['qris_image_url']) ? $qrisData['qris_image_url'] : null, // PHP < 7.0 compatibility
                    'paidAmount'  => isset($data['paid_amount']) ? $data['paid_amount'] : null, // PHP < 7.0 compatibility
                    'expiry'      => isset($data['expired_date_str']) ? $data['expired_date_str'] : null, // PHP < 7.0 compatibility // Kirim string asli ke frontend
                    'orderId'     => $orderId,
                ]);

            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Zeppelin Create - Premium] ' . $e->getMessage() . ' Order ID: ' . $orderId);
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Orderkuota untuk Premium. ' . $e->getMessage()])->setStatusCode(500);
            }
        }

        // If resolution fails somehow
        $db->transRollback();
        log_message('error', '[PaymentController] payForPremium: Invalid or unavailable gateway resolved: ' . $resolved);
        return $this->response->setJSON(['error' => 'Gateway pembayaran tidak valid atau tidak tersedia.'])->setStatusCode(500);
    }

    // =========================================================================
    // D. PAY FOR PRODUCT (MODIFIED FOR ALL GATEWAYS)
    // =========================================================================

    public function payForProduct()
    {
        // ... (Initial checks and product/stock validation remain the same) ...
        if (!$this->request->isAJAX()) {
            log_message('error', '[PaymentController] payForProduct accessed non-AJAX.');
            return $this->response->setStatusCode(403, 'Forbidden Action.');
        }

        $json = $this->request->getJSON();
        $productId = filter_var(isset($json->productId) ? $json->productId : null, FILTER_VALIDATE_INT); // PHP < 7.0 compatibility
        $variantId = filter_var(isset($json->variantId) ? $json->variantId : null, FILTER_VALIDATE_INT); // PHP < 7.0 compatibility // Null if not provided or invalid
        $buyerName = trim(strip_tags(isset($json->name) ? $json->name : '')); // PHP < 7.0 compatibility
        $buyerEmail = filter_var(trim(isset($json->email) ? $json->email : ''), FILTER_VALIDATE_EMAIL); // PHP < 7.0 compatibility
        $quantity = filter_var(isset($json->quantity) ? $json->quantity : 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); // PHP < 7.0 compatibility

        if (!$productId || !$buyerName || !$buyerEmail || $quantity === false || $quantity <= 0) {
            log_message('warning', '[PaymentController] payForProduct: Invalid input data.', (array)$json);
            $errorMsg = 'Data tidak valid.';
            if (!$productId) $errorMsg = 'Produk tidak valid.';
            elseif (!$buyerName) $errorMsg = 'Nama tidak boleh kosong.';
            elseif (!$buyerEmail) $errorMsg = 'Masukkan alamat email yang valid.';
            elseif ($quantity === false || $quantity <= 0) $errorMsg = 'Jumlah pembelian tidak valid.';
            return $this->response->setJSON(['error' => $errorMsg])->setStatusCode(400);
        }

        $product = $this->productModel->find($productId);
        if (!$product || $product->order_type !== 'auto' || !$product->is_active) {
            log_message('warning', '[PaymentController] payForProduct: Invalid/unavailable product requested. Product ID: ' . $productId);
            return $this->response->setJSON(['error' => 'Produk tidak valid, tidak aktif, atau bukan tipe otomatis.'])->setStatusCode(404);
        }

        $pricePerItem = $product->price;
        $productNameForGateway = $product->product_name; // Name sent to Gateway
        $productNameForDb = $product->product_name; // Name stored in DB transaction
        $isVariantSale = false;
        $availableStock = 0;
        $variant = null; // Initialize variant object
        $itemIdForGateway = (string)$product->id; // Default item ID for Gateway
        $variantNameToSave = null; // Initialize variant name

        if ($product->has_variants) {
            if (!$variantId) {
                log_message('warning', '[PaymentController] payForProduct: Variant ID missing for product with variants. Product ID: ' . $productId);
                return $this->response->setJSON(['error' => 'Varian produk belum dipilih.'])->setStatusCode(400);
            }
            $variant = $this->productVariantModel->find($variantId); // Assign to $variant
            if (!$variant || $variant->product_id != $productId || !$variant->is_active) {
                log_message('warning', '[PaymentController] payForProduct: Invalid or inactive variant requested. Product ID: ' . $productId . ', Variant ID: ' . $variantId);
                return $this->response->setJSON(['error' => 'Varian produk tidak valid atau tidak aktif.'])->setStatusCode(404);
            }
            $pricePerItem = $variant->price;
            $productNameForGateway = $product->product_name . ' - ' . $variant->name;
            $productNameForDb = $productNameForGateway; // Use combined name for DB too
            $isVariantSale = true;
            $availableStock = $this->productStockModel->getAvailableStockCountForVariant($variantId);
            $itemIdForGateway = 'P' . $product->id . '_V' . $variantId;
            $variantNameToSave = $variant->name; // Get variant name to save
        } else {
            if ($pricePerItem <= 0) {
                log_message('warning', '[PaymentController] payForProduct: Invalid price (<=0) for non-variant product. Product ID: ' . $productId);
                return $this->response->setJSON(['error' => 'Harga produk tidak valid.'])->setStatusCode(400);
            }
            $availableStock = $this->productStockModel->getAvailableStockCountForNonVariant($productId);
            $variantId = null; // Ensure variantId is null for non-variant
        }

        // Check Stock vs Quantity
        if ($availableStock < $quantity) {
            log_message('warning', "[PaymentController] payForProduct: Stock Check Failed. Attempt to buy {$quantity} of product ID {$productId}" . ($isVariantSale ? " (Variant ID {$variantId})" : "") . " but only {$availableStock} available.");
            $errorMessage = "Maaf, stok " . ($isVariantSale ? 'varian' : 'produk') . " ini hanya tersisa {$availableStock} item.";
            return $this->response->setJSON(['error' => $errorMessage])->setStatusCode(400);
        }

        $seller = $this->userModel->find($product->user_id);
        if (!$seller) {
            log_message('error', '[PaymentController] payForProduct: Seller not found for product ID: ' . $productId . ', Seller ID: ' . $product->user_id);
            return $this->response->setJSON(['error' => 'Penjual produk tidak ditemukan.'])->setStatusCode(404);
        }

        // --- GATEWAY RESOLUTION START ---
        $resolved = $this->resolveGatewayForSeller($seller);
        if ($resolved === 'system') {
            $resolved = $this->systemDefaultGateway();
        }

        // Configure Midtrans if selected
        $keySource = 'default';
        if ($resolved === 'midtrans') {
            $useUserKeys = (!empty($seller->midtrans_server_key) && !empty($seller->midtrans_client_key));
            $keySource = $useUserKeys ? 'user' : 'default';
            $this->configureMidtrans(
                $useUserKeys ? $seller->midtrans_server_key : null,
                $useUserKeys ? $seller->midtrans_client_key : null
            );
        }
        // --- END GATEWAY RESOLUTION / MIDTRANS CONFIG PREP ---

        $orderId = 'PROD-' . $product->id . '-' . ($isVariantSale ? $variantId . '-' : '') . time() . '-' . strtoupper(random_string('alnum', 4));
        $totalAmount = $pricePerItem * $quantity;
        $transactionDetails = ['order_id' => $orderId, 'gross_amount' => (int) $totalAmount];

        // Item details for Gateways
        $itemDetails = [[
            'id'       => $itemIdForGateway,
            'price'    => (int) $pricePerItem,
            'quantity' => $quantity,
            'name'     => substr($productNameForGateway, 0, 50) // Gateway name limit
        ]];

        $customerDetails = ['first_name' => $buyerName, 'email' => $buyerEmail];

        $db = \Config\Database::connect();
        $db->transBegin();

        $txData = [
            'order_id'            => $orderId,
            'user_id'             => $seller->id,
            'product_id'          => $product->id,
            'variant_id'          => $variantId, // Store variant ID
            'variant_name'        => $variantNameToSave, // Store variant name
            'buyer_name'          => $buyerName,
            'buyer_email'         => $buyerEmail,
            'transaction_type'    => 'product',
            'amount'              => $totalAmount,
            'quantity'            => $quantity,
            'status'              => 'pending',
            'payment_gateway'     => $resolved,
            'midtrans_key_source' => ($resolved === 'midtrans') ? $keySource : 'default',
            // Field Tripay & Orderkuota akan null awalnya
            'tripay_reference' => null,
            'tripay_pay_url'   => null,
            'tripay_raw'       => null,
            // Corrected Zeppelin field names
            'zeppelin_reference_id'  => null,
            'zeppelin_qr_url'  => null,
            'zeppelin_paid_amount' => null,
            'zeppelin_expiry_date' => null, // Changed from expiry_str
            'zeppelin_raw_response' => null, // Changed from raw
        ];

        $saveResult = $this->transactionModel->save($txData);
        $txId = $this->transactionModel->getInsertID();

        if (!$saveResult || !$txId) {
            $db->transRollback();
            log_message('error', '[PaymentController] payForProduct: Failed to save initial product transaction to DB. Order ID: ' . $orderId . ' Errors: ' . print_r($this->transactionModel->errors(), true));
            if ($resolved === 'midtrans') $this->configureMidtrans(); // Reset keys
            return $this->response->setJSON(['error' => 'Gagal mencatat transaksi awal.'])->setStatusCode(500);
        }

        // --- MIDTRANS BLOCK ---
        if ($resolved === 'midtrans') {
            $payload = [
                'transaction_details' => $transactionDetails,
                'item_details'        => $itemDetails,
                'customer_details'    => $customerDetails,
                'callbacks' => ['finish' => route_to('profile.public', $seller->username) . '?payment_attempt=' . $orderId]
            ];

            log_message('debug', '[PaymentController] payForProduct (Midtrans): Payload for Snap Token (Order ID: ' . $orderId . '): ' . json_encode($payload));

            try {
                $snapToken = Snap::getSnapToken($payload);
                $updateTokenResult = $this->transactionModel->update($txId, ['snap_token' => $snapToken]);

                if (!$updateTokenResult) {
                    $db->transRollback();
                    log_message('error', '[PaymentController] payForProduct (Midtrans): Failed to update snap_token for Order ID: ' . $orderId);
                    $this->configureMidtrans(); // Reset keys
                    return $this->response->setJSON(['error' => 'Gagal memperbarui token pembayaran.'])->setStatusCode(500);
                }

                if ($db->transStatus() === false) {
                    $db->transRollback();
                    log_message('error', '[PaymentController] payForProduct (Midtrans): Transaction failed before sending token. Order ID: ' . $orderId);
                    $this->configureMidtrans(); // Reset keys
                    return $this->response->setJSON(['error' => 'Terjadi kesalahan database.'])->setStatusCode(500);
                } else {
                    $db->transCommit();
                    log_message('info', "[PaymentController] payForProduct (Midtrans): Snap Token generated successfully for Order ID: {$orderId} (Qty: {$quantity}) using {$keySource} keys. Variant ID: " . ($variantId ?? 'N/A'));
                    $this->configureMidtrans(); // Reset keys
                    return $this->response->setJSON(['token' => $snapToken, 'gateway' => 'midtrans']);
                }

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', "[Midtrans Error - payForProduct] Order ID: {$orderId} (Qty: {$quantity}) using {$keySource} keys. Error: " . $e->getMessage() . " Payload: " . json_encode($payload));
                $this->configureMidtrans(); // Reset keys
                $errorMessage = 'Gagal membuat token pembayaran Midtrans.';
                if (ENVIRONMENT !== 'production') {
                    $errorMessage .= ' Detail: ' . $e->getMessage();
                }
                return $this->response->setJSON(['error' => $errorMessage])->setStatusCode(500);
            }
        }

        // --- TRIPAY BLOCK ---
        if ($resolved === 'tripay') {
            $client = $this->buildTripayClient($seller);

            $method = 'QRIS'; // contoh default
            $callbackUrl = base_url('payment/tripay/notify');
            $returnUrl   = route_to('profile.public', $seller->username); // Kembali ke profil seller

            $payload = [
                'method'         => $method,
                'merchant_ref'   => $orderId,
                'amount'         => (int) $totalAmount,
                'customer_name'  => $buyerName,
                'customer_email' => $buyerEmail,
                'order_items'    => [[
                    'sku'      => $itemIdForGateway,
                    'name'     => substr($productNameForGateway, 0, 50),
                    'price'    => (int) $pricePerItem,
                    'quantity' => (int) $quantity
                ]],
                'return_url'  => $returnUrl,
                'callback_url'=> $callbackUrl,
            ];

            try {
                $resp = $client->createTransaction($payload);
                if (!$resp['success']) {
                    throw new \RuntimeException(isset($resp['message']) ? $resp['message'] : 'Unknown Tripay error'); // PHP < 7.0 compatibility
                }
                $data = isset($resp['data']) ? $resp['data'] : []; // PHP < 7.0 compatibility

                $this->transactionModel->update($txId, [
                    'tripay_reference' => isset($data['reference']) ? $data['reference'] : null, // PHP < 7.0 compatibility
                    'tripay_pay_url'   => isset($data['checkout_url']) ? $data['checkout_url'] : null, // PHP < 7.0 compatibility
                    'tripay_raw'       => json_encode($resp),
                ]);
                $db->transCommit();

                return $this->response->setJSON([
                    'gateway' => 'tripay',
                    'status'  => 'ok',
                    'reference'   => isset($data['reference']) ? $data['reference'] : null, // PHP < 7.0 compatibility
                    'checkoutUrl' => isset($data['checkout_url']) ? $data['checkout_url'] : null, // PHP < 7.0 compatibility
                    'qrUrl'       => isset($data['qr_url']) ? $data['qr_url'] : null, // PHP < 7.0 compatibility
                    'orderId'     => $orderId,
                ]);
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Tripay Create - Product] ' . $e->getMessage() . ' Payload: ' . json_encode($payload));
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Tripay.'])->setStatusCode(500);
            }
        }

        // --- ORDERKUOTA/ZEPPELIN BLOCK ---
        if ($resolved === 'orderkuota') {
            $client = $this->buildZeppelinClient(null); // Selalu pakai config sistem
            $refId = $orderId; // Gunakan orderId sebagai reference_id untuk Zeppelin
            // Mengambil expiry time dari config Orderkuota
            $expiryMinutes = $this->defaultOrderkuotaConfig->expiryTime;

            try {
                // Perhatikan: amount di Zeppelin adalah TOTAL amount
                // Mengirim expiry time ke API
                $resp = $client->createPayment($refId, (int)$totalAmount); // expiry time now handled inside client
                if (!$resp['success']) {
                    throw new \RuntimeException(isset($resp['message']) ? $resp['message'] : 'Unknown Zeppelin error'); // PHP < 7.0 compatibility
                }
                $data = isset($resp['data']) ? $resp['data'] : []; // PHP < 7.0 compatibility
                $qrisData = isset($data['qris']) ? $data['qris'] : []; // PHP < 7.0 compatibility

                // Corrected field names
                $expiryDate = $this->parseZeppelinExpiry(isset($data['expired_date_str']) ? $data['expired_date_str'] : null); // PHP < 7.0 compatibility // Parse the date string

                $this->transactionModel->update($txId, [
                    'zeppelin_reference_id'  => isset($data['reference_id']) ? $data['reference_id'] : null, // PHP < 7.0 compatibility
                    'zeppelin_qr_url'  => isset($qrisData['qris_image_url']) ? $qrisData['qris_image_url'] : null, // PHP < 7.0 compatibility
                    'zeppelin_paid_amount' => isset($data['paid_amount']) ? $data['paid_amount'] : null, // PHP < 7.0 compatibility
                    'zeppelin_expiry_date' => $expiryDate, // Save parsed date
                    'zeppelin_raw_response' => json_encode($resp), // Corrected field name
                ]);

                $db->transCommit();

                return $this->response->setJSON([
                    'gateway'     => 'orderkuota',
                    'status'      => 'ok',
                    'reference'   => isset($data['reference_id']) ? $data['reference_id'] : null, // PHP < 7.0 compatibility
                    'qrUrl'       => isset($qrisData['qris_image_url']) ? $qrisData['qris_image_url'] : null, // PHP < 7.0 compatibility
                    'paidAmount'  => isset($data['paid_amount']) ? $data['paid_amount'] : null, // PHP < 7.0 compatibility
                    'expiry'      => isset($data['expired_date_str']) ? $data['expired_date_str'] : null, // PHP < 7.0 compatibility // Kirim string asli ke frontend
                    'orderId'     => $orderId,
                ]);

            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Zeppelin Create - Product] ' . $e->getMessage() . ' Order ID: ' . $orderId);
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Orderkuota. ' . $e->getMessage()])->setStatusCode(500);
            }
        }

        // If no gateway resolved
        $db->transRollback();
        log_message('error', '[PaymentController] payForProduct: Invalid or unavailable gateway resolved: ' . $resolved);
        return $this->response->setJSON(['error' => 'Gateway pembayaran tidak valid atau tidak tersedia.'])->setStatusCode(500);
    }

    // =========================================================================
    // E. COMMON FULFILLMENT LOGIC
    // =========================================================================

    /**
     * Executes fulfillment actions (stock reduction, balance update, premium upgrade, email)
     * after a transaction has been confirmed successful by a gateway.
     * The calling function is responsible for wrapping this in a DB transaction.
     *
     * @param string $orderId The order ID of the successful transaction.
     * @return bool True if all fulfillment actions succeeded, false otherwise.
     */
    private function handleSuccessfulPayment(string $orderId): bool
    {
        // Re-fetch the transaction since it's cleaner than passing the complex object
        $transaction = $this->transactionModel->where('order_id', $orderId)->first();

        // Extra check: ensure transaction is found and *NOW* marked as success in DB
        if (!$transaction || !in_array($transaction->status, ['success', 'settlement', 'capture'])) {
            log_message('error', "[Handle Success] Order ID {$orderId} not found or status is not 'success'/'settlement'/'capture' in DB (". (isset($transaction->status) ? $transaction->status : 'Not Found') .") for fulfillment."); // PHP < 7.0 compatibility
            return false; // Don't proceed if status is not correct in DB
        }

        $actionsFailed = false;

        // Action: Upgrade Premium
        if ($transaction->transaction_type === 'premium') {
            $user = $this->userModel->find($transaction->user_id);
            if ($user && $user->is_premium) {
                 log_message('info', "[Handle Success - Skip] User {$transaction->user_id} already premium for order {$orderId}.");
                 return true; // Already fulfilled
            }
            if (!$this->userModel->upgradeToPremium($transaction->user_id)) {
                log_message('error', "[Handle Success - ACTION FAILED] Failed to upgrade user {$transaction->user_id} for order {$orderId}.");
                $actionsFailed = true;
            } else {
                log_message('info', "[Handle Success - ACTION] User {$transaction->user_id} upgraded to premium (Order ID: {$orderId}).");
            }
        // Action: Process Product Sale
        } elseif ($transaction->transaction_type === 'product' && $transaction->product_id) {
            $quantityNeeded = (int) (isset($transaction->quantity) ? $transaction->quantity : 1); // PHP < 7.0 compatibility
            $variantId = isset($transaction->variant_id) ? $transaction->variant_id : null; // PHP < 7.0 compatibility // Retrieve variant_id
            $isVariantSale = ($variantId !== null);
            $buyerNameClean = $transaction->buyer_name;

            // 1. Fetch stock items
            $stockItems = $isVariantSale
                ? $this->productStockModel->getAvailableStockItemsForVariant($variantId, $quantityNeeded)
                : $this->productStockModel->getAvailableStockItemsForNonVariant($transaction->product_id, $quantityNeeded);

            if ($stockItems === null || count($stockItems) < $quantityNeeded) {
                $foundStockCount = ($stockItems === null) ? 0 : count($stockItems);
                log_message('critical', "[Handle Success - ACTION FAILED] Insufficient stock found for product ID {$transaction->product_id} on order {$orderId}! Needed: {$quantityNeeded}, Found: {$foundStockCount}. VARIANT_ID: " . ($variantId ?? 'N/A'));
                $this->sendStockAlertEmailToSeller($transaction, "STOK TIDAK CUKUP ({$foundStockCount} tersedia) saat pesanan {$orderId} dikonfirmasi berhasil! Hubungi pembeli ({$transaction->buyer_email}).", $variantId, $quantityNeeded);
                $actionsFailed = true;
            } else {
                $stockItemIds = array_column($stockItems, 'id');
                $allStockDataJson = array_column($stockItems, 'stock_data');

                // 2. Mark stocks as used
                if ($this->productStockModel->markMultipleStocksAsUsed($stockItemIds, $transaction->buyer_email, $transaction->id)) {

                    // 3. Synchronize variant stock count (jika varian)
                    $stockSyncSuccess = true; // Assume success if not variant
                    if ($isVariantSale) {
                        $stockSyncSuccess = $this->productVariantModel->synchronizeStock($variantId, $this->productStockModel);
                        if (!$stockSyncSuccess) {
                            log_message('error', "[Handle Success - ACTION FAILED] Failed sync variant stock count for Variant ID: {$variantId}, Order ID {$orderId}.");
                            $actionsFailed = true;
                        }
                    }

                    // 4. Add balance to seller (only if stock marking and sync succeeded)
                    if ($stockSyncSuccess && !$actionsFailed) {
                        if ($this->userModel->addBalance($transaction->user_id, (float)$transaction->amount)) {

                            // 5. Prepare and Send product email
                            $product = $this->productModel->find($transaction->product_id);
                            $productDisplayName = $product ? $product->product_name : 'Produk Tidak Ditemukan';
                            $variantNameFromTx = isset($transaction->variant_name) ? $transaction->variant_name : null; // PHP < 7.0 compatibility
                            if ($isVariantSale && $variantNameFromTx) {
                                 $productDisplayName .= ' - ' . $variantNameFromTx;
                            }

                            if (!$this->sendProductEmail($transaction, $productDisplayName, $allStockDataJson, $buyerNameClean, $quantityNeeded)) {
                                 $actionsFailed = true;
                                 log_message('error', "[Handle Success - ACTION FAILED] Failed to send product email for Order ID: {$orderId}.");
                                 // Pertimbangkan: Kirim notif ke admin jika email gagal?
                            }

                        } else {
                            log_message('error', "[Handle Success - ACTION FAILED] Failed to add balance to user {$transaction->user_id} for order {$orderId}.");
                            $actionsFailed = true;
                            // Kirim notif ke admin bahwa balance gagal ditambahkan
                            $this->sendStockAlertEmailToSeller($transaction, "CRITICAL: Balance GAGAL ditambahkan ke seller (ID: {$transaction->user_id}) setelah pembayaran {$orderId} sukses dan stok terkirim.", $variantId, $quantityNeeded);
                        }
                    }

                } else { // Failed to mark stock as used
                    log_message('error', "[Handle Success - ACTION FAILED] Failed to mark stock IDs as used for order {$orderId}. IDs: " . implode(', ', $stockItemIds));
                    $actionsFailed = true;
                    $this->sendStockAlertEmailToSeller($transaction, "CRITICAL: Gagal menandai stok terpakai (ID: " . implode(', ', $stockItemIds) . ") setelah pembayaran sukses. SALDO TIDAK DITAMBAHKAN.", $variantId, $quantityNeeded);
                }
            }
        }

        log_message('info', "[Handle Success] Order ID {$orderId} finished. Actions " . ($actionsFailed ? 'FAILED.' : 'SUCCEEDED.'));
        return !$actionsFailed;
    }

    // =========================================================================
    // F. WEBHOOK NOTIFICATION HANDLERS
    // =========================================================================

    /**
     * Handles incoming Midtrans notifications (webhook).
     */
    public function notificationHandler() // Midtrans
    {
        log_message('info', "[Midtrans Webhook] Received notification. Raw input: " . $this->request->getBody());

        if (strtoupper($this->request->getMethod()) !== 'POST') {
            log_message('error', '[Midtrans Webhook] Invalid request method: ' . $this->request->getMethod());
            return $this->response->setStatusCode(405, 'Method Not Allowed.');
        }

        $notif = null;
        $correctServerKey = $this->defaultMidtransConfig->serverKey;
        $transaction = null;
        $orderId = null;
        $statusUpdateSuccess = true; // Assume status update will succeed

        try {
            // 1. Initial Parse to get Order ID
            $initialNotificationCheck = json_decode($this->request->getBody());
            $orderId = isset($initialNotificationCheck->order_id) ? $initialNotificationCheck->order_id : null; // PHP < 7.0 compatibility // Assign to $orderId

            if (!$orderId) {
                 throw new \Exception('Order ID not found in initial notification payload.');
            }

            // 2. Fetch Transaction and Seller Keys based on Order ID
            $transaction = $this->transactionModel->getTransactionWithUser($orderId); // Fetch transaction with user details (including key)

            if ($transaction) {
                if ($transaction->midtrans_key_source === 'user' && !empty($transaction->user_server_key)) {
                    $correctServerKey = $transaction->user_server_key;
                    log_message('info', "[Midtrans Webhook - Key Source] Using USER server key for Order ID {$orderId}.");
                } elseif ($transaction->midtrans_key_source === 'user' && empty($transaction->user_server_key)) {
                    log_message('warning', "[Midtrans Webhook - Key Source] Transaction {$orderId} used 'user' source, but key is empty in DB. Using DEFAULT key for validation.");
                } else {
                     log_message('info', "[Midtrans Webhook - Key Source] Using DEFAULT server key for Order ID {$orderId}.");
                }
            } else {
                 log_message('warning', "[Midtrans Webhook] Transaction with Order ID {$orderId} not found in DB. Using DEFAULT key for validation.");
            }

            // 3. Configure Midtrans with the CORRECT key for THIS notification
            MidtransApiConfig::$serverKey = $correctServerKey;
            MidtransApiConfig::$isProduction = $this->defaultMidtransConfig->isProduction;

            // 4. Instantiate Midtrans Notification (Performs Signature Validation)
            $notif = new Notification(); // This uses the currently set server key

            // 5. Extract notification data (only if validation passes)
            $transactionStatus = isset($notif->transaction_status) ? $notif->transaction_status : null; // PHP < 7.0 compatibility
            $fraudStatus       = isset($notif->fraud_status) ? $notif->fraud_status : null; // PHP < 7.0 compatibility
            $paymentType       = isset($notif->payment_type) ? $notif->payment_type : 'unknown'; // PHP < 7.0 compatibility

            // Log successful validation
            log_message('info', "[Midtrans Webhook] SIGNATURE VALID for Order ID: {$orderId} using key source '". (isset($transaction->midtrans_key_source) ? $transaction->midtrans_key_source : 'unknown') ."'. Processing Status: {$transactionStatus}, Type: {$paymentType}, Fraud: {$fraudStatus}"); // PHP < 7.0 compatibility

        } catch (\Exception $e) {
            $keySourceInfo = $transaction ? "'{$transaction->midtrans_key_source}'" : 'Unknown (transaction not found)';
            log_message('error', "[Midtrans Notification Error - Validation/Parse] Order ID: {$orderId}. Using key source {$keySourceInfo}. Error: " . $e->getMessage() . " Raw Body: " . $this->request->getBody());
            $this->configureMidtrans(); // Reset to default config
            $httpStatusCode = (strpos(strtolower($e->getMessage()), 'signature') !== false) ? 403 : 400;
            return $this->response->setStatusCode($httpStatusCode, $e->getMessage()); // Return appropriate error
        } finally {
             // Always reset Midtrans config after handling notification
             $this->configureMidtrans();
        }

        // --- Processing Logic (after successful signature validation) ---

        if (!$transaction) {
            log_message('error', "[Midtrans Webhook] Processing aborted: Transaction {$orderId} not found in database (checked again after validation).");
            return $this->response->setStatusCode(200, 'Transaction not found, acknowledged.'); // Acknowledge Midtrans
        }

        // Prevent processing if transaction is already finalized (success, failed, expired)
        if (in_array($transaction->status, ['success', 'failed', 'expired'])) {
            log_message('info', "[Midtrans Webhook] Transaction {$orderId} is already finalized in DB ('{$transaction->status}'). Webhook ignored.");
            return $this->response->setStatusCode(200, 'Already processed.');
        }

        // Determine the new database status based on notification
        $newDbStatus = $transaction->status; // Default to current status
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            $newDbStatus = ($fraudStatus === 'accept') ? 'success' : (($fraudStatus === 'challenge') ? 'challenge' : 'failed');
        } elseif ($transactionStatus === 'pending') {
            // Keep pending
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
            $newDbStatus = ($transactionStatus === 'expire') ? 'expired' : 'failed';
        } else {
            log_message('warning', "[Midtrans Webhook] Unhandled Midtrans transaction status '{$transactionStatus}' for Order ID {$orderId}. Keeping DB status '{$transaction->status}'.");
        }


        // Start database transaction for updates and actions
        $db = \Config\Database::connect();
        $db->transBegin();
        $actionsFailed = false; // Flag to track if ACTIONS failed

        try {
            // Update transaction status in DB if it changed
            if ($newDbStatus !== $transaction->status) {
                $updateData = ['status' => $newDbStatus, 'updated_at' => date('Y-m-d H:i:s')];
                if (!$this->transactionModel->update($transaction->id, $updateData)) {
                    log_message('error', "[Midtrans Webhook] DB STATUS UPDATE FAILED for Tx ID {$transaction->id} (Order ID {$orderId}) from '{$transaction->status}' to '{$newDbStatus}'. Model Errors: " . print_r($this->transactionModel->errors(), true));
                    $statusUpdateSuccess = false; // Status update itself failed
                    throw new \Exception("DB status update failed."); // Throw to trigger rollback
                } else {
                    log_message('info', "[Midtrans Webhook] DB Status for Order ID {$orderId} updated from '{$transaction->status}' to '{$newDbStatus}'.");
                }
            } else {
                log_message('info', "[Midtrans Webhook] No status change needed for Order ID {$orderId}. Current DB Status: '{$transaction->status}', Notification Status maps to: '{$newDbStatus}'.");
            }

            // --- Perform Actions ONLY if new status maps to 'success' ---
            if ($newDbStatus === 'success') {
                log_message('info', "[Midtrans Webhook - ACTION] Processing success actions for Order ID {$orderId}...");
                if (!$this->handleSuccessfulPayment($orderId)) { // Use unified fulfillment method
                    $actionsFailed = true;
                    log_message('error', "[Midtrans Webhook - ACTION FAILED] Fulfillment failed for Order ID {$orderId} (check handleSuccessfulPayment logs).");
                    // We DO NOT throw here, commit the status update but log action failure.
                }
            }
            // --- End of success block ---

        } catch (Throwable $e) { // Catch Throwable for PHP 7+ errors/exceptions
            log_message('error', "[Midtrans Webhook - PROCESSING EXCEPTION] Error processing notification/actions for Order ID {$orderId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $actionsFailed = true; // Mark actions as failed on any exception during processing
            // We should rollback if an exception occurs during processing
            $db->transRollback();
            log_message('error', "[Midtrans Webhook] Transaction ROLLED BACK due to exception for Order ID: {$orderId}.");
            return $this->response->setStatusCode(500, 'Internal Server Error during processing.'); // Respond with error to Midtrans
        }

        // --- Finalize Transaction (Commit or Rollback based on $db->transStatus()) ---
        if ($db->transStatus() !== false) {
             $db->transCommit();
             $finalDbStatus = $statusUpdateSuccess ? $newDbStatus : $transaction->status; // Reflect actual committed status
             $logSuffix = $actionsFailed ? ' but ACTIONS FAILED (check logs).' : '.';
             log_message('info', "[Midtrans Webhook] Transaction COMMITTED for Order ID: {$orderId}. Final DB Status: '{$finalDbStatus}'{$logSuffix}");
        } else {
             // Rollback was triggered by an exception or explicit call
             if (!$db->transStatus()) { // Double check if rollback wasn't already logged by exception handler
                 $db->transRollback(); // Ensure rollback is called if not already
                 log_message('error', "[Midtrans Webhook] Transaction ROLLED BACK for Order ID: {$orderId} because transStatus was FALSE. Initial DB Status: '{$transaction->status}', Attempted New Status: '{$newDbStatus}'.");
             }
             return $this->response->setStatusCode(200, 'Error during DB transaction commit, acknowledged.'); // Acknowledge Midtrans but log error
        }

        return $this->response->setStatusCode(200, 'Notification received and processed.');
    }

    /**
     * Handles incoming Tripay notifications (webhook).
     */
    public function tripayNotification()
    {
        $raw = $this->request->getBody();
        $sig = $this->request->getHeaderLine('X-Callback-Signature');
        $evt = $this->request->getHeaderLine('X-Callback-Event');

        log_message('info', "[Tripay Webhook] Received notification. Event: {$evt}, Raw input: " . $raw);

        // Hanya proses event 'payment_status'
        if (strtolower($evt) !== 'payment_status') {
            log_message('warning', "[Tripay Webhook] Ignored event type: {$evt}");
            return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Invalid event']); // Return 200 OK but indicate invalid event
        }

        $json = json_decode($raw, true) ?: [];
        $merchantRef = isset($json['merchant_ref']) ? $json['merchant_ref'] : null; // PHP < 7.0 compatibility
        $reference   = isset($json['reference']) ? $json['reference'] : null; // PHP < 7.0 compatibility
        $status      = strtolower(isset($json['status']) ? $json['status'] : ''); // PHP < 7.0 compatibility

        if (!$merchantRef) {
            log_message('error', "[Tripay Webhook] No merchant_ref found.");
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'No merchant_ref']);
        }

        // Cari transaksi berdasarkan merchant_ref (order_id kita)
        $tx = $this->transactionModel->where('order_id', $merchantRef)->first();
        if (!$tx) {
            log_message('error', '[Tripay Notify] Transaction not found: ' . $merchantRef);
            // Return 200 OK ke Tripay tapi beri pesan error
            return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Transaction not found']);
        }

        // Prevent processing if transaction is already finalized (success, failed, expired)
        if (in_array($tx->status, ['success', 'failed', 'expired'])) {
            log_message('info', "[Tripay Webhook] Transaction {$merchantRef} is already finalized in DB ('{$tx->status}'). Webhook ignored.");
            return $this->response->setStatusCode(200)->setJSON(['success' => true, 'message' => 'Already processed']);
        }

        // Ambil seller (user_id di transaksi) untuk verifikasi signature
        $seller = $this->userModel->find($tx->user_id);
        if (!$seller) {
            // Jika seller tidak ada (misal user dihapus), mungkin fallback ke default key?
            // Atau anggap error. Untuk sekarang, fallback ke default key.
            log_message('warning', '[Tripay Notify] Seller not found for Tx ID: ' . $tx->id . '. Using system default keys for signature verification.');
            $seller = null; // Tandai untuk gunakan default key
        }

        // Build client menggunakan kunci seller (jika ada) atau default
        $client = $this->buildTripayClient($seller);

        // Verifikasi signature
        if (!$client->verifyCallback($raw, $sig)) {
            log_message('error', '[Tripay Notify] Invalid signature for order ' . $merchantRef);
             // Return 200 OK ke Tripay tapi beri pesan error
            return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Invalid signature']);
        }
        log_message('info', '[Tripay Notify] SIGNATURE VALID for order ' . $merchantRef);


        // Map status Tripay ke status kita
        $map = [
            'paid'    => 'success', // Status 'paid' dari Tripay dianggap 'success'
            'unpaid'  => 'pending', // Status 'unpaid' dianggap 'pending'
            'failed'  => 'failed',
            'expired' => 'expired',
            // Tambahkan mapping lain jika ada status baru dari Tripay
        ];
        $newDbStatus = isset($map[$status]) ? $map[$status] : $tx->status; // PHP < 7.0 compatibility // Default to current status if unmapped/unknown

        // Jika status tidak berubah, update raw data saja dan return
        if ($newDbStatus === $tx->status) {
            log_message('info', "[Tripay Webhook] No status change needed for Order ID {$merchantRef}. Current DB Status: '{$tx->status}', Notification Status ('{$status}') maps to: '{$newDbStatus}'. Updating raw data only.");
             $this->transactionModel->update($tx->id, [
                 'tripay_raw'        => $raw, // Update raw data
                 'updated_at'        => date('Y-m-d H:i:s'),
             ]);
             return $this->response->setJSON(['success' => true])->setStatusCode(200);
        }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();
        $actionsFailed = false;

        try {
            // 1. Update status dan data Tripay lainnya
            $save = [
                'status'            => $newDbStatus,
                'tripay_reference'  => $reference ?? $tx->tripay_reference, // PHP < 7.0 compatibility // Update jika ada reference baru
                'tripay_raw'        => $raw,
                'updated_at'        => date('Y-m-d H:i:s'),
            ];

            if (!$this->transactionModel->update($tx->id, $save)) {
                 log_message('error', "[Tripay Webhook] DB STATUS UPDATE FAILED for Tx ID {$tx->id} (Order ID {$merchantRef}) from '{$tx->status}' to '{$newDbStatus}'. Model Errors: " . print_r($this->transactionModel->errors(), true));
                 throw new \Exception("DB status update failed."); // Trigger rollback
            }
             log_message('info', "[Tripay Webhook] DB Status for Order ID {$merchantRef} updated from '{$tx->status}' to '{$newDbStatus}'.");


            // 2. Jika success: jalankan mekanisme fulfilment stok
            if ($newDbStatus === 'success') {
                log_message('info', "[Tripay Webhook - ACTION] Processing success actions for Order ID {$merchantRef}...");
                if (!$this->handleSuccessfulPayment($tx->order_id)) {
                    $actionsFailed = true;
                    log_message('error', "[Tripay Webhook - ACTION FAILED] Fulfillment failed for Order ID {$merchantRef} (check handleSuccessfulPayment logs).");
                    // Jangan throw, commit status tapi log error action
                }
            }

        } catch (\Throwable $e) {
            log_message('error', '[Tripay Notify - PROCESSING EXCEPTION] Error for Order ID ' . $merchantRef . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $actionsFailed = true; // Anggap action gagal jika ada exception
            // Rollback jika terjadi exception saat proses
            $db->transRollback();
            log_message('error', "[Tripay Webhook] Transaction ROLLED BACK due to exception for Order ID: {$merchantRef}.");
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Internal Server Error during processing']); // Kirim 500 jika error internal
        }

        // --- Finalize Transaction ---
        if ($db->transStatus() !== false) {
             $db->transCommit();
             $logSuffix = $actionsFailed ? ' but ACTIONS FAILED (check logs).' : '.';
             log_message('info', "[Tripay Webhook] Transaction COMMITTED for Order ID: {$merchantRef}. Final DB Status: '{$newDbStatus}'{$logSuffix}");
        } else {
             // Rollback sudah dihandle di catch block jika ada exception
             // Jika status false tanpa exception (jarang terjadi), log rollback
             if (!$db->transStatus()) {
                 $db->transRollback();
                 log_message('error', "[Tripay Webhook] Transaction ROLLED BACK for Order ID: {$merchantRef} because transStatus was FALSE.");
             }
             return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Error during DB transaction commit, acknowledged.']); // Acknowledge Tripay
        }

        // Tripay expects JSON response {success: true} for 200 OK
        return $this->response->setJSON(['success' => true])->setStatusCode(200);
    }

    /**
     * Handles incoming Orderkuota/Zeppelin notifications (webhook).
     * NOTE: Asumsi endpoint ini adalah callback, bukan notifikasi server-to-server aktif.
     * Jika ini notifikasi S2S, perlu verifikasi signature jika ada.
     * Logika saat ini hanya mengecek ulang status via API berdasarkan reference_id dari transaksi.
     */
    public function zeppelinNotification()
    {
        // Karena dokumentasi Zeppelin API tidak menyebutkan webhook signature,
        // kita akan mengandalkan pengecekan ulang status ke API sebagai validasi.
        // Asumsi payload webhook minimal berisi reference_id

        $raw = $this->request->getBody();
        log_message('info', "[Zeppelin Webhook] Received notification. Raw input: " . $raw);

        $json = json_decode($raw, true) ?: [];
        // Use the correct field name from migration: zeppelin_reference_id
        $referenceId = isset($json['reference_id']) ? $json['reference_id'] : null; // PHP < 7.0 compatibility // Sesuaikan key jika beda

        if (!$referenceId) {
            log_message('error', "[Zeppelin Webhook] No reference_id found in payload.");
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'No reference_id']);
        }

        // Cari transaksi berdasarkan zeppelin_reference_id (correct field name) atau order_id
        $tx = $this->transactionModel->where('zeppelin_reference_id', $referenceId)->first();
        if (!$tx) {
            // Coba cari berdasarkan order_id jika ref_id = order_id saat create
            $tx = $this->transactionModel->where('order_id', $referenceId)->first();
        }

        if (!$tx) {
            log_message('error', '[Zeppelin Notify] Transaction not found for reference_id: ' . $referenceId);
            return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Transaction not found']); // Return 200 OK
        }

        // Prevent processing if transaction is already finalized (success, failed, expired)
        if (in_array($tx->status, ['success', 'failed', 'expired'])) {
            log_message('info', "[Zeppelin Webhook] Transaction {$tx->order_id} (Ref: {$referenceId}) is already finalized in DB ('{$tx->status}'). Webhook ignored.");
            return $this->response->setStatusCode(200)->setJSON(['success' => true, 'message' => 'Already processed']);
        }

        // Jika transaksi masih pending, cek ulang status ke API Zeppelin
        $newDbStatus = $tx->status; // Default ke status saat ini
        $zeppelinStatus = 'unknown'; // Initialize variable
        try {
            $client = $this->buildZeppelinClient(null); // Pakai config sistem
            $statusResp = $client->checkStatus($referenceId);

            if ($statusResp['success'] && isset($statusResp['data']['payment_status'])) {
                $zeppelinStatus = strtolower($statusResp['data']['payment_status']);
                 log_message('info', "[Zeppelin Notify] Check Status API for Ref {$referenceId} returned: {$zeppelinStatus}");

                // Map status Zeppelin ke status DB kita
                $map = [
                    'success' => 'success',
                    'pending' => 'pending',
                    'failed'  => 'failed',
                    'expired' => 'expired',
                ];
                $newDbStatus = isset($map[$zeppelinStatus]) ? $map[$zeppelinStatus] : $tx->status; // PHP < 7.0 compatibility // Fallback ke status DB jika tidak termapping

            } else {
                 log_message('error', "[Zeppelin Notify] Failed to check status via API for Ref {$referenceId}. Response: " . json_encode($statusResp));
                 // Jangan ubah status jika gagal cek API, tapi proses webhook selesai
                 return $this->response->setStatusCode(200)->setJSON(['success' => true, 'message' => 'Failed to verify status via API']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Zeppelin Notify - API Check Exception] Error for Ref ' . $referenceId . ': ' . $e->getMessage());
            // Jangan ubah status jika gagal cek API, tapi proses webhook selesai
            return $this->response->setStatusCode(200)->setJSON(['success' => true, 'message' => 'Internal error during API status check']);
        }

         // Jika status tidak berubah, update raw data saja dan return
         if ($newDbStatus === $tx->status) {
            log_message('info', "[Zeppelin Webhook] No status change needed for Order ID {$tx->order_id} (Ref: {$referenceId}). Current DB Status: '{$tx->status}', API Status ('{$zeppelinStatus}') maps to: '{$newDbStatus}'. Updating raw data only.");
             // Corrected field name
             $this->transactionModel->update($tx->id, [
                 'zeppelin_raw_response' => $raw, // Update raw data dari webhook
                 'updated_at'   => date('Y-m-d H:i:s'),
             ]);
             return $this->response->setStatusCode(200)->setJSON(['success' => true, 'message' => 'No status change']);
         }

        // Mulai transaksi database
        $db = \Config\Database::connect();
        $db->transBegin();
        $actionsFailed = false;

        try {
            // 1. Update status dan data raw dari webhook (corrected field name)
            $save = [
                'status'            => $newDbStatus,
                'zeppelin_raw_response' => $raw, // Simpan payload webhook asli
                'updated_at'        => date('Y-m-d H:i:s'),
            ];

            if (!$this->transactionModel->update($tx->id, $save)) {
                 log_message('error', "[Zeppelin Webhook] DB STATUS UPDATE FAILED for Tx ID {$tx->id} (Ref {$referenceId}) from '{$tx->status}' to '{$newDbStatus}'. Model Errors: " . print_r($this->transactionModel->errors(), true));
                 throw new \Exception("DB status update failed."); // Trigger rollback
            }
            log_message('info', "[Zeppelin Webhook] DB Status for Order ID {$tx->order_id} (Ref {$referenceId}) updated from '{$tx->status}' to '{$newDbStatus}'.");

            // 2. Jika success: jalankan mekanisme fulfilment
            if ($newDbStatus === 'success') {
                log_message('info', "[Zeppelin Webhook - ACTION] Processing success actions for Order ID {$tx->order_id}...");
                if (!$this->handleSuccessfulPayment($tx->order_id)) {
                    $actionsFailed = true;
                    log_message('error', "[Zeppelin Webhook - ACTION FAILED] Fulfillment failed for Order ID {$tx->order_id} (check handleSuccessfulPayment logs).");
                }
            }

        } catch (\Throwable $e) {
            log_message('error', '[Zeppelin Notify - PROCESSING EXCEPTION] Error for Order ID ' . $tx->order_id . ' (Ref ' . $referenceId . '): ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $actionsFailed = true;
            $db->transRollback();
            log_message('error', "[Zeppelin Webhook] Transaction ROLLED BACK due to exception for Order ID: {$tx->order_id}.");
            // Return 200 OK agar tidak di-retry terus, tapi log error
            return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Internal Server Error during processing']);
        }

        // --- Finalize Transaction ---
        if ($db->transStatus() !== false) {
             $db->transCommit();
             $logSuffix = $actionsFailed ? ' but ACTIONS FAILED (check logs).' : '.';
             log_message('info', "[Zeppelin Webhook] Transaction COMMITTED for Order ID: {$tx->order_id} (Ref {$referenceId}). Final DB Status: '{$newDbStatus}'{$logSuffix}");
        } else {
             if (!$db->transStatus()) { // Cek lagi jika belum di-rollback oleh exception
                 $db->transRollback();
                 log_message('error', "[Zeppelin Webhook] Transaction ROLLED BACK for Order ID: {$tx->order_id} because transStatus was FALSE.");
             }
             return $this->response->setStatusCode(200)->setJSON(['success' => false, 'message' => 'Error during DB transaction commit, acknowledged.']);
        }

        // Response standar OK
        return $this->response->setStatusCode(200)->setJSON(['success' => true]);
    }

    // =========================================================================
    // G. MANUAL CHECK STATUS ENDPOINT (for Orderkuota/Zeppelin)
    // =========================================================================

    /**
     * Endpoint for AJAX call from frontend to check Orderkuota/Zeppelin status.
     * Requires referenceId via POST.
     */
    public function checkOrderkuotaStatus()
    {
        if (!$this->request->isAJAX()) {
            return $this->failForbidden('Invalid request type.');
        }

        $referenceId = $this->request->getPost('referenceId');
        if (empty($referenceId)) {
            return $this->failValidationErrors(['referenceId' => 'Reference ID is required.']);
        }

        // Cari transaksi berdasarkan zeppelin_reference_id (correct field name) atau order_id
        $tx = $this->transactionModel->where('zeppelin_reference_id', $referenceId)
                           ->orWhere('order_id', $referenceId) // fallback jika ref_id == order_id
                           ->first();

        if (!$tx) {
            log_message('warning', '[Check Status API] Transaction not found for Ref ID: ' . $referenceId);
            return $this->failNotFound('Transaction not found.');
        }

        // Jika sudah final, langsung kembalikan status DB
        if (in_array($tx->status, ['success', 'failed', 'expired'])) {
            return $this->respond(['status' => $tx->status]);
        }

        // Jika masih pending, cek ulang ke API Zeppelin
        $zeppelinStatus = 'unknown'; // Initialize variable
        try {
            $client = $this->buildZeppelinClient(null); // Pakai config sistem
            $statusResp = $client->checkStatus($referenceId);

            if ($statusResp['success'] && isset($statusResp['data']['payment_status'])) {
                $zeppelinStatus = strtolower($statusResp['data']['payment_status']);
                log_message('info', "[Check Status API] API check for Ref {$referenceId} returned: {$zeppelinStatus}");

                // Map status Zeppelin ke status DB kita
                $map = [
                    'success' => 'success',
                    'pending' => 'pending',
                    'failed'  => 'failed',
                    'expired' => 'expired',
                ];
                $newDbStatus = isset($map[$zeppelinStatus]) ? $map[$zeppelinStatus] : $tx->status; // PHP < 7.0 compatibility

                // Jika status berubah menjadi final (success, failed, expired) dari API
                if ($newDbStatus !== $tx->status && in_array($newDbStatus, ['success', 'failed', 'expired'])) {
                     log_message('info', "[Check Status API] Status changed for Ref {$referenceId} from DB '{$tx->status}' to API '{$newDbStatus}'. Updating DB...");

                     // Mulai transaksi DB untuk update status dan jalankan fulfillment jika success
                     $db = \Config\Database::connect();
                     $db->transBegin();
                     $actionsFailed = false;

                     $updateData = ['status' => $newDbStatus, 'updated_at' => date('Y-m-d H:i:s')];
                     if (!$this->transactionModel->update($tx->id, $updateData)) {
                         log_message('error', "[Check Status API] DB STATUS UPDATE FAILED for Tx ID {$tx->id} from '{$tx->status}' to '{$newDbStatus}'.");
                         $db->transRollback();
                         return $this->failServerError('Failed to update transaction status.');
                     }

                     if ($newDbStatus === 'success') {
                         if (!$this->handleSuccessfulPayment($tx->order_id)) {
                             $actionsFailed = true;
                             log_message('error', "[Check Status API - ACTION FAILED] Fulfillment failed for Order ID {$tx->order_id} during manual check.");
                             // Jangan rollback, status tetap success tapi action gagal
                         }
                     }

                     if ($db->transStatus() === false) {
                         $db->transRollback();
                         return $this->failServerError('Database transaction failed during status update.');
                     } else {
                         $db->transCommit();
                         $logSuffix = $actionsFailed ? ' but ACTIONS FAILED.' : '.';
                         log_message('info', "[Check Status API] Transaction COMMITTED for Order ID {$tx->order_id}. Final Status: '{$newDbStatus}'.{$logSuffix}");
                         return $this->respond(['status' => $newDbStatus]); // Kembalikan status baru
                     }

                } else {
                     // Status dari API tidak berubah atau belum final
                     return $this->respond(['status' => $tx->status]); // Kembalikan status DB saat ini
                }

            } else {
                 log_message('error', "[Check Status API] Failed API call for Ref {$referenceId}. Response: " . json_encode($statusResp));
                 return $this->failServerError('Failed to check status with payment gateway.');
            }
        } catch (\Throwable $e) {
            log_message('error', '[Check Status API - Exception] Error for Ref ' . $referenceId . ': ' . $e->getMessage());
            return $this->failServerError('Internal server error during status check.');
        }
    }


    // =========================================================================
    // H. HELPER EMAIL METHODS (Send Product & Stock Alert)
    // =========================================================================

    /**
     * Send Product Email with JSON data handling
     */
    private function sendProductEmail(object $transaction, string $productName, array $stockDataJsonArray, string $buyerName, int $quantity): bool
    {
        $decodedStockDataArray = [];
        $hasJsonError = false;
        foreach ($stockDataJsonArray as $jsonString) {
            $decoded = json_decode($jsonString);
            if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                $decodedStockDataArray[] = $decoded; // Add the decoded object
            } else {
                log_message('error', "[Email Prep] Failed to decode JSON stock data for Order ID {$transaction->order_id}. Raw: {$jsonString}. Error: " . json_last_error_msg());
                $decodedStockDataArray[] = (object)['raw_data' => $jsonString, 'error' => 'Format JSON tidak valid']; // Send an object indicating error
                $hasJsonError = true;
            }
        }

        if ($hasJsonError) {
             log_message('warning', "[Email Send] Sending email for Order ID {$transaction->order_id} with potential JSON format errors in stock data.");
        }

        if (empty($decodedStockDataArray)) {
             log_message('error', "[Email Send] No valid stock data to send for Order ID {$transaction->order_id}. Email not sent.");
             return false; // Prevent sending email with no data
        }

        try {
            $emailService = Services::email();
            $emailConfig = config('Email'); // Load from Config/Email.php

            // Override config with .env values
            $fromEmail = env('email.fromEmail', $emailConfig->fromEmail ?: 'no-reply@example.com');
            $fromName = env('email.fromName', $emailConfig->fromName ?: 'Your Store Name');
            $emailConfig->protocol = env('email.protocol', $emailConfig->protocol);
            $emailConfig->SMTPHost = env('email.SMTPHost', $emailConfig->SMTPHost);
            $emailConfig->SMTPPort = env('email.SMTPPort', $emailConfig->SMTPPort);
            $emailConfig->SMTPUser = env('email.SMTPUser', $emailConfig->SMTPUser);
            $emailConfig->SMTPPass = env('email.SMTPPass', $emailConfig->SMTPPass);
            $emailConfig->SMTPCrypto = env('email.SMTPCrypto', $emailConfig->SMTPCrypto);

            // Set mandatory settings from Config/Email.php (already set to HTML)
            $emailService->initialize((array)$emailConfig);

            $emailService->setTo($transaction->buyer_email);
            $emailService->setFrom($fromEmail, $fromName);
            $subjectQuantity = ($quantity > 1) ? " ({$quantity} Item)" : "";
            $emailService->setSubject("Produk Anda: {$productName}{$subjectQuantity}");

            $message = view('emails/product_delivery', [
                'buyer_name'          => $buyerName,
                'product_name'        => $productName,
                'decodedStockDataArray' => $decodedStockDataArray,
                'quantity'            => $quantity,
            ]);
            $emailService->setMessage($message);

            // Check SMTP credentials before sending
            if ($emailConfig->protocol === 'smtp' && (empty($emailConfig->SMTPUser) || empty($emailConfig->SMTPPass))) {
                 log_message('error', "[Email Error] SMTP credentials (User/Pass) are missing in configuration for Order ID {$transaction->order_id}. Email not sent.");
                 return false;
            }

            if ($emailService->send()) {
                log_message('info', "[Email Success] Product email '{$productName}' (Qty: {$quantity}) sent to {$transaction->buyer_email} (Order ID: {$transaction->order_id}).");
                return true;
            } else {
                log_message('error', "[Email Error] Failed to send product email to {$transaction->buyer_email} (Order ID: {$transaction->order_id}). Debug: " . $emailService->printDebugger(['headers', 'subject', 'body']));
                return false;
            }
        } catch (\Exception $e) {
             log_message('error', "[Email Exception] Error sending product email for Order ID {$transaction->order_id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             return false;
        }
    }

    /**
     * Sends an alert email to the seller about stock issues.
     */
    private function sendStockAlertEmailToSeller(object $transaction, string $reason = "Stok produk habis", ?int $variantId = null, int $quantityNeeded = 1): bool
    {
        $seller = $this->userModel->find($transaction->user_id);
        if (!$seller || !$seller->email) {
            log_message('error', "[Stock Alert Email] Seller not found or has no email for user ID: {$transaction->user_id}");
            return false;
        }
        $product = $this->productModel->find($transaction->product_id);
        $productName = $product ? $product->product_name : 'Produk Tidak Dikenal (ID: ' . $transaction->product_id . ')';
        $variantName = '';
        // FIX: Replace ?? with isset() for PHP < 7.0 compatibility
        // $variantNameFromTx = $transaction->variant_name ?? null; // Use saved variant name
        $variantNameFromTx = isset($transaction->variant_name) ? $transaction->variant_name : null; // PHP < 7.0 compatibility
        if ($variantId && $variantNameFromTx) {
            $variantName = ' (Varian: ' . $variantNameFromTx . ')';
        } elseif ($variantId) { // Fallback if name wasn't saved in tx
             $variant = $this->productVariantModel->find($variantId);
             if ($variant) $variantName = ' (Varian: ' . $variant->name . ')';
        }

         try {
            $emailService = Services::email();
            $emailConfig = config('Email');
            // Override with .env
            $fromEmail = env('email.fromEmail', $emailConfig->fromEmail ?: 'no-reply@example.com');
            $fromName = env('email.fromName', $emailConfig->fromName ?: 'Sistem Notifikasi');
            $emailConfig->protocol = env('email.protocol', $emailConfig->protocol);
            $emailConfig->SMTPHost = env('email.SMTPHost', $emailConfig->SMTPHost);
            $emailConfig->SMTPPort = env('email.SMTPPort', $emailConfig->SMTPPort);
            $emailConfig->SMTPUser = env('email.SMTPUser', $emailConfig->SMTPUser);
            $emailConfig->SMTPPass = env('email.SMTPPass', $emailConfig->SMTPPass);
            $emailConfig->SMTPCrypto = env('email.SMTPCrypto', $emailConfig->SMTPCrypto);

            // Use text format for alert
            $emailConfig->mailType = 'text';
            $emailService->initialize((array)$emailConfig);

            $emailService->setTo($seller->email);
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setSubject("PENTING: Peringatan Stok/Proses Produk {$productName}{$variantName}");

            $message = "Halo {$seller->username},\n\n";
            $message .= "Terjadi masalah penting terkait produk '{$productName}{$variantName}' Anda.\n\n";
            $message .= "Detail Masalah: {$reason}\n\n";
            $message .= "Detail Pesanan Terkait (jika ada):\n";
            $message .= "- Order ID: {$transaction->order_id}\n";
            $buyerNameClean = $transaction->buyer_name;
            $message .= "- Pembeli: {$buyerNameClean} ({$transaction->buyer_email})\n";
            $message .= "- Jumlah: Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
            $message .= "- Kuantitas Dibeli: {$quantityNeeded}\n\n";
            $message .= "Mohon segera periksa dashboard Anda dan ambil tindakan yang diperlukan.\n";
            if($product) {
                $stockManageLink = $product->has_variants
                    ? route_to('product.stock.manage', $product->id) // Link ke daftar varian
                    : route_to('product.stock.manage', $product->id); // Link ke stok non-varian
                if ($variantId) {
                     $stockManageLink = route_to('product.variant.stock.items', $product->id, $variantId); // Link ke item stok varian
                }
                $message .= "Link Kelola Stok/Varian: " . $stockManageLink . "\n\n";
            }
            $message .= "Terima kasih,\nSistem Notifikasi";
            $emailService->setMessage($message);

             // Cek kredensial SMTP
            if ($emailConfig->protocol === 'smtp' && (empty($emailConfig->SMTPUser) || empty($emailConfig->SMTPPass))) {
                 log_message('error', "[Stock Alert Email Error] SMTP credentials missing. Alert for Order ID {$transaction->order_id} not sent to {$seller->email}.");
                 return false;
            }

            if ($emailService->send()) {
                log_message('info', "[Stock Alert Email] Alert sent to seller {$seller->email} for product ID {$transaction->product_id}{$variantName} (Order ID: {$transaction->order_id}). Reason: {$reason}");
                return true;
            } else {
                log_message('error', "[Stock Alert Email Error] Failed to send alert email to {$seller->email}. Debug: " . $emailService->printDebugger(['headers', 'subject', 'body']));
                return false;
            }
         } catch (\Exception $e) {
             log_message('error', "[Email Exception] Error sending stock alert email for Order ID {$transaction->order_id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             return false;
         }
    }

} // End Class
