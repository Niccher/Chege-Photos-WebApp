<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmotionToFaceEncoding extends Migration
{
    public function up(): void
    {
        $fields = [
            'emotion' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'gender',
            ],
        ];
        $this->forge->addColumn('tbl_face_encoding', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tbl_face_encoding', 'emotion');
    }
}
