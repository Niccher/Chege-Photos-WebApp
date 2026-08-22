<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhotoTagsTable extends Migration
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
            'photo_id' => [
                'type'       => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'tag' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'confidence' => [
                'type' => 'FLOAT',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('photo_id', 'photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('tag');
        $this->forge->createTable('photo_tags');
    }

    public function down()
    {
        $this->forge->dropTable('photo_tags');
    }
}
