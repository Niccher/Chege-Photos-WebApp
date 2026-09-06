<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIgnoreFacesToPhotos extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_photos')) {
            if (!$this->db->fieldExists('ignore_faces', 'tbl_photos')) {
                $this->forge->addColumn('tbl_photos', [
                    'ignore_faces' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'after'      => 'scanned_face',
                    ],
                ]);

                // Add index for fast exclusion in sweeps
                $this->db->query("CREATE INDEX `idx_photos_ignore_faces` ON `tbl_photos` (`ignore_faces`)");
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_photos')) {
            if ($this->db->fieldExists('ignore_faces', 'tbl_photos')) {
                $this->forge->dropColumn('tbl_photos', 'ignore_faces');
            }
        }
    }
}
