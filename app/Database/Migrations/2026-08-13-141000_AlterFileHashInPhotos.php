<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterFileHashInPhotos extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('photos', [
            'file_hash' => [
                'name'       => 'file_hash',
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('photos', [
            'file_hash' => [
                'name'       => 'file_hash',
                'type'       => 'VARCHAR',
                'constraint' => '32',
                'null'       => true,
            ],
        ]);
    }
}
