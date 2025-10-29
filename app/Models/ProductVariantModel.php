<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'name',
        'price',
        'stock', // Kolom ini sekarang disinkronkan dengan hitungan item unik di product_stock
        'is_active'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation rules
    protected $validationRules = [
        'name'  => 'required|max_length[100]',
        'price' => 'required|numeric|greater_than[0]',
        'stock' => 'permit_empty|numeric|greater_than_equal_to[0]', // Digunakan untuk display/pengecekan awal
    ];
    
    /**
     * Mengambil varian aktif untuk produk tertentu.
     * @param int $productId
     * @return array<object>
     */
    public function getActiveVariantsByProductId(int $productId): array
    {
        // Varian yang ditampilkan sudah termasuk kolom 'stock' yang disinkronkan.
        return $this->where('product_id', $productId)
                    ->where('is_active', true)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }
    
    /**
     * Sinkronisasi kolom 'stock' di tabel product_variants dengan hitungan item di product_stock.
     * Dijalankan setelah operasi CRUD pada product_stock (tambah/hapus/pakai).
     *
     * @param int $variantId
     * @param \App\Models\ProductStockModel $stockModel
     * @return bool
     */
    public function synchronizeStock(int $variantId, ProductStockModel $stockModel): bool
    {
        $currentStockCount = $stockModel->getAvailableStockCountForVariant($variantId);
        
        $result = $this->update($variantId, ['stock' => $currentStockCount]);
        
        if ($result === false) {
            log_message('error', 'Failed to synchronize stock for Variant ID: ' . $variantId . '. Model errors: ' . print_r($this->errors(), true));
        }
        
        return $result;
    }
}
