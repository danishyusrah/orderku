<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResetTokenToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'reset_token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'password_hash', // Or choose another suitable position
            ],
            'reset_token_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'reset_token_hash',
            ],
        ];
        $this->forge->addColumn('users', $fields);

        // Add index for faster token lookup
        $this->forge->addKey('reset_token_hash');
        // If your DB driver supports it, ensure modifyColumn applies keys correctly,
        // otherwise, you might need raw SQL or separate addKey after addColumn.
        // For simplicity here, assuming addKey after addColumn works or index isn't strictly needed immediately.
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['reset_token_hash', 'reset_token_expires_at']);
    }
}
