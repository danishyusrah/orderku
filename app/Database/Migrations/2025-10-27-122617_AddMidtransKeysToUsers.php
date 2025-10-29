<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMidtransKeysToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'midtrans_server_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'account_name', // Sesuaikan posisi jika perlu
            ],
            'midtrans_client_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'midtrans_server_key',
            ],
             // Opsional: Tambahkan kolom untuk production/sandbox per user jika perlu
            // 'midtrans_is_production' => [
            //     'type'       => 'BOOLEAN',
            //     'null'       => true, // Null berarti pakai default config
            //     'after'      => 'midtrans_client_key',
            // ],
        ];
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['midtrans_server_key', 'midtrans_client_key'/*, 'midtrans_is_production'*/]);
    }
}
