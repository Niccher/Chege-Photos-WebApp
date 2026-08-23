<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCronLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'job_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 31,
                'null'       => false,
            ],
            'output' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'duration_seconds' => [
                'type'       => 'FLOAT',
                'default'    => 0,
            ],
            'run_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sys_cron_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('sys_cron_logs', true);
    }
}
