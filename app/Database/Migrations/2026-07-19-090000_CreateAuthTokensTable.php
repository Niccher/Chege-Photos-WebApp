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
            'scopes'            => ['type' => 'TEXT', 'null' => true],
            'is_used'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'used_at'           => ['type' => 'DATETIME', 'null' => true],
            'device_id'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'device_uuid'       => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true],
            'device_name'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'device_fingerprint' => ['type' => 'TEXT', 'null' => true],
            'os_version'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'screen_metrics'    => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'locale'            => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'timezone'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'kernel_version'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_auth_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tbl_auth_tokens', true);
    }
}
