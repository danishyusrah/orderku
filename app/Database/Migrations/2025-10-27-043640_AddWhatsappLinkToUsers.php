<?php // Pastikan ini ada di baris paling pertama tanpa spasi sebelumnya

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsappLinkToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'whatsapp_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true, // Link boleh kosong
                'after'      => 'is_premium', // Letakkan setelah kolom is_premium
            ],
        ];
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'whatsapp_link');
    }
}

