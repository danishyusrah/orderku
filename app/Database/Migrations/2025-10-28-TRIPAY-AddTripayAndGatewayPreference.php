<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TRIPAYAddTripayAndGatewayPreference extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'tripay_api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'midtrans_client_key',
            ],
            'tripay_private_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'tripay_api_key',
            ],
            'tripay_merchant_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'tripay_private_key',
            ],
            'gateway_active' => [
                'type'       => 'ENUM',
                // Tambahkan 'orderkuota' ke constraint ENUM
                'constraint' => ['system', 'midtrans', 'tripay', 'orderkuota'], // <-- Updated line
                'default'    => 'system',
                'after'      => 'tripay_merchant_code',
            ],
        ]);
    }

    public function down()
    {
        // Pastikan kolom dihapus saat rollback
        $this->forge->dropColumn('users', ['tripay_api_key', 'tripay_private_key', 'tripay_merchant_code', 'gateway_active']);
    }
}
