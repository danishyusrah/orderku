<?php

 namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVariantFieldsToTransactions extends Migration
    {
        public function up()
        {
            $fields = [
                'variant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true, // Nullable, karena tidak semua transaksi punya varian (cth: premium)
                    'after'      => 'product_id', // Letakkan setelah product_id
                ],
                'variant_name' => [ // Simpan juga nama varian saat transaksi untuk history
                    'type'       => 'VARCHAR',
                    'constraint' => 150, // Sesuaikan panjang jika perlu
                    'null'       => true,
                    'after'      => 'variant_id',
                ],
            ];

            // Tambahkan foreign key constraint (opsional tapi direkomendasikan)
            // Pastikan tabel product_variants sudah ada SEBELUM menjalankan migrasi ini
            $addForeignKey = false; // Flag untuk menandai apakah foreign key akan ditambahkan
            if ($this->db->tableExists('product_variants')) {
                // Cek apakah kolom id ada di product_variants
                if ($this->db->fieldExists('id', 'product_variants')) {
                     $addForeignKey = true;
                } else {
                     log_message('warning', 'Kolom `id` tidak ditemukan di tabel `product_variants`. Foreign key untuk `variant_id` di `transactions` tidak ditambahkan.');
                }
            } else {
                 log_message('warning', 'Tabel `product_variants` tidak ditemukan. Foreign key untuk `variant_id` di `transactions` tidak ditambahkan.');
            }

            // Cek tabel transactions sebelum menambah kolom
            if ($this->db->tableExists('transactions')) {
                 try {
                     $this->forge->addColumn('transactions', $fields);
                     log_message('info', 'Kolom variant_id dan variant_name berhasil ditambahkan ke tabel transactions.');

                     // Tambahkan foreign key JIKA flag true
                     if ($addForeignKey) {
                         // Kita perlu query manual untuk nama constraint agar down() bisa jalan
                         // $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'SET NULL', 'SET NULL');
                         // Gunakan query manual untuk nama constraint yang bisa diprediksi
                         $constraintName = 'transactions_variant_id_foreign'; // Nama constraint
                         $sql = "ALTER TABLE " . $this->db->prefixTable('transactions') . " ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`variant_id`) REFERENCES " . $this->db->prefixTable('product_variants') . " (`id`) ON DELETE SET NULL ON UPDATE SET NULL";
                         $this->db->query($sql);
                         log_message('info', 'Foreign key constraint transactions_variant_id_foreign berhasil ditambahkan.');
                     }

                 } catch (\Throwable $e) {
                      log_message('error', 'Gagal menambahkan kolom/foreign key variant_id/variant_name ke transactions: ' . $e->getMessage());
                 }
            } else {
                 log_message('error', 'Tabel transactions tidak ditemukan saat migrasi AddVariantFieldsToTransactions.');
            }
        }

        public function down()
        {
            if ($this->db->tableExists('transactions')) {
                 $tableName = $this->db->prefixTable('transactions');
                 $constraintName = 'transactions_variant_id_foreign'; // Nama constraint yang sama

                 // Hapus foreign key dulu jika ada
                 if ($this->db->fieldExists('variant_id', 'transactions')) {
                     try {
                         // Cek apakah constraint ada sebelum drop (Contoh MySQL)
                         $query = $this->db->query(
                             "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                              WHERE TABLE_SCHEMA = DATABASE()
                              AND TABLE_NAME = '{$tableName}'
                              AND CONSTRAINT_NAME = '{$constraintName}'"
                         );
                         if ($query->getRow()) {
                             $this->forge->dropForeignKey('transactions', $constraintName);
                             log_message('info', 'Foreign key constraint transactions_variant_id_foreign berhasil dihapus.');
                         } else {
                              log_message('info', 'Foreign key constraint transactions_variant_id_foreign tidak ditemukan, skip penghapusan.');
                         }
                     } catch (\Throwable $e) {
                         log_message('error', 'Gagal menghapus foreign key transactions_variant_id_foreign: ' . $e->getMessage());
                     }
                 }

                 // Hapus kolom
                 if ($this->db->fieldExists('variant_id', 'transactions') || $this->db->fieldExists('variant_name', 'transactions')) {
                      try {
                         $columnsToDrop = [];
                         if ($this->db->fieldExists('variant_id', 'transactions')) $columnsToDrop[] = 'variant_id';
                         if ($this->db->fieldExists('variant_name', 'transactions')) $columnsToDrop[] = 'variant_name';

                         if (!empty($columnsToDrop)) {
                              $this->forge->dropColumn('transactions', $columnsToDrop);
                              log_message('info', 'Kolom variant_id dan/atau variant_name berhasil dihapus dari tabel transactions.');
                         }
                      } catch (\Throwable $e) {
                          log_message('error', 'Gagal menghapus kolom variant_id/variant_name dari transactions: ' . $e->getMessage());
                      }
                 }
            }
        }
    }
    

