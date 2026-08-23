<?php

namespace App\Models;

use CodeIgniter\Model;

class AlbumPhotoModel extends Model
{
    protected $table            = 'tbl_album_photos';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['album_id', 'photo_id', 'added_at'];

    // Dates
    protected $useTimestamps = false; // We set added_at manually or via DB default
}
