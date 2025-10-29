<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object'; // Sesuaikan jika Anda lebih suka array
    protected $useSoftDeletes   = false; // Ganti jadi true jika ingin soft delete
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'product_name',
        'description',
        'price',            // Harga utama (bisa 0 jika pakai varian)
        'order_type',       // 'manual' atau 'auto'
        'target_url',       // URL untuk tipe manual
        'icon_filename',    // Nama file ikon yang diupload
        'is_active',        // Status aktif produk
        'has_variants',     // Flag boolean (0 atau 1)
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // Aktifkan jika useSoftDeletes = true

    // Validation
    protected $validationRules      = [
        'user_id'       => 'required|integer',
        'product_name'  => 'required|max_length[255]',
        'order_type'    => 'required|in_list[manual,auto]',
        // Harga utama boleh 0 jika has_variants=true, tapi wajib > 0 jika auto tanpa varian (validasi di controller)
        'price'         => 'permit_empty|numeric|greater_than_equal_to[0]',
        'target_url'    => 'permit_empty|valid_url|max_length[255]', // Wajib jika manual (validasi di controller)
        'has_variants'  => 'required|in_list[0,1]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Mengambil produk aktif milik user tertentu.
     * Digunakan di ProfileController dan DashboardController.
     *
     * @param int $userId ID User
     * @return array<object> List produk aktif
     */
    public function getActiveProductsByUserId(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('product_name', 'ASC') // Urutkan berdasarkan nama
                    ->findAll();
    }

    // Anda bisa menambahkan method lain di sini sesuai kebutuhan
    // Contoh:
    // public function getProductWithDetails(int $productId) { ... }
}
