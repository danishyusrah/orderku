<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql; // Import RawSql

class AddAdminBalanceBankWithdrawal extends Migration
{
    public function up()
    {
        // 1. Tambah kolom ke tabel users
        $userFields = [
            'is_admin' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'after'      => 'password_hash', // Letakkan setelah password_hash
            ],
            'balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,0', // Saldo dalam Rupiah, tanpa koma
                'default'    => 0,
                'after'      => 'is_premium',
            ],
            'bank_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'balance',
            ],
            'account_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'bank_name',
            ],
            'account_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'account_number',
            ],
        ];
        $this->forge->addColumn('users', $userFields);

        // 2. Buat tabel withdrawal_requests
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,0',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
            ],
            'bank_details' => [ // Simpan detail bank saat request dibuat
                'type' => 'TEXT',
                'null' => true,
            ],
            'processed_at' => [ // Waktu admin memproses
                'type' => 'DATETIME',
                'null' => true,
            ],
            'admin_notes' => [ // Catatan dari admin (misal alasan ditolak)
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'), // Default ke waktu sekarang
            ],
            'updated_at' => [
                'type'   => 'DATETIME',
                'null'   => true,
                'on update' => new RawSql('CURRENT_TIMESTAMP'), // Otomatis update saat baris diubah
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE'); // Jika user dihapus, request WD ikut terhapus
        $this->forge->createTable('withdrawal_requests');
    }

    public function down()
    {
        // 1. Hapus tabel withdrawal_requests
        $this->forge->dropTable('withdrawal_requests', true); // true agar tidak error jika tabel tidak ada

        // 2. Hapus kolom dari tabel users
        $this->forge->dropColumn('users', [
            'is_admin',
            'balance',
            'bank_name',
            'account_number',
            'account_name',
        ]);
    }
}
