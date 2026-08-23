<?php
namespace CodeIgniter\Shield\Database\Migrations;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration {
    public function up(): void {
        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'       => ['type' => 'varchar', 'constraint' => 30, 'null' => true],
            'name'           => ['type' => 'varchar', 'constraint' => 191, 'null' => true, 'after' => 'username'],
            'status'         => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'tinyint', 'constraint' => 1, 'null' => 0, 'default' => 0],
            'last_active'    => ['type' => 'datetime', 'null' => true],
            'created_at'     => ['type' => 'datetime', 'null' => true],
            'updated_at'     => ['type' => 'datetime', 'null' => true],
            'deleted_at'     => ['type' => 'datetime', 'null' => true],
    
            'first_name'     => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'last_name'      => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'avatar'         => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true);
    }
    public function down(): void {
        $this->forge->dropTable('users', true);
    }
}