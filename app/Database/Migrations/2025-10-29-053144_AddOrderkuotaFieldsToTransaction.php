<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderkuotaFieldsToTransactions extends Migration
{
    public function up()
    {
        // Tambahkan kolom setelah kolom Tripay yang sudah ada atau setelah snap_token
        $this->forge->addColumn('transactions', [
            'zeppelin_reference_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100, // Sesuaikan constraint jika perlu
                'null'       => true,
                'after'      => 'tripay_raw', // Letakkan setelah kolom Tripay terakhir
            ],
            'zeppelin_paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,0', // Jumlah yang harus dibayar (bisa beda karena biaya unik)
                'null'       => true,
                'after'      => 'zeppelin_reference_id',
            ],
            'zeppelin_qr_url' => [
                'type' => 'TEXT', // URL gambar QRIS
                'null' => true,
                'after' => 'zeppelin_paid_amount',
            ],
            'zeppelin_expiry_date' => [
                'type' => 'DATETIME', // Waktu kedaluwarsa dari API
                'null' => true,
                'after' => 'zeppelin_qr_url',
            ],
            'zeppelin_raw_response' => [
                'type' => 'TEXT', // Simpan response JSON mentah
                'null' => true,
                'after' => 'zeppelin_expiry_date',
            ],
        ]);

        // Ubah enum status untuk mengakomodasi status dari Orderkuota/Zeppelin jika perlu
        // Contoh: 'success', 'pending', 'failed', 'expired'
        // Jika status yang ada sudah cukup (misal 'paid' dari zeppelin dimap ke 'success'), tidak perlu diubah.
        // $this->forge->modifyColumn('transactions', [
        //     'status' => [
        //         'type'       => 'ENUM',
        //         // Tambahkan status baru jika ada dari Zeppelin/Orderkuota yang belum tercover
        //         'constraint' => ['pending', 'success', 'failed', 'expired', 'challenge', 'paid'],
        //         'default'    => 'pending',
        //     ],
        // ]);

        // Ganti enum payment_gateway untuk menambahkan 'orderkuota'
        // Perhatian: Mengubah ENUM bisa berisiko di beberapa DB atau memerlukan syntax khusus.
        // Alternatifnya adalah menggunakan VARCHAR. Jika tetap ENUM:
        // Syntax ini mungkin perlu disesuaikan tergantung driver database Anda (MySQL, PostgreSQL, dll.)
        try {
             // MySQL Syntax
             $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay', 'orderkuota') DEFAULT 'midtrans'");
        } catch (\Throwable $e) {
             log_message('error', 'Gagal mengubah ENUM payment_gateway: ' . $e->getMessage());
             // Handle error atau log, mungkin perlu dilakukan manual jika ALTER TABLE gagal
             // Jika gagal, pertimbangkan mengubah kolom payment_gateway menjadi VARCHAR
        }

    }

    public function down()
    {
        // Hapus kolom yang ditambahkan
        $this->forge->dropColumn('transactions', [
            'zeppelin_reference_id',
            'zeppelin_paid_amount',
            'zeppelin_qr_url',
            'zeppelin_expiry_date',
            'zeppelin_raw_response'
        ]);

        // Kembalikan enum payment_gateway (jika diubah di up())
         try {
             // MySQL Syntax
             $this->db->query("ALTER TABLE transactions MODIFY COLUMN payment_gateway ENUM('midtrans', 'tripay') DEFAULT 'midtrans'");
        } catch (\Throwable $e) {
             log_message('error', 'Gagal mengembalikan ENUM payment_gateway saat rollback: ' . $e->getMessage());
        }

        // Kembalikan enum status jika diubah di up()
        // $this->forge->modifyColumn('transactions', [
        //     'status' => [
        //         'type'       => 'ENUM',
        //         'constraint' => ['pending', 'success', 'failed', 'expired', 'challenge'],
        //         'default'    => 'pending',
        //     ],
        // ]);
    }
}
