<?php

namespace App\Models;

use App\Libraries\SmartAlbumRules;
use CodeIgniter\Model;

class AlbumModel extends Model
{
    protected $table            = 'tbl_albums';
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
                $photo = $db->table('tbl_album_photos')
                            ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                            ->where('album_id', $album['id'])
                            ->orderBy('added_at', 'DESC')
                            ->get()->getRowArray();
                $album['thumbnail'] = $photo['thumbnail_path'] ?? null;
            }

            if ($isSmart) {
                $rules = SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
                $count = SmartAlbumRules::countMatching($userId, $rules);
                $album['photo_count'] = (string) $count;
                $album['video_count'] = '0';
            } else {
                $total = $db->table('tbl_album_photos')
                    ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                    ->where('tbl_album_photos.album_id', $album['id'])
                    ->countAllResults();
                $videos = $db->table('tbl_album_photos')
                    ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                    ->where('tbl_album_photos.album_id', $album['id'])
                    ->where('tbl_photos.mime_type LIKE', 'video/%')
                    ->countAllResults();
                $album['photo_count'] = (string) ($total - $videos);
                $album['video_count'] = (string) $videos;
            }
        }
        
        return $albums;
    }

    /**
     * Get dynamic AI auto-albums with live matching counts and representative thumbnails
     */
    public function getAiCollections(int $userId): array
    {
        $presets = SmartAlbumRules::getPresets();
        $collections = [];

        foreach ($presets as $key => $preset) {
            $rules = SmartAlbumRules::fromArray($preset['rules']);
            $count = SmartAlbumRules::countMatching($userId, $rules);

            $thumbnail = null;
            if ($count > 0) {
                $pm = new PhotoModel();
                $pm->where('user_id', $userId);
                SmartAlbumRules::apply($pm, $rules);
                $first = $pm->orderBy('taken_at', 'DESC')->first();
                $thumbnail = $first['thumbnail_path'] ?? null;
            }

            $collections[] = array_merge($preset, [
                'key'         => $key,
                'photo_count' => $count,
                'thumbnail'   => $thumbnail,
            ]);
        }

        return $collections;
    }
}
