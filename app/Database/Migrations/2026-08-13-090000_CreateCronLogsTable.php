<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCronLogsTable extends Migration
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
            'command' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'output' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'execution_time_ms' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('command');
        $this->forge->addKey('created_at');
        $this->forge->createTable('sys_cron_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sys_cron_logs', true);
    }
}
