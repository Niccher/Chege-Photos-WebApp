<?php

namespace App\Models;

use App\Libraries\SmartAlbumRules;
use CodeIgniter\Model;

class AlbumModel extends Model
{
    protected $table            = 'albums';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'name', 'description', 'cover_photo_id', 'is_smart', 'smart_rules'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get albums with their first photo as thumbnail if cover is not set
     */
    public function getAlbumsWithThumbs($userId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('albums');
        
        $albums = $this->where('user_id', $userId)->findAll();
        
        foreach ($albums as &$album) {
            $isSmart = ! empty($album['is_smart']);
            if ($album['cover_photo_id']) {
                $photo = $db->table('photos')->where('id', $album['cover_photo_id'])->get()->getRowArray();
                $album['thumbnail'] = $photo['thumbnail_path'] ?? null;
            } elseif ($isSmart) {
                $rules = SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
                $pm    = new PhotoModel();
                $pm->where('user_id', $userId);
                SmartAlbumRules::apply($pm, $rules);
                $photo = $pm->orderBy('taken_at', 'DESC')->first();
                $album['thumbnail'] = $photo['thumbnail_path'] ?? null;
            } else {
                $photo = $db->table('album_photos')
                            ->join('photos', 'photos.id = album_photos.photo_id')
                            ->where('album_id', $album['id'])
                            ->orderBy('added_at', 'DESC')
                            ->get()->getRowArray();
                $album['thumbnail'] = $photo['thumbnail_path'] ?? null;
            }

            if ($isSmart) {
                $rules            = SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
                $album['count']   = SmartAlbumRules::countMatching($userId, $rules);
            } else {
                $album['count'] = $db->table('album_photos')->where('album_id', $album['id'])->countAllResults();
            }
        }
        
        return $albums;
    }
}
