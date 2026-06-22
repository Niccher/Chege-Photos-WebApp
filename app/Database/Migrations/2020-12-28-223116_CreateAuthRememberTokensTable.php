<?php
namespace CodeIgniter\Shield\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateAuthRememberTokensTable extends Migration {
    public function up(): void {
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'selector'        => ['type' => 'varchar', 'constraint' => 255],
            'hashedValidator' => ['type' => 'varchar', 'constraint' => 255],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'expires'         => ['type' => 'datetime'],
            'created_at'      => ['type' => 'datetime', 'null' => false],
            'updated_at'      => ['type' => 'datetime', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('selector');
        $this->forge->createTable('auth_remember_tokens', true);
    }
    public function down(): void {
        $this->forge->dropTable('auth_remember_tokens', true);
    }
}