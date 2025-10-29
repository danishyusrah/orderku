<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMidtransSourceToTransactions extends Migration
{
    public function up()
    {
        $fields = [
            'midtrans_key_source' => [
                'type'       => 'ENUM',
                'constraint' => ['default', 'user'],
                'default'    => 'default',
                'after'      => 'payment_gateway', // Atau posisi lain yang sesuai
            ],
        ];
        $this->forge->addColumn('transactions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'midtrans_key_source');
    }
}
