<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthTokenModel extends Model
{
    protected $table            = 'auth_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'token', 'description', 'is_used',
                                   'used_at', 'device_id', 'device_name', 'device_fingerprint'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
