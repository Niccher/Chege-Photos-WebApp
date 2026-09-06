<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SmartAlbumRules;
use App\Models\AlbumModel;
use App\Models\FaceEncodingModel;
use App\Models\PhotoModel;
use App\Models\PhotoShareModel;
use App\Models\PhotoTagModel;
use App\Models\SharedLinkModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use ZipArchive;

class Photos extends BaseController
{
    private const MAX_UPLOAD_BYTES = 5_368_709_120; // 5 GB (5,368,709,120 bytes) fallback
    public function index()
    {
        $photoModel = new \App\Models\PhotoModel();
        
        // 1. Get counts and storage first (resets builder)
        $userId = auth()->id();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $counts = $this->getSidebarCounts();

        // 2. Build the main query afresh
        $query = $photoModel->where('user_id', $userId)
                            ->where('is_archived', false);

        $sort = $this->request->getGet('sort') ?? 'date_desc';
        switch ($sort) {
            case 'date_asc':
                $query->orderBy('taken_at', 'ASC');
                break;
            case 'upload_desc':
                $query->orderBy('created_at', 'DESC');
                break;
            case 'upload_asc':
                $query->orderBy('created_at', 'ASC');
                break;
            case 'size_desc':
                $query->orderBy('size', 'DESC');
                break;
            case 'size_asc':
                $query->orderBy('size', 'ASC');
                break;
            case 'resolution_desc':
                $query->orderBy('(width * height)', 'DESC');
                break;
            case 'resolution_asc':
                $query->orderBy('(width * height)', 'ASC');
                break;
            case 'name_asc':
                $query->orderBy('filename', 'ASC');
                break;
            case 'name_desc':
                $query->orderBy('filename', 'DESC');
                break;
            case 'geotagged':
                $query->orderBy('(latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != 0 AND longitude != 0)', 'DESC')
                      ->orderBy('taken_at', 'DESC');
                break;
            case 'favorites':
                $query->orderBy('is_favorite', 'DESC')
                      ->orderBy('taken_at', 'DESC');
                break;
            case 'date_desc':
            default:
                $sort = 'date_desc';
                $query->orderBy('taken_at', 'DESC');
                break;
        }

        $q = $this->request->getGet('q');
        $query = $this->applySearchQuery($query, $q);

        $currentPage = (int) ($this->request->getGet('page') ?? 1);
        $paginatedPhotos = $query->paginate(100);
        $pager = $photoModel->pager;
        
        // If requested page is out of bounds, prevent CI4 fallback to page 1
        if ($currentPage > $pager->getPageCount() && $pager->getPageCount() > 0) {
            $paginatedPhotos = [];
        }

        $data = [
            'photos'         => $paginatedPhotos,
            'pager'          => $pager,
            'storageUsed'    => self::calculateStorageMetrics($totalBytes)['storageUsed'],
            'storagePercent' => self::calculateStorageMetrics($totalBytes)['storagePercent'],
            'counts'         => $counts,
            'searchQuery'    => $q,
            'currentSort'    => $sort
        ];
        
        if ($this->request->isAJAX()) {
            $formattedPhotos = array_map(function($p) use ($sort) {
                $p['groupHeader'] = self::getPhotoGroupHeader($p, $sort);
                return $p;
            }, $data['photos']);

            return $this->response->setJSON([
                'photos'  => $formattedPhotos,
                'hasMore' => $pager->hasMore(),
                'sort'    => $sort
            ]);
        }
        
        return view('photos/index', $data);
    }

    /**
     * Compute section header dynamically based on the active sort order.
     */
    public static function getPhotoGroupHeader(array $photo, string $sort): string
    {
        switch ($sort) {
            case 'size_desc':
            case 'size_asc':
                $mb = ($photo['size'] ?? 0) / 1024 / 1024;
                if ($mb >= 15) return 'Heavyweights (> 15 MB)';
                if ($mb >= 5)  return 'High Resolution (5 MB – 15 MB)';
                if ($mb >= 1)  return 'Standard (1 MB – 5 MB)';
                return 'Compressed (< 1 MB)';

            case 'resolution_desc':
            case 'resolution_asc':
                $w = (int) ($photo['width'] ?? 0);
                $h = (int) ($photo['height'] ?? 0);
                $mp = ($w * $h) / 1000000;
                if ($mp >= 8) return 'Ultra HD / 4K+ (≥ 8 MP)';
                if ($mp >= 2) return 'Full HD 1080p (2 MP – 8 MP)';
                if ($mp > 0)  return 'Standard (< 2 MP)';
                return 'Unknown Resolution';

            case 'name_asc':
            case 'name_desc':
                $name = trim($photo['filename'] ?? '');
                $first = strtoupper(substr($name, 0, 1));
                return ctype_alpha($first) ? $first : '#';

            case 'upload_desc':
            case 'upload_asc':
                $time = !empty($photo['created_at']) ? strtotime($photo['created_at']) : 0;
                return $time > 0 ? date('F Y', $time) : 'Unknown Upload Date';

            case 'geotagged':
                $hasGps = !empty($photo['latitude']) && !empty($photo['longitude']) && (abs((float)$photo['latitude']) > 0.0001 || abs((float)$photo['longitude']) > 0.0001);
                return $hasGps ? 'Geotagged Photos (With GPS)' : 'No Location Data';

            case 'favorites':
                return !empty($photo['is_favorite']) ? 'Starred Favorites' : 'All Photos';

            case 'date_asc':
            case 'date_desc':
            default:
                $time = !empty($photo['taken_at']) ? strtotime($photo['taken_at']) : 0;
                return $time > 0 ? date('F Y', $time) : 'Unknown Date';
        }
    }

    public function explore()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();
        $db = \Config\Database::connect();

        // 1. General Metrics
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $counts = $this->getSidebarCounts();
        $totalPhotos = $photoModel->where('user_id', $userId)->where('is_archived', false)->countAllResults();

        // 2. Hub 1: People & Pets (Top recognized faces)
        $personModel = new \App\Models\PersonModel();
        $topPersons = $personModel->getPersonsWithFaceCountForUser($userId);
        if (!empty($topPersons)) {
            $personIds = array_column($topPersons, 'id');
            $personFaces = $db->table('tbl_face_encodings fe')
                ->select('fe.person_id, fe.bbox_x, fe.bbox_y, fe.bbox_w, fe.bbox_h, p.path, p.thumbnail_path')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->whereIn('fe.person_id', $personIds)
                ->where('p.user_id', $userId)
                ->orderBy('fe.id', 'ASC')
                ->get()
                ->getResultArray();

            $personFaceMap = [];
            foreach ($personFaces as $pf) {
                $pid = (int) $pf['person_id'];
                if (!isset($personFaceMap[$pid])) {
                    $personFaceMap[$pid] = $pf;
                }
            }

            foreach ($topPersons as &$pr) {
                $pid = (int) $pr['id'];
                $pf = $personFaceMap[$pid] ?? null;
                $pr['thumb_url'] = $pf ? (!empty($pf['thumbnail_path']) ? base_url($pf['thumbnail_path']) : base_url($pf['path'])) : null;
            }
            usort($topPersons, fn($a, $b) => ($b['face_count'] ?? 0) <=> ($a['face_count'] ?? 0));
            $topPersons = array_slice($topPersons, 0, 10);
        }

        // 3. Hub 2: Places (Geotagged photos for Leaflet map + location clusters)
        $q = $this->request->getGet('q');
        $geoQuery = $photoModel->where('user_id', $userId)
                               ->where('latitude IS NOT NULL')
                               ->where('longitude IS NOT NULL')
                               ->where('is_archived', false);
        if ($q) {
            $geoQuery = $this->applySearchQuery($geoQuery, $q);
        }
        $locations = $geoQuery->findAll();

        // Group nearby coordinates into rough location clusters (~11km / 0.1 degree)
        $placeClusters = [];
        foreach ($locations as $loc) {
            $clusterKey = round((float)$loc['latitude'], 1) . ',' . round((float)$loc['longitude'], 1);
            if (!isset($placeClusters[$clusterKey])) {
                $placeClusters[$clusterKey] = [
                    'key'       => $clusterKey,
                    'lat'       => (float) $loc['latitude'],
                    'lng'       => (float) $loc['longitude'],
                    'count'     => 0,
                    'cover_url' => !empty($loc['thumbnail_path']) ? base_url($loc['thumbnail_path']) : base_url($loc['path']),
                    'sample_id' => $loc['id'],
                ];
            }
            $placeClusters[$clusterKey]['count']++;
        }
        usort($placeClusters, fn($a, $b) => $b['count'] <=> $a['count']);
        $placeClusters = array_slice($placeClusters, 0, 6);

        // 4. Hub 3: Things & Scenes (Aggregated AI tags from YOLO and objects)
        $tagRows = $db->table('tbl_photo_tags pt')
            ->select('pt.tag, COUNT(DISTINCT pt.photo_id) as photo_count, MIN(pt.photo_id) as sample_photo_id')
            ->join('tbl_photos p', 'p.id = pt.photo_id')
            ->where('p.user_id', $userId)
            ->where('p.is_archived', false)
            ->where('pt.confidence >=', 0.4)
            ->groupBy('pt.tag')
            ->having('photo_count >=', 1)
            ->orderBy('photo_count', 'DESC')
            ->limit(12)
            ->get()
            ->getResultArray();

        $samplePhotoIds = array_filter(array_column($tagRows, 'sample_photo_id'));
        $samplePhotos   = [];
        if (!empty($samplePhotoIds)) {
            $photoRows = $photoModel->select('id, path, thumbnail_path')
                ->whereIn('id', $samplePhotoIds)
                ->findAll();
            foreach ($photoRows as $pr) {
                $samplePhotos[$pr['id']] = $pr;
            }
        }

        $thingsCategories = [];
        foreach ($tagRows as $tr) {
            $sample   = $samplePhotos[$tr['sample_photo_id']] ?? null;
            $coverUrl = null;
            if ($sample) {
                $coverUrl = !empty($sample['thumbnail_path']) ? base_url($sample['thumbnail_path']) : base_url($sample['path']);
            }
            $thingsCategories[] = [
                'tag'       => $tr['tag'],
                'name'      => ucfirst(str_replace('_', ' ', $tr['tag'])),
                'count'     => (int) $tr['photo_count'],
                'cover_url' => $coverUrl,
            ];
        }

