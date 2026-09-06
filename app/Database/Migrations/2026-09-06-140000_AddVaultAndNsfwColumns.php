<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVaultAndNsfwColumns extends Migration
{
    public function up()
    {
        // 1. tbl_photos updates
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToAdd = [];

            if (!$this->db->fieldExists('is_vault', 'tbl_photos')) {
                $fieldsToAdd['is_vault'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'is_favorite',
                ];
            }

            if (!$this->db->fieldExists('is_nsfw', 'tbl_photos')) {
                $fieldsToAdd['is_nsfw'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'is_vault',
                ];
            }

            if (!$this->db->fieldExists('nsfw_score', 'tbl_photos')) {
                $fieldsToAdd['nsfw_score'] = [
                    'type'       => 'FLOAT',
                    'default'    => 0.0,
                    'after'      => 'is_nsfw',
                ];
            }

            if (!$this->db->fieldExists('scanned_nsfw', 'tbl_photos')) {
                $fieldsToAdd['scanned_nsfw'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'scanned_ocr',
                ];
            }

            if (!$this->db->fieldExists('vault_locked_at', 'tbl_photos')) {
                $fieldsToAdd['vault_locked_at'] = [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'nsfw_score',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('tbl_photos', $fieldsToAdd);
            }

            // Indexes for fast vault isolation queries
            try {
                $indexes = $this->db->getIndexData('tbl_photos');
                $indexNames = array_column($indexes, 'name');

                if (!in_array('idx_photos_vault', $indexNames, true)) {
                    $this->db->query("CREATE INDEX `idx_photos_vault` ON `tbl_photos` (`user_id`, `is_vault`)");
                }
                if (!in_array('idx_photos_nsfw', $indexNames, true)) {
                    $this->db->query("CREATE INDEX `idx_photos_nsfw` ON `tbl_photos` (`is_nsfw`)");
                }
                if (!in_array('idx_scanned_nsfw', $indexNames, true)) {
                    $this->db->query("CREATE INDEX `idx_scanned_nsfw` ON `tbl_photos` (`scanned_nsfw`)");
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed creating indexes on tbl_photos: ' . $e->getMessage());
            }
        }

        // 2. users table updates for PIN & lockout
        $usersTable = $this->db->tableExists('users') ? 'users' : ($this->db->tableExists('tbl_users') ? 'tbl_users' : null);
        if ($usersTable) {
            $userFields = [];

            if (!$this->db->fieldExists('vault_pin_hash', $usersTable)) {
                $userFields['vault_pin_hash'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ];
            }

            if (!$this->db->fieldExists('vault_failed_attempts', $usersTable)) {
                $userFields['vault_failed_attempts'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ];
            }

            if (!$this->db->fieldExists('vault_locked_until', $usersTable)) {
                $userFields['vault_locked_until'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                ];
            }

            if (!$this->db->fieldExists('auto_vault_nsfw', $usersTable)) {
                $userFields['auto_vault_nsfw'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ];
            }

            if (!empty($userFields)) {
                $this->forge->addColumn($usersTable, $userFields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tbl_photos')) {
            $fieldsToDrop = [];
            foreach (['is_vault', 'is_nsfw', 'nsfw_score', 'scanned_nsfw', 'vault_locked_at'] as $f) {
                if ($this->db->fieldExists($f, 'tbl_photos')) {
                    $fieldsToDrop[] = $f;
                }
            }
            if (!empty($fieldsToDrop)) {
                $this->forge->dropColumn('tbl_photos', $fieldsToDrop);
            }
        }

        $usersTable = $this->db->tableExists('users') ? 'users' : ($this->db->tableExists('tbl_users') ? 'tbl_users' : null);
        if ($usersTable) {
            $uFieldsToDrop = [];
            foreach (['vault_pin_hash', 'vault_failed_attempts', 'vault_locked_until', 'auto_vault_nsfw'] as $uf) {
                if ($this->db->fieldExists($uf, $usersTable)) {
                    $uFieldsToDrop[] = $uf;
                }
            }
            if (!empty($uFieldsToDrop)) {
                $this->forge->dropColumn($usersTable, $uFieldsToDrop);
            }
        }
    }
}
