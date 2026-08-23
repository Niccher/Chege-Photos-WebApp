<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFaceClusterTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'centroid_point_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
                'unique'     => true,
            ],
            'merged_from' => [
                'type' => 'TEXT', // Maps to SQLAlchemy JSON
                'null' => true,
            ],
            'split_from' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('person_id', 'tbl_person', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_face_cluster', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_face_cluster', true);
    }
}
