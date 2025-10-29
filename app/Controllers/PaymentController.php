<?php

namespace App\Controllers;

// ... (Use statements remain the same) ...
use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\TransactionModel;
use App\Models\ProductStockModel;
use Config\Midtrans as MidtransConfig;
use Config\Services;
use Midtrans\Config as MidtransApiConfig;
use Midtrans\Snap;
use Midtrans\Notification;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Throwable;

use App\Libraries\TripayClient; // <--- NEW IMPORT

class PaymentController extends BaseController
{
    // ... (Properties and __construct remain the same) ...
    protected UserModel $userModel;
    protected ProductModel $productModel;
    protected ProductVariantModel $productVariantModel; // Ditambahkan
    protected TransactionModel $transactionModel;
    protected ProductStockModel $productStockModel;
    protected MidtransConfig $defaultMidtransConfig; // Simpan default config
    protected $helpers = ['url', 'text', 'number', 'security'];

    public function __construct()
    {
        $this->userModel        = new UserModel();
        $this->productModel     = new ProductModel();
        $this->productVariantModel = new ProductVariantModel(); // Inisialisasi
        $this->transactionModel = new TransactionModel();
        $this->productStockModel = new ProductStockModel();
        $this->defaultMidtransConfig = new MidtransConfig(); // Load default config
        $this->configureMidtrans(); // Set initial config (default)
    }

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
     * @return string 'midtrans', 'tripay', atau 'system'.
     */
    private function resolveGatewayForSeller(object $seller): string
    {
        // pilihan seller
        $pref = $seller->gateway_active ?? 'system';

        if ($pref === 'midtrans') {
            return (!empty($seller->midtrans_server_key) && !empty($seller->midtrans_client_key)) ? 'midtrans' : 'system';
        }
        if ($pref === 'tripay') {
            return (!empty($seller->tripay_api_key) && !empty($seller->tripay_private_key) && !empty($seller->tripay_merchant_code)) ? 'tripay' : 'system';
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
        return in_array($def, ['midtrans','tripay'], true) ? $def : 'midtrans';
    }

    // =========================================================================
    // B. TRIPAY CLIENT BUILDER
    // =========================================================================

    /**
     * Membangun TripayClient, menggunakan kunci penjual jika tersedia, atau kembali ke kunci konfigurasi default.
     * @param object|null $seller Data user (penjual/pembeli premium) atau null.
     * @return \App\Libraries\TripayClient
     */
    private function buildTripayClient(?object $seller = null): TripayClient
    {
        $cfgUser = [
            'apiKey'       => $seller->tripay_api_key ?? '',
            'privateKey'   => $seller->tripay_private_key ?? '',
            'merchantCode' => $seller->tripay_merchant_code ?? '',
            'isProduction' => (bool) env('TRIPAY_IS_PRODUCTION', false),
        ];

        $useUser = !empty($cfgUser['apiKey']) && !empty($cfgUser['privateKey']) && !empty($cfgUser['merchantCode']);

        if (!$useUser) {
            // Asumsi class Config\Tripay ada
            $sys = new \Config\Tripay();
            $cfgUser = [
                'apiKey'       => $sys->apiKey,
                'privateKey'   => $sys->privateKey,
                'merchantCode' => $sys->merchantCode,
                'isProduction' => $sys->isProduction,
            ];
        }

        return new TripayClient($cfgUser);
    }

    // =========================================================================
    // C. PAY FOR PREMIUM (MODIFIED)
    // =========================================================================

    public function payForPremium()
    {
        // ... (Initial checks) ...
        if (! $this->request->isAJAX()) {
            log_message('error', '[PaymentController] payForPremium accessed non-AJAX.');
            return $this->response->setStatusCode(403, 'Forbidden Action.');
        }

        $userId = session()->get('user_id');
        if (!$userId) {
            log_message('error', '[PaymentController] payForPremium: User ID not found in session.');
            return $this->response->setJSON(['error' => 'Sesi tidak valid. Silakan login ulang.'])->setStatusCode(401);
        }
        $user = $this->userModel->find($userId); // $user will be used as $seller for gateway resolution

        if (! $user) {
            log_message('error', '[PaymentController] payForPremium: User not found. ID: ' . $userId);
            return $this->response->setJSON(['error' => 'User not found.'])->setStatusCode(404);
        }

        if ($user->is_premium) {
             log_message('notice', '[PaymentController] payForPremium: User already premium. ID: ' . $userId);
            return $this->response->setJSON(['error' => 'Anda sudah menjadi pengguna premium.'])->setStatusCode(400);
        }

        // --- GATEWAY RESOLUTION START ---
        $resolved = $this->resolveGatewayForSeller($user);
        if ($resolved === 'system') {
            $resolved = $this->systemDefaultGateway();
        }

        // Premium always uses default keys for Midtrans if selected
        $useUserKeys = false; // Always false for premium
        if ($resolved === 'midtrans') {
            $this->configureMidtrans(); // Use default config for Midtrans
        }
        // --- GATEWAY RESOLUTION END ---


        $premiumPrice = 100000;
        $orderId = 'PREM-' . $userId . '-' . time() . '-' . strtoupper(random_string('alnum', 4));

        $transactionDetails = ['order_id' => $orderId, 'gross_amount' => $premiumPrice];
        $itemDetails = [['id' => 'UPGRADE_PREMIUM_' . $userId, 'price' => $premiumPrice, 'quantity' => 1, 'name' => 'Upgrade Akun Premium Repo.ID']];
        $customerDetails = ['first_name' => $user->username, 'email' => $user->email];

        $db = \Config\Database::connect();
        $db->transBegin();

        $txData = [
            'order_id'         => $orderId,
            'user_id'          => $userId, // User yang melakukan upgrade (Seller ID is the system)
            'product_id'       => null,
            'variant_id'       => null,
            'variant_name'     => null,
            'buyer_name'       => $user->username,
            'buyer_email'      => $user->email,
            'transaction_type' => 'premium',
            'amount'           => $premiumPrice,
            'status'           => 'pending',
            'payment_gateway'  => $resolved, // <--- MODIFIED
            'midtrans_key_source' => ($resolved === 'midtrans') // <--- MODIFIED
                ? 'default' 
                : 'default', // Default if not midtrans
            'quantity'         => 1,
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
                    log_message('error', '[PaymentController] payForPremium: Failed to update snap_token for Order ID: ' . $orderId);
                    return $this->response->setJSON(['error' => 'Gagal memperbarui token pembayaran.'])->setStatusCode(500);
                }

                if ($db->transStatus() === false) {
                     $db->transRollback();
                     log_message('error', '[PaymentController] payForPremium: Transaction failed before sending token. Order ID: ' . $orderId);
                     return $this->response->setJSON(['error' => 'Terjadi kesalahan database.'])->setStatusCode(500);
                } else {
                     $db->transCommit();
                    log_message('info', '[PaymentController] payForPremium: Snap Token generated successfully for Order ID: ' . $orderId);
                    return $this->response->setJSON(['token' => $snapToken]);
                }

            } catch (\Exception $e) {
                 $db->transRollback();
                log_message('error', '[Midtrans Error - payForPremium] Order ID: ' . $orderId . ' Error: ' . $e->getMessage());
                return $this->response->setJSON(['error' => 'Gagal membuat token pembayaran. Silakan coba beberapa saat lagi. Detail: ' . $e->getMessage()])->setStatusCode(500);
            } finally {
                $this->configureMidtrans(); // Reset back to default keys after request
            }
        }

