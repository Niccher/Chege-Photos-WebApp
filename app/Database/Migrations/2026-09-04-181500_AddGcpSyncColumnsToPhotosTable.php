<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGcpSyncColumnsToPhotosTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToAdd = [];

            if (!$this->db->fieldExists('storage_driver', 'tbl_photos')) {
                $fieldsToAdd['storage_driver'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'hybrid',
                    'null'       => false,
                    'after'      => 'size',
                ];
            }

            if (!$this->db->fieldExists('gcp_synced', 'tbl_photos')) {
                $fieldsToAdd['gcp_synced'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'storage_driver',
                ];
            }

            if (!$this->db->fieldExists('gcp_synced_at', 'tbl_photos')) {
                $fieldsToAdd['gcp_synced_at'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'gcp_synced',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('tbl_photos', $fieldsToAdd);
            }

            // Create index for fast querying of unsynced items
            try {
                $this->db->query("CREATE INDEX `idx_gcp_synced` ON `tbl_photos` (`gcp_synced`)");
            } catch (\Throwable $e) {
                // Ignore if index already exists
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToDrop = [];
            foreach (['gcp_synced_at', 'gcp_synced', 'storage_driver'] as $f) {
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
