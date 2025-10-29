<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductStockModel extends Model
{
    protected $table            = 'product_stock';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'variant_id', // Ditambahkan
        'stock_data',
        'is_used',
        'buyer_email',
        'transaction_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at'; // Baris ini sudah diperbaiki

    /**
     * Mendapatkan satu item stok tersedia untuk varian tertentu.
     * Digunakan di PaymentController saat transaksi sukses (auto).
     * @param int $variantId
     * @return object|null
     */
    public function getAvailableStockForVariant(int $variantId): ?object
    {
        return $this->where('variant_id', $variantId)
                    ->where('is_used', false)
                    ->orderBy('id', 'ASC') // Ambil yang paling lama
                    ->first();
    }

    /**
     * Mendapatkan sejumlah item stok tersedia untuk varian tertentu.
     * @param int $variantId
     * @param int $quantity
     * @return array<object>|null Returns an array of stock items or null if not enough stock
     */
    public function getAvailableStockItemsForVariant(int $variantId, int $quantity): ?array
    {
        // First, check if enough stock exists
        $count = $this->getAvailableStockCountForVariant($variantId);
        if ($count < $quantity) {
            return null; // Not enough stock
        }

        // Fetch the required number of items
        return $this->where('variant_id', $variantId)
                    ->where('is_used', false)
                    ->orderBy('id', 'ASC') // Get the oldest ones first
                    ->limit($quantity)
                    ->find();
    }

    /**
     * Mendapatkan satu item stok tersedia untuk produk NON-VARIAN (variant_id IS NULL).
     * @param int $productId
     * @return object|null
     */
    public function getAvailableStockForNonVariant(int $productId): ?object
    {
        return $this->where('product_id', $productId)
                    ->where('variant_id', null) // Penting: Hanya ambil stok non-varian
                    ->where('is_used', false)
                    ->orderBy('id', 'ASC')
                    ->first();
    }

     /**
     * Mendapatkan sejumlah item stok tersedia untuk produk NON-VARIAN.
     * @param int $productId
     * @param int $quantity
     * @return array<object>|null Returns an array of stock items or null if not enough stock
     */
    public function getAvailableStockItemsForNonVariant(int $productId, int $quantity): ?array
    {
        // First, check if enough stock exists
        $count = $this->getAvailableStockCountForNonVariant($productId);
        if ($count < $quantity) {
            return null; // Not enough stock
        }

        // Fetch the required number of items
        return $this->where('product_id', $productId)
                    ->where('variant_id', null)
                    ->where('is_used', false)
                    ->orderBy('id', 'ASC')
                    ->limit($quantity)
                    ->find();
    }


    /**
     * Menandai item stok spesifik sebagai terpakai.
     * @param int $stockId
     * @param string $buyerEmail
     * @param int $transactionId
     * @return bool
     */
    public function markStockAsUsed(int $stockId, string $buyerEmail, int $transactionId): bool
    {
        return $this->update($stockId, [
            'is_used'        => true,
            'buyer_email'    => $buyerEmail,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Menandai BEBERAPA item stok sebagai terpakai (bulk update).
     * @param array<int> $stockIds Array of stock IDs
     * @param string $buyerEmail
     * @param int $transactionId
     * @return bool
     */
    public function markMultipleStocksAsUsed(array $stockIds, string $buyerEmail, int $transactionId): bool
    {
        if (empty($stockIds)) {
            return true; // Nothing to update
        }

        return $this->whereIn('id', $stockIds)
                    ->set([
                        'is_used'        => true,
                        'buyer_email'    => $buyerEmail,
                        'transaction_id' => $transactionId,
                        'updated_at'     => date('Y-m-d H:i:s') // Set updated_at manually for batch update
                    ])
                    ->update();
    }


    /**
     * Menghapus semua item stok yang terkait dengan ID Varian.
     * @param int $variantId
     * @return bool
     */
    public function deleteStocksByVariantId(int $variantId): bool
    {
        return $this->where('variant_id', $variantId)->delete();
    }

    /**
      * Menghitung total stok tersedia untuk varian tertentu.
      * @param int $variantId
      * @return int
      */
    public function getAvailableStockCountForVariant(int $variantId): int
    {
        return $this->where('variant_id', $variantId)
                    ->where('is_used', false)
                    ->countAllResults();
    }

    /**
      * Menghitung total stok tersedia untuk produk non-varian.
      * @param int $productId
      * @return int
      */
    public function getAvailableStockCountForNonVariant(int $productId): int
    {
        return $this->where('product_id', $productId)
                    ->where('variant_id', null)
                    ->where('is_used', false)
                    ->countAllResults();
    }

    /**
      * Menghapus semua item stok non-varian terkait dengan produk.
      * @param int $productId
      * @return bool
      */
    public function deleteStocksByProductId(int $productId): bool
    {
        // Hanya hapus item stok yang TIDAK terikat ke varian
        return $this->where('product_id', $productId)
                    ->where('variant_id', null)
                    ->delete();
    }

    /**
     * Mengambil item stok unik (data akun) yang terkirim pada transaksi tertentu.
     * @param int $transactionId
     * @return array<object> Returns an array of stock items
     */
    public function getStockItemsByTransactionId(int $transactionId): array
    {
         return $this->where('transaction_id', $transactionId)
                     ->where('is_used', true)
                     ->find(); // Use find() to get multiple items if they exist
    }
}