        // 5. Hub 4: Media Types
        $mediaTypes = [
            'videos' => [
                'name'  => 'Videos',
                'icon'  => 'bi-camera-video',
                'color' => '#e63946',
                'count' => $photoModel->where('user_id', $userId)->where('is_archived', false)->like('mime_type', 'video/', 'after')->countAllResults(),
                'filter'=> 'video',
            ],
            'favorites' => [
                'name'  => 'Favorites',
                'icon'  => 'bi-heart-fill',
                'color' => '#d62828',
                'count' => $photoModel->where('user_id', $userId)->where('is_archived', false)->where('is_favorite', true)->countAllResults(),
                'filter'=> 'favorite',
            ],
            'panoramas' => [
                'name'  => 'Panoramas',
                'icon'  => 'bi-aspect-ratio',
                'color' => '#457b9d',
                'count' => $photoModel->where('user_id', $userId)->where('is_archived', false)->where('(width / height) >=', 2.0)->countAllResults(),
                'filter'=> 'panorama',
            ],
            'people' => [
                'name'  => 'People Photos',
                'icon'  => 'bi-people-fill',
                'color' => '#2a9d8f',
                'count' => $photoModel->where('user_id', $userId)->where('is_archived', false)->where('scanned_face', 1)->countAllResults(),
                'filter'=> 'people',
            ],
        ];

