<?php

namespace App\Models;

use CodeIgniter\Model;

class PhotoTagModel extends Model
{
    protected $table            = 'photo_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['photo_id', 'tag', 'confidence', 'created_at'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
