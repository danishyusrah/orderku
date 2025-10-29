<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderkuotaFieldsToTransactions extends Migration
{
    public function up()
    {
        // Definisikan field yang akan ditambahkan
        $fieldsToAdd = [
            'zeppelin_reference_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                // Hapus 'after' => 'tripay_raw' agar tidak bergantung pada kolom sebelumnya
                // Posisi default adalah di akhir tabel jika 'after' tidak ditentukan
            ],
            'zeppelin_paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,0',
                'null'       => true,
                'after'      => 'zeppelin_reference_id', // Bergantung pada kolom yang baru ditambahkan di migrasi ini
            ],
            'zeppelin_qr_url' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'zeppelin_paid_amount', // Bergantung pada kolom yang baru ditambahkan di migrasi ini
            ],
            'zeppelin_expiry_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'zeppelin_qr_url', // Bergantung pada kolom yang baru ditambahkan di migrasi ini
            ],
            'zeppelin_raw_response' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'zeppelin_expiry_date', // Bergantung pada kolom yang baru ditambahkan di migrasi ini
            ],
        ];

        // Cek apakah tabel ada sebelum menambahkan kolom
        if ($this->db->tableExists('transactions')) {
            // Coba tambahkan kolom
            try {
                $this->forge->addColumn('transactions', $fieldsToAdd);
            } catch (\Throwable $e) {
                 log_message('error', 'Gagal menambahkan kolom Orderkuota: ' . $e->getMessage());
                 // Jika perlu, Anda bisa melempar exception lagi atau menangani error
                 // throw $e;
            }
        } else {
             log_message('error', 'Tabel "transactions" tidak ditemukan saat mencoba menambahkan kolom Orderkuota.');
        }


        // Ganti enum payment_gateway untuk menambahkan 'orderkuota'
        // Gunakan try-catch untuk menangani potensi error saat mengubah ENUM
        try {
             // Pastikan semua nilai enum yang sudah ada (midtrans, tripay) disertakan
             $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay', 'orderkuota') DEFAULT 'midtrans'");
        } catch (\Throwable $e) {
             log_message('error', 'Gagal mengubah ENUM payment_gateway saat menambahkan orderkuota: ' . $e->getMessage());
             // Handle error atau log, mungkin perlu dilakukan manual jika ALTER TABLE gagal
             // Jika gagal, pertimbangkan mengubah kolom payment_gateway menjadi VARCHAR sebagai alternatif
        }

    }

    public function down()
    {
        // Hapus kolom yang ditambahkan
        $columnsToDrop = [
            'zeppelin_reference_id',
            'zeppelin_paid_amount',
            'zeppelin_qr_url',
            'zeppelin_expiry_date',
            'zeppelin_raw_response'
        ];

        // Periksa apakah tabel dan kolom ada sebelum mencoba menghapusnya
        if ($this->db->tableExists('transactions')) {
            foreach ($columnsToDrop as $column) {
                if ($this->db->fieldExists($column, 'transactions')) {
                    try {
                        $this->forge->dropColumn('transactions', $column);
                    } catch (\Throwable $e) {
                         log_message('error', "Gagal menghapus kolom {$column}: " . $e->getMessage());
                    }
                }
            }
        }

        // Kembalikan enum payment_gateway (jika diubah di up())
        if ($this->db->tableExists('transactions')) {
            try {
                 // MySQL Syntax - Hapus 'orderkuota' dari enum
                 $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay') DEFAULT 'midtrans'");
            } catch (\Throwable $e) {
                 log_message('error', 'Gagal mengembalikan ENUM payment_gateway saat rollback: ' . $e->getMessage());
            }
        }
    }
}
