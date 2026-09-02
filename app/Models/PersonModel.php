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
            ->groupBy('p.id')
            ->having('face_count >', 0)
            ->orderBy('p.id')
            ->get()
            ->getResultArray();
    }
}
