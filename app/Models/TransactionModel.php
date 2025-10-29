<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'order_id',
        'user_id',
        'product_id',
        'variant_id', // <-- Tambahkan ini
        'variant_name', // <-- Tambahkan ini
        'buyer_name',
        'buyer_email',
        'transaction_type',
        'amount',
        'quantity',
        'status',
        'snap_token',
        'payment_gateway',
        'midtrans_key_source',
        'tripay_reference', // Tripay field
        'tripay_pay_url',   // Tripay field
        'tripay_raw',       // Tripay field
        'zeppelin_reference_id', // <-- Tambahkan Orderkuota/Zeppelin field
        'zeppelin_paid_amount',  // <-- Tambahkan Orderkuota/Zeppelin field
        'zeppelin_qr_url',       // <-- Tambahkan Orderkuota/Zeppelin field
        'zeppelin_expiry_date',  // <-- Tambahkan Orderkuota/Zeppelin field
        'zeppelin_raw_response', // <-- Tambahkan Orderkuota/Zeppelin field
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ... (rest of the model remains the same) ...

    public function getTransactionByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->first();
    }

    public function updateStatusByOrderId($orderId, $status)
    {
        // Gunakan update biasa karena kita mungkin ingin update field lain juga nanti
        return $this->where('order_id', $orderId)->set(['status' => $status])->update();
    }

    /**
     * @deprecated Gunakan getTransactionWithDetails()
     */
    public function getTransactionWithProduct($orderId)
    {
        return $this->getTransactionWithDetails($orderId);
    }

    /**
     * Mengambil detail transaksi termasuk produk, varian (jika ada), dan user (penjual).
     *
     * @param string $orderId
     * @return object|null
     */
    public function getTransactionWithDetails(string $orderId): ?object
    {
        return $this->select('transactions.*,
                              products.product_name, products.target_url,
                              users.username as seller_username, users.midtrans_server_key as user_server_key')
                    ->join('users', 'users.id = transactions.user_id', 'left')
                    ->join('products', 'products.id = transactions.product_id', 'left')
                    // Join product_variants left, in case variant_id is NULL or variant deleted
                    // ->join('product_variants', 'product_variants.id = transactions.variant_id', 'left') // Join ini tidak perlu jika variant_name sudah disimpan
                    ->where('transactions.order_id', $orderId)
                    ->first();
    }

    /**
     * Fungsi untuk mendapatkan transaksi dengan data user (penjual) saja.
     * Digunakan spesifik untuk cek kunci Midtrans di notification handler.
     * @param string $orderId
     * @return object|null
     */
    public function getTransactionWithUser(string $orderId): ?object
    {
        // Tambahkan kolom baru ke select jika diperlukan oleh logic webhook nanti
        return $this->select('transactions.id, transactions.user_id, transactions.midtrans_key_source, users.midtrans_server_key as user_server_key, transactions.status, transactions.transaction_type, transactions.product_id, transactions.variant_id, transactions.variant_name, transactions.quantity, transactions.amount, transactions.buyer_email, transactions.buyer_name, transactions.payment_gateway') // Pilih kolom yang relevan saja
                    ->join('users', 'users.id = transactions.user_id', 'left')
                    ->where('transactions.order_id', $orderId)
                    ->first();
    }
}
