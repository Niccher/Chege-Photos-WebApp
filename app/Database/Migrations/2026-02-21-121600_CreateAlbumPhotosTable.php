<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAlbumPhotosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'album_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'photo_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'added_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addForeignKey('album_id', 'tbl_albums', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_album_photos');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_album_photos');
    }
}
