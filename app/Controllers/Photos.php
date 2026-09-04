<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SmartAlbumRules;
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
                            ->where('is_archived', false)
                            ->orderBy('taken_at', 'DESC');

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
            'searchQuery'    => $q
        ];
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'photos' => $data['photos'],
                'hasMore' => $pager->hasMore()
            ]);
        }
        
        return view('photos/index', $data);
    }
    public function explore()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId = auth()->id();

        // 1. Metrics first
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $counts = $this->getSidebarCounts();
        
        // 2. Main query
        $query = $photoModel->where('user_id', $userId)
                            ->where('latitude IS NOT NULL')
                            ->where('longitude IS NOT NULL')
                            ->where('is_archived', false);

        $q = $this->request->getGet('q');
        $query = $this->applySearchQuery($query, $q);

        $data = [
            'locations'      => $query->findAll(),
            'storageUsed'    => self::calculateStorageMetrics($totalBytes)['storageUsed'],
            'storagePercent' => self::calculateStorageMetrics($totalBytes)['storagePercent'],
            'counts'         => $counts,
            'searchQuery'    => $q
        ];

        return view('photos/explore', $data);
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
        $fileHash = hash_file('sha256', $tempPath);

        $photoModel = new \App\Models\PhotoModel();
        
        // Check for duplicates
        $existing = $photoModel->where('file_hash', $fileHash)->where('user_id', auth()->id())->first();
        if ($existing) {
            return $this->response->setJSON([
                'status'  => 'success', 
                'message' => 'File already exists.', 
                'id'      => $existing['id'],
                'is_duplicate' => true
            ]);
        }

        $userId = auth()->id() ?: 1;

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
        $thumbnailPath = "{$targetThumbDir}/{$newName}";

        $isVideo = strpos($mimeType, 'video/') === 0;
        $imageInfo = $isVideo ? false : @getimagesize($fullPath);
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
            'file_hash'      => $fileHash,
            'taken_at'       => $metadata['taken_at'] ?? date('Y-m-d H:i:s'),
            'thumbnail_path' => "thumbnails/{$subDir}/{$newName}",
            'latitude'       => $metadata['lat'] ?? null,
            'longitude'      => $metadata['lng'] ?? null,
            'exif_data'      => $metadata['exif'] ?? null,
        ];

        if (!$isVideo) {
            $this->generateThumbnail($fullPath, $thumbnailPath);
        }
        $photoId = $photoModel->insert($data);
        $this->clearSidebarCountsCache();
        $this->checkAndDispatchStorageWarning((int) $userId);

        if (!$isVideo && $photoId) {
            if (function_exists('fastcgi_finish_request')) {
                $this->response->setJSON(['status' => 'success', 'message' => 'Uploaded successfully.', 'id' => (int) $photoId])->send();
                fastcgi_finish_request();
                ignore_user_abort(true);
                $this->triggerFaceScan((int) $photoId);
                return;
            }
            $this->triggerFaceScanAsync((int) $photoId);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Uploaded successfully.', 'id' => (int) $photoId]);
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

        // Check for existing link
        $existing = $shareModel->where('photo_id', $id)->first();
        if ($existing) {
            $token = $existing['access_token'];
            $updateData = [];
            if ($existing['expires_at'] !== $expiresAt) {
                $updateData['expires_at'] = $expiresAt;
            }
            if (!empty($password)) {
                $updateData['password_hash'] = $passwordHash;
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
            if (!empty($passwordHash)) {
                $insertData['password_hash'] = $passwordHash;
            }
            $shareModel->insert($insertData);
        }

        return $this->response->setJSON([
            'status'     => 'success', 
            'url'        => base_url("s/{$token}"),
            'expires_at' => $expiresAt
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
        
        // Define milestones
        $today = date('m-d');
        $thisYear = date('Y');
        $sixMonthsDate = date('Y-m-d', strtotime('-6 months'));
        
        // Fetch photos taken on this day in past years
        $pastYearsPhotos = $photoModel->where('user_id', $userId)
                                      ->where('is_archived', false)
                                      ->where("DATE_FORMAT(taken_at, '%m-%d') =", $today)
                                      ->where("YEAR(taken_at) <", $thisYear)
                                      ->orderBy('taken_at', 'DESC')
                                      ->findAll();
                                      
        // Fetch photos from exactly 6 months ago
        $sixMonthsPhotos = $photoModel->where('user_id', $userId)
                                      ->where('is_archived', false)
                                      ->where("DATE(taken_at) =", $sixMonthsDate)
                                      ->orderBy('taken_at', 'DESC')
                                      ->findAll();

        $data = [
            'pastYearsPhotos' => $pastYearsPhotos,
            'sixMonthsPhotos' => $sixMonthsPhotos,
            'counts'          => $counts
        ];

        return view('photos/memories', $data);
    }

    public function albums()
    {
        $albumModel = new \App\Models\AlbumModel();
        $albums = $albumModel->getAlbumsWithThumbs(auth()->id());

        if ($this->request->getGet('json')) {
            return $this->response->setJSON(['albums' => $albums]);
        }

        $data = [
            'albums' => $albums,
            'counts' => $this->getSidebarCounts()
        ];
        return view('photos/albums', $data);
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
        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->find($id);
        if (!$photo) return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        
        $newStatus = !$photo['is_archived'];
        $photoModel->update($id, ['is_archived' => $newStatus]);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success', 'is_archived' => $newStatus]);
    }

    public function deletePhoto($id)
    {
        $photoModel = new \App\Models\PhotoModel();
        // If already deleted, force delete
        if ($photoModel->onlyDeleted()->find($id)) {
            $photo = $photoModel->onlyDeleted()->find($id);
            if ($photo) {
                @unlink(FCPATH . $photo['path']);
                @unlink(FCPATH . $photo['thumbnail_path']);
                $photoModel->delete($id, true);
                $this->clearSidebarCountsCache();
            }
            return $this->response->setJSON(['status' => 'success', 'message' => 'Permanently deleted']);
        }
        
        // Otherwise, soft delete
        $photoModel->delete($id);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Moved to trash']);
    }

    public function restorePhoto($id)
    {
        $photoModel = new \App\Models\PhotoModel();
        // The model has useSoftDeletes = true, so update() works on the soft-deleted row?
        // Actually CI4 update doesn't automatically find soft-deleted items unless specifically told to,
        // or we can just set deleted_at to null directly via builder.
        $photoModel->builder()->where('id', $id)->update(['deleted_at' => null]);
        $this->clearSidebarCountsCache();
        return $this->response->setJSON(['status' => 'success']);
    }

    public function emptyTrash()
    {
        $userId = auth()->id();
        $db     = \Config\Database::connect();
        
        // Find all soft-deleted photos for this user
        $photos = $db->table('photos')
            ->where('user_id', $userId)
            ->where('deleted_at IS NOT NULL')
            ->get()
            ->getResultArray();

        $purged = 0;
        foreach ($photos as $photo) {
            $id = (int) $photo['id'];

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

            // Hard delete row
            $db->table('tbl_photos')->where('id', $id)->delete();
            $purged++;
        }

        $this->clearSidebarCountsCache();
        helper('audit');
        log_security_action('USER_EMPTIED_TRASH', 'SUCCESS', ['user_id' => $userId, 'purged_count' => $purged]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Successfully emptied trash. Permanently deleted {$purged} items."
        ]);
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

                $builder = $db->table('tbl_album_photos');
                foreach ($photoIds as $id) {
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

    private function generateThumbnail($source, $target)
    {
        if (file_exists($target)) @unlink($target);

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
            'file_hash' => md5_file($fullPath),
            'updated_at'=> date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Changes saved successfully']);
    }

    private function triggerFaceScan(int $photoId): void
    {
        try {
            $mlDefault = (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) ? 'http://ml-chege-photos.railway.internal:8000' : 'http://ml-chege-photos:8000';
            $mlUrl = env('ML_URL') ?: $mlDefault;
            $client = service('curlrequest', [
                'connect_timeout' => 10,
                'timeout'        => 60,
            ]);
            $client->post($mlUrl . '/api/v1/faces/encode', [
                'headers' => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ],
                'form_params' => [
                    'photo_id'   => $photoId,
                    'async_task' => 1,
                    'scan_faces' => 1,
                    'scan_tags'  => 1,
                    'scan_clip'  => 1,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', "Auto face scan failed for photo {$photoId}: " . $e->getMessage());
        }
    }

    private function triggerFaceScanAsync(int $photoId): void
    {
        $mlDefault = (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) ? 'http://ml-chege-photos.railway.internal:8000' : 'http://ml-chege-photos:8000';
        $mlUrl = env('ML_URL') ?: $mlDefault;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $mlUrl . '/api/v1/faces/encode',
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-KEY: ' . (env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!')
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'photo_id'   => $photoId,
                'async_task' => 1,
                'scan_faces' => 1,
                'scan_tags'  => 1,
                'scan_clip'  => 1,
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

        // Query FastAPI ML service for CLIP semantic search
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 3,
                'timeout'         => 10,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $mlDefault = (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) ? 'http://ml-chege-photos.railway.internal:8000' : 'http://ml-chege-photos:8000';
            $url = (env('ML_URL') ?: $mlDefault) . '/api/v1/search/semantic?' . http_build_query([
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
              ->orLike('exif_data', $q)
              ->orLike('taken_at', $q);
        if (!empty($photoIds)) {
            $query->orWhereIn('id', $photoIds);
        }
        $query->groupEnd();

        return $query;
    }
}
