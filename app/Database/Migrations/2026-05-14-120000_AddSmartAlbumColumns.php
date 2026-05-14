<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSmartAlbumColumns extends Migration
{
    public function up()
    {
        $this->forge->addColumn('albums', [
            'is_smart' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'cover_photo_id',
            ],
            'smart_rules' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'is_smart',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('albums', ['is_smart', 'smart_rules']);
    }
}
