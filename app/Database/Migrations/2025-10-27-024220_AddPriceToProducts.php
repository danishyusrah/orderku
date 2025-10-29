<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPriceToProducts extends Migration
{
    public function up()
    {
        $fields = [
            'price' => [
                'type'       => 'DECIMAL(10, 0)', // Untuk harga dalam Rupiah (tanpa koma)
                'null'       => true,
                'default'    => 0,
                'after'      => 'description', // Letakkan setelah kolom deskripsi
            ],
        ];
        $this->forge->addColumn('products', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('products', 'price');
    }
}
