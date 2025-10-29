<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateProductVariantsTable extends Migration
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
            'name' => [ // Nama varian (cth: Merah, XL, 1 Bulan)
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'price' => [ // Harga spesifik untuk varian ini
                'type'       => 'DECIMAL(10, 0)',
                'null'       => false,
                'default'    => 0,
            ],
            'stock' => [ // Jumlah stok untuk varian ini (jika produk tipe 'auto')
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
             'is_active' => [ // Status aktif varian
                'type'       => 'BOOLEAN',
                'default'    => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'      => 'DATETIME',
                'null'      => true,
                'on update' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE'); // Jika produk dihapus, variannya ikut terhapus
        $this->forge->addKey(['product_id', 'name']); // Index untuk pencarian
        $this->forge->createTable('product_variants');

        // Tambah kolom flag has_variants ke tabel products (opsional tapi bisa berguna)
        $this->forge->addColumn('products', [
            'has_variants' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'after' => 'price' // Sesuaikan posisi jika perlu
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', 'has_variants');
        $this->forge->dropTable('product_variants');
    }
}
