<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRIPAYAddTripayColumnsToTransactions extends Migration
{
    public function up()
    {
        $this->forge->addColumn('transactions', [
            'tripay_reference' => [
                'type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'snap_token'
            ],
            'tripay_pay_url' => [
                'type' => 'TEXT', 'null' => true, 'after' => 'tripay_reference'
            ],
            'tripay_raw' => [
                'type' => 'TEXT', 'null' => true, 'after' => 'tripay_pay_url'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', ['tripay_reference', 'tripay_pay_url', 'tripay_raw']);
    }
}
