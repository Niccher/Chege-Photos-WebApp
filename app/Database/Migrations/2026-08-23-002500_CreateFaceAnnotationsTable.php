<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFaceAnnotationsTable extends Migration
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
            'face_encoding_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 20, // 'confirm', 'reject'
            ],
            'annotated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('face_encoding_id', 'tbl_face_encodings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'tbl_people', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_face_annotations', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_face_annotations', true);
    }
}
