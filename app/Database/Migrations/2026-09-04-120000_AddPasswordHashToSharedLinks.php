<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordHashToSharedLinks extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_shared_links') && !$this->db->fieldExists('password_hash', 'tbl_shared_links')) {
            $this->forge->addColumn('tbl_shared_links', [
                'password_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'access_token',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_shared_links') && $this->db->fieldExists('password_hash', 'tbl_shared_links')) {
            $this->forge->dropColumn('tbl_shared_links', 'password_hash');
        }
    }
}
