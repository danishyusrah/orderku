<?php // Pastikan ini ada di baris paling pertama tanpa spasi sebelumnya

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyProductIconColumn extends Migration
{
    public function up()
    {
        $fields = [
            'icon' => [
                'name'       => 'icon_filename', // Ganti nama kolom
                'type'       => 'VARCHAR',
                'constraint' => 255,         // Perbesar constraint untuk nama file acak
                'null'       => true,          // Izinkan null jika tidak ada ikon
                'default'    => null,
            ],
        ];
        $this->forge->modifyColumn('products', $fields);
    }

    public function down()
    {
        // Kembalikan ke state sebelumnya jika diperlukan (opsional)
        $fields = [
            'icon_filename' => [
                'name'       => 'icon',
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'fa-solid fa-box',
                 'null'      => false, // Kembali ke not null jika sebelumnya begitu
            ],
        ];
        $this->forge->modifyColumn('products', $fields);
    }
}

