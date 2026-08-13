<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLogTables extends Migration
{
    public function up()
    {
        // ── Cron Logs Table ──────────────────────────────────────────
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
        $this->forge->createTable('cron_logs', true);

        // ── Email Logs Table ─────────────────────────────────────────
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tracking_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 63,
                'null'       => false,
            ],
            'recipient' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 31,
                'null'       => false,
            ],
            'debug_log' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tracking_id');
        $this->forge->createTable('email_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('cron_logs', true);
        $this->forge->dropTable('email_logs', true);
    }
}
