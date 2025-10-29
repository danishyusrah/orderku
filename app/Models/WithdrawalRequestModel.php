<?php

namespace App\Models;

use CodeIgniter\Model;

class WithdrawalRequestModel extends Model
{
    protected $table            = 'withdrawal_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'amount',
        'status',
        'bank_details', // <-- Gunakan kolom ini
        'processed_at',
        'admin_notes',
        // created_at dan updated_at dihandle oleh useTimestamps
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at'; // Otomatis update saat status berubah

    /**
     * Mengambil data withdrawal request beserta username user.
     * (Digunakan di AdminController, bisa juga digunakan di tempat lain)
     *
     * @param int|null $id Jika null, ambil semua. Jika ada ID, ambil satu.
     * @return \CodeIgniter\Database\BaseBuilder|array|object|null
     */
    public function getRequestsWithUser(?int $id = null)
    {
        $builder = $this->select('withdrawal_requests.*, users.username')
                      ->join('users', 'users.id = withdrawal_requests.user_id', 'left'); // Left join just in case user deleted

        if ($id !== null) {
            return $builder->find($id);
        }

        // Return builder for pagination or findAll
        return $builder;
    }

    /**
     * Override find/findAll to automatically parse bank_details JSON
     *
     * @param bool $singleton
     * @return array|object|null
     */
    protected function doFind(bool $singleton, $id = null)
    {
        $result = parent::doFind($singleton, $id);

        if ($result) {
            if ($singleton && is_object($result)) {
                if (isset($result->bank_details)) {
                    $decoded = json_decode($result->bank_details);
                    // Add decoded details as separate properties for easier access in views
                    $result->bank_name = $decoded->bank_name ?? null;
                    $result->account_number = $decoded->account_number ?? null;
                    $result->account_name = $decoded->account_name ?? null;
                }
            } elseif (is_array($result)) {
                foreach ($result as $key => $row) {
                    if (is_object($row) && isset($row->bank_details)) {
                        $decoded = json_decode($row->bank_details);
                        // Add decoded details as separate properties
                        $result[$key]->bank_name = $decoded->bank_name ?? null;
                        $result[$key]->account_number = $decoded->account_number ?? null;
                        $result[$key]->account_name = $decoded->account_name ?? null;
                    }
                }
            }
        }

        return $result;
    }


}

