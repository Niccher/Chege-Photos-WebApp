<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOcrTextToPhotosTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToAdd = [];

            if (!$this->db->fieldExists('ocr_text', 'tbl_photos')) {
                $fieldsToAdd['ocr_text'] = [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                    'after' => 'exif_data',
                ];
            }

            if (!$this->db->fieldExists('scanned_ocr', 'tbl_photos')) {
                $fieldsToAdd['scanned_ocr'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'scanned_clip',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('tbl_photos', $fieldsToAdd);
            }

            // Add index on scanned_ocr if not present
            try {
                $indexes = $this->db->getIndexData('tbl_photos');
                $hasIndex = false;
                foreach ($indexes as $idx) {
                    if ($idx->name === 'idx_scanned_ocr') {
                        $hasIndex = true;
                        break;
                    }
                }
                if (!$hasIndex) {
                    $this->db->query("CREATE INDEX `idx_scanned_ocr` ON `tbl_photos` (`scanned_ocr`)");
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed creating index idx_scanned_ocr: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToDrop = [];
            foreach (['ocr_text', 'scanned_ocr'] as $f) {
                if ($this->db->fieldExists($f, 'tbl_photos')) {
                    $fieldsToDrop[] = $f;
                }
            }
            if (!empty($fieldsToDrop)) {
                $this->forge->dropColumn('tbl_photos', $fieldsToDrop);
            }
        }
    }
}
