<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNsfwDetailsColumn extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_photos')) {
            if (!$this->db->fieldExists('nsfw_details', 'tbl_photos')) {
                $this->forge->addColumn('tbl_photos', [
                    'nsfw_details' => [
                        'type'  => 'TEXT',
                        'null'  => true,
                        'after' => 'nsfw_score',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_photos')) {
            if ($this->db->fieldExists('nsfw_details', 'tbl_photos')) {
                $this->forge->dropColumn('tbl_photos', 'nsfw_details');
            }
        }
    }
}
