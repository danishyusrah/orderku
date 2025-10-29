<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVariantIdToProductStock extends Migration
{
    public function up()
    {
        // 1. Tambahkan kolom variant_id ke tabel product_stock
        $fields = [
            'variant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Null untuk stok produk non-varian yang lama
                'after'      => 'product_id',
            ],
        ];

        $this->forge->addColumn('product_stock', $fields);

        // 2. Tambahkan Foreign Key ke tabel product_variants
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', 'CASCADE', 'CASCADE');
        
        // 3. Tambahkan index untuk pencarian stok varian
        $this->forge->addKey(['variant_id', 'is_used']);
    }

    public function down()
    {
        // Hapus kolom
        $this->forge->dropColumn('product_stock', 'variant_id');
    }
}
