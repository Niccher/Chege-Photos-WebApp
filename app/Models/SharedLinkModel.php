<?php

namespace App\Models;

use CodeIgniter\Model;

class SharedLinkModel extends Model
{
    protected $table            = 'tbl_shared_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['photo_id', 'access_token', 'password_hash', 'created_at', 'expires_at'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $deletedField  = '';

    /**
     * Finds a valid shared link by its token.
     */
    public function findByToken(string $token)
    {
        return $this->where('access_token', $token)
                    ->groupStart()
                        ->where('expires_at IS NULL')
                        ->orWhere('expires_at >', date('Y-m-d H:i:s'))
                    ->groupEnd()
                    ->first();
    }
}
