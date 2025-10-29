<?php

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $beforeInsert     = ['hashPassword'];
    protected $protectFields    = true;
    // Updated allowedFields
    protected $allowedFields    = [
        'username',
        'store_name',           // Added
        'profile_subtitle',     // Added
        'logo_filename',        // Added
        'email',
        'password',
        'password_hash',
        'is_premium',
        'is_admin',
        'balance',
        'whatsapp_link',
        'bank_name',
        'account_number',
        'account_name',
        'midtrans_server_key',
        'midtrans_client_key',
        'tripay_api_key',
        'tripay_private_key',
        'tripay_merchant_code',
        'gateway_active',
        // 'midtrans_is_production',
        'created_at',
        'updated_at',
        'reset_token_hash',
        'reset_token_expires_at'
    ];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            unset($data['data']['password']);
        }
        return $data;
    }

    public function upgradeToPremium(int $userId): bool
    {
        return $this->update($userId, ['is_premium' => 1]);
    }

    public function addBalance(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            log_message('error', "[Balance Update] Attempted to add non-positive amount ({$amount}) to user ID {$userId}.");
            return false;
        }

        $this->db->transStart();
        $this->where('id', $userId)->set('balance', 'balance + ' . $this->db->escape($amount), false)->update();
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            log_message('error', "[Balance Update] Transaction failed for user ID {$userId}. Amount: {$amount}. Error: " . print_r($this->db->error(), true));
            return false;
        }

        log_message('info', "[Balance Update] Added {$amount} to balance for user ID {$userId}.");
        return true;
    }

     public function deductBalance(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            log_message('error', "[Balance Update] Attempted to deduct non-positive amount ({$amount}) from user ID {$userId}.");
            return false;
        }

        $user = $this->find($userId);
        if (!$user || $user->balance < $amount) {
            log_message('warning', "[Balance Update] Insufficient balance for user ID {$userId} to deduct {$amount}. Current balance: " . ($user->balance ?? 'N/A'));
            return false;
        }

        $this->db->transStart();
        $this->where('id', $userId)
             ->where('balance >=', $amount)
             ->set('balance', 'balance - ' . $this->db->escape($amount), false)
             ->update();

        $affectedRows = $this->db->affectedRows();

        $this->db->transComplete();

        if ($this->db->transStatus() === false || $affectedRows === 0) {
            log_message('error', "[Balance Update] Transaction failed or balance condition not met for user ID {$userId}. Amount: {$amount}. Affected Rows: {$affectedRows}. Error: " . print_r($this->db->error(), true));
            return false;
        }

        log_message('info', "[Balance Update] Deducted {$amount} from balance for user ID {$userId}.");
        return true;
    }
}
