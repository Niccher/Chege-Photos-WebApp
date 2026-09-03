<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompatibilityViews extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        $mappings = [
            'photos'            => 'tbl_photos',
            'albums'            => 'tbl_albums',
            'album_photos'      => 'tbl_album_photos',
            'photo_shares'      => 'tbl_photo_shares',
            'shared_links'      => 'tbl_shared_links',
            'photo_tags'        => 'tbl_photo_tags',
            'people'            => 'tbl_people',
            'person'            => 'tbl_people',
            'face_encodings'    => 'tbl_face_encodings',
            'face_encoding'     => 'tbl_face_encodings',
            'photo_scans'       => 'tbl_photo_scans',
            'photo_scan'        => 'tbl_photo_scans',
            'scan_jobs'         => 'tbl_scan_jobs',
            'scan_job'          => 'tbl_scan_jobs',
            'face_clusters'     => 'tbl_face_clusters',
            'face_cluster'      => 'tbl_face_clusters',
            'face_annotations'  => 'tbl_face_annotations',
            'face_annotation'   => 'tbl_face_annotations',
        ];

        foreach ($mappings as $view => $table) {
            if ($db->tableExists($table) && ! $db->tableExists($view)) {
                $db->query("CREATE OR REPLACE VIEW `{$view}` AS SELECT * FROM `{$table}`;");
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $views = [
            'photos', 'albums', 'album_photos', 'photo_shares', 'shared_links',
            'photo_tags', 'people', 'person', 'face_encodings', 'face_encoding',
            'photo_scans', 'photo_scan', 'scan_jobs', 'scan_job',
            'face_clusters', 'face_cluster', 'face_annotations', 'face_annotation'
        ];

        foreach ($views as $view) {
            $db->query("DROP VIEW IF EXISTS `{$view}`;");
        }
    }
}
