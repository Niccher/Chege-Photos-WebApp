<?php

namespace App\Models;

use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table            = 'person';
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
        return $db->table('person p')
            ->select('p.*, COUNT(fe.id) AS face_count')
            ->join('face_encoding fe', 'fe.person_id = p.id', 'left')
            ->groupBy('p.id')
            ->orderBy('p.id')
            ->get()
            ->getResultArray();
    }
}
