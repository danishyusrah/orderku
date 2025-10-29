<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderkuotaKeysToUsers extends Migration
{
    public function up()
    {
        // Tambahkan kolom untuk menyimpan kredensial Orderkuota/Zeppelin per user
        $fields = [
            'zeppelin_auth_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'gateway_active', // Sesuaikan posisi jika perlu
            ],
            'zeppelin_auth_token' => [
                'type'       => 'VARCHAR', // Simpan sebagai VARCHAR, enkripsi jika perlu
                'constraint' => 255,
                'null'       => true,
                'after'      => 'zeppelin_auth_username',
            ],
        ];

        // Pastikan tabel users ada sebelum menambahkan kolom
        if ($this->db->tableExists('users')) {
             try {
                $this->forge->addColumn('users', $fields);
                 log_message('info', 'Kolom zeppelin_auth_username dan zeppelin_auth_token berhasil ditambahkan ke tabel users.');
             } catch (\Throwable $e) {
                 log_message('error', 'Gagal menambahkan kolom Orderkuota ke tabel users: ' . $e->getMessage());
             }
        } else {
             log_message('error', 'Tabel "users" tidak ditemukan saat migrasi AddOrderkuotaKeysToUsers.');
        }
    }

    public function down()
    {
        // Hapus kolom saat rollback migrasi
        if ($this->db->tableExists('users')) {
             $columnsToDrop = ['zeppelin_auth_username', 'zeppelin_auth_token'];
             foreach ($columnsToDrop as $column) {
                 if ($this->db->fieldExists($column, 'users')) {
                     try {
                         $this->forge->dropColumn('users', $column);
                          log_message('info', "Kolom {$column} berhasil dihapus dari tabel users.");
                     } catch (\Throwable $e) {
                         log_message('error', "Gagal menghapus kolom {$column} dari tabel users: " . $e->getMessage());
                     }
                 }
             }
        }
    }
}
