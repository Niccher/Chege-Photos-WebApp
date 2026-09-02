<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up(): void
    {
        $fields = [
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'username',
            ],
            'avatar' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'name',
            ],
        ];

        if ($this->db->tableExists('users')) {
            if (!$this->db->fieldExists('name', 'users')) {
                $this->forge->addColumn('users', ['name' => $fields['name']]);
            }
            if (!$this->db->fieldExists('avatar', 'users')) {
                $this->forge->addColumn('users', ['avatar' => $fields['avatar']]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('users')) {
            if ($this->db->fieldExists('name', 'users')) {
                $this->forge->dropColumn('users', 'name');
            }
            if ($this->db->fieldExists('avatar', 'users')) {
                $this->forge->dropColumn('users', 'avatar');
            }
        }
    }
}
