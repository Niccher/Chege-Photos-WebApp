<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFaceEncodingTable extends Migration
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
            'photo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'qdrant_point_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'unique'     => true,
            ],
            'bbox_x' => [
                'type' => 'FLOAT',
            ],
            'bbox_y' => [
                'type' => 'FLOAT',
            ],
            'bbox_w' => [
                'type' => 'FLOAT',
            ],
            'bbox_h' => [
                'type' => 'FLOAT',
            ],
            'landmark_left_eye_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_eye_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_eye_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_eye_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_nose_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_nose_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_mouth_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_left_mouth_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_mouth_x' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'landmark_right_mouth_y' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'detection_score' => [
                'type' => 'FLOAT',
                'null' => true,
            ],
            'model_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'face_image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'age' => [
                'type' => 'INT',
                'null' => true,
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('photo_id', 'tbl_photos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('person_id', 'tbl_person', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('tbl_face_encoding', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_face_encoding', true);
    }
}