        // 6. Hub 5: Expressions & Emotions
        $emotionCategories = [];
        try {
            $emotionRows = $db->table('tbl_face_encodings fe')
                ->select('fe.emotion, COUNT(DISTINCT fe.photo_id) as photo_count, MIN(fe.photo_id) as sample_photo_id')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->where('p.user_id', $userId)
                ->where('p.is_archived', false)
                ->where('fe.emotion IS NOT NULL')
                ->where("fe.emotion != ''")
                ->groupBy('fe.emotion')
                ->having('photo_count >=', 1)
                ->orderBy('photo_count', 'DESC')
                ->limit(8)
                ->get()
                ->getResultArray();

            $emPhotoIds = array_filter(array_column($emotionRows, 'sample_photo_id'));
            $emPhotos = [];
            if (!empty($emPhotoIds)) {
                $emPhotoRows = $photoModel->select('id, path, thumbnail_path')
                    ->whereIn('id', $emPhotoIds)
                    ->findAll();
                foreach ($emPhotoRows as $epr) {
                    $emPhotos[$epr['id']] = $epr;
                }
            }

            foreach ($emotionRows as $er) {
                $sample = $emPhotos[$er['sample_photo_id']] ?? null;
                $coverUrl = null;
                if ($sample) {
                    $coverUrl = !empty($sample['thumbnail_path']) ? base_url($sample['thumbnail_path']) : base_url($sample['path']);
                }
                $cleanLabel = trim($er['emotion']);
                $rawQuery = explode('/', $cleanLabel)[0];
                $rawQuery = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $rawQuery);
                $emotionCategories[] = [
                    'emotion'   => $cleanLabel,
                    'query'     => strtolower(trim($rawQuery)),
                    'count'     => (int) $er['photo_count'],
                    'cover_url' => $coverUrl,
                ];
            }
        } catch (\Throwable $t) {
            log_message('error', 'Explore emotion hub error: ' . $t->getMessage());
        }

        $data = [
            'locations'        => $locations,
            'placeClusters'    => $placeClusters,
            'topPersons'       => $topPersons,
            'thingsCategories' => $thingsCategories,
            'emotionCategories'=> $emotionCategories,
            'mediaTypes'       => $mediaTypes,
            'totalPhotos'      => $totalPhotos,
            'geotaggedPhotos'  => count($locations),
            'storageUsed'      => self::calculateStorageMetrics($totalBytes)['storageUsed'],
            'storagePercent'   => self::calculateStorageMetrics($totalBytes)['storagePercent'],
            'counts'           => $counts,
            'searchQuery'      => $q,
        ];

        return view('photos/explore', $data);
    }

    public function map()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId     = auth()->id();
        $counts     = $this->getSidebarCounts();

        $q = $this->request->getGet('q');
        $geoQuery = $photoModel->where('user_id', $userId)
                               ->where('latitude IS NOT NULL')
                               ->where('longitude IS NOT NULL')
                               ->where('is_archived', false)
                               ->orderBy('taken_at', 'DESC');

        if ($q) {
            $geoQuery = $this->applySearchQuery($geoQuery, $q);
        }

        $rawLocations = $geoQuery->findAll();

        $mapPhotos = [];
        $placeClusters = [];

        foreach ($rawLocations as $loc) {
            $lat = (float) $loc['latitude'];
            $lng = (float) $loc['longitude'];

            // Skip invalid 0,0 coordinates
            if ($lat == 0.0 && $lng == 0.0) {
                continue;
            }

            $thumbUrl = !empty($loc['thumbnail_path']) ? base_url($loc['thumbnail_path']) : base_url($loc['path']);
            $fullUrl  = base_url($loc['path']);

            $camera = null;
            if (!empty($loc['exif_data'])) {
                $exif = is_string($loc['exif_data']) ? json_decode($loc['exif_data'], true) : $loc['exif_data'];
                if (is_array($exif)) {
                    $make = $exif['Make'] ?? '';
                    $model = $exif['Model'] ?? '';
                    $camera = trim("$make $model");
                }
            }

            $item = [
                'id'          => (int) $loc['id'],
                'filename'    => $loc['filename'],
                'lat'         => $lat,
                'lng'         => $lng,
                'thumb_url'   => $thumbUrl,
                'photo_url'   => $fullUrl,
                'taken_at'    => $loc['taken_at'] ? date('M j, Y g:i A', strtotime($loc['taken_at'])) : null,
                'raw_date'    => $loc['taken_at'] ?: ($loc['created_at'] ?? ''),
                'camera'      => $camera ?: null,
                'is_favorite' => (bool) $loc['is_favorite'],
                'ocr_text'    => !empty($loc['ocr_text']) ? mb_substr($loc['ocr_text'], 0, 150) : null,
            ];

            $mapPhotos[] = $item;

            // Group into location clusters (~11km / 0.1 degree)
            $clusterKey = round($lat, 1) . ',' . round($lng, 1);
            if (!isset($placeClusters[$clusterKey])) {
                $placeClusters[$clusterKey] = [
                    'key'       => $clusterKey,
                    'lat'       => $lat,
                    'lng'       => $lng,
                    'count'     => 0,
                    'cover_url' => $thumbUrl,
                ];
            }
            $placeClusters[$clusterKey]['count']++;
        }

        usort($placeClusters, fn($a, $b) => $b['count'] <=> $a['count']);
        $topClusters = array_slice($placeClusters, 0, 12);

        $totalPhotos = $photoModel->where('user_id', $userId)->where('is_archived', false)->countAllResults();

        $data = [
            'counts'         => $counts,
            'mapPhotos'      => $mapPhotos,
            'placeClusters'  => $topClusters,
            'totalPhotos'    => $totalPhotos,
            'geotaggedCount' => count($mapPhotos),
            'searchQuery'    => $q,
        ];

        return view('photos/map', $data);
    }



    public function upload()
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '900');

        $file = $this->request->getFile('file');

        if ($file === null || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => $file ? $file->getErrorString() : 'No file uploaded']);
        }

        // Enforce maximum upload size from database setting
        $configuredMaxMb = (int) (setting('App.maxUploadSizeMb') ?: 50);
        $maxUploadBytes = $configuredMaxMb * 1024 * 1024;
        if ($file->getSize() > $maxUploadBytes) {
            $actual = round($file->getSize() / 1024 / 1024, 1);
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "File exceeds the configured {$configuredMaxMb} MB limit ({$actual} MB)."
            ])->setStatusCode(400);
        }

        // Enforce allowed extensions from database setting
        $rawExts = setting('App.allowedExtensions') ?: 'jpg,jpeg,png,webp,heic';
        $allowedExts = array_map('trim', explode(',', strtolower($rawExts)));
        $fileExt = strtolower($file->getClientExtension());
        if (!in_array($fileExt, $allowedExts)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "File extension .{$fileExt} is not permitted. Allowed: " . implode(', ', $allowedExts)
            ])->setStatusCode(400);
        }

        // Get info BEFORE move, as move() deletes the temporary file
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        $tempPath = $file->getTempName();

        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id() ?: 1;

        // For large media (> 100MB), defer SHA-256 calculation to background to avoid upload freezes/timeouts
        $isLargeMedia = ($size > 100 * 1024 * 1024);
        $fileHash = null;

        if (! $isLargeMedia) {
            $fileHash = hash_file('sha256', $tempPath);
            // Check for duplicates
            $existing = $photoModel->where('file_hash', $fileHash)->where('user_id', $userId)->first();
            if ($existing) {
                return $this->response->setJSON([
                    'status'  => 'success', 
                    'message' => 'File already exists.', 
                    'id'      => $existing['id'],
                    'is_duplicate' => true
                ]);
            }
        }

        // Enforce user storage quota
        $quotaBytes = (float) (setting('App.storageLimit') ?? 1073741824);
        $enforcement = setting('App.quotaEnforcement') ?? 'strict';
        if ($quotaBytes > 0 && $enforcement === 'strict') {
            $userTotalBytes = (float) ($photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0);
            if ($userTotalBytes + $size > $quotaBytes) {
                $quotaFormatted = ($quotaBytes >= 1073741824) ? round($quotaBytes / 1024 / 1024 / 1024, 1) . ' GB' : round($quotaBytes / 1024 / 1024, 1) . ' MB';
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "Upload rejected: Storage quota limit ({$quotaFormatted}) reached."
                ])->setStatusCode(403);
            }
        }
        $yearMonth = date('Y/m');
        $newName = $file->getRandomName();

        // Target directories in user and date hierarchy
        $subDir = "users/{$userId}/{$yearMonth}";
        $targetUploadDir = FCPATH . "uploads/{$subDir}";
        if (!is_dir($targetUploadDir)) {
            if (!@mkdir($targetUploadDir, 0777, true) && !is_dir($targetUploadDir)) {
                @chmod(FCPATH . 'uploads', 0777);
                @mkdir($targetUploadDir, 0777, true);
            }
        }

        $targetThumbDir = FCPATH . "thumbnails/{$subDir}";
        if (!is_dir($targetThumbDir)) {
            if (!@mkdir($targetThumbDir, 0777, true) && !is_dir($targetThumbDir)) {
                @chmod(FCPATH . 'thumbnails', 0777);
                @mkdir($targetThumbDir, 0777, true);
            }
        }

        $file->move($targetUploadDir, $newName);

        $fullPath = "{$targetUploadDir}/{$newName}";
        $ext = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
        $isVideo = strpos($mimeType, 'video/') === 0 || in_array($ext, ['mp4', 'mov', 'm4v', 'webm', 'mkv', 'avi']);
        $isHeic = in_array($ext, ['heic', 'heif']) || strpos($mimeType, 'heic') !== false || strpos($mimeType, 'heif') !== false;

        $thumbName = ($isVideo || $isHeic) ? (pathinfo($newName, PATHINFO_FILENAME) . '.jpg') : $newName;
        $thumbnailPath = "{$targetThumbDir}/{$thumbName}";

        $imageInfo = null;
        if ($isVideo) {
            self::generateVideoThumbnail($fullPath, $thumbnailPath);
            if (file_exists($thumbnailPath)) {
                $imageInfo = @getimagesize($thumbnailPath);
            }
        } elseif ($isHeic) {
            $tmpJpg = sys_get_temp_dir() . '/' . uniqid('heic_info_', true) . '.jpg';
            @exec(sprintf('heif-convert %s %s 2>&1', escapeshellarg($fullPath), escapeshellarg($tmpJpg)));
            if (file_exists($tmpJpg) && filesize($tmpJpg) > 0) {
                $imageInfo = @getimagesize($tmpJpg);
                $this->generateThumbnail($tmpJpg, $thumbnailPath);
                @unlink($tmpJpg);
            } else {
                $this->generateThumbnail($fullPath, $thumbnailPath);
            }
        } else {
            $imageInfo = @getimagesize($fullPath);
            $this->generateThumbnail($fullPath, $thumbnailPath);
        }

        $metadata = $isVideo ? null : $this->getMergedMetadata($fullPath);

        $deviceUuid = $this->request->getPost('device_uuid') ?: ($this->request->getHeaderLine('X-Device-UUID') ?: null);
        $uploadSource = !empty($deviceUuid) ? 'android' : 'webapp';

        $data = [
            'user_id'        => $userId,
            'device_id'      => $this->request->getPost('device_id') ?? null,
            'device_uuid'    => $deviceUuid,
            'upload_source'  => $uploadSource,
            'filename'       => $newName,
            'path'           => "uploads/{$subDir}/{$newName}",
            'mime_type'      => $isVideo ? $mimeType : ($imageInfo['mime'] ?? $mimeType),
            'width'          => $imageInfo ? $imageInfo[0] : null,
            'height'         => $imageInfo ? $imageInfo[1] : null,
            'size'           => $size,
            'storage_driver' => 'hybrid',
            'gcp_synced'     => 0,
            'gcp_synced_at'  => null,
            'file_hash'      => $fileHash,
            'taken_at'       => $metadata['taken_at'] ?? date('Y-m-d H:i:s'),
            'thumbnail_path' => "thumbnails/{$subDir}/{$thumbName}",
            'latitude'       => $metadata['lat'] ?? null,
            'longitude'      => $metadata['lng'] ?? null,
            'exif_data'      => $metadata['exif'] ?? null,
        ];

        $photoId = $photoModel->insert($data);
        $this->clearSidebarCountsCache();
        $this->checkAndDispatchStorageWarning((int) $userId);

        if ($isLargeMedia) {
            $this->dispatchBackgroundMediaProcessing((int) $photoId);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Uploaded successfully (large media queued for background processing).',
                'id'      => (int) $photoId,
            ]);
        }

        if (function_exists('fastcgi_finish_request')) {
            $this->response->setJSON(['status' => 'success', 'message' => 'Uploaded successfully.', 'id' => (int) $photoId])->send();
            fastcgi_finish_request();
            ignore_user_abort(true);
            $this->mirrorToGcp($data['path'], $data['thumbnail_path'], $data['mime_type'], (int) $photoId);
            if (!$isVideo && $photoId) {
                $this->triggerFaceScan((int) $photoId);
            }
            return;
        }

        $this->mirrorToGcp($data['path'], $data['thumbnail_path'], $data['mime_type'], (int) $photoId);
        if (!$isVideo && $photoId) {
            $this->triggerFaceScanAsync((int) $photoId);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Uploaded successfully.', 'id' => (int) $photoId]);
    }

    /**
     * Dispatches asynchronous CLI processing for media (SHA-256 calculation, GCP mirroring).
     */
    protected function dispatchBackgroundMediaProcessing(int $photoId): void
    {
        try {
            // Guard against process storms: if a worker is already running, pending items are queued in DB
            $running = @shell_exec('pgrep -f "media:process-pending" 2>/dev/null');
            if (!empty(trim((string) $running))) {
                return;
            }

            $spark = escapeshellarg(ROOTPATH . 'spark');
            $idArg = escapeshellarg("--id={$photoId}");
            $cmd   = "php {$spark} media:process-pending {$idArg} > /dev/null 2>&1 &";
            if (function_exists('exec')) {
                @exec($cmd);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to dispatch media:process-pending: ' . $e->getMessage());
        }
    }

    protected function checkAndDispatchStorageWarning(int $userId): void
    {
        try {
            $quotaBytes = (float) (setting('App.storageLimit') ?? 1073741824);
            if ($quotaBytes <= 0) {
                return;
            }

            $photoModel = new \App\Models\PhotoModel();
            $userTotalBytes = (float) ($photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0);
            $percent = ($userTotalBytes / $quotaBytes) * 100;

            if ($percent < 85) {
                return;
            }

            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);
            $recipient = $user->email ?? null;
            if (!$recipient) {
                return;
            }

            $db = \Config\Database::connect();
            if (!$db->tableExists('sys_email_logs')) {
                return;
            }

            // Throttle: check if an alert was sent to this user within the last 7 days
            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
            $recentAlert = $db->table('sys_email_logs')
                ->where('recipient', $recipient)
                ->like('subject', 'Storage Threshold Warning')
                ->where('sent_at >=', $sevenDaysAgo)
                ->first();

            if ($recentAlert) {
                return;
            }

            $trackingId = 'EVT-' . strtoupper(bin2hex(random_bytes(8)));
            $subject = 'Storage Threshold Warning Notice (' . round($percent) . '% used) [' . $trackingId . ']';

            $email = $this->getEmailService();
            $email->setTo($recipient);
            $email->setSubject($subject);
            $email->setMailType('html');
            $email->setMessage(view('emails/storage_warning', [
                'subject'       => $subject,
                'trackingId'    => $trackingId,
                'username'      => $user->username ?? 'User',
                'used_storage'  => self::formatBytesStatic($userTotalBytes),
                'total_storage' => self::formatBytesStatic($quotaBytes),
                'percent_used'  => round($percent, 1),
            ]));

            if ($email->send()) {
                $db->table('sys_email_logs')->insert([
                    'tracking_id' => $trackingId,
                    'recipient'   => $recipient,
                    'subject'     => $subject,
                    'status'      => 'sent',
                    'debug_log'   => 'Automated quota warning dispatched at ' . round($percent, 1) . '% usage.',
                    'sent_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'checkAndDispatchStorageWarning error: ' . $e->getMessage());
        }
    }

    public function backfillExif()
    {
        $photoModel = new \App\Models\PhotoModel();
        $photos = $photoModel->where('user_id', auth()->id())
            ->where('mime_type NOT LIKE', 'video/%')
            ->findAll();

        $updated = 0;
        foreach ($photos as $photo) {
            $fullPath = FCPATH . $photo['path'];
            if (!file_exists($fullPath)) continue;

            $metadata = $this->getMergedMetadata($fullPath);
            if ($metadata === null) continue;

            $update = [];
            if ($metadata['exif'] !== null && ($photo['exif_data'] === null || $photo['exif_data'] === '')) {
                $update['exif_data'] = $metadata['exif'];
            }
            if ($metadata['lat'] !== null && $photo['latitude'] === null) {
                $update['latitude'] = $metadata['lat'];
            }
            if ($metadata['lng'] !== null && $photo['longitude'] === null) {
                $update['longitude'] = $metadata['lng'];
            }
            if ($metadata['taken_at'] !== null && ($photo['taken_at'] === null || $photo['taken_at'] === '')) {
                $update['taken_at'] = $metadata['taken_at'];
            }
            if (!empty($update)) {
                $photoModel->update($photo['id'], $update);
                $updated++;
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "EXIF backfill complete. Updated $updated photos."
        ]);
    }

    public function sharing()
    {
        $photoModel = new \App\Models\PhotoModel();
        $linkModel = new \App\Models\SharedLinkModel();
        $shareModel = new \App\Models\PhotoShareModel();

        // 1. Photos I've shared via Public Links
        $publicShares = $linkModel->select('tbl_photos.*, tbl_shared_links.access_token')
            ->join('tbl_photos', 'tbl_photos.id = tbl_shared_links.photo_id')
            ->where('tbl_photos.user_id', auth()->id())
            ->findAll();

        // 2. Photos others shared WITH me
        $sharedWithMe = $shareModel->select('tbl_photos.*, tbl_photo_shares.permission')
            ->join('tbl_photos', 'tbl_photos.id = tbl_photo_shares.photo_id')
            ->where('tbl_photo_shares.shared_with', auth()->id())
            ->findAll();

        $data = [
            'publicShares' => $publicShares,
            'sharedWithMe' => $sharedWithMe,
            'counts'       => $this->getSidebarCounts()
        ];

        return view('photos/sharing', $data);
    }

    /**
     * Public Link Sharing
     */
    public function generateShareLink($id)
    {
        $photoModel = new \App\Models\PhotoModel();
        $shareModel = new \App\Models\SharedLinkModel();

        // Verify ownership
        $photo = $photoModel->where('user_id', auth()->id())->find($id);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        $preset = $this->request->getPost('expires_preset');
        $expiresAt = $this->request->getPost('expires_at');
        if ($preset) {
            if ($preset === '24h') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            } elseif ($preset === '7d') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
            } elseif ($preset === '30d') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            } elseif ($preset === 'never') {
                $expiresAt = null;
            }
        } elseif ($expiresAt === '' || $expiresAt === null) {
            $expiresAt = null;
        } else {
            $ts = strtotime($expiresAt);
            $expiresAt = $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
        }

        $password = trim((string) ($this->request->getPost('password') ?? ''));
        $passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        $removePassword = (bool) $this->request->getPost('remove_password');

        $hasPassword = false;

        // Check for existing link
        $existing = $shareModel->where('photo_id', $id)->first();
        if ($existing) {
            $token = $existing['access_token'];
            $updateData = [];
            if ($existing['expires_at'] !== $expiresAt) {
                $updateData['expires_at'] = $expiresAt;
            }
            if ($removePassword) {
                $updateData['password_hash'] = null;
                $hasPassword = false;
            } elseif (!empty($password)) {
                $updateData['password_hash'] = $passwordHash;
                $hasPassword = true;
            } else {
                $hasPassword = !empty($existing['password_hash']);
            }
            if (!empty($updateData)) {
                $shareModel->update($existing['id'], $updateData);
            }
        } else {
            // Generate unique secure token
            $token = bin2hex(random_bytes(16));
            $insertData = [
                'photo_id'     => $id,
                'access_token' => $token,
                'expires_at'   => $expiresAt,
            ];
            if (!empty($passwordHash) && !$removePassword) {
                $insertData['password_hash'] = $passwordHash;
                $hasPassword = true;
            }
            $shareModel->insert($insertData);
        }

        return $this->response->setJSON([
            'status'       => 'success', 
            'url'          => base_url("s/{$token}"),
            'expires_at'   => $expiresAt,
            'has_password' => $hasPassword,
        ]);
    }

    public function sharePhoto($id)
    {
        $userId   = auth()->id();
        $targetId = (int) $this->request->getPost('user_id');
        if (! $targetId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User ID required']);
        }
        if ($targetId === (int) $userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You cannot share a photo with yourself']);
        }

        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', $userId)->find($id);
        if (! $photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        $userModel = new \App\Models\UserModel();
        $target = $userModel->find($targetId);
        if (! $target) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found']);
        }

        $shareModel = new \App\Models\PhotoShareModel();
        $exists = $shareModel->where('photo_id', $id)->where('shared_with', $targetId)->first();
        if ($exists) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Already shared with this user']);
        }

        $shareModel->insert([
            'photo_id'    => $id,
            'shared_by'   => $userId,
            'shared_with' => $targetId,
            'permission'  => 'view',
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function unsharePhoto($id)
    {
        $userId   = auth()->id();
        $targetId = (int) $this->request->getPost('user_id');
        if (! $targetId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User ID required']);
        }

        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', $userId)->find($id);
        if (! $photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        $shareModel = new \App\Models\PhotoShareModel();
        $shareModel->where('photo_id', $id)->where('shared_with', $targetId)->delete();

        return $this->response->setJSON(['status' => 'success']);
    }

    public function searchUsers()
    {
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '') {
            return $this->response->setJSON([]);
        }

        $db    = \Config\Database::connect();
        $users = $db->table('users')
            ->select('users.id, users.username, users.name, auth_identities.secret as email')
            ->join('auth_identities', 'auth_identities.user_id = users.id AND auth_identities.type = ' . $db->escape('email_password'), 'left')
            ->where('users.id !=', auth()->id())
            ->groupStart()
                ->like('users.username', $q)
                ->orLike('users.name', $q)
                ->orLike('auth_identities.secret', $q)
            ->groupEnd()
            ->orderBy('users.username', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($users);
    }

    public function viewShared($token)
    {
        $shareModel = new \App\Models\SharedLinkModel();
        $photoModel = new \App\Models\PhotoModel();

        $link = $shareModel->findByToken($token);
        if (!$link) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Link has expired or is invalid.");
        }

        $sessionKey = 'shared_link_authed_' . $token;
        if (!empty($link['password_hash']) && !session()->get($sessionKey)) {
            if ($this->request->getMethod() === 'POST' || $this->request->is('post')) {
                $submittedPassword = (string) ($this->request->getPost('password') ?? '');
                if (password_verify($submittedPassword, $link['password_hash'])) {
                    session()->set($sessionKey, true);
                } else {
                    return view('photos/view_shared', [
                        'photo'            => null,
                        'token'            => $token,
                        'passwordRequired' => true,
                        'error'            => 'Incorrect password. Please try again.'
                    ]);
                }
            } else {
                return view('photos/view_shared', [
                    'photo'            => null,
                    'token'            => $token,
                    'passwordRequired' => true,
                    'error'            => null
                ]);
            }
        }

        $photo = $photoModel->find($link['photo_id']);
        if (!$photo) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Shared photo no longer exists.");
        }

        return view('photos/view_shared', [
            'photo'            => $photo,
            'token'            => $token,
            'passwordRequired' => false,
            'error'            => null
        ]);
    }

    public function analytics()
    {
        $photoModel = new \App\Models\PhotoModel();
        $albumModel = new \App\Models\AlbumModel();
        $linkModel = new \App\Models\SharedLinkModel();
        $shareModel = new \App\Models\PhotoShareModel();
        $userId = auth()->id();
        $db = \Config\Database::connect();

        // 1. Storage Stats
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $totalCount = $photoModel->where('user_id', $userId)->countAllResults();

        // 2. Photo vs Video ratio
        $imageCount = $photoModel->where('user_id', $userId)->where('mime_type NOT LIKE', 'video/%')->countAllResults();
        $videoCount = $photoModel->where('user_id', $userId)->where('mime_type LIKE', 'video/%')->countAllResults();
        $imageBytes = $photoModel->where('user_id', $userId)->where('mime_type NOT LIKE', 'video/%')->selectSum('size')->first()['size'] ?? 0;
        $videoBytes = $photoModel->where('user_id', $userId)->where('mime_type LIKE', 'video/%')->selectSum('size')->first()['size'] ?? 0;

        // 3. Favorites / Archive / Trash
        $favoritesCount = $photoModel->where('user_id', $userId)->where('is_favorite', true)->countAllResults();
        $archivedCount  = $photoModel->where('user_id', $userId)->where('is_archived', true)->countAllResults();
        $trashCount     = $photoModel->where('user_id', $userId)->onlyDeleted()->countAllResults();

        // 4. Albums count
        $albumCount = $albumModel->where('user_id', $userId)->countAllResults();

        // 5. Date range (oldest / newest photo)
        $dateRange = $photoModel->where('user_id', $userId)
            ->select('MIN(taken_at) as oldest, MAX(taken_at) as newest')
            ->first();
        $oldestDate = $dateRange['oldest'] ?? null;
        $newestDate = $dateRange['newest'] ?? null;

        // 6. Average file size
        $avgSize = $totalCount > 0 ? $totalBytes / $totalCount : 0;

        // 7. MIME Type Breakdown
        $mimeStats = $photoModel->where('user_id', $userId)
            ->select('mime_type, COUNT(*) as count')
            ->groupBy('mime_type')
            ->findAll();

        // 8. Monthly Activity (Current Year)
        $monthlyQuery = $db->table('photos')
            ->select("DATE_FORMAT(taken_at, '%M') as month, COUNT(*) as count, MONTH(taken_at) as month_num")
            ->where('user_id', $userId)
            ->where('YEAR(taken_at)', date('Y'))
            ->where('taken_at IS NOT NULL')
            ->groupBy('month, month_num')
            ->orderBy('month_num', 'ASC')
            ->get()
            ->getResultArray();

        // 9. Hourly activity breakdown
        $hourlyQuery = $db->table('photos')
            ->select("HOUR(taken_at) as hour, COUNT(*) as count")
            ->where('user_id', $userId)
            ->where('taken_at IS NOT NULL')
            ->groupBy('hour')
            ->orderBy('hour', 'ASC')
            ->get()
            ->getResultArray();

        // 10. Top camera models from EXIF
        $cameraQuery = $db->table('photos')
            ->select("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Model')) as camera, COUNT(*) as count")
            ->where('user_id', $userId)
            ->where('exif_data IS NOT NULL')
            ->where("JSON_EXTRACT(exif_data, '$.Model') IS NOT NULL")
            ->groupBy('camera')
            ->orderBy('count', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        // 11. GPS-tagged count
        $gpsCount = $photoModel->where('user_id', $userId)
            ->where('latitude IS NOT NULL')
            ->where('longitude IS NOT NULL')
            ->countAllResults();

        // 12. Year-over-year growth
        $yearlyQuery = $db->table('photos')
            ->select("YEAR(taken_at) as year, COUNT(*) as count")
            ->where('user_id', $userId)
            ->where('taken_at IS NOT NULL')
            ->groupBy('year')
            ->orderBy('year', 'ASC')
            ->get()
            ->getResultArray();

        // 13. Sharing Stats
        $publicShares = $linkModel->join('tbl_photos', 'tbl_photos.id = tbl_shared_links.photo_id')
                                   ->where('tbl_photos.user_id', $userId)->countAllResults();
        $internalShares = $shareModel->where('shared_by', $userId)->countAllResults();

        // 14. Library lifespan in days
        $lifespanDays = 0;
        if ($oldestDate && $newestDate) {
            $old = new \DateTime($oldestDate);
            $new = new \DateTime($newestDate);
            $lifespanDays = (int) $old->diff($new)->days;
        }

        $data = [
            'totalBytes'      => $totalBytes,
            'totalCount'      => $totalCount,
            'storageUsed'     => self::calculateStorageMetrics($totalBytes)['storageUsed'],
            'storagePercent'  => self::calculateStorageMetrics($totalBytes)['storagePercent'],
            'imageCount'      => $imageCount,
            'videoCount'      => $videoCount,
            'imageBytes'      => $imageBytes,
            'videoBytes'      => $videoBytes,
            'favoritesCount'  => $favoritesCount,
            'archivedCount'   => $archivedCount,
            'trashCount'      => $trashCount,
            'albumCount'      => $albumCount,
            'oldestDate'      => $oldestDate,
            'newestDate'      => $newestDate,
            'avgSize'         => $avgSize,
            'avgSizeFormatted'=> $this->formatBytes($avgSize),
            'mimeStats'       => $mimeStats,
            'monthlyQuery'    => $monthlyQuery,
            'hourlyQuery'     => $hourlyQuery,
            'cameraStats'     => $cameraQuery,
            'gpsCount'        => $gpsCount,
            'yearlyQuery'     => $yearlyQuery,
            'lifespanDays'    => $lifespanDays,
            'sharingStats'    => [
                'public'   => $publicShares,
                'internal' => $internalShares
            ],
            'counts'          => $this->getSidebarCounts()
        ];

        return view('photos/analytics', $data);
    }

    public function archive()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();
        
        // 1. Counts first
        $counts = $this->getSidebarCounts();

        // 2. Main query afresh
        $query = $photoModel->where('user_id', $userId)->where('is_archived', true)->orderBy('taken_at', 'DESC');

        $q = $this->request->getGet('q');
        $query = $this->applySearchQuery($query, $q);

        $data = [
            'photos'      => $query->paginate(100),
            'pager'       => $photoModel->pager,
            'counts'      => $counts,
            'searchQuery' => $q
        ];
        return view('photos/archive', $data);
    }

    public function trash()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();
        
        $counts = $this->getSidebarCounts();
        
        $query = $photoModel->where('user_id', $userId)->onlyDeleted()->orderBy('deleted_at', 'DESC');

        $q = $this->request->getGet('q');
        $query = $this->applySearchQuery($query, $q);

        $data = [
            'photos'      => $query->paginate(100),
            'pager'       => $photoModel->pager,
            'counts'      => $counts,
            'searchQuery' => $q
        ];
        return view('photos/trash', $data);
    }

    public function favorites()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();
        
        $counts = $this->getSidebarCounts();
        
        $query = $photoModel->where('user_id', $userId)
                            ->where('is_favorite', true)
                            ->where('is_archived', false)
                            ->orderBy('taken_at', 'DESC');

        $q = $this->request->getGet('q');
        $query = $this->applySearchQuery($query, $q);

        $data = [
            'title'       => 'Favorites',
            'photos'      => $query->paginate(100),
            'pager'       => $photoModel->pager,
            'counts'      => $counts,
            'searchQuery' => $q
        ];
        return view('photos/index', $data); // We reuse index for simple filtered views
    }

    public function memories()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();
        $counts = $this->getSidebarCounts();

        // 1. Time Machine Date Selection (default to today)
        $rawDate = $this->request->getGet('date');
        $validTime = ($rawDate && strtotime($rawDate)) ? strtotime($rawDate) : time();
        $selectedDate   = date('Y-m-d', $validTime);
        $targetMonthDay = date('m-d', $validTime);
        $targetMonth    = (int) date('m', $validTime);
        $targetYear     = (int) date('Y', $validTime);
        $isToday        = ($selectedDate === date('Y-m-d'));

        // +/- 3 days range around selected date
        $dayRange = [];
        for ($offset = -3; $offset <= 3; $offset++) {
            $dayRange[] = date('m-d', strtotime("$offset days", $validTime));
        }

        // 2. Privacy filter: exclude unnamed/hidden background stranger photos if set
        $excludeHidden = (bool) (setting('ML.excludeHiddenMemories', "user:{$userId}") ?? true);
        $excludedPhotoIds = [];
        if ($excludeHidden) {
            $db = \Config\Database::connect();
            $unnamedOnlyRows = $db->query("
                SELECT fe.photo_id
                FROM tbl_face_encodings fe
                JOIN tbl_photos ph ON ph.id = fe.photo_id
                LEFT JOIN tbl_people p ON p.id = fe.person_id
                WHERE ph.user_id = ?
                GROUP BY fe.photo_id
                HAVING COUNT(fe.id) > 0 AND SUM(CASE WHEN p.name IS NOT NULL AND p.name != '' THEN 1 ELSE 0 END) = 0
            ", [$userId])->getResultArray();
            $excludedPhotoIds = array_column($unnamedOnlyRows, 'photo_id');
        }

        $seenIds = [];

        // Helper query builder
        $baseQuery = function() use ($photoModel, $userId, $excludedPhotoIds) {
            $q = $photoModel->where('user_id', $userId)->where('is_archived', false);
            if (!empty($excludedPhotoIds)) {
                $q->whereNotIn('id', $excludedPhotoIds);
            }
            return $q;
        };

        // Tier 1: Photos taken within +/- 3 days of target date in past years
        $pastYearsQuery = $baseQuery()
            ->whereIn("DATE_FORMAT(taken_at, '%m-%d')", $dayRange)
            ->where("YEAR(taken_at) <", $targetYear)
            ->orderBy('taken_at', 'DESC');
        $pastYearsPhotos = $pastYearsQuery->findAll(100);

        foreach ($pastYearsPhotos as $p) {
            $seenIds[] = (int) $p['id'];
        }

        // Tier 2: Flashback to this month in past years (excluding seen)
        $monthQuery = $baseQuery()
            ->where("MONTH(taken_at) =", $targetMonth)
            ->where("YEAR(taken_at) <", $targetYear);
        if (!empty($seenIds)) {
            $monthQuery->whereNotIn('id', $seenIds);
        }
        $thisMonthPhotos = $monthQuery->orderBy('is_favorite', 'DESC')
            ->orderBy('taken_at', 'DESC')
            ->findAll(40);

        foreach ($thisMonthPhotos as $p) {
            $seenIds[] = (int) $p['id'];
        }

        // Tier 3: Starred Favorites (excluding seen)
        $favQuery = $baseQuery()->where('is_favorite', true);
        if (!empty($seenIds)) {
            $favQuery->whereNotIn('id', $seenIds);
        }
        $favoritePhotos = $favQuery->orderBy('taken_at', 'DESC')->findAll(30);

        foreach ($favoritePhotos as $p) {
            $seenIds[] = (int) $p['id'];
        }

        // Tier 4: Recent Highlights (1 to 6 months ago, excluding seen)
        $sixMoQuery = $baseQuery()
            ->where("taken_at >=", date('Y-m-d H:i:s', strtotime('-6 months', $validTime)))
            ->where("taken_at <=", date('Y-m-d H:i:s', strtotime('-1 month', $validTime)));
        if (!empty($seenIds)) {
            $sixMoQuery->whereNotIn('id', $seenIds);
        }
        $sixMonthsPhotos = $sixMoQuery->orderBy('is_favorite', 'DESC')
            ->orderBy('taken_at', 'DESC')
            ->findAll(30);

        // 3. Build Story Reels for top carousel
        $stories = [];

        // Group past years by individual year
        $byYear = [];
        foreach ($pastYearsPhotos as $photo) {
            $y = date('Y', strtotime($photo['taken_at']));
            $byYear[$y][] = $photo;
        }

        foreach ($byYear as $year => $photos) {
            $yearsAgo = $targetYear - (int) $year;
            $label = $yearsAgo === 1 ? '1 Year Ago' : "{$yearsAgo} Years Ago";
            $hero = $photos[0];
            $stories[] = [
                'id'         => 'year_' . $year,
                'title'      => $label,
                'subtitle'   => date('F j, Y', strtotime($hero['taken_at'])),
                'cover_url'  => $hero['thumbnail_path'] ? base_url($hero['thumbnail_path']) : base_url($hero['path']),
                'count'      => count($photos),
                'photo_ids'  => array_column($photos, 'id'),
                'photos'     => $photos,
            ];
        }

        if (!empty($thisMonthPhotos)) {
            $hero = $thisMonthPhotos[0];
            $monthName = date('F', $validTime);
            $stories[] = [
                'id'        => 'month_flashback',
                'title'     => "{$monthName} Flashback",
                'subtitle'  => 'From previous years',
                'cover_url' => $hero['thumbnail_path'] ? base_url($hero['thumbnail_path']) : base_url($hero['path']),
                'count'     => count($thisMonthPhotos),
                'photo_ids' => array_column($thisMonthPhotos, 'id'),
                'photos'    => $thisMonthPhotos,
            ];
        }

        if (!empty($favoritePhotos)) {
            $hero = $favoritePhotos[0];
            $stories[] = [
                'id'        => 'favorites_highlight',
                'title'     => 'Treasured Stars',
                'subtitle'  => 'Your all-time favorites',
                'cover_url' => $hero['thumbnail_path'] ? base_url($hero['thumbnail_path']) : base_url($hero['path']),
                'count'     => count($favoritePhotos),
                'photo_ids' => array_column($favoritePhotos, 'id'),
                'photos'    => $favoritePhotos,
            ];
        }

        if (!empty($sixMonthsPhotos)) {
            $hero = $sixMonthsPhotos[0];
            $stories[] = [
                'id'        => 'recent_moments',
                'title'     => 'Recent Highlights',
                'subtitle'  => 'A few months ago',
                'cover_url' => $hero['thumbnail_path'] ? base_url($hero['thumbnail_path']) : base_url($hero['path']),
                'count'     => count($sixMonthsPhotos),
                'photo_ids' => array_column($sixMonthsPhotos, 'id'),
                'photos'    => $sixMonthsPhotos,
            ];
        }

        $data = [
            'stories'         => $stories,
            'pastYearsPhotos' => $pastYearsPhotos,
            'thisMonthPhotos' => $thisMonthPhotos,
            'favoritePhotos'  => $favoritePhotos,
            'sixMonthsPhotos' => $sixMonthsPhotos,
            'counts'          => $counts,
            'selectedDate'    => $selectedDate,
            'isToday'         => $isToday,
        ];

        return view('photos/memories', $data);
    }

    public function albums()
    {
        $userId = auth()->id();
        $albumModel = new \App\Models\AlbumModel();
        $albums = $albumModel->getAlbumsWithThumbs($userId);
        $aiCollections = $albumModel->getAiCollections($userId);

        if ($this->request->getGet('json')) {
            return $this->response->setJSON([
                'albums'        => $albums,
                'aiCollections' => $aiCollections,
            ]);
        }

        $data = [
            'albums'        => $albums,
            'aiCollections' => $aiCollections,
            'counts'        => $this->getSidebarCounts(),
        ];
        return view('photos/albums', $data);
    }

    public function smartCollection(string $key)
    {
        $presets = SmartAlbumRules::getPresets();
        if (! isset($presets[$key])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $preset     = $presets[$key];
        $userId     = auth()->id();
        $photoModel = new \App\Models\PhotoModel();
        $rules      = SmartAlbumRules::fromArray($preset['rules']);

        $photoModel->where('user_id', $userId);
        SmartAlbumRules::apply($photoModel, $rules);
        $query = $photoModel->select('tbl_photos.*')->orderBy('taken_at', 'DESC');

        $data = [
            'title'          => $preset['name'],
            'subtitle'       => $preset['description'],
            'isAiCollection' => true,
            'collectionKey'  => $key,
            'preset'         => $preset,
            'photos'         => $query->paginate(100),
            'pager'          => $photoModel->pager,
            'counts'         => $this->getSidebarCounts(),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'photos'  => $data['photos'],
                'hasMore' => $photoModel->pager->hasMore(),
            ]);
        }

        return view('photos/index', $data);
    }

    public function viewAlbum($id)
    {
        $albumModel = new \App\Models\AlbumModel();
        $album = $albumModel->where('user_id', auth()->id())->find($id);
        if (!$album) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();

        if (! empty($album['is_smart'])) {
            $rules = SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
            $photoModel->where('user_id', $userId);
            SmartAlbumRules::apply($photoModel, $rules);
            $query = $photoModel->select('tbl_photos.*')
                ->orderBy('taken_at', 'DESC');
        } else {
            $query = $photoModel->select('tbl_photos.*, tbl_album_photos.added_at as album_added_at')
                ->join('tbl_album_photos', 'tbl_album_photos.photo_id = tbl_photos.id')
                ->where('tbl_album_photos.album_id', $id)
                ->orderBy('tbl_album_photos.added_at', 'DESC');
        }

        $data = [
            'title'    => $album['name'],
            'subtitle' => $album['description'],
            'album'    => $album,
            'photos'   => $query->paginate(100),
            'pager'    => $photoModel->pager,
            'counts'   => $this->getSidebarCounts(),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'photos'  => $data['photos'],
                'hasMore' => $photoModel->pager->hasMore(),
            ]);
        }

        return view('photos/index', $data); // Reuse gallery grid
    }

    public function createAlbum()
    {
        $userId = auth()->id();
        if (! $userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated']);
        }

        $albumModel = new \App\Models\AlbumModel();
        $name = $this->request->getPost('name');
        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Name is required']);
        }

        $isSmart = $this->request->getPost('album_type') === 'smart';

        $data = [
            'user_id'     => $userId,
            'name'        => $name,
            'description' => $this->request->getPost('description'),
            'is_smart'    => $isSmart ? 1 : 0,
            'smart_rules' => null,
        ];

        if ($isSmart) {
            $rules = $this->smartRulesFromRequest();
            $err   = SmartAlbumRules::validateForSave($rules);
            if ($err !== null) {
                return $this->response->setJSON(['status' => 'error', 'message' => $err]);
            }
            $data['smart_rules'] = json_encode($rules);
        }

        if ($albumModel->insert($data)) {
            $this->clearSidebarCountsCache($userId);

            return $this->response->setJSON(['status' => 'success', 'id' => $albumModel->getInsertID()]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to create album: ' . print_r($albumModel->errors(), true)]);
    }

    public function updateSmartAlbum($id)
    {
        $userId = auth()->id();
        if (! $userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated']);
        }

        $albumModel = new \App\Models\AlbumModel();
        $album      = $albumModel->where('user_id', $userId)->find($id);
        if (! $album || empty($album['is_smart'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Album not found or not a smart album']);
        }

        $name = $this->request->getPost('name');
        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Name is required']);
        }

        $rules = $this->smartRulesFromRequest();
        $err   = SmartAlbumRules::validateForSave($rules);
        if ($err !== null) {
            return $this->response->setJSON(['status' => 'error', 'message' => $err]);
        }

        $albumModel->update($id, [
            'name'        => $name,
            'description' => $this->request->getPost('description'),
            'smart_rules' => json_encode($rules),
        ]);

        $this->clearSidebarCountsCache($userId);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function addPhotoToAlbum()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_album_photos');

        $albumId = $this->request->getPost('album_id');
        $photoId = $this->request->getPost('photo_id');

        $albumModel = new \App\Models\AlbumModel();
        $album      = $albumModel->where('user_id', auth()->id())->find($albumId);
        if (! $album) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Album not found']);
        }
        if (! empty($album['is_smart'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Smart albums follow rules automatically; you cannot add photos manually.']);
        }

        $photoModel = new \App\Models\PhotoModel();
        $photo      = $photoModel->where('user_id', auth()->id())->find($photoId);
        if (! $photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        // Check if already in album
        $exists = $builder->where(['album_id' => $albumId, 'photo_id' => $photoId])->get()->getRow();
        if ($exists) return $this->response->setJSON(['status' => 'error', 'message' => 'Already in album']);

        $builder->insert([
            'album_id' => $albumId,
            'photo_id' => $photoId,
            'added_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    private function smartRulesFromRequest(): array
    {
        return SmartAlbumRules::fromArray([
            'date_from'       => $this->request->getPost('date_from'),
            'date_to'         => $this->request->getPost('date_to'),
            'camera_contains' => $this->request->getPost('camera_contains'),
            'has_gps'         => $this->request->getPost('has_gps'),
            'min_latitude'    => $this->request->getPost('min_latitude'),
            'max_latitude'    => $this->request->getPost('max_latitude'),
            'min_longitude'   => $this->request->getPost('min_longitude'),
            'max_longitude'   => $this->request->getPost('max_longitude'),
            'favorite_only'   => $this->request->getPost('favorite_only'),
            'mime_kind'       => $this->request->getPost('mime_kind'),
            'ai_tags'         => $this->request->getPost('ai_tags'),
            'person_id'       => $this->request->getPost('person_id'),
        ]);
    }

    public function toggleFavorite($id)
    {
        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($id);
        if (!$photo) return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);

        $newVal = !$photo['is_favorite'];
        $photoModel->update($id, ['is_favorite' => $newVal]);
        $this->clearSidebarCountsCache();

        return $this->response->setJSON(['status' => 'success', 'is_favorite' => $newVal]);
    }

    public function archivePhoto($id)
    {
        $userId     = auth()->id();
        $photoModel = new \App\Models\PhotoModel();
        $photo      = $photoModel->where('user_id', $userId)->find($id);
        if (!$photo) return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        
        $newStatus = !$photo['is_archived'];
        $photoModel->update($id, ['is_archived' => $newStatus]);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success', 'is_archived' => $newStatus]);
    }

    public function deletePhoto($id)
    {
        $userId     = auth()->id();
        $photoModel = new \App\Models\PhotoModel();

        // If already deleted, force delete (only for owner)
        $deletedPhoto = $photoModel->onlyDeleted()->where('user_id', $userId)->find($id);
        if ($deletedPhoto) {
            @unlink(FCPATH . $deletedPhoto['path']);
            @unlink(FCPATH . $deletedPhoto['thumbnail_path']);
            $this->deleteFromGcp($deletedPhoto['path'], $deletedPhoto['thumbnail_path']);
            $this->deleteMlDataForPhotos([(int) $id]);
            $photoModel->delete($id, true);
            $this->clearSidebarCountsCache();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Permanently deleted']);
        }

        // Verify ownership for active photo
        $activePhoto = $photoModel->where('user_id', $userId)->find($id);
        if (!$activePhoto) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found'])->setStatusCode(404);
        }
        
        // Otherwise, soft delete
        $photoModel->delete($id);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Moved to trash']);
    }

    public function restorePhoto($id)
    {
        $userId     = auth()->id();
        $photoModel = new \App\Models\PhotoModel();
        $photoModel->builder()->where('id', $id)->where('user_id', $userId)->update(['deleted_at' => null]);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success']);
    }

    public function emptyTrash()
    {
        $userId = auth()->id();
        $db     = \Config\Database::connect();
        
        // Find all soft-deleted photos for this user
        $photos = $db->table('tbl_photos')
            ->where('user_id', $userId)
            ->where('deleted_at IS NOT NULL')
            ->get()
            ->getResultArray();

        $purged = 0;
        $purgedIds = [];
        foreach ($photos as $photo) {
            $id = (int) $photo['id'];
            $purgedIds[] = $id;

            // Clean related tables
            $db->table('tbl_album_photos')->where('photo_id', $id)->delete();
            $db->table('tbl_photo_shares')->where('photo_id', $id)->delete();
            $db->table('tbl_shared_links')->where('photo_id', $id)->delete();
            $db->table('tbl_face_encodings')->where('photo_id', $id)->delete();
            $db->table('tbl_photo_tags')->where('photo_id', $id)->delete();
            $db->table('tbl_photo_scans')->where('photo_id', $id)->delete();

            // Delete physical files
            foreach (['path', 'thumbnail_path'] as $field) {
                if (! empty($photo[$field])) {
                    $full = FCPATH . ltrim($photo[$field], '/');
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
            }
            $this->deleteFromGcp($photo['path'] ?? '', $photo['thumbnail_path'] ?? '');

            // Hard delete row
            $db->table('tbl_photos')->where('id', $id)->delete();
            $purged++;
        }

        // Clean up corresponding Qdrant vector embeddings and ML data
        $this->deleteMlDataForPhotos($purgedIds);

        $this->clearSidebarCountsCache();
        helper('audit');
        log_security_action('USER_EMPTIED_TRASH', 'SUCCESS', ['user_id' => $userId, 'purged_count' => $purged]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Successfully emptied trash. Permanently deleted {$purged} items."
        ]);
    }

    private function deleteMlDataForPhotos(array $photoIds): void
    {
        if (empty($photoIds)) return;
        try {
            $client = service('curlrequest', ['connect_timeout' => 3, 'timeout' => 10]);
            $client->post($this->getMlUrl() . '/api/v1/faces/delete-by-photo-ids', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-KEY'    => $this->getMlApiKey(),
                ],
                'body'    => json_encode(['photo_ids' => array_values($photoIds)]),
            ]);
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to clean ML vector data for photos: ' . $e->getMessage());
        }
    }

    public function migrate()
    {
        $migrate = \Config\Services::migrations();
        try {
            $migrate->latest();
            return "Migration successful";
        } catch (\Throwable $e) {
            return "Migration failed: " . $e->getMessage();
        }
    }

    public function bulkAction()
    {
        $userId  = auth()->id();
        $action  = $this->request->getPost('action');
        $photoIds = $this->request->getPost('ids'); // Expects array

        if (!$userId || empty($photoIds) || empty($action)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid request']);
        }

        $photoModel = new \App\Models\PhotoModel();
        $db = \Config\Database::connect();

        switch ($action) {
            case 'archive':
                $photoModel->whereIn('id', $photoIds)->where('user_id', $userId)->set(['is_archived' => true])->update();
                $this->clearSidebarCountsCache();
                break;
            case 'unarchive':
                $photoModel->whereIn('id', $photoIds)->where('user_id', $userId)->set(['is_archived' => false])->update();
                $this->clearSidebarCountsCache();
                break;
            case 'favorite':
                $photoModel->whereIn('id', $photoIds)->where('user_id', $userId)->set(['is_favorite' => true])->update();
                $this->clearSidebarCountsCache();
                break;
            case 'unfavorite':
                $photoModel->whereIn('id', $photoIds)->where('user_id', $userId)->set(['is_favorite' => false])->update();
                $this->clearSidebarCountsCache();
                break;
            case 'delete':
                // Soft delete (moves to trash)
                $photoModel->whereIn('id', $photoIds)->where('user_id', $userId)->delete();
                $this->clearSidebarCountsCache();
                break;
            case 'trash':
                // Soft delete (moves to trash) — alias of delete
                $db->table('photos')
                    ->whereIn('id', $photoIds)
                    ->where('user_id', $userId)
                    ->where('deleted_at IS NULL', null, false)
                    ->set('deleted_at', date('Y-m-d H:i:s'))
                    ->update();
                $this->clearSidebarCountsCache();
                break;
            case 'download':
                $photos = $db->table('photos')
                    ->whereIn('id', $photoIds)
                    ->where('user_id', $userId)
                    ->get()
                    ->getResultArray();
                if (empty($photos)) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'No photos found']);
                }

                if (! class_exists('ZipArchive')) {
                    $first = FCPATH . ltrim($photos[0]['path'], '/');
                    if (! is_file($first)) {
                        return $this->response->setJSON(['status' => 'error', 'message' => 'File not found on disk']);
                    }

                    return $this->response->download($first, null)->setFileName($photos[0]['filename']);
                }

                $zipDir  = WRITEPATH . 'downloads/';
                if (! is_dir($zipDir)) {
                    mkdir($zipDir, 0755, true);
                }
                $zipPath = $zipDir . 'photos_' . $userId . '_' . time() . '.zip';

                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Could not create archive']);
                }

                $added = 0;
                foreach ($photos as $photo) {
                    $full = FCPATH . ltrim($photo['path'], '/');
                    if (is_file($full)) {
                        $zip->addFile($full, $photo['filename'] ?? basename($full));
                        $added++;
                    }
                }
                $zip->close();

                if ($added === 0) {
                    @unlink($zipPath);

                    return $this->response->setJSON(['status' => 'error', 'message' => 'No files found on disk']);
                }

                register_shutdown_function(function () use ($zipPath) {
                    if (is_file($zipPath)) @unlink($zipPath);
                });

                return $this->response->download($zipPath, null)->setFileName('chege-photos-' . date('Ymd-His') . '.zip');
                break;
            case 'add_to_album':
                $albumId = $this->request->getPost('album_id');
                if (! $albumId) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Album ID required']);
                }
                $albumModel = new \App\Models\AlbumModel();
                $album      = $albumModel->where('user_id', $userId)->find($albumId);
                if (! $album) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Album not found']);
                }
                if (! empty($album['is_smart'])) {
                    return $this->response->setJSON(['status' => 'error', 'message' => 'Smart albums follow rules automatically; you cannot add photos manually.']);
                }

                $validPhotos = $photoModel->select('id')
                    ->whereIn('id', $photoIds)
                    ->where('user_id', $userId)
                    ->findAll();
                $validPhotoIds = array_column($validPhotos, 'id');

                $builder = $db->table('tbl_album_photos');
                foreach ($validPhotoIds as $id) {
                    $exists = $builder->where(['album_id' => $albumId, 'photo_id' => $id])->get()->getRow();
                    if (!$exists) {
                        $builder->insert([
                            'album_id' => $albumId,
                            'photo_id' => $id,
                            'added_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
                break;
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    private function getMergedMetadata($path)
    {
        if (!function_exists('exif_read_data')) return null;

        $result = [
            'taken_at' => null,
            'lat'      => null,
            'lng'      => null,
            'exif'     => null
        ];

        try {
            $exif = @exif_read_data($path, 'ANY_TAG', true, true);
            if (!$exif) return $result;

            // 1. Extract Date (try multiple fields)
            if (isset($exif['EXIF']['DateTimeOriginal'])) {
                $result['taken_at'] = date('Y-m-d H:i:s', strtotime($exif['EXIF']['DateTimeOriginal']));
            } elseif (isset($exif['EXIF']['DateTimeDigitized'])) {
                $result['taken_at'] = date('Y-m-d H:i:s', strtotime($exif['EXIF']['DateTimeDigitized']));
            } elseif (isset($exif['IFD0']['DateTime'])) {
                $result['taken_at'] = date('Y-m-d H:i:s', strtotime($exif['IFD0']['DateTime']));
            } elseif (isset($exif['FILE']['FileDateTime'])) {
                $result['taken_at'] = date('Y-m-d H:i:s', $exif['FILE']['FileDateTime']);
            }

            // 2. Extract GPS (including altitude & direction)
            if (isset($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef'], $exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef'])) {
                $result['lat'] = $this->getGpsValue($exif['GPS']['GPSLatitude'], $exif['GPS']['GPSLatitudeRef']);
                $result['lng'] = $this->getGpsValue($exif['GPS']['GPSLongitude'], $exif['GPS']['GPSLongitudeRef']);
            }

            // 3. Store all useful EXIF data as a rich JSON object
            $richExif = [];

            // IFD0 — camera body info
            foreach (['Make', 'Model', 'Software', 'Artist', 'Copyright', 'Orientation', 'ImageDescription'] as $k) {
                if (!empty($exif['IFD0'][$k])) $richExif[$k] = $exif['IFD0'][$k];
            }

            // EXIF — shooting parameters
            $exifFields = [
                'ExposureTime', 'FNumber', 'ISOSpeedRatings', 'FocalLength',
                'FocalLengthIn35mmFilm', 'ExposureBiasValue', 'MaxApertureValue',
                'MeteringMode', 'ExposureProgram', 'Flash', 'WhiteBalance',
                'DigitalZoomRatio', 'SceneCaptureType', 'Contrast', 'Saturation',
                'Sharpness', 'SubjectDistance', 'LightSource', 'ColorSpace',
                'ExposureMode', 'SensingMethod', 'FileSource', 'SceneType',
                'CustomRendered', 'GainControl',
            ];
            foreach ($exifFields as $k) {
                if (isset($exif['EXIF'][$k])) $richExif[$k] = $exif['EXIF'][$k];
            }

            // GPS — full GPS data
            $gpsFields = ['GPSLatitudeRef', 'GPSLatitude', 'GPSLongitudeRef', 'GPSLongitude',
                          'GPSAltitudeRef', 'GPSAltitude', 'GPSImgDirectionRef', 'GPSImgDirection',
                          'GPSMapDatum', 'GPSSpeedRef', 'GPSSpeed', 'GPSTrackRef', 'GPSTrack'];
            foreach ($gpsFields as $k) {
                if (isset($exif['GPS'][$k])) $richExif[$k] = $exif['GPS'][$k];
            }

            // Thumbnail info
            if (isset($exif['THUMBNAIL']['THUMBNAIL_FORMAT'])) {
                $richExif['ThumbnailFormat'] = $exif['THUMBNAIL']['THUMBNAIL_FORMAT'];
            }

            // Computed fields for convenience
            if (isset($exif['COMPUTED']['ApertureFNumber'])) {
                $richExif['ApertureFNumber'] = $exif['COMPUTED']['ApertureFNumber'];
            }
            if (isset($exif['COMPUTED']['CCDWidth'])) {
                $richExif['CCDWidth'] = $exif['COMPUTED']['CCDWidth'];
            }

            $result['exif'] = !empty($richExif) ? json_encode($richExif) : null;

        } catch (\Exception $e) { }

        return $result;
    }


    private function getGpsValue($coordinate, $ref)
    {
        if (!is_array($coordinate)) return null;
        
        $degrees = count($coordinate) > 0 ? $this->gpsToFloat($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? $this->gpsToFloat($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? $this->gpsToFloat($coordinate[2]) : 0;

        $flip = ($ref === 'W' || $ref === 'S') ? -1 : 1;
        return ($degrees + $minutes / 60 + $seconds / 3600) * $flip;
    }

    private function gpsToFloat($coordPart)
    {
        $parts = explode('/', $coordPart);
        if (count($parts) <= 0) return 0;
        if (count($parts) === 1) return (float)$parts[0];
        return (float)$parts[0] / (float)$parts[1];
    }

    public static function generateVideoThumbnail(string $videoPath, string $thumbPath): bool
    {
        if (!file_exists($videoPath)) {
            return false;
        }

        $thumbDir = dirname($thumbPath);
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0777, true);
        }

        // Try extracting poster frame at 1s mark
        $cmd = sprintf(
            'ffmpeg -y -ss 00:00:01.000 -i %s -vframes 1 -vf "scale=400:400:force_original_aspect_ratio=increase,crop=400:400" %s 2>&1',
            escapeshellarg($videoPath),
            escapeshellarg($thumbPath)
        );
        @exec($cmd, $out, $ret);

        if ($ret !== 0 || !file_exists($thumbPath) || filesize($thumbPath) === 0) {
            // Fallback to start frame 0s (for very short clips)
            $cmdFallback = sprintf(
                'ffmpeg -y -ss 00:00:00.000 -i %s -vframes 1 -vf "scale=400:400:force_original_aspect_ratio=increase,crop=400:400" %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($thumbPath)
            );
            @exec($cmdFallback, $outF, $retF);
        }

        return file_exists($thumbPath) && filesize($thumbPath) > 0;
    }

    private function generateThumbnail($source, $target)
    {
        if (file_exists($target)) @unlink($target);

        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (in_array($ext, ['heic', 'heif'])) {
            $tmpJpg = sys_get_temp_dir() . '/' . uniqid('heic_thumb_', true) . '.jpg';
            @exec(sprintf('heif-convert %s %s 2>&1', escapeshellarg($source), escapeshellarg($tmpJpg)));
            if (file_exists($tmpJpg) && filesize($tmpJpg) > 0) {
                try {
                    \Config\Services::image()
                        ->withFile($tmpJpg)
                        ->resize(400, 400, true, 'height')
                        ->save($target);
                } catch (\Exception $e) {
                    @copy($tmpJpg, $target);
                }
                @unlink($tmpJpg);
                return;
            }
        }

        try {
            $image = \Config\Services::image()
                ->withFile($source)
                ->resize(400, 400, true, 'height')
                ->save($target);
        } catch (\Exception $e) {
            @copy($source, $target); 
        }
    }

    public function saveEdit($id)
    {
        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($id);
        
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid image data']);
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            $maxMb = self::MAX_UPLOAD_BYTES / 1024 / 1024;
            $actual = round($file->getSize() / 1024 / 1024, 1);
            return $this->response->setJSON([
                'status' => 'error',
                'message' => "File exceeds the {$maxMb} MB limit ({$actual} MB)."
            ]);
        }

        $fullPath = FCPATH . $photo['path'];
        $thumbnailPath = FCPATH . $photo['thumbnail_path'];

        // Overwrite original
        if (file_exists($fullPath)) @unlink($fullPath);
        $file->move(dirname($fullPath), basename($fullPath));

        // Regenerate thumbnail
        $this->generateThumbnail($fullPath, $thumbnailPath);

        // Update DB
        $imageInfo = @getimagesize($fullPath);
        $photoModel->update($id, [
            'width'     => $imageInfo[0] ?? $photo['width'],
            'height'    => $imageInfo[1] ?? $photo['height'],
            'size'      => filesize($fullPath),
            'file_hash' => hash_file('sha256', $fullPath),
            'updated_at'=> date('Y-m-d H:i:s')
        ]);

        $this->mirrorToGcp($photo['path'], $photo['thumbnail_path'], null, (int) $id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Changes saved successfully']);
    }

    private function triggerFaceScan(int $photoId): void
    {
        try {
            $mlUrl     = $this->getMlUrl();
            $webappUrl = rtrim(base_url(), '/');
            $client = service('curlrequest', [
                'connect_timeout' => 10,
                'timeout'        => 60,
            ]);
            $client->post($mlUrl . '/api/v1/faces/encode', [
                'headers' => [
                    'X-API-KEY'    => $this->getMlApiKey(),
                    'X-Webapp-Url' => $webappUrl,
                ],
                'form_params' => [
                    'photo_id'   => $photoId,
                    'async_task' => 1,
                    'scan_faces' => 1,
                    'scan_tags'  => 1,
                    'scan_clip'  => 1,
                    'webapp_url' => $webappUrl,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', "Auto face scan failed for photo {$photoId}: " . $e->getMessage());
        }
    }

    private function triggerFaceScanAsync(int $photoId): void
    {
        $mlUrl     = $this->getMlUrl();
        $webappUrl = rtrim(base_url(), '/');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $mlUrl . '/api/v1/faces/encode',
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . $this->getMlApiKey(),
                'X-Webapp-Url: ' . $webappUrl,
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'photo_id'   => $photoId,
                'async_task' => 1,
                'scan_faces' => 1,
                'scan_tags'  => 1,
                'scan_clip'  => 1,
                'webapp_url' => $webappUrl,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 100,
            CURLOPT_CONNECTTIMEOUT_MS => 100,
            CURLOPT_NOSIGNAL => 1,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function addTag(): ResponseInterface
    {
        $photoId = (int) $this->request->getPost('photo_id');
        $tag = trim($this->request->getPost('tag'));

        if (!$photoId || empty($tag)) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Photo ID and tag are required',
            ])->setStatusCode(400);
        }

        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($photoId);
        if (! $photo) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Photo not found or unauthorized',
            ])->setStatusCode(404);
        }

        $tagModel = new \App\Models\PhotoTagModel();
        $exists = $tagModel->where('photo_id', $photoId)->where('tag', $tag)->first();
        if ($exists) {
            return $this->response->setJSON([
                'status' => 'success', 'message' => 'Tag already exists',
            ]);
        }

        $tagModel->insert([
            'photo_id'   => $photoId,
            'tag'        => $tag,
            'confidence' => 1.0,
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'tag'    => $tag,
        ]);
    }

    public function removeTag(): ResponseInterface
    {
        $photoId = (int) $this->request->getPost('photo_id');
        $tag = trim($this->request->getPost('tag'));

        if (!$photoId || empty($tag)) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Photo ID and tag are required',
            ])->setStatusCode(400);
        }

        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($photoId);
        if (! $photo) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Photo not found or unauthorized',
            ])->setStatusCode(404);
        }

        $tagModel = new \App\Models\PhotoTagModel();
        $tagModel->where('photo_id', $photoId)->where('tag', $tag)->delete();

        return $this->response->setJSON([
            'status' => 'success',
        ]);
    }

    private function applySearchQuery($query, $q)
    {
        if (empty($q)) {
            return $query;
        }

        $db = \Config\Database::connect();
        $matchedPhotoIds = $db->table('photo_tags')
                              ->select('photo_id')
                              ->like('tag', $q)
                              ->get()
                              ->getResultArray();
        $photoIds = array_column($matchedPhotoIds, 'photo_id');

        $userId = auth()->id() ?: 0;
        $qLower = strtolower(trim($q));

        // Emotion synonyms mapping
        $emotionTerms = [];
        if (in_array($qLower, ['smile', 'smiling', 'happy', 'laugh', 'laughing', 'joy', 'grin', 'cheerful', 'happiness'])) {
            $emotionTerms = ['happy', 'smiling', 'smile'];
        } elseif (in_array($qLower, ['sad', 'crying', 'cry', 'unhappy', 'sorrow', 'sadness', 'depressed'])) {
            $emotionTerms = ['sad', 'crying'];
        } elseif (in_array($qLower, ['neutral', 'serious', 'calm', 'blank', 'expressionless'])) {
            $emotionTerms = ['neutral', 'serious'];
        } elseif (in_array($qLower, ['angry', 'mad', 'furious', 'anger', 'annoyed'])) {
            $emotionTerms = ['angry', 'mad'];
        } elseif (in_array($qLower, ['surprised', 'surprise', 'shocked', 'astonished', 'gasp'])) {
            $emotionTerms = ['surpris', 'shock'];
        } elseif (in_array($qLower, ['fear', 'fearful', 'scared', 'afraid', 'frightened'])) {
            $emotionTerms = ['fear', 'scared'];
        } elseif (in_array($qLower, ['disgust', 'disgusted', 'gross'])) {
            $emotionTerms = ['disgust'];
        } else {
            $emotionTerms = [$qLower];
        }

        try {
            $faceQuery = $db->table('tbl_face_encodings fe')
                ->select('fe.photo_id')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->where('p.user_id', $userId);

            $faceQuery->groupStart();
            foreach ($emotionTerms as $term) {
                $faceQuery->orLike('fe.emotion', $term);
            }

            // Person name matching (e.g. searching "John" finds photos of John)
            $faceQuery->orWhereIn('fe.person_id', function ($builder) use ($q) {
                return $builder->select('id')->from('tbl_people')->like('name', $q);
            });

            // Gender matching
            if (in_array($qLower, ['male', 'man', 'men', 'boy'])) {
                $faceQuery->orWhere('fe.gender', 'male');
            } elseif (in_array($qLower, ['female', 'woman', 'women', 'girl', 'lady'])) {
                $faceQuery->orWhere('fe.gender', 'female');
            }
            $faceQuery->groupEnd();

            $matchedFacePhotos = $faceQuery->get()->getResultArray();
            if (!empty($matchedFacePhotos)) {
                $facePhotoIds = array_column($matchedFacePhotos, 'photo_id');
                $photoIds = array_merge($photoIds, $facePhotoIds);
                $photoIds = array_values(array_unique($photoIds));
            }
        } catch (\Throwable $t) {
            log_message('error', 'Face/Emotion search error: ' . $t->getMessage());
        }

        // Query FastAPI ML service for CLIP semantic search
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 4,
                'timeout'         => 10,
                'headers'         => [
                    'X-API-KEY' => $this->getMlApiKey()
                ]
            ]);

            $url = $this->getMlUrl() . '/api/v1/search/semantic?' . http_build_query([
                'query'   => $q,
                'limit'   => 100,
                'user_id' => auth()->id() ?: 0
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
        } catch (\Exception $e) {
            log_message('error', 'CLIP semantic search call failed: ' . $e->getMessage());
        }

        $query->groupStart()
              ->like('filename', $q)
              ->orLike('ocr_text', $q)
              ->orLike('exif_data', $q)
              ->orLike('taken_at', $q);
        if (!empty($photoIds)) {
            $query->orWhereIn('id', $photoIds);
        }
        $query->groupEnd();

        return $query;
    }

    public function apiSimilar(int $photoId): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = auth()->id() ?: 0;
        $photoModel = new \App\Models\PhotoModel();
        $targetPhoto = $photoModel->where('user_id', $userId)->find($photoId);

        if (! $targetPhoto) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Photo not found',
            ])->setStatusCode(404);
        }

        $similarResults = [];
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 4,
                'timeout'         => 8,
                'headers'         => [
                    'X-API-KEY'    => $this->getMlApiKey(),
                    'X-Webapp-Url' => rtrim(base_url(), '/'),
                ],
                'http_errors'     => false,
            ]);

            $url = $this->getMlUrl() . '/api/v1/search/similar/' . $photoId . '?' . http_build_query([
                'limit'   => 18,
                'user_id' => $userId,
            ]);

            $response = $client->get($url);
            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                $similarResults = $body['results'] ?? [];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Similar photos lookup failed: ' . $e->getMessage());
        }

        if (empty($similarResults)) {
            return $this->response->setJSON([
                'status'   => 'success',
                'photo_id' => $photoId,
                'count'    => 0,
                'photos'   => [],
            ]);
        }

        $similarIds = array_column($similarResults, 'photo_id');
        $scoreMap   = array_column($similarResults, 'score', 'photo_id');

        $matchedPhotos = $photoModel
            ->whereIn('id', $similarIds)
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->findAll();

        $photosOut = [];
        foreach ($matchedPhotos as $p) {
            $score = (float)($scoreMap[$p['id']] ?? 0.0);
            $photosOut[] = [
                'id'             => (int) $p['id'],
                'filename'       => $p['filename'],
                'path'           => $p['path'],
                'thumbnail_path' => $p['thumbnail_path'],
                'url'            => base_url($p['path']),
                'thumbnail_url'  => $p['thumbnail_path'] ? base_url($p['thumbnail_path']) : base_url($p['path']),
                'taken_at'       => $p['taken_at'],
                'score'          => $score,
                'similarity'     => $score,
                'similarity_pct' => round($score * 100),
            ];
        }

        usort($photosOut, fn($a, $b) => $b['score'] <=> $a['score']);

        return $this->response->setJSON([
            'status'   => 'success',
            'photo_id' => $photoId,
            'count'    => count($photosOut),
            'photos'   => $photosOut,
        ]);
    }

    /**
     * Mirrors media file and its thumbnail to Google Cloud Storage if configured.
     */
    protected function mirrorToGcp(string $uploadPath, ?string $thumbPath = null, ?string $mime = null, ?int $photoId = null): bool
    {
        try {
            $gcp = new \App\Services\GcpStorageService();
            if (!$gcp->isConfigured()) {
                return false;
            }

            $cleanUpload = ltrim($uploadPath, '/');
            $localUpload = FCPATH . $cleanUpload;
            $uploaded = false;
            if (file_exists($localUpload)) {
                $res = $gcp->uploadFile($localUpload, $cleanUpload, $mime);
                $uploaded = !empty($res['success']);
            }

            if ($thumbPath) {
                $cleanThumb = ltrim($thumbPath, '/');
                $localThumb = FCPATH . $cleanThumb;
                if (file_exists($localThumb)) {
                    $gcp->uploadFile($localThumb, $cleanThumb, 'image/jpeg');
                }
            }

            if ($uploaded && $photoId) {
                $photoModel = new \App\Models\PhotoModel();
                $photoModel->update($photoId, [
                    'storage_driver' => 'hybrid',
                    'gcp_synced'     => 1,
                    'gcp_synced_at'  => date('Y-m-d H:i:s'),
                ]);
            }

            return $uploaded;
        } catch (\Throwable $e) {
            log_message('warning', 'GCP media mirroring failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes media file and its thumbnail from Google Cloud Storage if configured.
     */
    protected function deleteFromGcp(string $uploadPath, ?string $thumbPath = null): void
    {
        try {
            $gcp = new \App\Services\GcpStorageService();
            if (!$gcp->isConfigured()) {
                return;
            }

            if (!empty($uploadPath)) {
                $gcp->deleteObject(ltrim($uploadPath, '/'));
            }
            if (!empty($thumbPath)) {
                $gcp->deleteObject(ltrim($thumbPath, '/'));
            }
        } catch (\Throwable $e) {
            log_message('warning', 'GCP media deletion failed: ' . $e->getMessage());
        }
    }
}

