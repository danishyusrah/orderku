<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsAndQuantity extends Migration
{
    public function up()
    {
        // 1. Add columns to 'users' table
        $userFields = [
            'store_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'username', // Position after username
            ],
            'profile_subtitle' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'store_name', // Position after store_name
            ],
            'logo_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'profile_subtitle', // Position after profile_subtitle
            ],
        ];
        // Ensure table exists before adding columns (optional but safer)
        if ($this->db->tableExists('users')) {
            $this->forge->addColumn('users', $userFields);
        } else {
             log_message('error', 'Table "users" does not exist. Migration AddProfileFieldsAndQuantity skipped adding columns to users.');
        }


        // 2. Add 'quantity' column to 'transactions' table
        $transactionFields = [
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1, // Default quantity is 1
                'after'      => 'amount', // Position after amount
            ],
            // Add other stock detail fields here (Option B, not recommended initially)
            // 'stock_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'midtrans_key_source'],
            // 'stock_password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'stock_email'],
            // 'stock_2fa' => ['type' => 'TEXT', 'null' => true, 'after' => 'stock_password'],
            // 'stock_gdrive_link' => ['type' => 'TEXT', 'null' => true, 'after' => 'stock_2fa'],
        ];
         if ($this->db->tableExists('transactions')) {
            $this->forge->addColumn('transactions', $transactionFields);
        } else {
            log_message('error', 'Table "transactions" does not exist. Migration AddProfileFieldsAndQuantity skipped adding columns to transactions.');
        }

         // 3. Modify 'stock_data' in 'product_stock' to TEXT (if not already)
         // And add optional fields for structured data (Option B, not recommended initially)
         $stockFieldsModify = [
             'stock_data' => [
                 'name' => 'stock_data',
                 'type' => 'TEXT', // Ensure it can hold JSON or longer text
                 'null' => false,
             ],
            // Add other stock detail fields here (Option B, not recommended initially)
            // 'stock_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'variant_id'],
            // 'stock_password' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'stock_email'],
            // 'stock_2fa' => ['type' => 'TEXT', 'null' => true, 'after' => 'stock_password'],
            // 'stock_gdrive_link' => ['type' => 'TEXT', 'null' => true, 'after' => 'stock_2fa'],
         ];
        if ($this->db->tableExists('product_stock')) {
            $this->forge->modifyColumn('product_stock', $stockFieldsModify);
             // If adding new columns for Option B
             // $this->forge->addColumn('product_stock', [ /* new fields */ ]);
        } else {
             log_message('error', 'Table "product_stock" does not exist. Migration AddProfileFieldsAndQuantity skipped modifying product_stock.');
        }
    }

    public function down()
    {
        // 1. Drop columns from 'users' table
        if ($this->db->tableExists('users')) {
            $this->forge->dropColumn('users', ['store_name', 'profile_subtitle', 'logo_filename']);
        }

        // 2. Drop column from 'transactions' table
         if ($this->db->tableExists('transactions')) {
            $this->forge->dropColumn('transactions', 'quantity');
            // Drop other stock detail columns if Option B was used
            // $this->forge->dropColumn('transactions', ['stock_email', 'stock_password', 'stock_2fa', 'stock_gdrive_link']);
        }

        // 3. Revert 'stock_data' type in 'product_stock' if needed (optional)
        // And drop columns if Option B was used
        if ($this->db->tableExists('product_stock')) {
            // Revert type change if necessary
            // $this->forge->modifyColumn('product_stock', [
            //     'stock_data' => ['name' => 'stock_data', 'type' => 'VARCHAR', 'constraint' => 255] // Example revert
            // ]);
             // Drop columns if Option B was used
             // $this->forge->dropColumn('product_stock', ['stock_email', 'stock_password', 'stock_2fa', 'stock_gdrive_link']);
        }
    }
}
