<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table            = 'tbl_people';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'thumbnail_face_id', 'cluster_label'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPersonsWithFaceCount(): array
    {
        $db = \Config\Database::connect();
        return $db->table('tbl_people p')
            ->select('p.*, COUNT(fe.id) AS face_count')
            ->join('tbl_face_encodings fe', 'fe.person_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.id')
            ->get()
            ->getResultArray();
    }

    public function getPersonsWithFaceCountForUser(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('tbl_people p')
            ->select('p.*, COUNT(fe.id) AS face_count')
            ->join('tbl_face_encodings fe', 'fe.person_id = p.id', 'left')
            ->join('tbl_photos ph', 'ph.id = fe.photo_id', 'left')
            ->where('ph.user_id', $userId)
            ->where('ph.is_vault', 0)
            ->groupBy('p.id')
            ->having('face_count >', 0)
            ->orderBy('p.id')
            ->get()
            ->getResultArray();
    }

    public function getPersonsWithVaultCountForUser(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('tbl_people p')
            ->select('p.*, COUNT(DISTINCT ph.id) AS vault_photo_count')
            ->join('tbl_face_encodings fe', 'fe.person_id = p.id', 'inner')
            ->join('tbl_photos ph', 'ph.id = fe.photo_id', 'inner')
            ->where('ph.user_id', $userId)
            ->where('ph.is_vault', 1)
            ->groupBy('p.id')
            ->having('vault_photo_count >', 0)
            ->orderBy('p.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPersonPhotoIds(int $personId, int $userId, ?int $isVault = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_face_encodings fe')
            ->select('ph.id')
            ->join('tbl_photos ph', 'ph.id = fe.photo_id', 'inner')
            ->where('fe.person_id', $personId)
            ->where('ph.user_id', $userId);

        if ($isVault !== null) {
            $builder->where('ph.is_vault', $isVault);
        }

        $rows = $builder->groupBy('ph.id')->get()->getResultArray();
        return array_map('intval', array_column($rows, 'id'));
    }
}
