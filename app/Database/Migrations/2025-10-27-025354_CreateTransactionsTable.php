<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
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
            'order_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'user_id' => [ // ID Penjual
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'product_id' => [ // ID Produk yang dibeli
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Nullable, krn upgrade premium tdk ada product_id
            ],
            'buyer_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'buyer_email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'transaction_type' => [
                'type'       => 'ENUM',
                'constraint' => ['premium', 'product'],
                'default'    => 'product',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,0',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'success', 'failed', 'expired', 'challenge'],
                'default'    => 'pending',
            ],
            'snap_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'payment_gateway' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'midtrans',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('transactions');
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
    }
}
