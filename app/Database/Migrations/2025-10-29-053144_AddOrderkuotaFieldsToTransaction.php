<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderkuotaFieldsToTransactions extends Migration
{
    public function up()
    {
        // Tambahkan kolom setelah kolom Tripay yang sudah ada atau setelah snap_token
        $fieldsToAdd = [
            'zeppelin_reference_id' => [ // Nama sudah benar
                'type'       => 'VARCHAR',
                'constraint' => 100, // Sesuaikan constraint jika perlu
                'null'       => true,
                'after'      => 'tripay_raw', // Letakkan setelah kolom Tripay terakhir
            ],
            'zeppelin_paid_amount' => [ // Nama sudah benar
                'type'       => 'DECIMAL',
                'constraint' => '15,0', // Jumlah yang harus dibayar (bisa beda karena biaya unik)
                'null'       => true,
                'after'      => 'zeppelin_reference_id',
            ],
            'zeppelin_qr_url' => [ // Nama sudah benar
                'type' => 'TEXT', // URL gambar QRIS
                'null' => true,
                'after' => 'zeppelin_paid_amount',
            ],
            'zeppelin_expiry_date' => [ // Nama sudah benar (sebelumnya expiry_str)
                'type' => 'DATETIME', // Waktu kedaluwarsa dari API (sudah diparse)
                'null' => true,
                'after' => 'zeppelin_qr_url',
            ],
            'zeppelin_raw_response' => [ // Nama sudah benar (sebelumnya raw)
                'type' => 'TEXT', // Simpan response JSON mentah
                'null' => true,
                'after' => 'zeppelin_expiry_date',
            ],
        ];
        $this->forge->addColumn('transactions', $fieldsToAdd);


        // Ganti enum payment_gateway untuk menambahkan 'orderkuota'
        // Perhatian: Mengubah ENUM bisa berisiko di beberapa DB atau memerlukan syntax khusus.
        // Alternatifnya adalah menggunakan VARCHAR. Jika tetap ENUM:
        // Syntax ini mungkin perlu disesuaikan tergantung driver database Anda (MySQL, PostgreSQL, dll.)
        try {
             // MySQL Syntax - Pastikan semua enum yang ada disertakan
             $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay', 'orderkuota') DEFAULT 'midtrans'");
        } catch (\Throwable $e) {
             log_message('error', 'Gagal mengubah ENUM payment_gateway saat menambahkan orderkuota: ' . $e->getMessage());
             // Handle error atau log, mungkin perlu dilakukan manual jika ALTER TABLE gagal
             // Jika gagal, pertimbangkan mengubah kolom payment_gateway menjadi VARCHAR
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
         // Periksa apakah kolom ada sebelum mencoba menghapusnya
        foreach ($columnsToDrop as $column) {
            if ($this->db->fieldExists($column, 'transactions')) {
                $this->forge->dropColumn('transactions', $column);
            }
        }


        // Kembalikan enum payment_gateway (jika diubah di up())
         try {
             // MySQL Syntax - Hapus 'orderkuota' dari enum
             $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay') DEFAULT 'midtrans'");
        } catch (\Throwable $e) {
             log_message('error', 'Gagal mengembalikan ENUM payment_gateway saat rollback: ' . $e->getMessage());
        }

    }
}
