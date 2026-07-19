<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNameToUsers extends Migration
{
    public function up(): void
    {
        // Only add 'name' if the column does not yet exist (idempotent)
        if (! $this->db->fieldExists('name', 'users')) {
            $this->forge->addColumn('users', [
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'after'      => 'username',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('name', 'users')) {
            $this->forge->dropColumn('users', 'name');
        }
    }
}