        // --- TRIPAY BLOCK ---
        if ($resolved === 'tripay') {
            $client = $this->buildTripayClient($user); // Use $user as $seller (to get system/default keys)

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
                $data = $resp['data'] ?? [];
                $this->transactionModel->update($txId, [
                    'tripay_reference' => $data['reference'] ?? null,
                    'tripay_pay_url'   => $data['checkout_url'] ?? null,
                    'tripay_raw'       => json_encode($resp),
                ]);
                $db->transCommit();

                return $this->response->setJSON([
                    'gateway' => 'tripay',
                    'status'  => 'ok',
                    'reference'   => $data['reference'] ?? null,
                    'checkoutUrl' => $data['checkout_url'] ?? null,
                    'qrUrl'       => $data['qr_url'] ?? null,
                    'orderId'     => $orderId,
                ]);
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Tripay Create - Premium] ' . $e->getMessage() . ' Payload: ' . json_encode($payload));
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Tripay untuk Premium.'])->setStatusCode(500);
            }
        }
        
        // If resolution fails somehow (shouldn't happen with systemDefaultGateway)
        $db->transRollback();
        return $this->response->setJSON(['error' => 'Gateway pembayaran tidak valid atau tidak tersedia.'])->setStatusCode(500);
    }

    // =========================================================================
    // C. PAY FOR PRODUCT (MODIFIED)
    // =========================================================================

    public function payForProduct()
    {
        // ... (Initial checks and product/stock validation remain the same) ...
        if (! $this->request->isAJAX()) {
             log_message('error', '[PaymentController] payForProduct accessed non-AJAX.');
            return $this->response->setStatusCode(403, 'Forbidden Action.');
        }

        $json = $this->request->getJSON();
        $productId = filter_var($json->productId ?? null, FILTER_VALIDATE_INT);
        $variantId = filter_var($json->variantId ?? null, FILTER_VALIDATE_INT); // Null if not provided or invalid
        $buyerName = trim(strip_tags($json->name ?? ''));
        $buyerEmail = filter_var(trim($json->email ?? ''), FILTER_VALIDATE_EMAIL);
        $quantity = filter_var($json->quantity ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

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
        $productNameForMidtrans = $product->product_name; // Name sent to Midtrans
        $productNameForDb = $product->product_name; // Name stored in DB transaction (can include variant later)
        $isVariantSale = false;
        $availableStock = 0;
        $variant = null; // Initialize variant object
        $itemIdForMidtrans = (string)$product->id; // Default item ID for Midtrans
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
            $productNameForMidtrans = $product->product_name . ' - ' . $variant->name;
            $productNameForDb = $productNameForMidtrans; // Use combined name for DB too
            $isVariantSale = true;
            $availableStock = $this->productStockModel->getAvailableStockCountForVariant($variantId);
            $itemIdForMidtrans = 'P' . $product->id . '_V' . $variantId;
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

        // --- NEW GATEWAY RESOLUTION START (C. point) ---
        $resolved = $this->resolveGatewayForSeller($seller);
        if ($resolved === 'system') {
            $resolved = $this->systemDefaultGateway();
        }

        // Determine which Midtrans keys to use (ONLY if Midtrans is selected)
        $useUserKeys = (!empty($seller->midtrans_server_key) && !empty($seller->midtrans_client_key));

        // Configure Midtrans is only done if $resolved === 'midtrans'
        if ($resolved === 'midtrans') {
            $keySource = $useUserKeys ? 'user' : 'default';

            // Configure Midtrans with the correct keys for THIS request
            $this->configureMidtrans(
                $useUserKeys ? $seller->midtrans_server_key : null,
                $useUserKeys ? $seller->midtrans_client_key : null
            );
        }
        // --- END GATEWAY RESOLUTION / MIDTRANS CONFIG PREP ---

        $orderId = 'PROD-' . $product->id . '-' . ($isVariantSale ? $variantId . '-' : '') . time() . '-' . strtoupper(random_string('alnum', 4));
        $totalAmount = $pricePerItem * $quantity;
        $transactionDetails = ['order_id' => $orderId, 'gross_amount' => (int) $totalAmount];

        // Item details for Midtrans/Tripay
        $itemDetails = [[
            'id'       => $itemIdForMidtrans,
            'price'    => (int) $pricePerItem,
            'quantity' => $quantity,
            'name'     => substr($productNameForMidtrans, 0, 50) // Midtrans/Tripay name limit
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
            'buyer_name'          => $buyerName, // Store original buyer name
            'buyer_email'         => $buyerEmail,
            'transaction_type'    => 'product',
            'amount'              => $totalAmount,
            'quantity'            => $quantity,
            'status'              => 'pending',
            'payment_gateway'     => $resolved, // <--- MODIFIED
            'midtrans_key_source' => ($resolved === 'midtrans') // <--- MODIFIED
                ? ($useUserKeys ? 'user' : 'default')
                : 'default',
        ];

        $saveResult = $this->transactionModel->save($txData);
        $txId = $this->transactionModel->getInsertID();

        if (!$saveResult || !$txId) {
            $db->transRollback();
            log_message('error', '[PaymentController] payForProduct: Failed to save initial product transaction to DB. Order ID: ' . $orderId . ' Errors: ' . print_r($this->transactionModel->errors(), true));
            $this->configureMidtrans(); // Reset keys in case they were set
            return $this->response->setJSON(['error' => 'Gagal mencatat transaksi awal.'])->setStatusCode(500);
        }

        // --- MIDTRANS BLOCK (Existing logic wrapped) ---
        if ($resolved === 'midtrans') {
            $payload = [
                'transaction_details' => $transactionDetails,
                'item_details'        => $itemDetails,
                'customer_details'    => $customerDetails,
                'callbacks' => ['finish' => route_to('profile.public', $seller->username) . '?payment_attempt=' . $orderId]
            ];

            log_message('debug', '[PaymentController] payForProduct: Payload for Snap Token Generation (Order ID: ' . $orderId . '): ' . json_encode($payload));

            try {
                $snapToken = Snap::getSnapToken($payload);
                $updateTokenResult = $this->transactionModel->update($txId, ['snap_token' => $snapToken]);

                if (!$updateTokenResult) {
                    $db->transRollback();
                    log_message('error', '[PaymentController] payForProduct: Failed to update snap_token for Order ID: ' . $orderId);
                    $this->configureMidtrans(); // Reset keys
                    return $this->response->setJSON(['error' => 'Gagal memperbarui token pembayaran.'])->setStatusCode(500);
                }

                if ($db->transStatus() === false) {
                     $db->transRollback();
                     log_message('error', '[PaymentController] payForProduct: Transaction failed before sending token. Order ID: ' . $orderId);
                     $this->configureMidtrans(); // Reset keys
                     return $this->response->setJSON(['error' => 'Terjadi kesalahan database.'])->setStatusCode(500);
                } else {
                     $db->transCommit();
                    log_message('info', "[PaymentController] payForProduct: Snap Token generated successfully for Order ID: {$orderId} (Qty: {$quantity}) using {$keySource} keys. Variant ID: " . ($variantId ?? 'N/A'));
                    $this->configureMidtrans(); // Reset keys
                    return $this->response->setJSON(['token' => $snapToken]);
                }

            } catch (\Exception $e) {
                $db->transRollback();
                log_message('error', "[Midtrans Error - payForProduct] Order ID: {$orderId} (Qty: {$quantity}) using {$keySource} keys. Error: " . $e->getMessage() . " Payload: " . json_encode($payload));
                $this->configureMidtrans(); // Reset keys
                $errorMessage = 'Gagal membuat token pembayaran. Silakan coba beberapa saat lagi.';
                if (ENVIRONMENT !== 'production') {
                    $errorMessage .= ' Detail: ' . $e->getMessage();
                }
                return $this->response->setJSON(['error' => $errorMessage])->setStatusCode(500);
            }
        }
        
        // --- TRIPAY BLOCK (C. point) ---
        if ($resolved === 'tripay') {
            $client = $this->buildTripayClient($seller);

            // Tentukan method: kamu bisa default ke 'QRIS' atau expose pilihan channel di UI pembelian
            $method = 'QRIS'; // contoh default
            $callbackUrl = base_url('payment/tripay/notify');
            $returnUrl   = base_url('dashboard/transactions'); // atau halaman sukses kamu
            
            // Reformat itemDetails for Tripay (matching the structure we created above)
            $payload = [
                'method'         => $method,
                'merchant_ref'   => $orderId,
                'amount'         => (int) $totalAmount,
                'customer_name'  => $buyerName,
                'customer_email' => $buyerEmail,
                'order_items'    => [[
                    'sku'      => (string) $itemIdForMidtrans,
                    'name'     => substr($productNameForMidtrans, 0, 50),
                    'price'    => (int) $pricePerItem,
                    'quantity' => (int) $quantity
                ]],
                'return_url'  => $returnUrl,
                'callback_url'=> $callbackUrl,
                // 'expired_time' => time() + 24*3600, // optional
            ];

            try {
                $resp = $client->createTransaction($payload);
                // contoh response penting: data.reference, data.checkout_url, data.qr_url dsb
                $data = $resp['data'] ?? [];
                
                // Save Tripay specific data
                $this->transactionModel->update($txId, [
                    'tripay_reference' => $data['reference'] ?? null,
                    'tripay_pay_url'   => $data['checkout_url'] ?? null,
                    'tripay_raw'       => json_encode($resp),
                ]);
                $db->transCommit();

                return $this->response->setJSON([
                    'gateway' => 'tripay',
                    'status'  => 'ok',
                    'reference'   => $data['reference'] ?? null,
                    'checkoutUrl' => $data['checkout_url'] ?? null,
                    'qrUrl'       => $data['qr_url'] ?? null, // bila method QR
                    'orderId'     => $orderId,
                ]);
            } catch (\Throwable $e) {
                $db->transRollback();
                log_message('error', '[Tripay Create] ' . $e->getMessage() . ' Payload: ' . json_encode($payload));
                return $this->response->setJSON(['error' => 'Gagal membuat transaksi Tripay.'])->setStatusCode(500);
            }
        }
        
        // If no gateway resolved (which shouldn't happen based on resolveGatewayForSeller logic), return error
        $db->transRollback();
        return $this->response->setJSON(['error' => 'Gateway pembayaran tidak valid atau tidak tersedia.'])->setStatusCode(500);
    }

    // =========================================================================
    // REFACTOR: COMMON FULFILLMENT LOGIC
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
        
        if (!$transaction || $transaction->status !== 'success') {
            log_message('error', "[Handle Success] Order ID {$orderId} not found or status is not 'success' for fulfillment.");
            return false;
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
            $quantityNeeded = (int) ($transaction->quantity ?? 1);
            $variantId = $transaction->variant_id ?? null; // Retrieve variant_id
            $isVariantSale = ($variantId !== null);
            $buyerNameClean = $transaction->buyer_name;

            // 1. Fetch stock items
            // We assume getAvailableStockItems will only return unused items.
            $stockItems = $isVariantSale
                ? $this->productStockModel->getAvailableStockItemsForVariant($variantId, $quantityNeeded)
                : $this->productStockModel->getAvailableStockItemsForNonVariant($transaction->product_id, $quantityNeeded);

            if ($stockItems === null || count($stockItems) < $quantityNeeded) {
                $foundStockCount = ($stockItems === null) ? 0 : count($stockItems);
                log_message('critical', "[Handle Success - ACTION FAILED] Insufficient stock found for product ID {$transaction->product_id} on order {$orderId}! Needed: {$quantityNeeded}, Found: {$foundStockCount}.");
                $this->sendStockAlertEmailToSeller($transaction, "STOK TIDAK CUKUP ({$foundStockCount} tersedia) saat pesanan {$orderId} dikonfirmasi berhasil! Hubungi pembeli ({$transaction->buyer_email}).", $variantId, $quantityNeeded);
                $actionsFailed = true;
            } else {
                $stockItemIds = array_column($stockItems, 'id');
                $allStockDataJson = array_column($stockItems, 'stock_data');

                // 2. Mark stocks as used (Passing the correct transaction ID for tracking)
                if ($this->productStockModel->markMultipleStocksAsUsed($stockItemIds, $transaction->buyer_email, $transaction->id)) {

                    // 3. Synchronize variant stock
                    if ($isVariantSale) {
                        if (!$this->productVariantModel->synchronizeStock($variantId, $this->productStockModel)) {
                            log_message('error', "[Handle Success - ACTION FAILED] Failed sync variant stock count for Variant ID: {$variantId}, Order ID {$orderId}.");
                            $actionsFailed = true;
                        }
                    }

                    // 4. Add balance to seller
                    if (!$actionsFailed) {
                        if ($this->userModel->addBalance($transaction->user_id, (float)$transaction->amount)) {

                            // 5. Prepare and Send product email
                            $product = $this->productModel->find($transaction->product_id);
                            $productDisplayName = $product ? $product->product_name : 'Produk Tidak Ditemukan';
                            $variantNameFromTx = $transaction->variant_name ?? null;
                            if ($isVariantSale && $variantNameFromTx) {
                                 $productDisplayName .= ' - ' . $variantNameFromTx;
                            }

                            if (!$this->sendProductEmail($transaction, $productDisplayName, $allStockDataJson, $buyerNameClean, $quantityNeeded)) {
                                 $actionsFailed = true;
                                 log_message('error', "[Handle Success - ACTION FAILED] Failed to send product email for Order ID: {$orderId}.");
                            }

                        } else {
                            log_message('error', "[Handle Success - ACTION FAILED] Failed to add balance to user {$transaction->user_id} for order {$orderId}.");
                            $actionsFailed = true;
                        }
                    }

                } else { // Failed to mark stock as used
                    log_message('error', "[Handle Success - ACTION FAILED] Failed to mark stock IDs as used for order {$orderId}.");
                    $actionsFailed = true;
                    $this->sendStockAlertEmailToSeller($transaction, "CRITICAL: Gagal menandai stok terpakai (ID: " . implode(', ', $stockItemIds) . ") setelah pembayaran sukses. SALDO TIDAK DITAMBAHKAN.", $variantId, $quantityNeeded);
                }
            }
        }
        
        log_message('info', "[Handle Success] Order ID {$orderId} finished. Actions " . ($actionsFailed ? 'FAILED.' : 'SUCCEEDED.'));
        return !$actionsFailed;
    }


    /**
     * Handles incoming Midtrans notifications (webhook). (MODIFIED)
     */
    public function notificationHandler()
    {
        // ... (Logika awal untuk validasi signature dan fetch transaction tetap sama) ...
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
            $orderId = $initialNotificationCheck->order_id ?? null; // Assign to $orderId

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
            $transactionStatus = $notif->transaction_status ?? null;
            $fraudStatus       = $notif->fraud_status ?? null;
            $paymentType       = $notif->payment_type ?? 'unknown';

            // Log successful validation
            log_message('info', "[Midtrans Webhook] SIGNATURE VALID for Order ID: {$orderId} using key source '{$transaction->midtrans_key_source ?? 'unknown'}'. Processing Status: {$transactionStatus}, Type: {$paymentType}, Fraud: {$fraudStatus}");

        } catch (\Exception $e) {
            // Log detailed error
            $keySourceInfo = $transaction ? "'{$transaction->midtrans_key_source}'" : 'Unknown (transaction not found)';
            log_message('error', "[Midtrans Notification Error - Validation/Parse] Order ID: {$orderId}. Using key source {$keySourceInfo}. Error: " . $e->getMessage() . " Raw Body: " . $this->request->getBody());
            $this->configureMidtrans(); // Reset to default config
            $httpStatusCode = (strpos(strtolower($e->getMessage()), 'signature') !== false) ? 403 : 400;
            return $this->response->setStatusCode($httpStatusCode, $e->getMessage()); // Return appropriate error
        }

        // --- Processing Logic (after successful signature validation) ---

        if (!$transaction) {
            log_message('error', "[Midtrans Webhook] Processing aborted: Transaction {$orderId} not found in database (checked again after validation).");
            $this->configureMidtrans(); // Reset config
            return $this->response->setStatusCode(200, 'Transaction not found, acknowledged.'); // Acknowledge Midtrans
        }

        // Prevent processing if transaction is already finalized and successful
        if (in_array($transaction->status, ['success', 'failed', 'expired'])) { // Check against DB final statuses
            log_message('info', "[Midtrans Webhook] Transaction {$orderId} is already finalized in DB ('{$transaction->status}'). Webhook ignored.");
            $this->configureMidtrans(); // Reset config
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
                $updateData = ['status' => $newDbStatus];
                if (!$this->transactionModel->update($transaction->id, $updateData)) {
                    log_message('error', "[Midtrans Webhook] DB STATUS UPDATE FAILED for Tx ID {$transaction->id} (Order ID {$orderId}) from '{$transaction->status}' to '{$newDbStatus}'. Model Errors: " . print_r($this->transactionModel->errors(), true));
                    $statusUpdateSuccess = false; // Status update itself failed
                    throw new \Exception("DB status update failed."); // Throw to trigger rollback
                } else {
                    log_message('info', "[Midtrans Webhook] DB Status for Order ID {$orderId} updated from '{$transaction->status}' to '{$newDbStatus}'.");
                }
            }

            // --- Perform Actions ONLY if new status is 'success' (REFACTORED) ---
            if ($newDbStatus === 'success') {
                log_message('info', "[Midtrans Webhook - ACTION] Processing success actions for Order ID {$orderId} using unified handler...");
                // Use new unified fulfillment method
                if (!$this->handleSuccessfulPayment($orderId)) {
                    $actionsFailed = true;
                    log_message('error', "[Midtrans Webhook - ACTION FAILED] Fulfillment failed for Order ID {$orderId} (check handleSuccessfulPayment logs).");
                    // We do not throw here, we commit the status update but log action failure.
                }
            }
            // --- End of success block ---

        } catch (Throwable $e) { // Catch Throwable for PHP 7+ errors/exceptions
            log_message('error', "[Midtrans Webhook - ACTION EXCEPTION] Error processing actions for Order ID {$orderId}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $actionsFailed = true; // Mark actions as failed on any exception during processing
        }

        // --- Finalize Transaction ---
        if ($db->transStatus() !== false) {
             $db->transCommit();
             $finalDbStatus = $statusUpdateSuccess ? $newDbStatus : $transaction->status; // Reflect actual committed status
             $logSuffix = $actionsFailed ? ' but ACTIONS FAILED (check logs).' : '.';
             log_message('info', "[Midtrans Webhook] Transaction COMMITTED for Order ID: {$orderId}. Final DB Status: '{$finalDbStatus}'{$logSuffix}");
        } else {
             $db->transRollback();
             log_message('error', "[Midtrans Webhook] Transaction ROLLED BACK for Order ID: {$orderId}. DB Transaction Status was FALSE. Initial DB Status: '{$transaction->status}', Attempted New Status: '{$newDbStatus}'.");
             $this->configureMidtrans(); // Reset config
             return $this->response->setStatusCode(200, 'Error during DB transaction commit/rollback, acknowledged.');
        }

        $this->configureMidtrans(); // Reset config
        return $this->response->setStatusCode(200, 'Notification received and processed.');
    }

    // =========================================================================
    // D. TRIPAY WEBHOOK NOTIFICATION
    // =========================================================================

    /**
     * Handles incoming Tripay notifications (webhook).
     */
    public function tripayNotification()
    {
        $raw = $this->request->getBody();
        $sig = $this->request->getHeaderLine('X-Callback-Signature');
        $evt = $this->request->getHeaderLine('X-Callback-Event');
        
        log_message('info', "[Tripay Webhook] Received notification. Event: {$evt}, Raw input: " . $raw);

        if (strtolower($evt) !== 'payment_status') {
            log_message('warning', "[Tripay Webhook] Invalid event type: {$evt}");
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid event']);
        }

        $json = json_decode($raw, true) ?: [];
        $merchantRef = $json['merchant_ref'] ?? null;
        $reference   = $json['reference'] ?? null;
        $status      = strtolower($json['status'] ?? '');

        if (!$merchantRef) {
            log_message('error', "[Tripay Webhook] No merchant_ref found.");
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No merchant_ref']);
        }

        $tx = $this->transactionModel->where('order_id', $merchantRef)->first();
        if (!$tx) {
            log_message('error', '[Tripay Notify] Transaction not found: ' . $merchantRef);
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Transaction not found']);
        }
        
        // Prevent processing if transaction is already finalized and successful
        if (in_array($tx->status, ['success', 'failed', 'expired'])) {
            log_message('info', "[Tripay Webhook] Transaction {$merchantRef} is already finalized in DB ('{$tx->status}'). Webhook ignored.");
            return $this->response->setStatusCode(200, 'Already processed.');
        }

        // Ambil seller (user_id di transaksi adalah penjual)
        // Jika tx type premium, seller ID adalah user yang melakukan upgrade, yang akan mengarahkan ke default key system.
        $seller = (new UserModel())->find($tx->user_id);
        
        // Jika penjual tidak ditemukan, gunakan objek transaksi sebagai fallback seller untuk mendapatkan kunci default.
        if (!$seller) {
            $seller = $tx; 
            log_message('warning', '[Tripay Notify] Seller not found for Tx ID: ' . $tx->id . '. Using transaction object for default key check.');
        }
        
        // Build client using seller's key (if available) or system default key
        $client = $this->buildTripayClient($seller);

        if (!$client->verifyCallback($raw, $sig)) {
            log_message('error', '[Tripay Notify] Invalid signature for order ' . $merchantRef);
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Invalid signature']);
        }

        // Map status Tripay ke status kita
        // Tripay docs: PAID, EXPIRED, FAILED, etc. Kita pakai: pending, success, failed, expired, challenge
        $map = [
            'paid'    => 'success',
            'success' => 'success',
            'failed'  => 'failed',
            'expire'  => 'expired',
            'expired' => 'expired',
            'pending' => 'pending', // Keep pending if Tripay sends 'pending' again
        ];
        $newDbStatus = $map[$status] ?? $tx->status; // Default to current status if unmapped

        // Update transaksi
        $db = \Config\Database::connect();
        $db->transBegin();
        $actionsFailed = false;

        try {
            // 1. Update status
            $save = [
                'status'            => $newDbStatus,
                'tripay_reference'  => $reference ?? $tx->tripay_reference,
                'tripay_raw'        => $raw,
                'updated_at'        => date('Y-m-d H:i:s'),
            ];

            $this->transactionModel->update($tx->id, $save);

            // 2. Jika success: jalankan mekanisme fulfilment stok (gunakan handleSuccessfulPayment)
            if ($newDbStatus === 'success') {
                log_message('info', "[Tripay Webhook - ACTION] Processing success actions for Order ID {$merchantRef}...");
                // PAKAI method internalmu yang sudah dipakai Midtrans notification
                if (!$this->handleSuccessfulPayment($tx->order_id)) {
                    $actionsFailed = true;
                    log_message('error', "[Tripay Webhook - ACTION FAILED] Fulfillment failed for Order ID {$merchantRef} (check handleSuccessfulPayment logs).");
                }
            }
            
            // 3. Commit
            if ($db->transStatus() !== false) {
                 $db->transCommit();
                 log_message('info', "[Tripay Webhook] Transaction COMMITTED for Order ID: {$merchantRef}. Final DB Status: '{$newDbStatus}'. Actions " . ($actionsFailed ? 'FAILED.' : 'SUCCEEDED.'));
            } else {
                 $db->transRollback();
                 log_message('error', "[Tripay Webhook] Transaction ROLLED BACK for Order ID: {$merchantRef}. DB Transaction Status was FALSE.");
                 return $this->response->setStatusCode(500, 'Error during DB transaction commit/rollback.');
            }

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[Tripay Fulfilment Exception] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500, 'Error during processing actions.');
        }

        // Tripay expects 200 OK after successful signature verification
        return $this->response->setJSON(['ok' => true])->setStatusCode(200);
    }


    /**
     * Send Product Email with JSON data handling
     */
    private function sendProductEmail(object $transaction, string $productName, array $stockDataJsonArray, string $buyerName, int $quantity): bool
    {
        // ... (Implementation remains the same) ...
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
            $emailConfig = config('Email');

            // Override config with .env values if they exist
            $emailConfig->protocol = env('email.protocol', $emailConfig->protocol);
            $emailConfig->SMTPHost = env('email.SMTPHost', $emailConfig->SMTPHost);
            $emailConfig->SMTPPort = env('email.SMTPPort', $emailConfig->SMTPPort);
            $emailConfig->SMTPUser = env('email.SMTPUser', $emailConfig->SMTPUser);
            $emailConfig->SMTPPass = env('email.SMTPPass', $emailConfig->SMTPPass);
            $emailConfig->SMTPCrypto = env('email.SMTPCrypto', $emailConfig->SMTPCrypto);
            $fromEmail = env('email.fromEmail', $emailConfig->fromEmail ?: 'no-reply@example.com');
            $fromName = env('email.fromName', $emailConfig->fromName ?: 'Repo.ID');

            // Set mandatory email settings
            $emailConfig->mailType = 'html';
            $emailConfig->charset = 'utf-8';
            $emailConfig->newline = "\r\n";
            $emailConfig->CRLF = "\r\n";
            $emailService->initialize((array)$emailConfig);

            $emailService->setTo($transaction->buyer_email);
            $emailService->setFrom($fromEmail, $fromName);
             $subjectQuantity = ($quantity > 1) ? " ({$quantity} Item)" : "";
             $emailService->setSubject("Produk Anda: {$productName}{$subjectQuantity} | Repo.ID");

            $message = view('emails/product_delivery', [
                'buyer_name'          => $buyerName,
                'product_name'        => $productName,
                'decodedStockDataArray' => $decodedStockDataArray,
                'quantity'            => $quantity,
            ]);
            $emailService->setMessage($message);

            // Cek kredensial SMTP sebelum mengirim
            if ($emailConfig->protocol === 'smtp' && (empty($emailConfig->SMTPUser) || empty($emailConfig->SMTPPass))) {
                 log_message('error', "[Email Error] SMTP credentials (User/Pass) are missing in configuration for Order ID {$transaction->order_id}. Email not sent.");
                 return false;
            }

            if ($emailService->send()) {
                log_message('info', "[Email Success] Product email '{$productName}' (Qty: {$quantity}) sent to {$transaction->buyer_email} (Order ID: {$transaction->order_id}).");
                return true;
            } else {
                log_message('error', "[Email Error] Failed to send product email to {$transaction->buyer_email} (Order ID: {$transaction->order_id}). Debug: " . $emailService->printDebugger(['headers', 'subject', 'body'])); // Log more details
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
        // ... (Implementation remains the same) ...
        $seller = $this->userModel->find($transaction->user_id);
        if (!$seller || !$seller->email) {
            log_message('error', "[Stock Alert Email] Seller not found or has no email for user ID: {$transaction->user_id}");
            return false;
        }
        $product = $this->productModel->find($transaction->product_id);
        $productName = $product ? $product->product_name : 'Produk Tidak Dikenal (ID: ' . $transaction->product_id . ')';
        $variantName = '';
        $variantNameFromTx = $transaction->variant_name ?? null; // Use saved variant name
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
            $emailConfig->protocol = env('email.protocol', $emailConfig->protocol);
            $emailConfig->SMTPHost = env('email.SMTPHost', $emailConfig->SMTPHost);
            $emailConfig->SMTPPort = env('email.SMTPPort', $emailConfig->SMTPPort);
            $emailConfig->SMTPUser = env('email.SMTPUser', $emailConfig->SMTPUser);
            $emailConfig->SMTPPass = env('email.SMTPPass', $emailConfig->SMTPPass);
            $emailConfig->SMTPCrypto = env('email.SMTPCrypto', $emailConfig->SMTPCrypto);
            $fromEmail = env('email.fromEmail', $emailConfig->fromEmail ?: 'no-reply@example.com');
            $fromName = env('email.fromName', $emailConfig->fromName ?: 'Sistem Repo.ID');

            $emailConfig->mailType = 'text';
            $emailConfig->charset = 'utf-8';
            $emailConfig->newline = "\r\n";
            $emailConfig->CRLF = "\r\n";
            $emailService->initialize((array)$emailConfig);

            $emailService->setTo($seller->email);
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setSubject("PENTING: Peringatan Stok Produk {$productName}{$variantName}");

            $message = "Halo {$seller->username},\n\n";
            $message .= "Terjadi masalah penting terkait stok produk '{$productName}{$variantName}' Anda di Repo.ID.\n\n";
            $message .= "Detail Masalah: {$reason}\n\n";
            $message .= "Detail Pesanan Terkait (jika ada):\n";
            $message .= "- Order ID: {$transaction->order_id}\n";
            $buyerNameClean = $transaction->buyer_name; // Use original buyer name
            $message .= "- Pembeli: {$buyerNameClean} ({$transaction->buyer_email})\n";
            $message .= "- Jumlah: Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
            $message .= "- Kuantitas Dibeli: {$quantityNeeded}\n\n";
            $message .= "Mohon segera periksa stok produk Anda di dashboard dan ambil tindakan yang diperlukan.\n";
            if($product) {
                $stockManageLink = $product->has_variants
                    ? route_to('product.stock.manage', $product->id)
                    : route_to('product.stock.manage', $product->id);
                if ($variantId) {
                     $stockManageLink = route_to('product.variant.stock.items', $product->id, $variantId);
                }
                $message .= "Link Kelola Stok: " . $stockManageLink . "\n\n";
            }
            $message .= "Terima kasih,\nTim Repo.ID";
            $emailService->setMessage($message);

             // Cek kredensial SMTP sebelum mengirim
            if ($emailConfig->protocol === 'smtp' && (empty($emailConfig->SMTPUser) || empty($emailConfig->SMTPPass))) {
                 log_message('error', "[Stock Alert Email Error] SMTP credentials missing. Alert for Order ID {$transaction->order_id} not sent to {$seller->email}.");
                 return false;
            }

            if ($emailService->send()) {
                log_message('info', "[Stock Alert Email] Alert sent to seller {$seller->email} for product ID {$transaction->product_id}{$variantName} (Order ID: {$transaction->order_id}). Reason: {$reason}");
                return true;
            } else {
                log_message('error', "[Stock Alert Email Error] Failed to send alert email to {$seller->email}. Debug: " . $emailService->printDebugger(['headers', 'subject', 'body'])); // Log more details
                return false;
            }
         } catch (\Exception $e) {
             log_message('error', "[Email Exception] Error sending stock alert email for Order ID {$transaction->order_id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
             return false;
         }
    }
}
