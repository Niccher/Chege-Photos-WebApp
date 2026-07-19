<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTokensTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'token'             => ['type' => 'VARCHAR', 'constraint' => 8],
            'description'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_used'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'used_at'           => ['type' => 'DATETIME', 'null' => true],
            'device_id'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'device_name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'device_fingerprint' => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addKey('user_id');
        $this->forge->createTable('auth_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auth_tokens', true);
    }
}
