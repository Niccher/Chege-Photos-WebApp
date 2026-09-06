<?php

namespace App\Models;

use CodeIgniter\Model;

class PhotoModel extends Model
{
    protected $table            = 'tbl_photos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'device_id', 'device_uuid', 'upload_source', 'filename', 'path', 'thumbnail_path', 'taken_at', 'width', 'height', 'size', 'storage_driver', 'gcp_synced', 'gcp_synced_at', 'file_hash', 'mime_type', 'latitude', 'longitude', 'exif_data', 'ocr_text', 'is_archived', 'is_favorite', 'scanned_face', 'scanned_tag', 'scanned_clip', 'scanned_ocr', 'scanned_nsfw', 'is_vault', 'is_nsfw', 'nsfw_score', 'nsfw_details', 'vault_locked_at'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
