<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PhotoModel;
use App\Models\AlbumModel;
use CodeIgniter\HTTP\ResponseInterface;

class Admin extends BaseController
{
    private const DEFAULT_ML_URL = 'http://ml-chege-photos:8000';

    public function home()
    {
        $userModel  = new UserModel();
        $photoModel = new PhotoModel();
        $albumModel = new AlbumModel();

        // System-wide statistics
        $totalUsers   = $userModel->countAllResults();
        $totalPhotos  = $photoModel->countAllResults();
        $totalVideos  = $photoModel->like('mime_type', 'video/')->countAllResults();
        $totalBytes   = $photoModel->selectSum('size')->first()['size'] ?? 0;
        $totalAlbums  = $albumModel->countAllResults();

        // Contact ML health check
        $mlHealth = $this->getMlHealth();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'stats'  => [
                'users'   => $totalUsers,
                'photos'  => $totalPhotos - $totalVideos,
                'videos'  => $totalVideos,
                'albums'  => $totalAlbums,
                'storage' => $this->formatBytes($totalBytes),
            ],
            'mlHealth'       => $mlHealth,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 10)) * 100), // Admin context shows scale of 10GB for overview
        ];

        return view('admin/home', $data);
    }

    public function settings()
    {
        $photoModel = new PhotoModel();
        $userId     = auth()->id();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $data = [
            'counts' => $this->getSidebarCounts(),
            'settings' => [
                'storageLimit'      => setting('App.storageLimit') ?: (1024 * 1024 * 1024), // 1GB default
                'allowRegistration' => setting('Auth.allowRegistration') ?? true,
                'maintenanceMode'   => setting('App.maintenanceMode') ?? false,
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/settings', $data);
    }

    public function saveSettings()
    {
        $rules = [
            'storageLimit' => 'required|numeric',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        $storageLimit      = $this->request->getPost('storageLimit');
        $allowRegistration = (bool) $this->request->getPost('allowRegistration');
        $maintenanceMode   = (bool) $this->request->getPost('maintenanceMode');

        $oldMaintenance = setting('App.maintenanceMode') ?? false;

        setting()->set('App.storageLimit', (int) $storageLimit);
        setting()->set('Auth.allowRegistration', $allowRegistration);
        setting()->set('App.maintenanceMode', $maintenanceMode);

        helper('audit');
        log_security_action('SYSTEM_SETTINGS_CHANGE', 'SUCCESS', [
            'storageLimit'      => $storageLimit,
            'allowRegistration' => $allowRegistration,
            'maintenanceMode'   => $maintenanceMode
        ]);

        if ($oldMaintenance !== $maintenanceMode) {
            log_security_action('MAINTENANCE_MODE_SET', 'SUCCESS', [
                'maintenance_mode' => $maintenanceMode ? 'ENABLED' : 'DISABLED'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'System settings saved successfully.'
        ]);
    }

    public function ml()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $faceModel   = new \App\Models\FaceEncodingModel();
        $personModel = new \App\Models\PersonModel();

        $totalEncodings = $faceModel->countAllResults();
        $totalPersons   = $personModel->countAllResults();
        $unassigned     = $faceModel->where('person_id', null)->countAllResults();

        // Contact ML health check
        $mlHealth = $this->getMlHealth();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'mlHealth' => $mlHealth,
            'mlStats' => [
                'total_encodings' => $totalEncodings,
                'total_persons'   => $totalPersons,
                'unassigned'      => $unassigned,
                'total_photos'    => $photoModel->countAllResults(),
                'scanned_faces'   => $photoModel->where('scanned_face', 1)->countAllResults(),
                'scanned_tags'    => $photoModel->where('scanned_tag', 1)->countAllResults(),
                'scanned_clips'   => $photoModel->where('scanned_clip', 1)->countAllResults(),
            ],
            'settings' => [
                'faceModelPack'     => setting('ML.faceModelPack') ?? 'buffalo_l',
                'faceDetThresh'     => setting('ML.faceDetThresh') ?? 0.5,
                'includeSensitive'  => setting('ML.includeSensitive') ?? false,
                'hdbscanMinCluster' => setting('ML.hdbscanMinCluster') ?? 2,
                'hdbscanMinSamples' => setting('ML.hdbscanMinSamples') ?? 1,
                'clipModelName'     => setting('ML.clipModelName') ?? 'openai/clip-vit-base-patch32',
                'objectDetThresh'   => setting('ML.objectDetThresh') ?? 0.5,
            ],
            'apiKey'         => setting('ML.apiKey') ?? env('ML_API_KEY') ?? 'my_super_secret_shared_token_key_123!',
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/ml', $data);
    }

    public function mlStats()
    {
        $photoModel  = new PhotoModel();
        $faceModel   = new \App\Models\FaceEncodingModel();
        $personModel = new \App\Models\PersonModel();

        $totalEncodings = $faceModel->countAllResults();
        $totalPersons   = $personModel->countAllResults();
        $unassigned     = $faceModel->where('person_id', null)->countAllResults();

        return $this->response->setJSON([
            'status' => 'success',
            'stats' => [
                'total_encodings' => $totalEncodings,
                'total_persons'   => $totalPersons,
                'unassigned'      => $unassigned,
                'total_photos'    => $photoModel->countAllResults(),
                'scanned_faces'   => $photoModel->where('scanned_face', 1)->countAllResults(),
                'scanned_tags'    => $photoModel->where('scanned_tag', 1)->countAllResults(),
                'scanned_clips'   => $photoModel->where('scanned_clip', 1)->countAllResults(),
            ]
        ]);
    }

    public function regenerateApiKey()
    {
        $newKey = bin2hex(random_bytes(32));
        setting()->set('ML.apiKey', $newKey);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'New ML API Key generated successfully.',
            'apiKey'  => $newKey
        ]);
    }

    public function saveMlSettings()
    {
        $rules = [
            'faceModelPack'     => 'required|in_list[buffalo_l,buffalo_m,buffalo_s,buffalo_sc]',
            'faceDetThresh'     => 'required|numeric|greater_than_equal_to[0.1]|less_than_equal_to[1.0]',
            'hdbscanMinCluster' => 'required|integer|greater_than_equal_to[1]',
            'hdbscanMinSamples' => 'required|integer|greater_than_equal_to[1]',
            'clipModelName'     => 'required|string',
            'objectDetThresh'   => 'required|numeric|greater_than_equal_to[0.1]|less_than_equal_to[1.0]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        $faceModelPack     = $this->request->getPost('faceModelPack');
        $faceDetThresh     = $this->request->getPost('faceDetThresh');
        $includeSensitive  = (bool) $this->request->getPost('includeSensitive');
        $hdbscanMinCluster = $this->request->getPost('hdbscanMinCluster');
        $hdbscanMinSamples = $this->request->getPost('hdbscanMinSamples');
        $clipModelName     = $this->request->getPost('clipModelName');
        $objectDetThresh   = $this->request->getPost('objectDetThresh');

        setting()->set('ML.faceModelPack', $faceModelPack);
        setting()->set('ML.faceDetThresh', (float) $faceDetThresh);
        setting()->set('ML.includeSensitive', $includeSensitive);
        setting()->set('ML.hdbscanMinCluster', (int) $hdbscanMinCluster);
        setting()->set('ML.hdbscanMinSamples', (int) $hdbscanMinSamples);
        setting()->set('ML.clipModelName', $clipModelName);
        setting()->set('ML.objectDetThresh', (float) $objectDetThresh);

        helper('audit');
        log_security_action('ML_SETTINGS_CHANGE', 'SUCCESS', [
            'faceModelPack'     => $faceModelPack,
            'faceDetThresh'     => $faceDetThresh,
            'includeSensitive'  => $includeSensitive,
            'hdbscanMinCluster' => $hdbscanMinCluster,
            'hdbscanMinSamples' => $hdbscanMinSamples,
            'clipModelName'     => $clipModelName,
            'objectDetThresh'   => $objectDetThresh
        ]);

        // Tell FastAPI service to reload models dynamically
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 60,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $url = self::DEFAULT_ML_URL . '/api/v1/models/reload?' . http_build_query([
                'model_pack'           => $faceModelPack,
                'face_det_thresh'      => $faceDetThresh,
                'clip_model_name'      => $clipModelName,
                'object_det_threshold' => $objectDetThresh,
            ]);

            $response = $client->post($url);

            if ($response->getStatusCode() === 200) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'ML parameters saved and backend models reloaded successfully.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to dynamically reload model pack: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'ML parameters saved successfully (FastAPI background reload pending container restart).'
        ]);
    }

    public function storage()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        // DB footprint
        $db = \Config\Database::connect();
        $query = $db->query("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = '" . $db->database . "'");
        $dbSize = $query->getRow()->size ?? 0;

        // Directory Stats
        $uploadStats = $this->getDirectoryStats(FCPATH . 'uploads', ['avatars']);
        $thumbStats  = $this->getDirectoryStats(FCPATH . 'thumbnails');

        $data = [
            'counts' => $this->getSidebarCounts(),
            'storage' => [
                'autoPurgeMonths' => setting('Storage.autoPurgeMonths') ?? 3,
                'dbSize'          => $this->formatBytes($dbSize),
                'uploadsSize'     => $this->formatBytes($uploadStats['size']),
                'uploadsCount'    => $uploadStats['count'],
                'thumbsSize'      => $this->formatBytes($thumbStats['size']),
                'thumbsCount'     => $thumbStats['count'],
                'totalFootprint'  => $this->formatBytes($dbSize + $uploadStats['size'] + $thumbStats['size']),
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/storage', $data);
    }

    public function saveStorageSettings()
    {
        $rules = [
            'autoPurgeMonths' => 'required|integer|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        $autoPurgeMonths = $this->request->getPost('autoPurgeMonths');
        setting()->set('Storage.autoPurgeMonths', (int) $autoPurgeMonths);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Storage auto-purge configuration saved successfully.'
        ]);
    }

    public function wipeSystem()
    {
        helper('audit');
        try {
            $db = \Config\Database::connect();
            $db->disableForeignKeyChecks();

            // Drop all tables
            $tables = $db->listTables();
            foreach ($tables as $table) {
                $db->query("DROP TABLE IF EXISTS `{$table}`");
            }

            // Drop all views
            $views = ['photos', 'albums', 'album_photos', 'photo_shares', 'shared_links', 'person', 'face_encoding', 'photo_tags', 'photo_scan', 'scan_job', 'face_cluster', 'face_annotation'];
            foreach ($views as $view) {
                $db->query("DROP VIEW IF EXISTS `{$view}`");
            }

            $db->enableForeignKeyChecks();

            // Run migrations
            $runner = \Config\Services::migrations();
            $runner->latest();

            // Recreate views
            $db->query("CREATE OR REPLACE VIEW photos AS SELECT * FROM tbl_photos;");
            $db->query("CREATE OR REPLACE VIEW albums AS SELECT * FROM tbl_albums;");
            $db->query("CREATE OR REPLACE VIEW album_photos AS SELECT * FROM tbl_album_photos;");
            $db->query("CREATE OR REPLACE VIEW photo_shares AS SELECT * FROM tbl_photo_shares;");
            $db->query("CREATE OR REPLACE VIEW shared_links AS SELECT * FROM tbl_shared_links;");
            $db->query("CREATE OR REPLACE VIEW person AS SELECT * FROM tbl_people;");
            $db->query("CREATE OR REPLACE VIEW face_encoding AS SELECT * FROM tbl_face_encodings;");
            $db->query("CREATE OR REPLACE VIEW photo_tags AS SELECT * FROM tbl_photo_tags;");
            $db->query("CREATE OR REPLACE VIEW photo_scan AS SELECT * FROM tbl_photo_scans;");
            $db->query("CREATE OR REPLACE VIEW scan_job AS SELECT * FROM tbl_scan_jobs;");
            $db->query("CREATE OR REPLACE VIEW face_cluster AS SELECT * FROM tbl_face_clusters;");
            $db->query("CREATE OR REPLACE VIEW face_annotation AS SELECT * FROM tbl_face_annotations;");

            // Seed default settings and users
            $seeder = \Config\Database::seeder();
            $seeder->call('SystemDefaultSeeder');
            $seeder->call('AdminSeeder');
            $seeder->call('SuperAdminSeeder');

            // Delete uploaded photos and thumbnails
            $this->wipeUploadedFiles();

            // Log security action (since DB was reset, we log a fresh action in the new table)
            log_security_action('ADMIN_SYSTEM_WIPE', 'SUCCESS', ['admin_username' => 'superadmin']);

            // Clear session to force fresh login
            auth()->logout();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Factory reset completed successfully. The system has been completely wiped.'
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to wipe system: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function resetDataKeepUsers()
    {
        helper('audit');
        try {
            $db = \Config\Database::connect();
            $db->disableForeignKeyChecks();

            $tablesToTruncate = [
                'tbl_photos', 'tbl_albums', 'tbl_album_photos', 'tbl_photo_shares', 
                'tbl_shared_links', 'tbl_photo_tags', 'tbl_auth_tokens', 
                'sys_cron_logs', 'sys_email_logs', 'sys_security_logs',
                'tbl_face_encodings', 'tbl_people', 'tbl_photo_scans', 
                'tbl_scan_jobs', 'tbl_face_clusters', 'tbl_face_annotations'
            ];

            foreach ($tablesToTruncate as $table) {
                $db->table($table)->truncate();
            }

            $db->enableForeignKeyChecks();

            // Delete uploaded photos and thumbnails
            $this->wipeUploadedFiles();

            log_security_action('ADMIN_DATA_RESET_KEEP_USERS', 'SUCCESS', ['admin_id' => auth()->id()]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Application data cleared successfully. User accounts remain active.'
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to clear data: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function emptyTrashAll()
    {
        helper('audit');
        try {
            $db = \Config\Database::connect();
            $photos = $db->table('photos')
                ->where('deleted_at IS NOT NULL')
                ->get()
                ->getResultArray();

            $purged = 0;
            foreach ($photos as $photo) {
                $id = (int) $photo['id'];

                // Clean related tables
                $db->table('album_photos')->where('photo_id', $id)->delete();
                $db->table('photo_shares')->where('photo_id', $id)->delete();
                $db->table('shared_links')->where('photo_id', $id)->delete();

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
                $db->table('photos')->where('id', $id)->delete();
                $purged++;
            }

            log_security_action('ADMIN_PURGED_TRASH_ALL', 'SUCCESS', [
                'admin_id' => auth()->id(),
                'purged_count' => $purged
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Successfully emptied trash folders. Permanently deleted {$purged} photos."
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to empty trash: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function audits()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $logs = $db->table('sys_security_logs sl')
            ->select('sl.*, u.username')
            ->join('users u', 'u.id = sl.user_id', 'left')
            ->orderBy('sl.created_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'logs'   => $logs,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/audits', $data);
    }

    public function devices()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $tokens = $db->table('tbl_auth_tokens t')
            ->select('t.*, u.username')
            ->join('users u', 'u.id = t.user_id', 'left')
            ->where('t.device_uuid IS NOT NULL')
            ->groupBy('t.device_uuid')
            ->get()
            ->getResultArray();

        // Get image count for each device
        foreach ($tokens as &$tk) {
            $uuid = $tk['device_uuid'];
            $tk['image_count'] = $photoModel->where('device_uuid', $uuid)->countAllResults();
        }

        $data = [
            'counts' => $this->getSidebarCounts(),
            'devices' => $tokens,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/devices', $data);
    }

    private function wipeUploadedFiles()
    {
        $uploadsDir = FCPATH . 'uploads';
        $thumbsDir  = FCPATH . 'thumbnails';
        
        $this->deleteDirContents($uploadsDir, ['avatars', '.gitkeep']);
        $this->deleteDirContents($thumbsDir, ['.gitkeep']);
    }

    private function deleteDirContents($dir, array $exclude = [])
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $name = $fileinfo->getFilename();
            $path = $fileinfo->getRealPath();
            $shouldExclude = false;
            foreach ($exclude as $ex) {
                if (str_contains($path, DIRECTORY_SEPARATOR . $ex)) {
                    $shouldExclude = true;
                    break;
                }
            }
            if ($shouldExclude) continue;

            if ($fileinfo->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    private function getDirectoryStats($path, $excludeDirs = [])
    {
        $bytes = 0;
        $count = 0;
        $path = realpath($path);
        if ($path !== false && is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $filePath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($filePath)) {
                    $bytes += filesize($filePath);
                    $count++;
                } elseif (is_dir($filePath) && !in_array($file, $excludeDirs)) {
                    $subStats = $this->getDirectoryStats($filePath, $excludeDirs);
                    $bytes += $subStats['size'];
                    $count += $subStats['count'];
                }
            }
        }
        return ['size' => $bytes, 'count' => $count];
    }

    public function users()
    {
        $userModel = new UserModel();
        $db        = \Config\Database::connect();
        
        $users = $userModel->orderBy('username', 'ASC')->findAll();
        $formattedUsers = [];

        foreach ($users as $user) {
            // Calculate storage used by this user
            $photoModel = new PhotoModel();
            $storageBytes = $photoModel->where('user_id', $user->id)->selectSum('size')->first()['size'] ?? 0;
            $photoCount = $photoModel->where('user_id', $user->id)->countAllResults();

            // Get user groups / roles
            $groups = $db->table('auth_groups_users')
                ->where('user_id', $user->id)
                ->get()
                ->getResultArray();
            $groupNames = array_column($groups, 'group');

            // Format last active (optional or default)
            $lastActive = $user->last_active ? $user->last_active->toDateTimeString() : 'Never';

            $formattedUsers[] = [
                'id'           => $user->id,
                'username'     => $user->username,
                'email'        => $user->email,
                'name'         => $user->name ?? 'N/A',
                'groups'       => implode(', ', $groupNames),
                'photo_count'  => $photoCount,
                'storage'      => $this->formatBytes($storageBytes),
                'last_active'  => $lastActive,
                'active'       => $user->active,
            ];
        }

        $userId = auth()->id();
        $totalBytes = (new PhotoModel())->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $data = [
            'counts'         => $this->getSidebarCounts(),
            'users'          => $formattedUsers,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/users', $data);
    }

    public function resetMl()
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 30,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $response = $client->delete(self::DEFAULT_ML_URL . '/api/v1/faces/reset');

            if ($response->getStatusCode() === 200) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'ML Engine face data successfully reset and collection recreated.'
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'ML Engine returned status code: ' . $response->getStatusCode()
            ])->setStatusCode($response->getStatusCode());

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to reach ML Engine: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function triggerCluster()
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 30,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $minCluster = setting('ML.hdbscanMinCluster') ?? 2;
            $minSamples = setting('ML.hdbscanMinSamples') ?? 1;

            $response = $client->post(
                self::DEFAULT_ML_URL . '/api/v1/faces/cluster?min_cluster_size=' . $minCluster . '&min_samples=' . $minSamples
            );

            if ($response->getStatusCode() === 200) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'HDBSCAN face clustering successfully completed.'
                ]);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'ML Engine returned status code: ' . $response->getStatusCode()
            ])->setStatusCode($response->getStatusCode());

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to reach ML Engine: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function rescan()
    {
        $type = $this->request->getPost('type'); // 'faces', 'tags', 'clip'
        $mode = $this->request->getPost('mode'); // 'all', 'missing'

        if (!in_array($type, ['faces', 'tags', 'clip'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid scan type.']);
        }

        $photoModel = new PhotoModel();

        $query = $photoModel->select('id');
        if ($mode === 'missing') {
            if ($type === 'faces') {
                $query->where('scanned_face', 0);
            } elseif ($type === 'tags') {
                $query->where('scanned_tag', 0);
            } elseif ($type === 'clip') {
                $query->where('scanned_clip', 0);
            }
        }

        $photos = $query->findAll();
        $total = count($photos);

        if ($total === 0) {
            return $this->response->setJSON(['status' => 'success', 'message' => "No photos require processing for {$type}."]);
        }

        if ($mode === 'all') {
            $db = \Config\Database::connect();
            $col = $type === 'faces' ? 'scanned_face' : ($type === 'tags' ? 'scanned_tag' : 'scanned_clip');
            $db->table('photos')->update([$col => 0]);
        }

        $client = service('curlrequest', [
            'connect_timeout' => 2,
            'timeout'         => 5,
            'headers'         => [
                'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
            ]
        ]);

        $queued = 0;
        foreach ($photos as $p) {
            $photoId = (int) $p['id'];
            try {
                $client->post(self::DEFAULT_ML_URL . '/api/v1/faces/encode', [
                    'form_params' => [
                        'photo_id'   => $photoId,
                        'scan_faces' => $type === 'faces' ? 1 : 0,
                        'scan_tags'  => $type === 'tags' ? 1 : 0,
                        'scan_clip'  => $type === 'clip' ? 1 : 0,
                        'async_task' => 1,
                    ]
                ]);
                $queued++;
            } catch (\Exception $e) {
                log_message('error', "Rescan queuing failed for photo {$photoId}: " . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Successfully queued {$queued} of {$total} photos for {$type} processing."
        ]);
    }

    public function updateRole()
    {
        $userId = $this->request->getPost('user_id');
        $role   = $this->request->getPost('role');

        if (empty($userId) || ! in_array($role, ['user', 'admin', 'superadmin'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid parameters.'])->setStatusCode(400);
        }

        if ((int)$userId === (int)auth()->id()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You cannot modify your own role.'])->setStatusCode(400);
        }

        $userModel = new UserModel();
        $user      = $userModel->find($userId);
        if (! $user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.'])->setStatusCode(404);
        }

        $user->removeGroup('user', 'admin', 'superadmin');
        $user->addGroup($role);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User role successfully updated to ' . $role]);
    }

    public function toggleUserStatus()
    {
        $userId = $this->request->getPost('user_id');
        if (empty($userId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing user ID.'])->setStatusCode(400);
        }

        if ((int)$userId === (int)auth()->id()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You cannot modify your own status.'])->setStatusCode(400);
        }

        $userModel = new UserModel();
        $user      = $userModel->find($userId);
        if (! $user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User not found.'])->setStatusCode(404);
        }

        $newActive = $user->active ? 0 : 1;
        $user->active = $newActive;
        $user->status = $newActive ? 'active' : 'suspended';
        $user->status_message = $newActive ? 'Account active' : 'Suspended by Administrator';

        if ($userModel->skipValidation(true)->save($user)) {
            helper('audit');
            $action = $newActive ? 'ADMIN_UNSUSPEND_USER' : 'ADMIN_SUSPEND_USER';
            log_security_action($action, 'SUCCESS', [
                'target_user_id'  => $userId,
                'target_username' => $user->username
            ]);

            // Dispatch email notification if user suspended
            if (!$newActive) {
                try {
                    $email = service('email');
                    $email->setTo($user->email);
                    $email->setSubject('Security Notice - Account Suspended');
                    $email->setMailType('html');
                    $email->setMessage(view('emails/user_suspended', [
                        'subject'  => 'Security Notice - Account Suspended',
                        'username' => $user->username
                    ]));
                    $email->send();
                } catch (\Exception $e) {
                    log_message('error', 'Failed to send account suspension email: ' . $e->getMessage());
                }
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $newActive ? 'User account activated successfully.' : 'User account suspended successfully.'
            ]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update user status.'])->setStatusCode(500);
    }

    public function purgeUserData()
    {
        $userId = $this->request->getPost('user_id');
        if (empty($userId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing user ID.'])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $photoModel = new PhotoModel();

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
        $db->table('photo_shares')->where('shared_by', $userId)->orWhere('shared_with', $userId)->delete();

        // Shared links
        $photoIds = $db->table('photos')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $ids      = array_column($photoIds, 'id');
        if (! empty($ids)) {
            $db->table('shared_links')->whereIn('photo_id', $ids)->delete();
        }

        // Albums
        $albumIds = $db->table('albums')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $aIds     = array_column($albumIds, 'id');
        if (! empty($aIds)) {
            $db->table('album_photos')->whereIn('album_id', $aIds)->delete();
        }
        $db->table('albums')->where('user_id', $userId)->delete();

        // Photos
        $photoModel->where('user_id', $userId)->purgeDeleted();
        $db->table('photos')->where('user_id', $userId)->delete();

        // Reset user profile details
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if ($user) {
            if ($user->avatar && str_starts_with($user->avatar, 'uploads/avatars/')) {
                $full = FCPATH . $user->avatar;
                if (is_file($full)) @unlink($full);
            }
            $user->fill([
                'name'     => null,
                'avatar'   => null,
                'username' => 'user_' . $userId,
            ]);
            $userModel->skipValidation(true)->save($user);
        }

        $db->transComplete();

        // Clear ML face data
        if (! empty($ids)) {
            try {
                $client = service('curlrequest', ['connect_timeout' => 5, 'timeout' => 30]);
                $client->post(self::DEFAULT_ML_URL . '/api/v1/faces/delete-by-photo-ids', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode(['photo_ids' => $ids]),
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Failed to clear ML face data: ' . $e->getMessage());
            }
        }

        log_security_action('ADMIN_PURGED_USER_DATA', 'SUCCESS', ['purged_user_id' => $userId, 'purged_username' => $user->username ?? '']);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User library successfully cleared. Profile reset to default.']);
    }

    public function deleteUser()
    {
        $userId = $this->request->getPost('user_id');
        if (empty($userId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Missing user ID.'])->setStatusCode(400);
        }

        if ((int)$userId === (int)auth()->id()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You cannot delete your own account.'])->setStatusCode(400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $photoModel = new PhotoModel();

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
        $db->table('photo_shares')->where('shared_by', $userId)->orWhere('shared_with', $userId)->delete();

        // Shared links
        $photoIds = $db->table('photos')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $ids      = array_column($photoIds, 'id');
        if (! empty($ids)) {
            $db->table('shared_links')->whereIn('photo_id', $ids)->delete();
        }

        // Albums
        $albumIds = $db->table('albums')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $aIds     = array_column($albumIds, 'id');
        if (! empty($aIds)) {
            $db->table('album_photos')->whereIn('album_id', $aIds)->delete();
        }
        $db->table('albums')->where('user_id', $userId)->delete();

        // Photos
        $photoModel->where('user_id', $userId)->purgeDeleted();
        $db->table('photos')->where('user_id', $userId)->delete();

        // Delete avatar file
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        if ($user && $user->avatar && str_starts_with($user->avatar, 'uploads/avatars/')) {
            $full = FCPATH . $user->avatar;
            if (is_file($full)) @unlink($full);
        }

        // Delete user
        $db->table('users')->where('id', $userId)->delete();

        // Clean up auth tokens, logins, remember-me
        $db->table('auth_token_logins')->where('user_id', $userId)->delete();
        $db->table('auth_identities')->where('user_id', $userId)->delete();
        $db->table('auth_logins')->where('user_id', $userId)->delete();
        $db->table('auth_remember_tokens')->where('user_id', $userId)->delete();
        $db->table('auth_groups_users')->where('user_id', $userId)->delete();

        $db->transComplete();

        // Clear ML face data
        if (! empty($ids)) {
            try {
                $client = service('curlrequest', ['connect_timeout' => 5, 'timeout' => 30]);
                $client->post(self::DEFAULT_ML_URL . '/api/v1/faces/delete-by-photo-ids', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode(['photo_ids' => $ids]),
                ]);
            } catch (\Exception $e) {
                log_message('error', 'Failed to clear ML face data: ' . $e->getMessage());
            }
        }

        log_security_action('ADMIN_DELETE_USER', 'SUCCESS', ['deleted_user_id' => $userId, 'deleted_username' => $user->username ?? '']);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User account and all data permanently deleted.']);
    }

    public function smtp()
    {
        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $data = [
            'counts' => $this->getSidebarCounts(),
            'smtp' => [
                'fromEmail'   => setting('Email.fromEmail') ?? 'chegephotos@chegecache.co.ke',
                'fromName'    => setting('Email.fromName') ?? 'Chege Photos System',
                'protocol'    => setting('Email.protocol') ?? 'smtp',
                'SMTPHost'    => setting('Email.SMTPHost') ?? 'mail.chegecache.co.ke',
                'SMTPUser'    => setting('Email.SMTPUser') ?? 'chegephotos@chegecache.co.ke',
                'SMTPPass'    => setting('Email.SMTPPass') ?? 'wzj1Vvk]7p9l',
                'SMTPPort'    => setting('Email.SMTPPort') ?? 465,
                'SMTPCrypto'  => setting('Email.SMTPCrypto') ?? 'ssl',
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/smtp', $data);
    }

    public function sentMails()
    {
        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $emailLogs = $db->table('sys_email_logs')
            ->orderBy('sent_at', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        $data = [
            'counts'         => $this->getSidebarCounts(),
            'emailLogs'      => $emailLogs,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/sent_mails', $data);
    }

    public function triggerEvents()
    {
        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $data = [
            'counts'         => $this->getSidebarCounts(),
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/trigger_events', $data);
    }

    public function saveSmtp()
    {
        $rules = [
            'fromEmail' => 'required|valid_email',
            'fromName'  => 'required|string',
            'protocol'  => 'required|in_list[mail,sendmail,smtp]',
            'SMTPHost'  => 'required|string',
            'SMTPUser'  => 'permit_empty|string',
            'SMTPPass'  => 'permit_empty|string',
            'SMTPPort'  => 'required|integer',
            'SMTPCrypto'=> 'permit_empty|in_list[ssl,tls]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        setting()->set('Email.fromEmail', $this->request->getPost('fromEmail'));
        setting()->set('Email.fromName', $this->request->getPost('fromName'));
        setting()->set('Email.protocol', $this->request->getPost('protocol'));
        setting()->set('Email.SMTPHost', $this->request->getPost('SMTPHost'));
        setting()->set('Email.SMTPUser', $this->request->getPost('SMTPUser'));
        setting()->set('Email.SMTPPass', $this->request->getPost('SMTPPass'));
        setting()->set('Email.SMTPPort', (int) $this->request->getPost('SMTPPort'));
        setting()->set('Email.SMTPCrypto', $this->request->getPost('SMTPCrypto'));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'SMTP Configurations saved successfully.'
        ]);
    }

    public function testEmail()
    {
        $email = service('email');
        $adminEmail = auth()->user()->email;
        $trackingId = 'CP-' . strtoupper(bin2hex(random_bytes(8)));
        $db = \Config\Database::connect();

        $email->setTo($adminEmail);
        $email->setSubject('Chege Photos - SMTP Test Email [' . $trackingId . ']');
        $email->setMailType('html');
        $email->setMessage(view('emails/test_email', [
            'subject'    => 'Chege Photos - SMTP Test Email',
            'trackingId' => $trackingId
        ]));

        if ($email->send()) {
            $db->table('sys_email_logs')->insert([
                'tracking_id' => $trackingId,
                'recipient'   => $adminEmail,
                'subject'     => 'Chege Photos - SMTP Test Email',
                'status'      => 'sent',
                'debug_log'   => 'Sent successfully.',
                'sent_at'     => date('Y-m-d H:i:s'),
            ]);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Test email successfully sent to ' . $adminEmail . ' (Tracking ID: ' . $trackingId . ')'
            ]);
        }

        $debugger = $email->printDebugger(['headers', 'subject', 'body']);
        $db->table('sys_email_logs')->insert([
            'tracking_id' => $trackingId,
            'recipient'   => $adminEmail,
            'subject'     => 'Chege Photos - SMTP Test Email',
            'status'      => 'failed',
            'debug_log'   => strip_tags($debugger),
            'sent_at'     => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => 'Email dispatch failed. See debug output below.',
            'debug'   => strip_tags($debugger)
        ])->setStatusCode(500);
    }

    public function verifyEventEmail()
    {
        $eventType = $this->request->getPost('event_type');
        $recipient = $this->request->getPost('recipient_email') ?: auth()->user()->email;

        $eventsMap = [
            'welcome' => [
                'subject' => 'Welcome to Chege Photos - Account Ready',
                'view'    => 'emails/welcome'
            ],
            'storage_warning' => [
                'subject' => 'Storage Threshold Warning Notice',
                'view'    => 'emails/storage_warning'
            ],
            'password_reset' => [
                'subject' => 'Security Notice - Password Reset Requested',
                'view'    => 'emails/password_reset'
            ],
            'system_alert' => [
                'subject' => 'System Administrative Alert - ML Task Completed',
                'view'    => 'emails/system_alert'
            ],
            'maintenance_on' => [
                'subject' => 'System Notice - Maintenance Mode Active',
                'view'    => 'emails/maintenance_on'
            ],
            'maintenance_off' => [
                'subject' => 'System Notice - Maintenance Completed',
                'view'    => 'emails/maintenance_off'
            ],
            'user_registration' => [
                'subject' => 'Confirm Your Registration - Chege Photos',
                'view'    => 'emails/user_registered'
            ],
            'user_deleted_data' => [
                'subject' => 'Data Purge Confirmation - Chege Photos',
                'view'    => 'emails/user_deleted_data'
            ],
            'new_features' => [
                'subject' => 'Discover What\'s New in Chege Photos!',
                'view'    => 'emails/new_features'
            ],
            'account_info' => [
                'subject' => 'Security Alert - Profile Settings Updated',
                'view'    => 'emails/account_info'
            ],
            'user_suspended' => [
                'subject' => 'Security Notice - Account Suspended',
                'view'    => 'emails/user_suspended'
            ],
            'login_alert' => [
                'subject' => 'Security Alert - New Sign-in Detected',
                'view'    => 'emails/login_alert'
            ],
            'weekly_summary' => [
                'subject' => 'Your Weekly Library Summary',
                'view'    => 'emails/weekly_summary'
            ],
            'album_invite' => [
                'subject' => 'Shared Album Invitation',
                'view'    => 'emails/album_invite'
            ],
        ];

        if (! isset($eventsMap[$eventType])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid event type selected.'])->setStatusCode(400);
        }

        $email = service('email');
        $trackingId = 'EVT-' . strtoupper(bin2hex(random_bytes(8)));
        $db = \Config\Database::connect();

        $event = $eventsMap[$eventType];
        $username = auth()->user()->username ?? 'Administrator';

        $email->setTo($recipient);
        $email->setSubject($event['subject'] . ' [' . $trackingId . ']');
        $email->setMailType('html');
        $email->setMessage(view($event['view'], [
            'subject'     => $event['subject'],
            'trackingId'  => $trackingId,
            'username'    => $username,
            'code'        => '948-201',
            'token'       => 'sample_activation_token_123',
            'device_name' => 'Chrome Browser on Linux',
            'ip_address'  => '192.168.100.80',
            'album_name'  => 'Family Gathering 2026',
            'sender_name' => 'Admin',
            'album_token' => 'invite_token_sample'
        ]));

        if ($email->send()) {
            $db->table('sys_email_logs')->insert([
                'tracking_id' => $trackingId,
                'recipient'   => $recipient,
                'subject'     => $event['subject'],
                'status'      => 'sent',
                'debug_log'   => 'Event trigger email dispatched successfully.',
                'sent_at'     => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Event '{$eventType}' email successfully sent to {$recipient} (Tracking ID: {$trackingId})"
            ]);
        }

        $debugger = $email->printDebugger(['headers', 'subject', 'body']);
        $db->table('sys_email_logs')->insert([
            'tracking_id' => $trackingId,
            'recipient'   => $recipient,
            'subject'     => $event['subject'],
            'status'      => 'failed',
            'debug_log'   => strip_tags($debugger),
            'sent_at'     => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => "Failed to send event '{$eventType}' email. Debug logs recorded.",
            'debug'   => strip_tags($debugger)
        ])->setStatusCode(500);
    }

    public function crons()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $cronLogs = $db->table('sys_cron_logs')
            ->orderBy('run_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'cronLogs' => $cronLogs,
            'settings' => [
                'trashPurge' => setting('Cron.trashPurge') ?? '0 2 * * *',
                'mlCluster'  => setting('Cron.mlCluster') ?? '0 * * * *',
                'mlSweep'    => setting('Cron.mlSweep') ?? '*/5 * * * *',
                'cleanTemp'  => setting('Cron.cleanTemp') ?? '30 1 * * *',
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/crons', $data);
    }

    public function saveCronSettings()
    {
        $rules = [
            'trashPurge' => 'required|string',
            'mlCluster'  => 'required|string',
            'mlSweep'    => 'required|string',
            'cleanTemp'  => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        setting()->set('Cron.trashPurge', $this->request->getPost('trashPurge'));
        setting()->set('Cron.mlCluster', $this->request->getPost('mlCluster'));
        setting()->set('Cron.mlSweep', $this->request->getPost('mlSweep'));
        setting()->set('Cron.cleanTemp', $this->request->getPost('cleanTemp'));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'System tasks schedules saved successfully.'
        ]);
    }

    public function runCronJob()
    {
        $job = $this->request->getPost('job');
        $validJobs = ['ml:cluster', 'ml:sweep', 'trash:purge', 'storage:clean-temp'];

        if (! in_array($job, $validJobs, true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid job target.'])->setStatusCode(400);
        }

        try {
            ob_start();
            $result = command($job);
            $output = ob_get_clean();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Job '{$job}' completed successfully.",
                'output'  => $output ?: 'Execution finished without output.'
            ]);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "Job '{$job}' failed: " . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function runAllCronJobs()
    {
        try {
            ob_start();
            $result = command('cron:run');
            $output = ob_get_clean();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Master cron runner executed successfully.',
                'output'  => $output ?: 'No pending jobs were due to run.'
            ]);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cron runner failed: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    private function getMlHealth(): array
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 2,
                'timeout'         => 3,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $response = $client->get(self::DEFAULT_ML_URL . '/api/v1/health');

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                return [
                    'online'  => true,
                    'status'  => $body['status'] ?? 'degraded',
                    'db'      => $body['db_connected'] ?? false,
                    'qdrant'  => $body['qdrant_connected'] ?? false,
                    'models'  => $body['models_loaded'] ?? false,
                    'clip'    => $body['clip_loaded'] ?? false,
                    'yolo'    => $body['yolo_loaded'] ?? false,
                ];
            }
        } catch (\Exception $e) {
            // Degrade gracefully
        }

        return [
            'online'  => false,
            'status'  => 'offline',
            'db'      => false,
            'qdrant'  => false,
            'models'  => false,
            'clip'    => false,
            'yolo'    => false,
        ];
    }

    public function health()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $data = [
            'counts'         => $this->getSidebarCounts(),
            'mlHealth'       => $this->getMlHealth(),
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/health', $data);
    }

    public function testService(): ResponseInterface
    {
        $serviceName = $this->request->getPost('service');

        if (empty($serviceName)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Missing service name parameter.'
            ])->setStatusCode(400);
        }

        switch ($serviceName) {
            case 'mysql':
                try {
                    $db = \Config\Database::connect();
                    $start = microtime(true);
                    $db->query("SELECT 1");
                    $elapsed = round((microtime(true) - $start) * 1000, 2);
                    return $this->response->setJSON([
                        'status'  => 'success',
                        'message' => "Successfully connected to MySQL database '{$db->database}' in {$elapsed}ms."
                    ]);
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'MySQL connection failed: ' . $e->getMessage()
                    ]);
                }

            case 'phpmyadmin':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                    ]);
                    $start = microtime(true);
                    $response = $client->get('http://shared-phpmyadmin:80');
                    $elapsed = round((microtime(true) - $start) * 1000, 2);
                    if ($response->getStatusCode() === 200 || $response->getStatusCode() === 302) {
                        return $this->response->setJSON([
                            'status'  => 'success',
                            'message' => "phpMyAdmin console is online & reachable in {$elapsed}ms."
                        ]);
                    }
                    throw new \Exception("Status code: " . $response->getStatusCode());
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'phpMyAdmin console check failed: ' . $e->getMessage()
                    ]);
                }

            case 'ml':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                        'headers'         => [
                            'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                        ]
                    ]);
                    $start = microtime(true);
                    $mlUrl = env('ML_URL') ?: self::DEFAULT_ML_URL;
                    $response = $client->get($mlUrl . '/api/v1/health');
                    $elapsed = round((microtime(true) - $start) * 1000, 2);
                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody(), true);
                        return $this->response->setJSON([
                            'status'  => 'success',
                            'message' => "FastAPI ML service is online & responding in {$elapsed}ms (status: " . ($body['status'] ?? 'unknown') . ")."
                        ]);
                    }
                    throw new \Exception("Status code: " . $response->getStatusCode());
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'ML service check failed: ' . $e->getMessage()
                    ]);
                }

            case 'qdrant':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                    ]);
                    $start = microtime(true);
                    $qdrantUrl = env('QDRANT_URL') ?: 'http://ml-qdrant:6333';
                    $response = $client->get($qdrantUrl . '/collections');
                    $elapsed = round((microtime(true) - $start) * 1000, 2);
                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody(), true);
                        $count = count($body['result']['collections'] ?? []);
                        return $this->response->setJSON([
                            'status'  => 'success',
                            'message' => "Qdrant Vector database is online in {$elapsed}ms. Collections registered: {$count}."
                        ]);
                    }
                    throw new \Exception("Status code: " . $response->getStatusCode());
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'Qdrant check failed: ' . $e->getMessage()
                    ]);
                }

            case 'clip':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                        'headers'         => [
                            'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                        ]
                    ]);
                    $response = $client->get(self::DEFAULT_ML_URL . '/api/v1/health');
                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody(), true);
                        if ($body['clip_loaded'] ?? false) {
                            return $this->response->setJSON([
                                'status'  => 'success',
                                'message' => 'CLIP model (ViT-B/32) is fully loaded and ready in ML memory.'
                            ]);
                        } else {
                            return $this->response->setJSON([
                                'status'  => 'warning',
                                'message' => 'CLIP model is unloaded (it will load automatically on next query).'
                            ]);
                        }
                    }
                    throw new \Exception("Unreachable ML server");
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'CLIP model check failed: ' . $e->getMessage()
                    ]);
                }

            case 'yolo':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                        'headers'         => [
                            'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                        ]
                    ]);
                    $response = $client->get(self::DEFAULT_ML_URL . '/api/v1/health');
                    if ($response->getStatusCode() === 200) {
                        $body = json_decode($response->getBody(), true);
                        if ($body['yolo_loaded'] ?? false) {
                            return $this->response->setJSON([
                                'status'  => 'success',
                                'message' => 'YOLOv8 ONNX model is fully loaded and ready in ML memory.'
                            ]);
                        } else {
                            return $this->response->setJSON([
                                'status'  => 'warning',
                                'message' => 'YOLOv8 model is unloaded (it will load automatically on next upload/scan).'
                            ]);
                        }
                    }
                    throw new \Exception("Unreachable ML server");
                } catch (\Exception $e) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => 'YOLOv8 model check failed: ' . $e->getMessage()
                    ]);
                }

            default:
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Unknown service request.'
                ])->setStatusCode(400);
        }
    }
}
