<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePhotoSharesTable extends Migration
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
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'shared_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'shared_with' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permission' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'view',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shared_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('shared_with', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_photo_shares');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_photo_shares', true);
    }
}
