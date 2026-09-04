<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use ZipArchive;

class Settings extends BaseController
{
    public function index()
    {
        return redirect()->to('settings/profile');
    }

    public function profile()
    {
        $userId = auth()->id();
        $user = auth()->user();

        $userBytes = (new \App\Models\PhotoModel())->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
        ];

        return view('photos/settings/profile', $data);
    }

    public function security()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $db = \Config\Database::connect();

        $activeAccessTokens = [];
        if ($user) {
            $model = new \App\Models\AuthTokenModel();
            $rawAccessTokens = $user->accessTokens();
            $authTokens = $model->where('user_id', $userId)->where('is_used', 1)->findAll();
            $deviceMap = [];
            foreach ($authTokens as $t) {
                $key = $t['device_name'] . ' (token)';
                $deviceMap[$key] = $t;
                $deviceMap[$t['device_name']] = $t;
            }

            foreach ($rawAccessTokens as $at) {
                $mapped = $deviceMap[$at->name] ?? null;
                $activeAccessTokens[] = [
                    'id'                 => $at->id,
                    'name'               => $at->name,
                    'scopes'             => $at->scopes,
                    'last_used_at'       => $at->last_used_at ? $at->last_used_at->format('Y-m-d H:i') : null,
                    'created_at'         => $at->created_at ? $at->created_at->format('Y-m-d H:i') : null,
                    'device_name'        => $mapped['device_name'] ?? $at->name,
                    'device_id'          => $mapped['device_id'] ?? null,
                    'device_uuid'        => $mapped['device_uuid'] ?? null,
                    'os_version'         => $mapped['os_version'] ?? null,
                    'screen_metrics'     => $mapped['screen_metrics'] ?? null,
                    'locale'             => $mapped['locale'] ?? null,
                    'timezone'           => $mapped['timezone'] ?? null,
                    'kernel_version'     => $mapped['kernel_version'] ?? null,
                    'device_fingerprint' => $mapped['device_fingerprint'] ?? null,
                    'ip_address'         => $mapped['ip_address'] ?? null,
                ];
            }
        }

        $userBytes = (new \App\Models\PhotoModel())->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
            'activeDevices'  => $activeAccessTokens,
            'logs'           => $db->table('sys_security_logs')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit(50)
                ->get()
                ->getResultArray(),
        ];

        return view('photos/settings/security', $data);
    }

    public function preferences()
    {
        $userId = auth()->id();
        $user = auth()->user();

        $userBytes = (new \App\Models\PhotoModel())->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
            'theme'          => setting('App.theme', "user:{$userId}") ?? 'auto',
            'density'        => setting('App.gridDensity', "user:{$userId}") ?? 'standard',
            'videoAutoplay'  => (bool) (setting('App.videoAutoplay', "user:{$userId}") ?? true),
        ];

        return view('photos/settings/preferences', $data);
    }

    public function updateDensity()
    {
        $userId  = auth()->id();
        $density = $this->request->getPost('density') ?? 'standard';
        if (!in_array($density, ['comfortable', 'standard', 'compact'])) {
            $density = 'standard';
        }
        setting()->set('App.gridDensity', $density, "user:{$userId}");
        return $this->response->setJSON(['status' => 'success', 'message' => 'Grid density updated successfully']);
    }

    public function updateVideoAutoplay()
    {
        $userId   = auth()->id();
        $autoplay = (bool) $this->request->getPost('autoplay');
        setting()->set('App.videoAutoplay', $autoplay, "user:{$userId}");
        return $this->response->setJSON(['status' => 'success', 'message' => 'Video autoplay preference updated']);
    }

    public function notifications()
    {
        $userId = auth()->id();
        $user = auth()->user();

        $userBytes = (new \App\Models\PhotoModel())->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
            'notifications'  => [
                'notifyMemoryDigest'   => (bool) (setting('User.notifyMemoryDigest', "user:{$userId}") ?? true),
                'notifyQuotaAlert'     => (bool) (setting('User.notifyQuotaAlert', "user:{$userId}") ?? true),
                'notifyAlbumInvites'   => (bool) (setting('User.notifyAlbumInvites', "user:{$userId}") ?? true),
                'notifySecurityAlerts' => (bool) (setting('User.notifySecurityAlerts', "user:{$userId}") ?? true),
            ]
        ];

        return view('photos/settings/notifications', $data);
    }

    public function updateNotifications()
    {
        $userId = auth()->id();
        setting()->set('User.notifyMemoryDigest', (bool)$this->request->getPost('notifyMemoryDigest'), "user:{$userId}");
        setting()->set('User.notifyQuotaAlert', (bool)$this->request->getPost('notifyQuotaAlert'), "user:{$userId}");
        setting()->set('User.notifyAlbumInvites', (bool)$this->request->getPost('notifyAlbumInvites'), "user:{$userId}");
        setting()->set('User.notifySecurityAlerts', (bool)$this->request->getPost('notifySecurityAlerts'), "user:{$userId}");

        return $this->response->setJSON(['status' => 'success', 'message' => 'Notification preferences saved successfully']);
    }

    public function storage()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $photoModel = new \App\Models\PhotoModel();

        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $videoBytes = $photoModel->where('user_id', $userId)
            ->like('mime_type', 'video/', 'after')
            ->selectSum('size')
            ->first()['size'] ?? 0;
        $photoBytes = $totalBytes - $videoBytes;

        $storageMetrics = self::calculateStorageMetrics($totalBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
            'storage' => [
                'total'   => $totalBytes,
                'photos'  => $photoBytes,
                'videos'  => $videoBytes,
                'percent' => $storageMetrics['storagePercent'],
                'limit'   => $storageMetrics['storageQuotaFormatted'],
            ],
        ];

        return view('photos/settings/storage', $data);
    }

    public function ml()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $photoModel = new \App\Models\PhotoModel();
        $db = \Config\Database::connect();

        $detectedFaces = (int) $db->table('tbl_face_encodings fe')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->countAllResults();

        $totalImages = $photoModel->where('user_id', $userId)
            ->where('mime_type NOT LIKE', 'video/%')
            ->countAllResults();

        $analyzedImages = $photoModel->where('user_id', $userId)
            ->where('mime_type NOT LIKE', 'video/%')
            ->where('scanned_face', 1)
            ->countAllResults();

        $persons = (int) $db->table('tbl_people pr')
            ->join('tbl_face_encodings fe', 'fe.person_id = pr.id')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->distinct()
            ->select('pr.id')
            ->countAllResults();

        $userBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
            'mlStats' => [
                'detected_faces'  => $detectedFaces,
                'total_images'    => $totalImages,
                'analyzed_images' => $analyzedImages,
                'persons'         => $persons,
            ],
            'mlPrivacy' => [
                'estimate_sensitive_attributes' => (bool) (setting('ML.estimateSensitiveAttributes', "user:{$userId}") ?? true),
                'exclude_hidden_memories'       => (bool) (setting('ML.excludeHiddenMemories', "user:{$userId}") ?? true),
                'auto_indexing'                 => (bool) (setting('ML.autoIndexing', "user:{$userId}") ?? true),
            ],
        ];

        return view('photos/settings/ml', $data);
    }

    public function updateMlPrivacy()
    {
        $userId = auth()->id();
        setting()->set('ML.estimateSensitiveAttributes', (bool)$this->request->getPost('estimate_sensitive_attributes'), "user:{$userId}");
        setting()->set('ML.excludeHiddenMemories', (bool)$this->request->getPost('exclude_hidden_memories'), "user:{$userId}");
        setting()->set('ML.autoIndexing', (bool)$this->request->getPost('auto_indexing'), "user:{$userId}");

        return $this->response->setJSON(['status' => 'success', 'message' => 'AI Privacy settings updated successfully']);
    }

    public function scanStorageCleanup()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();

        // 1. Exact duplicates by sha256
        $dupRows = $db->query("
            SELECT sha256, COUNT(*) as cnt, SUM(size) as total_bytes, MIN(size) as single_size
            FROM tbl_photos
            WHERE user_id = ? AND deleted_at IS NULL AND sha256 IS NOT NULL AND sha256 != ''
            GROUP BY sha256
            HAVING cnt > 1
        ", [$userId])->getResultArray();

        $dupCount = 0;
        $dupReclaimBytes = 0;
        foreach ($dupRows as $row) {
            $extraFiles = (int) $row['cnt'] - 1;
            $dupCount += $extraFiles;
            $dupReclaimBytes += ($extraFiles * (int) $row['single_size']);
        }

        // 2. Large videos > 100 MB
        $videoRows = $db->query("
            SELECT id, size, title, mime_type
            FROM tbl_photos
            WHERE user_id = ? AND deleted_at IS NULL AND mime_type LIKE 'video/%' AND size > ?
        ", [$userId, 100 * 1024 * 1024])->getResultArray();

        $videoCount = count($videoRows);
        $videoBytes = array_sum(array_column($videoRows, 'size'));

        return $this->response->setJSON([
            'status'             => 'success',
            'duplicates_count'   => $dupCount,
            'duplicates_size'    => $this->formatBytes($dupReclaimBytes),
            'large_videos_count' => $videoCount,
            'large_videos_size'  => $this->formatBytes($videoBytes),
        ]);
    }

    public function purgeDuplicates()
    {
        $userId = auth()->id();
        $photoModel = new \App\Models\PhotoModel();
        $db = \Config\Database::connect();

        // Find duplicate groups
        $dupHashes = $db->query("
            SELECT sha256
            FROM tbl_photos
            WHERE user_id = ? AND deleted_at IS NULL AND sha256 IS NOT NULL AND sha256 != ''
            GROUP BY sha256
            HAVING COUNT(*) > 1
        ", [$userId])->getResultArray();

        $purged = 0;
        $freedBytes = 0;

        foreach ($dupHashes as $item) {
            $photos = $photoModel->where('user_id', $userId)
                ->where('sha256', $item['sha256'])
                ->orderBy('created_at', 'ASC')
                ->findAll();

            // Keep the first (oldest) one, soft-delete the rest to Trash
            for ($i = 1; $i < count($photos); $i++) {
                $photoModel->delete($photos[$i]['id']);
                $freedBytes += (int) $photos[$i]['size'];
                $purged++;
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Moved {$purged} duplicate photo(s) to Trash, recovering " . $this->formatBytes($freedBytes) . " of quota space.",
        ]);
    }

    public function export()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $photoModel = new \App\Models\PhotoModel();

        $userBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
        ];

        return view('photos/settings/export', $data);
    }

    public function accessTokens()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $photoModel = new \App\Models\PhotoModel();

        $userBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
        ];

        return view('photos/settings/access_tokens', $data);
    }

    public function danger()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $photoModel = new \App\Models\PhotoModel();

        $userBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;
        $storageMetrics = self::calculateStorageMetrics($userBytes);

        $data = [
            'user'           => $user,
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $storageMetrics['storageUsed'],
            'storagePercent' => $storageMetrics['storagePercent'],
        ];

        return view('photos/settings/danger', $data);
    }

    public function updateProfile()
    {
        $userId = auth()->id();
        $rules  = [
            'name'     => 'permit_empty|string|max_length[191]',
            'username' => 'required|min_length[3]|max_length[30]|regex_match[/^[a-zA-Z0-9_\.\-]+$/]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $this->validator->getErrors())]);
        }

        $user = auth()->user();
        $user->fill([
            'name'     => $this->request->getPost('name'),
            'username' => $this->request->getPost('username'),
        ]);

        $userModel = new UserModel();
        if ($userModel->skipValidation(true)->save($user)) {
            $this->clearSidebarCountsCache($userId);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Profile updated successfully.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $userModel->errors())]);
    }

    public function updateAvatar()
    {
        $file = $this->request->getFile('avatar');
        if (! $file || ! $file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please choose a valid image file.']);
        }

        if (! $file->hasMoved()) {
            $mime = $file->getMimeType();
            $ok   = in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
            if (! $ok) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Allowed types: JPEG, PNG, WebP, or GIF.']);
            }
            if ($file->getSize() > 2 * 1024 * 1024) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Image must be 2 MB or smaller.']);
            }
        }

        $user   = auth()->user();
        $userId = (int) auth()->id();

        $dir = FCPATH . 'uploads/avatars/';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower((string) pathinfo($file->getName(), PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $newName = 'user_' . $userId . '_' . time() . '.' . $ext;

        $oldAvatar = $user->avatar ?? null;
        $file->move($dir, $newName, true);
        $relative = 'uploads/avatars/' . $newName;

        $user->avatar = $relative;
        $userModel    = new UserModel();
        if (! $userModel->skipValidation(true)->save($user)) {
            @unlink($dir . $newName);

            return $this->response->setJSON(['status' => 'error', 'message' => 'Could not save avatar.']);
        }

        if ($oldAvatar && str_starts_with($oldAvatar, 'uploads/avatars/')) {
            $oldPath = FCPATH . $oldAvatar;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $this->clearSidebarCountsCache($userId);

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => 'Avatar updated.',
            'avatar_url' => base_url($relative),
        ]);
    }

    public function removeAvatar()
    {
        $user   = auth()->user();
        $userId = auth()->id();
        $path   = $user->avatar ?? null;

        if ($path && str_starts_with($path, 'uploads/avatars/')) {
            $full = FCPATH . $path;
            if (is_file($full)) {
                @unlink($full);
            }
        }

        $user->avatar = null;
        $userModel    = new UserModel();
        $userModel->skipValidation(true)->save($user);
        $this->clearSidebarCountsCache($userId);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Avatar removed.']);
    }

    public function updatePassword()
    {
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON(['status' => 'error', 'message' => implode(' ', $this->validator->getErrors())]);
        }

        $user = auth()->user();

        $auth   = service('auth');
        $result = $auth->check([
            'email'    => $user->email,
            'password' => $this->request->getPost('current_password'),
        ]);

        if (! $result->isOK()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Current password incorrect.']);
        }

        $user->password = $this->request->getPost('new_password');
        $userModel      = new UserModel();

        if ($userModel->skipValidation(true)->save($user)) {
            helper('audit');
            log_security_action('PASSWORD_CHANGE', 'SUCCESS', ['user_id' => $user->id]);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Password changed successfully.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update password.']);
    }

    public function updateTheme()
    {
        $theme  = $this->request->getPost('theme');
        $userId = auth()->id();

        // 'grey' was removed — silently map to 'dark' for backwards compatibility
        if ($theme === 'grey') {
            $theme = 'dark';
        }

        if (! in_array($theme, ['auto', 'light', 'dark', 'solarized'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid theme.']);
        }

        setting()->set('App.theme', $theme, "user:{$userId}");

        return $this->response->setJSON(['status' => 'success', 'message' => 'Theme updated successfully.']);
    }

    public function clearData()
    {
        $userId  = auth()->id();
        $confirm = $this->request->getPost('confirm');
        if ($confirm !== 'CLEAR') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please type CLEAR to confirm.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $photoModel = new \App\Models\PhotoModel();

        // Delete physical files
        $photos = $photoModel->where('user_id', $userId)->withDeleted()->findAll();
        foreach ($photos as $photo) {
            foreach (['path', 'thumbnail_path'] as $field) {
                if (! empty($photo[$field])) {
                    $full = FCPATH . ltrim($photo[$field], '/');
                    if (is_file($full)) @unlink($full);
                }
            }
        }

        // Photo shares where user is shared_by or shared_with
        $db->table('photo_shares')->where('shared_by', $userId)->orWhere('shared_with', $userId)->delete();

        // Shared links via user's photos
        $photoIds = $db->table('photos')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $ids      = array_column($photoIds, 'id');
        if (! empty($ids)) {
            $db->table('tbl_shared_links')->whereIn('photo_id', $ids)->delete();
        }

        // Album photos + albums
        $albumIds = $db->table('tbl_albums')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $aIds     = array_column($albumIds, 'id');
        if (! empty($aIds)) {
            $db->table('tbl_album_photos')->whereIn('album_id', $aIds)->delete();
        }
        $db->table('tbl_albums')->where('user_id', $userId)->delete();

        // Delete all photos (hard delete)
        $photoModel->where('user_id', $userId)->purgeDeleted();
        $db->table('tbl_photos')->where('user_id', $userId)->delete();

        // Reset user profile fields
        $user = auth()->user();
        $user->fill([
            'name'       => null,
            'avatar'     => null,
            'username'   => 'user_' . $userId,
        ]);
        (new UserModel())->skipValidation(true)->save($user);

        $db->transComplete();

        $this->clearSidebarCountsCache($userId);

        // Clear ML face data for this user
        if (! empty($ids)) {
            try {
                $mlUrl = $this->getMlUrl();
                $client = service('curlrequest', ['connect_timeout' => 10, 'timeout' => 60]);
                $client->post($mlUrl . '/api/v1/faces/delete-by-photo-ids', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-API-KEY'    => $this->getMlApiKey()
                    ],
                    'body'    => json_encode(['photo_ids' => $ids]),
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Failed to clear ML face data: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'All user data cleared. Your account has been reset.']);
    }

    public function deleteAccount()
    {
        $userId  = auth()->id();
        $confirm = $this->request->getPost('confirm');
        if ($confirm !== 'DELETE') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please type DELETE to confirm.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $photoModel = new \App\Models\PhotoModel();

        // Delete physical files
        $photos = $photoModel->where('user_id', $userId)->withDeleted()->findAll();
        foreach ($photos as $photo) {
            foreach (['path', 'thumbnail_path'] as $field) {
                if (! empty($photo[$field])) {
                    $full = FCPATH . ltrim($photo[$field], '/');
                    if (is_file($full)) @unlink($full);
                }
            }
        }

        // Photo shares
        $db->table('tbl_photo_shares')->where('shared_by', $userId)->orWhere('shared_with', $userId)->delete();

        // Shared links
        $photoIds = $db->table('tbl_photos')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $ids      = array_column($photoIds, 'id');
        if (! empty($ids)) {
            $db->table('tbl_shared_links')->whereIn('photo_id', $ids)->delete();
        }

        // Albums
        $albumIds = $db->table('tbl_albums')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $aIds     = array_column($albumIds, 'id');
        if (! empty($aIds)) {
            $db->table('tbl_album_photos')->whereIn('album_id', $aIds)->delete();
        }
        $db->table('tbl_albums')->where('user_id', $userId)->delete();

        // Photos
        $photoModel->where('user_id', $userId)->purgeDeleted();
        $db->table('tbl_photos')->where('user_id', $userId)->delete();

        // Delete avatar file
        $user = auth()->user();
        if ($user->avatar && str_starts_with($user->avatar, 'uploads/avatars/')) {
            $full = FCPATH . $user->avatar;
            if (is_file($full)) @unlink($full);
        }

        // Delete user
        $db->table('users')->where('id', $userId)->delete();

        // Clean up auth tokens, remember-me, etc.
        $db->table('auth_token_logins')->where('user_id', $userId)->delete();
        $db->table('auth_identities')->where('user_id', $userId)->delete();
        $db->table('auth_logins')->where('user_id', $userId)->delete();
        $db->table('auth_remember_tokens')->where('user_id', $userId)->delete();

        $db->transComplete();

        $this->clearSidebarCountsCache($userId);

        // Clear ML face data for this user
        if (! empty($ids)) {
            try {
                $mlUrl = $this->getMlUrl();
                $client = service('curlrequest', ['connect_timeout' => 10, 'timeout' => 60]);
                $client->post($mlUrl . '/api/v1/faces/delete-by-photo-ids', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-API-KEY'    => $this->getMlApiKey()
                    ],
                    'body'    => json_encode(['photo_ids' => $ids]),
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Failed to clear ML face data: ' . $e->getMessage());
            }
        }

        // Log out
        auth()->logout();

        return $this->response->setJSON(['status' => 'success', 'message' => 'Account permanently deleted.']);
    }

    public function refreshMetadata()
    {
        $userId    = auth()->id();
        $photoModel = new \App\Models\PhotoModel();

        $photos = $photoModel->where('user_id', $userId)->findAll();
        $count  = 0;

        foreach ($photos as $photo) {
            $fullPath = FCPATH . ltrim($photo['path'], '/');
            if (! is_file($fullPath)) continue;

            $isVideo = strpos($photo['mime_type'] ?? '', 'video/') === 0;
            if ($isVideo) continue;

            $metadata = (new \App\Controllers\Photos())->getMergedMetadata($fullPath);
            if (! $metadata || ! $metadata['exif']) continue;

            $photoModel->update($photo['id'], [
                'exif_data' => $metadata['exif'],
                'taken_at'  => $metadata['taken_at'] ?? $photo['taken_at'],
                'latitude'  => $metadata['lat'] ?? $photo['latitude'],
                'longitude' => $metadata['lng'] ?? $photo['longitude'],
            ]);
            $count++;
        }

        $this->clearSidebarCountsCache($userId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Metadata refreshed for {$count} photo(s)."
        ]);
    }

    public function exportData()
    {
        $userId  = auth()->id();
        $type    = $this->request->getPost('type') ?? 'all'; // all, images, videos
        $includeMetadata = (bool) ($this->request->getPost('metadata') ?? true);
        $includeAlbums   = (bool) ($this->request->getPost('albums') ?? true);

        if (! in_array($type, ['all', 'images', 'videos'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid export type.']);
        }

        $photoModel = new \App\Models\PhotoModel();
        $db         = \Config\Database::connect();

        $pQuery = $photoModel->where('user_id', $userId);
        if ($type === 'images') {
            $pQuery->where('mime_type NOT LIKE', 'video/%');
        } elseif ($type === 'videos') {
            $pQuery->like('mime_type', 'video/');
        }
        $photos = $pQuery->findAll();

        if (empty($photos)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No files match your export criteria.']);
        }

        $exportDir = WRITEPATH . 'uploads/exports/';
        if (! is_dir($exportDir)) mkdir($exportDir, 0755, true);

        $token  = bin2hex(random_bytes(16));
        $zipPath = $exportDir . $token . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Could not create archive.']);
        }

        $added = 0;
        foreach ($photos as $photo) {
            $fullPath = FCPATH . ltrim($photo['path'], '/');
            if (! is_file($fullPath)) continue;

            $arcName = $type . '/' . ($photo['filename'] ?? basename($fullPath));
            $zip->addFile($fullPath, $arcName);
            $added++;
        }

        // Include metadata JSON
        if ($includeMetadata) {
            $metaPayload = [];
            foreach ($photos as $p) {
                $entry = [
                    'filename'       => $p['filename'],
                    'path'           => $p['path'],
                    'mime_type'      => $p['mime_type'],
                    'size'           => $p['size'],
                    'width'          => $p['width'],
                    'height'         => $p['height'],
                    'taken_at'       => $p['taken_at'],
                    'latitude'       => $p['latitude'],
                    'longitude'      => $p['longitude'],
                    'is_favorite'    => $p['is_favorite'],
                    'is_archived'    => $p['is_archived'],
                    'exif_data'      => $p['exif_data'] ? json_decode($p['exif_data'], true) : null,
                    'file_hash'      => $p['file_hash'],
                ];
                $metaPayload[] = $entry;
            }

            // Include album structure
            if ($includeAlbums) {
                $albumModel = new \App\Models\AlbumModel();
                $albums     = $albumModel->where('user_id', $userId)->findAll();
                $albumPhotoModel = new \App\Models\AlbumPhotoModel();
                foreach ($albums as &$album) {
                    $albumPhotos = $albumPhotoModel->where('album_id', $album['id'])->findAll();
                    $album['photo_ids'] = array_column($albumPhotos, 'photo_id');
                }
                unset($album);
                $metaPayload['_albums'] = $albums;
            }

            $zip->addFromString('metadata.json', json_encode($metaPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $zip->close();

        // Clean up old exports (older than 1 hour)
        foreach (glob($exportDir . '*.zip') as $oldZip) {
            if (basename($oldZip) !== $token . '.zip' && filemtime($oldZip) < time() - 3600) {
                @unlink($oldZip);
            }
        }

        $url = base_url('settings/download-export/' . $token);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Archive created with {$added} file(s).",
            'url'     => $url,
            'size'    => $this->formatBytes(filesize($zipPath)),
        ]);
    }

    public function downloadExport($token)
    {
        $file = WRITEPATH . 'uploads/exports/' . basename($token) . '.zip';
        if (! is_file($file)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Export file not found or expired.');
        }

        return $this->response->download($file, null)->setFileName('chege-photos-export.zip');
    }    public function getSettings()
    {
        $userId = auth()->id();

        return $this->response->setJSON([
            'theme' => setting('App.theme', "user:{$userId}") ?? 'auto',
        ]);
    }

    public function mlStatus()
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthenticated'])->setStatusCode(401);
        }

        $db = \Config\Database::connect();
        
        $totalPhotos = $db->table('photos')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->countAllResults();

        $scannedFaces = $db->table('photos')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->where('scanned_face', 1)
            ->countAllResults();

        $scannedTags = $db->table('photos')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->where('scanned_tag', 1)
            ->countAllResults();

        $scannedClips = $db->table('tbl_photos')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->where('scanned_clip', 1)
            ->countAllResults();

        $peopleCount = $db->table('tbl_people pr')
            ->join('tbl_face_encodings fe', 'fe.person_id = pr.id')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->distinct()
            ->select('pr.id')
            ->countAllResults();

        $tagsCount = $db->table('tbl_photo_tags pt')
            ->join('tbl_photos p', 'p.id = pt.photo_id')
            ->where('p.user_id', $userId)
            ->countAllResults();

        return $this->response->setJSON([
            'status' => 'success',
            'stats' => [
                'total_photos'  => $totalPhotos,
                'scanned_faces' => $scannedFaces,
                'scanned_tags'  => $scannedTags,
                'scanned_clips' => $scannedClips,
                'people_count'  => $peopleCount,
                'tags_count'    => $tagsCount,
            ]
        ]);
    }
}
