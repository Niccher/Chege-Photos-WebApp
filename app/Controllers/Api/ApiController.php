<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ApiController extends BaseController
{
    /**
     * Get list of photos for the authenticated user.
     *
     * @return ResponseInterface
     */
    public function index()
    {
        try {
            $photoModel = new \App\Models\PhotoModel();
            $userId = auth()->id();

            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $photos = $photoModel->where('user_id', $userId)
                                ->where('is_archived', false)
                                ->orderBy('taken_at', 'DESC')
                                ->findAll();

            // Cast integer fields to strings for consistent Kotlin serialization
            $photos = array_map(function ($p) {
                $p['is_favorite'] = (string) ($p['is_favorite'] ?? '0');
                $p['is_archived'] = (string) ($p['is_archived'] ?? '0');
                return $p;
            }, $photos);

            return $this->response->setJSON([
                'status' => 'success',
                'photos' => $photos
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Get list of albums for the authenticated user.
     *
     * @return ResponseInterface
     */
    public function albums()
    {
        try {
            $albumModel = new \App\Models\AlbumModel();
            $userId = auth()->id();

            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $albums = $albumModel->getAlbumsWithThumbs($userId);

            return $this->response->setJSON([
                'status' => 'success',
                'albums' => $albums
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function albumPhotos($albumId)
    {
        try {
            $userId = auth()->id();

            if (! $userId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User not authenticated'])->setStatusCode(401);
            }

            $albumModel = new \App\Models\AlbumModel();
            $album      = $albumModel->where('user_id', $userId)->find($albumId);
            if (! $album) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Album not found'])->setStatusCode(404);
            }

            if (! empty($album['is_smart'])) {
                $rules      = \App\Libraries\SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
                $photoModel = new \App\Models\PhotoModel();
                $photoModel->where('user_id', $userId);
                \App\Libraries\SmartAlbumRules::apply($photoModel, $rules);
                $photos = $photoModel->orderBy('taken_at', 'DESC')->findAll();
            } else {
                $db     = \Config\Database::connect();
                $photos = $db->table('album_photos')
                    ->select('photos.*')
                    ->join('photos', 'photos.id = album_photos.photo_id')
                    ->where('album_photos.album_id', $albumId)
                    ->where('photos.user_id', $userId)
                    ->where('photos.is_archived', false)
                    ->orderBy('photos.taken_at', 'DESC')
                    ->get()->getResultArray();
            }

            $photos = array_map(function ($p) {
                $p['is_favorite'] = (string) ($p['is_favorite'] ?? '0');
                $p['is_archived'] = (string) ($p['is_archived'] ?? '0');
                return $p;
            }, $photos);

            return $this->response->setJSON([
                'status' => 'success',
                'photos' => $photos,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(500);
        }
    }

    public function memories()
    {
        return $this->getPhotosByCategory('memories');
    }

    public function favorites()
    {
        return $this->getPhotosByCategory('is_favorite', true);
    }

    public function archive()
    {
        return $this->getPhotosByCategory('is_archived', true);
    }

    public function trash()
    {
        return $this->getPhotosByCategory('is_deleted', true);
    }

    public function explore()
    {
        return $this->getPhotosByCategory('explore');
    }

    private function getPhotosByCategory($field, $value = null)
    {
        try {
            $photoModel = new \App\Models\PhotoModel();
            $userId = auth()->id();

            if (!$userId) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'User not authenticated'])->setStatusCode(401);
            }

            $query = $photoModel->where('user_id', $userId);

            if ($field === 'memories') {
                $today        = date('m-d');
                $thisYear     = date('Y');
                $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
                $query->where('is_archived', false)
                      ->groupStart()
                          ->where("DATE_FORMAT(taken_at, '%m-%d') =", $today)
                          ->where('YEAR(taken_at) <', $thisYear)
                      ->groupEnd()
                      ->orWhere('DATE(taken_at) =', $sixMonthsAgo)
                      ->orderBy('taken_at', 'DESC');
            } elseif ($field === 'explore') {
                $query->where('latitude IS NOT NULL')
                      ->where('longitude IS NOT NULL')
                      ->where('is_archived', false);
            } elseif ($field === 'is_deleted') {
                // Use CodeIgniter's soft delete scoping instead of a raw column
                $query->onlyDeleted();
            } else {
                $query->where($field, $value);
                // Exclude archived photos from standard views
                if ($field !== 'is_archived') $query->where('is_archived', false);
            }

            $photos = $query->orderBy('taken_at', 'DESC')->findAll();

            $photos = array_map(function ($p) {
                $p['is_favorite'] = (string) ($p['is_favorite'] ?? '0');
                $p['is_archived'] = (string) ($p['is_archived'] ?? '0');
                return $p;
            }, $photos);

            return $this->response->setJSON([
                'status' => 'success',
                'photos' => $photos
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(500);
        }
    }
}
