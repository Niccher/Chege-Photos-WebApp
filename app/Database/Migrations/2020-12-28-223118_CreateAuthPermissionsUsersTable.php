<?php
namespace CodeIgniter\Shield\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateAuthPermissionsUsersTable extends Migration {
    public function up(): void {
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'permission' => ['type' => 'varchar', 'constraint' => 255, 'null' => false],
            'created_at' => ['type' => 'datetime', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('auth_permissions_users', true);
    }
    public function down(): void {
        $this->forge->dropTable('auth_permissions_users', true);
    }
}