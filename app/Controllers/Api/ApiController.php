<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ApiController extends BaseController
{
    /**
     * Get dynamic system configuration and mobile client capabilities.
     */
    public function config()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'app_name'               => setting('App.siteName') ?? 'Chege Photos',
                'support_email'          => setting('App.supportEmail') ?? 'support@chegephotos.com',
                'max_upload_size_mb'     => (int) (setting('App.maxUploadSizeMb') ?: 500),
                'max_batch_upload_count' => (int) (setting('App.maxBatchUploadCount') ?: 50),
                'allowed_extensions'     => setting('App.allowedExtensions') ?: 'jpg,jpeg,png,webp,heic,tiff,mp4,mov,m4v,webm,mkv,avi',
                'default_storage_limit'  => (int) (setting('App.storageLimit') ?: 1073741824),
                'allow_registration'     => (bool) (setting('Auth.allowRegistration') ?? true),
                'timezone'               => setting('App.timezone') ?? 'Africa/Nairobi',
                'date_format'            => setting('App.dateFormat') ?? 'Y-m-d',
                'capabilities'           => [
                    'video_upload'     => true,
                    'face_recognition' => true,
                    'semantic_search'  => true,
                    'object_tagging'   => true,
                    'photo_editing'    => false, // Explicitly disabled for Android for now
                ]
            ]
        ]);
    }

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

            $q = trim($this->request->getGet('q') ?? '');
            $query = $photoModel->where('user_id', $userId)
                                ->where('is_archived', false);

            if ($q !== '') {
                $db = \Config\Database::connect();
                $matchedPhotoIds = $db->table('photo_tags')
                                      ->select('photo_id')
                                      ->like('tag', $q)
                                      ->get()
                                      ->getResultArray();
                $photoIds = array_column($matchedPhotoIds, 'photo_id');

                // Query FastAPI ML service for CLIP semantic search
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 6,
                        'headers'         => [
                            'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                        ]
                    ]);

                    $mlDefault = (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) ? 'http://ml-chege-photos.railway.internal:8000' : 'http://ml-chege-photos:8000';
                    $url = (env('ML_URL') ?: $mlDefault) . '/api/v1/search/semantic?' . http_build_query([
                        'query'   => $q,
                        'limit'   => 100,
                        'user_id' => $userId
                    ]);

                    $response = $client->get($url);
                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody(), true);
                        if (!empty($body['results'])) {
                            $semanticPhotoIds = array_column($body['results'], 'photo_id');
                            $photoIds = array_merge($photoIds, $semanticPhotoIds);
                            $photoIds = array_values(array_unique($photoIds));
                        }
                    }
                } catch (\Throwable $t) {
                    log_message('error', 'API CLIP semantic search call failed: ' . $t->getMessage());
                }

                $query->groupStart()
                      ->like('filename', $q)
                      ->orLike('exif_data', $q)
                      ->orLike('taken_at', $q);
                if (!empty($photoIds)) {
                    $query->orWhereIn('id', $photoIds);
                }
                $query->groupEnd();
            }

            $photos = $query->orderBy('taken_at', 'DESC')->findAll();

            $photos = array_map([$this, 'formatPhotoForApi'], $photos);

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
            $albums = array_map([$this, 'formatAlbumForApi'], $albums);

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
                $photos = $db->table('tbl_album_photos')
                    ->select('tbl_photos.*')
                    ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                    ->where('tbl_album_photos.album_id', $albumId)
                    ->where('tbl_photos.user_id', $userId)
                    ->where('tbl_photos.is_archived', false)
                    ->orderBy('tbl_photos.taken_at', 'DESC')
                    ->get()->getResultArray();
            }

            $photos = array_map([$this, 'formatPhotoForApi'], $photos);

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
                $thisYear     = (int) date('Y');
                $threeDaysAgo = date('m-d', strtotime('-3 days'));
                $threeDaysFut = date('m-d', strtotime('+3 days'));

                // Tier 1: On this exact calendar day in previous years
                $builder = (new \App\Models\PhotoModel())
                    ->where('user_id', $userId)
                    ->where('is_archived', false)
                    ->where("DATE_FORMAT(taken_at, '%m-%d') =", $today)
                    ->where('YEAR(taken_at) <', $thisYear);

                if ($builder->countAllResults(false) > 0) {
                    $query = $builder->orderBy('taken_at', 'DESC');
                } else {
                    // Tier 2: Within +/- 3 days in previous years
                    $builder2 = (new \App\Models\PhotoModel())
                        ->where('user_id', $userId)
                        ->where('is_archived', false)
                        ->where("DATE_FORMAT(taken_at, '%m-%d') >=", $threeDaysAgo)
                        ->where("DATE_FORMAT(taken_at, '%m-%d') <=", $threeDaysFut)
                        ->where('YEAR(taken_at) <', $thisYear);

                    if ($builder2->countAllResults(false) > 0) {
                        $query = $builder2->orderBy('taken_at', 'DESC');
                    } else {
                        // Tier 3: Same month in previous years
                        $month = date('m');
                        $query->where('is_archived', false)
                              ->where("DATE_FORMAT(taken_at, '%m') =", $month)
                              ->where('YEAR(taken_at) <', $thisYear)
                              ->orderBy('is_favorite', 'DESC')
                              ->orderBy('taken_at', 'DESC');
                    }
                }
            } elseif ($field === 'explore') {
                // Multi-modal explore: Photos with GPS, or analyzed by ML, or favorited
                $query->where('is_archived', false)
                      ->groupStart()
                          ->where('latitude IS NOT NULL')
                          ->where('longitude IS NOT NULL')
                          ->orWhere('scanned_tag', 1)
                          ->orWhere('scanned_face', 1)
                          ->orWhere('is_favorite', true)
                      ->groupEnd()
                      ->orderBy('is_favorite', 'DESC')
                      ->orderBy('taken_at', 'DESC');
            } elseif ($field === 'is_deleted') {
                // Use CodeIgniter's soft delete scoping instead of a raw column
                $query->onlyDeleted();
            } else {
                $query->where($field, $value);
                // Exclude archived photos from standard views
                if ($field !== 'is_archived') $query->where('is_archived', false);
            }

            $photos = $query->orderBy('taken_at', 'DESC')->findAll();

            $photos = array_map([$this, 'formatPhotoForApi'], $photos);

            return $this->response->setJSON([
                'status' => 'success',
                'photos' => $photos
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => $e->getMessage()])->setStatusCode(500);
        }
    }

    public function checkHashes()
    {
        try {
            $sha256 = $this->request->getPost('sha256') ?? $this->request->getVar('sha256');

            if (empty($sha256)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'sha256 parameter is required'
                ])->setStatusCode(400);
            }

            $photoModel = new \App\Models\PhotoModel();
            $userId = auth()->id();

            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $existing = $photoModel->where('file_hash', $sha256)
                                  ->where('user_id', $userId)
                                  ->first();

            if ($existing) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Photo exists',
                    'photo' => $this->formatPhotoForApi($existing)
                ]);
            }

            return $this->response->setJSON([
                'status' => 'not_found',
                'message' => 'Photo does not exist'
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function createAlbum()
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $albumModel = new \App\Models\AlbumModel();
            $name = $this->request->getPost('name') ?? $this->request->getVar('name');
            $description = $this->request->getPost('description') ?? $this->request->getVar('description');

            if (empty($name)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Name is required'
                ])->setStatusCode(400);
            }

            $data = [
                'user_id'     => $userId,
                'name'        => $name,
                'description' => $description,
                'is_smart'    => 0,
                'smart_rules' => null,
            ];

            if ($albumModel->insert($data)) {
                $this->clearSidebarCountsCache($userId);
                $insertedId = $albumModel->getInsertID();
                $album = $albumModel->find($insertedId);

                $formattedAlbum = [
                    'id'          => (string) $album['id'],
                    'user_id'     => (string) $album['user_id'],
                    'name'        => (string) $album['name'],
                    'description' => $album['description'] ? (string) $album['description'] : null,
                    'cover_photo' => null,
                    'photo_count' => '0',
                    'video_count' => '0'
                ];

                return $this->response->setJSON([
                    'status' => 'success',
                    'album'  => $formattedAlbum
                ]);
            }

            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to create album'
            ])->setStatusCode(500);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function updateAlbum($id)
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $albumModel = new \App\Models\AlbumModel();
            $album = $albumModel->where('user_id', $userId)->find($id);

            if (!$album) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Album not found'
                ])->setStatusCode(404);
            }

            $input = $this->request->getRawInput();
            $name = $input['name'] ?? $this->request->getVar('name') ?? $album['name'];
            $description = $input['description'] ?? $this->request->getVar('description') ?? $album['description'];

            if (empty($name)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Name is required'
                ])->setStatusCode(400);
            }

            $albumModel->update($id, [
                'name'        => $name,
                'description' => $description
            ]);

            $this->clearSidebarCountsCache($userId);

            $db = \Config\Database::connect();
            $updatedAlbum = $albumModel->find($id);

            $total = $db->table('tbl_album_photos')
                ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                ->where('tbl_album_photos.album_id', $id)
                ->countAllResults();
            $videos = $db->table('tbl_album_photos')
                ->join('tbl_photos', 'tbl_photos.id = tbl_album_photos.photo_id')
                ->where('tbl_album_photos.album_id', $id)
                ->where('tbl_photos.mime_type LIKE', 'video/%')
                ->countAllResults();

            $formattedAlbum = [
                'id'          => (string) $updatedAlbum['id'],
                'user_id'     => (string) $updatedAlbum['user_id'],
                'name'        => (string) $updatedAlbum['name'],
                'description' => $updatedAlbum['description'] ? (string) $updatedAlbum['description'] : null,
                'cover_photo' => null,
                'photo_count' => (string) ($total - $videos),
                'video_count' => (string) $videos
            ];

            return $this->response->setJSON([
                'status' => 'success',
                'album'  => $formattedAlbum
            ]);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function deleteAlbum($id)
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ])->setStatusCode(401);
            }

            $albumModel = new \App\Models\AlbumModel();
            $album = $albumModel->where('user_id', $userId)->find($id);

            if (!$album) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Album not found'
                ])->setStatusCode(404);
            }

            $db = \Config\Database::connect();
            $db->table('tbl_album_photos')->where('album_id', $id)->delete();
            $albumModel->delete($id);

            $this->clearSidebarCountsCache($userId);

            return $this->response->setJSON([
                'status' => 'success'
            ]);

        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
    private function formatPhotoForApi(array $p): array
    {
        $p['id']          = isset($p['id']) ? (string) $p['id'] : null;
        $p['user_id']     = isset($p['user_id']) ? (string) $p['user_id'] : null;
        $p['album_id']    = isset($p['album_id']) ? (string) $p['album_id'] : null;
        $p['width']       = isset($p['width']) ? (string) $p['width'] : null;
        $p['height']      = isset($p['height']) ? (string) $p['height'] : null;
        $p['size']         = isset($p['size']) ? (string) $p['size'] : null;
        $p['latitude']    = isset($p['latitude']) ? (string) $p['latitude'] : null;
        $p['longitude']   = isset($p['longitude']) ? (string) $p['longitude'] : null;
        $p['is_favorite'] = (string) ($p['is_favorite'] ?? '0');
        $p['is_archived'] = (string) ($p['is_archived'] ?? '0');
        $p['file_hash']   = isset($p['file_hash']) ? (string) $p['file_hash'] : null;
        if (array_key_exists('deleted_at', $p)) {
            $p['is_deleted'] = $p['deleted_at'] ? '1' : '0';
        } else {
            $p['is_deleted'] = '0';
        }
        return $p;
    }

    private function formatAlbumForApi(array $album): array
    {
        return [
            'id'          => isset($album['id']) ? (string) $album['id'] : null,
            'user_id'     => isset($album['user_id']) ? (string) $album['user_id'] : null,
            'name'        => (string) $album['name'],
            'description' => $album['description'] ? (string) $album['description'] : null,
            'cover_photo' => isset($album['thumbnail']) ? (string) $album['thumbnail'] : null,
            'photo_count' => isset($album['photo_count']) ? (string) $album['photo_count'] : '0',
            'video_count' => isset($album['video_count']) ? (string) $album['video_count'] : '0',
        ];
    }
}
