<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductStockTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'product_id' => [ // Foreign key ke tabel products
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'stock_data' => [ // Data stok (bisa berupa teks, JSON, dll.)
                'type' => 'TEXT',
                'null' => false,
            ],
            'is_used' => [ // Status apakah stok ini sudah terpakai/terkirim
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
             'buyer_email' => [ // Email pembeli yang menerima stok ini (opsional, untuk tracking)
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'transaction_id' => [ // ID transaksi terkait (opsional, untuk tracking)
                 'type'       => 'INT',
                 'constraint' => 11,
                 'unsigned'   => true,
                 'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [ // Akan terupdate saat is_used=true atau saat diedit
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE'); // Jika produk dihapus, stoknya ikut terhapus
        $this->forge->addForeignKey('transaction_id', 'transactions', 'id', 'SET NULL', 'SET NULL'); // Jika transaksi dihapus, stok tetap ada tapi relasi hilang
        $this->forge->addKey(['product_id', 'is_used']); // Index untuk pencarian stok
        $this->forge->createTable('product_stock');
    }

    public function down()
    {
        $this->forge->dropTable('product_stock');
    }
}
