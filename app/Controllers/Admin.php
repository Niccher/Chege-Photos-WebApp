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

    private function getMlUrl(): string
    {
        if ($url = env('ML_URL') ?: (setting('ML.url') ?: getenv('ML_URL'))) {
            return rtrim($url, '/');
        }

        // Auto-detect Railway internal networking environment
        if (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) {
            return 'http://ml-chege-photos.railway.internal:8000';
        }

        return self::DEFAULT_ML_URL;
    }

    private function getQdrantUrl(): string
    {
        if ($url = env('QDRANT_URL') ?: getenv('QDRANT_URL')) {
            return rtrim($url, '/');
        }

        // Auto-detect Railway internal networking environment
        if (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) {
            $candidates = [
                'ml-qdrant.railway.internal',
                'qdrant.railway.internal',
                'ml-qdrant',
                'qdrant'
            ];
            foreach ($candidates as $host) {
                if (gethostbyname($host) !== $host) {
                    return "http://{$host}:6333";
                }
            }
            return 'http://ml-qdrant.railway.internal:6333';
        }

        return 'http://ml-qdrant:6333';
    }

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

        $db = \Config\Database::connect();
        $dbVersion = 'MySQL ' . $db->getVersion();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'settings' => [
                // Platform Branding
                'siteName'                 => setting('App.siteName') ?? 'Chege Photos',
                'supportEmail'             => setting('App.supportEmail') ?? 'support@chegephotos.com',
                'timezone'                 => setting('App.timezone') ?? 'Africa/Nairobi',
                'dateFormat'               => setting('App.dateFormat') ?? 'Y-m-d',

                // Security & Auth
                'allowRegistration'        => setting('Auth.allowRegistration') ?? true,
                'requireEmailVerification' => setting('Auth.requireEmailVerification') ?? false,
                'sessionLifetime'          => setting('Auth.sessionLifetime') ?? 86400,
                'maxLoginAttempts'         => setting('Auth.maxLoginAttempts') ?? 5,

                // Media & Upload Constraints
                'maxUploadSizeMb'          => setting('App.maxUploadSizeMb') ?? 500,
                'maxBatchUploadCount'      => setting('App.maxBatchUploadCount') ?? 50,
                'allowedExtensions'        => setting('App.allowedExtensions') ?? 'jpg,jpeg,png,webp,heic,tiff,mp4,mov,m4v,webm,mkv,avi',

                // Quotas & Retention
                'storageLimit'             => setting('App.storageLimit') ?: (1024 * 1024 * 1024), // 1GB default
                'trashRetentionDays'       => setting('App.trashRetentionDays') ?? 30,

                // Governance & Maintenance
                'maintenanceMode'          => setting('App.maintenanceMode') ?? false,
                'maintenanceMessage'       => setting('App.maintenanceMessage') ?? 'Chege Photos is currently undergoing routine system maintenance. We will be back online shortly.',
            ],
            'serverSpecs' => [
                'phpVersion'        => PHP_VERSION,
                'ciVersion'         => \CodeIgniter\CodeIgniter::CI_VERSION,
                'memoryLimit'       => ini_get('memory_limit'),
                'uploadMaxFilesize' => ini_get('upload_max_filesize'),
                'postMaxSize'       => ini_get('post_max_size'),
                'maxExecutionTime'  => ini_get('max_execution_time') . 's',
                'dbVersion'         => $dbVersion,
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/settings', $data);
    }

    public function saveSettings()
    {
        $rules = [
            'storageLimit'        => 'required|numeric',
            'siteName'            => 'required|min_length[2]|max_length[100]',
            'supportEmail'        => 'required|valid_email',
            'maxUploadSizeMb'     => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[10240]',
            'maxBatchUploadCount' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[250]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $this->validator->getErrors())
            ])->setStatusCode(400);
        }

        // Platform Branding
        $siteName     = $this->request->getPost('siteName');
        $supportEmail = $this->request->getPost('supportEmail');
        $timezone     = $this->request->getPost('timezone') ?? 'Africa/Nairobi';
        $dateFormat   = $this->request->getPost('dateFormat') ?? 'Y-m-d';

        // Security & Auth
        $allowRegistration        = (bool) $this->request->getPost('allowRegistration');
        $requireEmailVerification = (bool) $this->request->getPost('requireEmailVerification');
        $sessionLifetime          = (int) ($this->request->getPost('sessionLifetime') ?? 86400);
        $maxLoginAttempts         = (int) ($this->request->getPost('maxLoginAttempts') ?? 5);

        // Upload Constraints
        $maxUploadSizeMb     = (int) $this->request->getPost('maxUploadSizeMb');
        $maxBatchUploadCount = (int) $this->request->getPost('maxBatchUploadCount');
        $allowedExtensions   = strtolower(trim($this->request->getPost('allowedExtensions') ?? 'jpg,jpeg,png,webp,heic'));

        // Quotas & Retention
        $storageLimit       = (int) $this->request->getPost('storageLimit');
        $trashRetentionDays = (int) ($this->request->getPost('trashRetentionDays') ?? 30);

        // Governance
        $maintenanceMode    = (bool) $this->request->getPost('maintenanceMode');
        $maintenanceMessage = trim($this->request->getPost('maintenanceMessage') ?? '');

        $oldMaintenance = setting('App.maintenanceMode') ?? false;

        // Persist to MySQL settings table
        setting()->set('App.siteName', $siteName);
        setting()->set('App.supportEmail', $supportEmail);
        setting()->set('App.timezone', $timezone);
        setting()->set('App.dateFormat', $dateFormat);

        setting()->set('Auth.allowRegistration', $allowRegistration);
        setting()->set('Auth.requireEmailVerification', $requireEmailVerification);
        setting()->set('Auth.sessionLifetime', $sessionLifetime);
        setting()->set('Auth.maxLoginAttempts', $maxLoginAttempts);

        setting()->set('App.maxUploadSizeMb', $maxUploadSizeMb);
        setting()->set('App.maxBatchUploadCount', $maxBatchUploadCount);
        setting()->set('App.allowedExtensions', $allowedExtensions);

        setting()->set('App.storageLimit', $storageLimit);
        setting()->set('App.trashRetentionDays', $trashRetentionDays);

        setting()->set('App.maintenanceMode', $maintenanceMode);
        setting()->set('App.maintenanceMessage', $maintenanceMessage);

        helper('audit');
        log_security_action('SYSTEM_SETTINGS_CHANGE', 'SUCCESS', [
            'siteName'          => $siteName,
            'storageLimit'      => $storageLimit,
            'allowRegistration' => $allowRegistration,
            'maintenanceMode'   => $maintenanceMode,
            'maxUploadSizeMb'   => $maxUploadSizeMb,
        ]);

        if ($oldMaintenance !== $maintenanceMode) {
            log_security_action('MAINTENANCE_MODE_SET', 'SUCCESS', [
                'maintenance_mode' => $maintenanceMode ? 'ENABLED' : 'DISABLED'
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'System settings updated and persisted successfully.'
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
                'includeSensitive'  => setting('ML.includeSensitive') ?? true,
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
        $clipModelName     = trim($this->request->getPost('clipModelName') ?? 'openai/clip-vit-base-patch32');
        $objectDetThresh   = $this->request->getPost('objectDetThresh');

        // Dimension safety check: prevent 768-d models from colliding with Qdrant's 512-d collection
        $incompatibleModels = ['clip-vit-large', 'vit-l', 'siglip-so400m'];
        foreach ($incompatibleModels as $incomp) {
            if (stripos($clipModelName, $incomp) !== false) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "Model '{$clipModelName}' generates 768-dimensional vectors. Your Qdrant collection is locked to 512 dimensions. Please choose a 512-d model (such as openai/clip-vit-base-patch16) to avoid vector dimension collision."
                ])->setStatusCode(400);
            }
        }

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

            $url = $this->getMlUrl() . '/api/v1/models/reload?' . http_build_query([
                'model_pack'           => $faceModelPack,
                'face_det_thresh'      => $faceDetThresh,
                'clip_model_name'      => $clipModelName,
                'object_det_threshold' => $objectDetThresh,
                'include_sensitive'    => $includeSensitive ? 'true' : 'false',
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

    public function autotuneMl()
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 60,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $url = $this->getMlUrl() . '/api/v1/faces/autotune';
            $response = $client->post($url);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $this->response->setJSON($data);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'ML autotune failed: HTTP ' . $response->getStatusCode()
            ])->setStatusCode(502);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot connect to ML service: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function simulateClustering()
    {
        $minClusterSize = (int) ($this->request->getPost('minClusterSize') ?? 2);
        $minSamples     = (int) ($this->request->getPost('minSamples') ?? 1);

        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 60,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $url = $this->getMlUrl() . '/api/v1/faces/simulate?' . http_build_query([
                'min_cluster_size' => $minClusterSize,
                'min_samples'      => $minSamples,
            ]);
            $response = $client->post($url);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $this->response->setJSON($data);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'ML simulation failed: HTTP ' . $response->getStatusCode()
            ])->setStatusCode(502);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot connect to ML service: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function modelsInventory()
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 30,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $url = $this->getMlUrl() . '/api/v1/models/inventory';
            $response = $client->get($url);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $this->response->setJSON($data);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Failed to fetch model inventory: HTTP ' . $response->getStatusCode()
            ])->setStatusCode(502);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot connect to ML service: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function downloadModel()
    {
        $group = $this->request->getPost('group') ?? 'all';

        try {
            $client = service('curlrequest', [
                'connect_timeout' => 10,
                'timeout'         => 300,
                'headers'         => [
                    'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                ]
            ]);

            $url = $this->getMlUrl() . '/api/v1/models/download?group=' . urlencode($group);
            $response = $client->post($url);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                return $this->response->setJSON($data);
            }

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Model download failed: HTTP ' . $response->getStatusCode()
            ])->setStatusCode(502);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Cannot connect to ML service: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
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

        $gcpService = new \App\Services\GcpStorageService();

        // Recent Local Backups
        $recentBackups = [];
        $backupDir = WRITEPATH . 'backups/database/';
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '*.sql.gz') as $bf) {
                $recentBackups[] = [
                    'filename'   => basename($bf),
                    'size'       => $this->formatBytes(filesize($bf)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($bf)),
                ];
            }
            usort($recentBackups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        }

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
            'gcp' => [
                'bucket'             => setting('Storage.gcpBucket') ?? env('GCP_BUCKET') ?? '',
                'authType'           => setting('Storage.gcpAuthType') ?? env('GCP_AUTH_TYPE') ?? 'json',
                'serviceAccountJson' => setting('Storage.gcpServiceAccountJson') ?? env('GCP_SERVICE_ACCOUNT_JSON') ?? '',
                'accessKey'          => setting('Storage.gcpAccessKey') ?? env('GCP_ACCESS_KEY') ?? '',
                'secretKey'          => setting('Storage.gcpSecretKey') ?? env('GCP_SECRET_KEY') ?? '',
                'retentionDays'      => (int) (setting('Storage.backupRetentionDays') ?? 30),
                'isConfigured'       => $gcpService->isConfigured(),
            ],
            'recentBackups'  => $recentBackups,
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

    public function saveGcpSettings()
    {
        $authType      = $this->request->getPost('authType') ?: 'json';
        $bucket        = trim($this->request->getPost('bucket') ?? '');
        $jsonCreds     = trim($this->request->getPost('serviceAccountJson') ?? '');
        $accessKey     = trim($this->request->getPost('accessKey') ?? '');
        $secretKey     = trim($this->request->getPost('secretKey') ?? '');
        $retentionDays = max(1, (int) ($this->request->getPost('retentionDays') ?? 30));

        setting()->set('Storage.gcpBucket', $bucket);
        setting()->set('Storage.gcpAuthType', $authType);
        setting()->set('Storage.gcpServiceAccountJson', $jsonCreds);
        setting()->set('Storage.gcpAccessKey', $accessKey);
        setting()->set('Storage.gcpSecretKey', $secretKey);
        setting()->set('Storage.backupRetentionDays', $retentionDays);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Google Cloud Storage settings saved successfully.'
        ]);
    }

    public function testGcpConnection()
    {
        $config = [
            'bucket'             => trim($this->request->getPost('bucket') ?? setting('Storage.gcpBucket') ?? ''),
            'authType'           => $this->request->getPost('authType') ?? setting('Storage.gcpAuthType') ?? 'json',
            'serviceAccountJson' => trim($this->request->getPost('serviceAccountJson') ?? setting('Storage.gcpServiceAccountJson') ?? ''),
            'accessKey'          => trim($this->request->getPost('accessKey') ?? setting('Storage.gcpAccessKey') ?? ''),
            'secretKey'          => trim($this->request->getPost('secretKey') ?? setting('Storage.gcpSecretKey') ?? ''),
            'retentionDays'      => (int) ($this->request->getPost('retentionDays') ?? 30)
        ];

        $gcp = new \App\Services\GcpStorageService($config);
        $result = $gcp->testConnection();

        return $this->response->setJSON($result);
    }

    public function triggerCloudBackup()
    {
        try {
            ob_start();
            command('db:backup');
            $output = ob_get_clean();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Database backup and cloud sync completed successfully.',
                'output'  => $output ?: 'Backup finished.'
            ]);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) ob_end_clean();
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Backup failed: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
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
                $db->table('tbl_album_photos')->where('photo_id', $id)->delete();
                $db->table('tbl_photo_shares')->where('photo_id', $id)->delete();
                $db->table('tbl_shared_links')->where('photo_id', $id)->delete();

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
            ->join('(SELECT MAX(id) AS max_id FROM tbl_auth_tokens WHERE device_uuid IS NOT NULL AND device_uuid != \'\' GROUP BY device_uuid) latest', 't.id = latest.max_id')
            ->join('users u', 'u.id = t.user_id', 'left')
            ->orderBy('t.used_at', 'DESC')
            ->orderBy('t.id', 'DESC')
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

            $response = $client->delete($this->getMlUrl() . '/api/v1/faces/reset');

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
                $this->getMlUrl() . '/api/v1/faces/cluster?min_cluster_size=' . $minCluster . '&min_samples=' . $minSamples
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
                $client->post($this->getMlUrl() . '/api/v1/faces/encode', [
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
        $albumIds = $db->table('tbl_albums')->select('id')->where('user_id', $userId)->get()->getResultArray();
        $aIds     = array_column($albumIds, 'id');
        if (! empty($aIds)) {
            $db->table('tbl_album_photos')->whereIn('album_id', $aIds)->delete();
        }
        $db->table('tbl_albums')->where('user_id', $userId)->delete();

        // Photos
        $photoModel->where('user_id', $userId)->purgeDeleted();
        $db->table('tbl_photos')->where('user_id', $userId)->delete();

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
                $client->post($this->getMlUrl() . '/api/v1/faces/delete-by-photo-ids', [
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
                $client->post($this->getMlUrl() . '/api/v1/faces/delete-by-photo-ids', [
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
            ->limit(100)
            ->get()
            ->getResultArray();

        $now = time();
        $tasksConfig = [
            [
                'key'         => 'mlSweep',
                'command'     => 'ml:sweep',
                'name'        => 'ML Pipeline Sweep & Auto-Recovery',
                'description' => 'Scans library for newly uploaded or unprocessed media frames and dispatches them for face recognition, object categorization, and CLIP semantic vectorization.',
                'default'     => '*/5 * * * *',
                'icon'        => 'bi-cpu',
                'color'       => 'warning',
                'category'    => 'Machine Learning',
                'presets'     => [
                    '*/1 * * * *'  => 'Every minute',
                    '*/5 * * * *'  => 'Every 5 minutes (Default)',
                    '*/15 * * * *' => 'Every 15 minutes',
                    '0 * * * *'    => 'Hourly',
                ],
            ],
            [
                'key'         => 'mlCluster',
                'command'     => 'ml:cluster',
                'name'        => 'ML Face Clustering (HDBSCAN)',
                'description' => 'Clusters newly extracted face embeddings with HDBSCAN to associate faces with existing people and discover new unknown people groups.',
                'default'     => '0 * * * *',
                'icon'        => 'bi-people',
                'color'       => 'primary',
                'category'    => 'Machine Learning',
                'presets'     => [
                    '*/10 * * * *' => 'Every 10 minutes',
                    '*/30 * * * *' => 'Every 30 minutes',
                    '0 * * * *'    => 'Hourly at :00 (Default)',
                    '0 0 * * *'    => 'Daily at midnight',
                ],
            ],
            [
                'key'         => 'trashPurge',
                'command'     => 'trash:purge',
                'name'        => 'Trash Auto-Purge Lifecycle',
                'description' => 'Enforces data retention policy by permanently wiping soft-deleted items and cleaning file attachments, shares, and thumbnails after 60 days.',
                'default'     => '0 2 * * *',
                'icon'        => 'bi-trash',
                'color'       => 'danger',
                'category'    => 'Storage & Maintenance',
                'presets'     => [
                    '0 * * * *' => 'Hourly',
                    '0 2 * * *' => 'Daily at 2:00 AM UTC (Default)',
                    '0 2 * * 0' => 'Weekly on Sundays at 2:00 AM',
                ],
            ],
            [
                'key'         => 'cleanTemp',
                'command'     => 'storage:clean-temp',
                'name'        => 'Stale Temp Uploads & Artifacts',
                'description' => 'Prunes abandoned chunk upload directories, temporary export ZIPs, and incomplete upload chunks older than 24 hours.',
                'default'     => '30 1 * * *',
                'icon'        => 'bi-hdd-network',
                'color'       => 'info',
                'category'    => 'Storage & Maintenance',
                'presets'     => [
                    '0 * * * *'  => 'Hourly',
                    '30 1 * * *' => 'Daily at 1:30 AM UTC (Default)',
                    '0 0 1 * *'  => 'Monthly on the 1st',
                ],
            ],
            [
                'key'         => 'dbBackup',
                'command'     => 'db:backup',
                'name'        => 'Database Backup & Cloud Sync',
                'description' => 'Creates a compressed MySQL database dump, streams it to Google Cloud Storage, and purges archives older than retention threshold.',
                'default'     => '0 3 * * *',
                'icon'        => 'bi-cloud-arrow-up',
                'color'       => 'success',
                'category'    => 'Backup & Disaster Recovery',
                'presets'     => [
                    '0 3 * * *'    => 'Daily at 3:00 AM UTC (Default)',
                    '0 0 * * *'    => 'Daily at midnight',
                    '0 */6 * * *'  => 'Every 6 hours',
                ],
            ],
            [
                'key'         => 'cloudSync',
                'command'     => 'cloud:sync',
                'name'        => 'Offsite Media Mirroring (GCP)',
                'description' => 'Synchronizes newly uploaded photos and video frames from the last 24 hours to the remote Google Cloud Storage bucket.',
                'default'     => '30 3 * * *',
                'icon'        => 'bi-arrow-repeat',
                'color'       => 'primary',
                'category'    => 'Backup & Disaster Recovery',
                'presets'     => [
                    '30 3 * * *'   => 'Daily at 3:30 AM UTC (Default)',
                    '0 * * * *'    => 'Hourly',
                    '0 0 * * *'    => 'Daily at midnight',
                ],
            ],
            [
                'key'         => 'photosThumbs',
                'command'     => 'photos:generate-missing-thumbs',
                'name'        => 'Thumbnail Auto-Healer',
                'description' => 'Scans photo records for missing thumbnails and automatically regenerates high-speed WebP previews to prevent gallery rendering issues.',
                'default'     => '*/30 * * * *',
                'icon'        => 'bi-images',
                'color'       => 'info',
                'category'    => 'Media Maintenance',
                'presets'     => [
                    '*/15 * * * *' => 'Every 15 minutes',
                    '*/30 * * * *' => 'Every 30 minutes (Default)',
                    '0 * * * *'    => 'Hourly',
                ],
            ],
            [
                'key'         => 'authPrune',
                'command'     => 'auth:prune-tokens',
                'name'        => 'Session & Auth Token Pruner',
                'description' => 'Purges expired, revoked, and abandoned authentication tokens and inactive device sessions older than 90 days.',
                'default'     => '0 4 * * *',
                'icon'        => 'bi-key',
                'color'       => 'warning',
                'category'    => 'Security & Auth',
                'presets'     => [
                    '0 4 * * *'    => 'Daily at 4:00 AM UTC (Default)',
                    '0 0 * * *'    => 'Daily at midnight',
                    '0 0 * * 0'    => 'Weekly on Sunday',
                ],
            ],
            [
                'key'         => 'qdrantSync',
                'command'     => 'qdrant:sync-vectors',
                'name'        => 'Vector Database Consistency Sync',
                'description' => 'Audits relational MySQL face encodings against Qdrant vector points, purging orphaned vectors from deleted photos.',
                'default'     => '30 3 * * 0',
                'icon'        => 'bi-diagram-3',
                'color'       => 'secondary',
                'category'    => 'Machine Learning',
                'presets'     => [
                    '30 3 * * 0'   => 'Weekly on Sunday at 3:30 AM UTC (Default)',
                    '0 0 * * 0'    => 'Weekly on Sunday at midnight',
                    '0 0 1 * *'    => 'Monthly on the 1st',
                ],
            ],
            [
                'key'         => 'logsPrune',
                'command'     => 'logs:prune',
                'name'        => 'System Log Rotation & Cleanup',
                'description' => 'Rotates and prunes background execution logs, email transaction logs, and local log files older than 30 days.',
                'default'     => '30 4 * * 0',
                'icon'        => 'bi-journal-x',
                'color'       => 'dark',
                'category'    => 'Storage & Maintenance',
                'presets'     => [
                    '30 4 * * 0'   => 'Weekly on Sunday at 4:30 AM UTC (Default)',
                    '0 0 * * 0'    => 'Weekly on Sunday at midnight',
                    '0 0 1 * *'    => 'Monthly on the 1st',
                ],
            ],
        ];

        // Process each task with live stats, next run time, and last execution log
        $processedTasks = [];
        $settings = [];

        foreach ($tasksConfig as $t) {
            $expr = setting("Cron.{$t['key']}") ?: $t['default'];
            $settings[$t['key']] = $expr;

            $nextRunTs = $this->getNextCronRun($expr, $now);
            $humanSched = $this->describeCron($expr);

            // Fetch last execution log for this specific command
            $lastLog = $db->table('sys_cron_logs')
                ->where('job_name', $t['command'])
                ->orderBy('run_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $lastRunAt = $lastLog['run_at'] ?? null;
            $lastStatus = $lastLog['status'] ?? 'pending';
            $lastOutput = $lastLog['output'] ?? 'No execution recorded yet.';
            $lastDuration = isset($lastLog['duration_seconds']) ? number_format($lastLog['duration_seconds'], 2) . 's' : '-';

            $processedTasks[] = array_merge($t, [
                'expression'     => $expr,
                'human_schedule' => $humanSched,
                'next_run_at'    => $nextRunTs ? date('Y-m-d H:i:s', $nextRunTs) : 'Indeterminate',
                'next_run_diff'  => $nextRunTs ? $this->getRelativeTimeDiff($nextRunTs - $now) : '-',
                'last_run_at'    => $lastRunAt,
                'last_run_diff'  => $lastRunAt ? $this->getRelativeTimeAgo(strtotime($lastRunAt)) : 'Never ran',
                'last_status'    => $lastStatus,
                'last_output'    => $lastOutput,
                'last_duration'  => $lastDuration,
            ]);
        }

        // Check container cron daemon status
        $daemonActive = false;
        if (function_exists('shell_exec')) {
            $check = @shell_exec('pgrep cron 2>/dev/null');
            if (!empty($check)) {
                $daemonActive = true;
            }
        }
        if (!$daemonActive && !empty($cronLogs)) {
            $latestRun = strtotime($cronLogs[0]['run_at']);
            if (($now - $latestRun) < 900) {
                $daemonActive = true;
            }
        }

        $data = [
            'counts'         => $this->getSidebarCounts(),
            'tasks'          => $processedTasks,
            'cronLogs'       => $cronLogs,
            'settings'       => $settings,
            'daemonActive'   => $daemonActive,
            'serverTime'     => date('Y-m-d H:i:s'),
            'serverTimezone' => date_default_timezone_get(),
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/crons', $data);
    }

    public function saveCronSettings()
    {
        $keys = [
            'trashPurge', 'mlCluster', 'mlSweep', 'cleanTemp',
            'dbBackup', 'cloudSync', 'photosThumbs', 'authPrune',
            'qdrantSync', 'logsPrune'
        ];

        foreach ($keys as $k) {
            $val = $this->request->getPost($k);
            if ($val !== null && $val !== '') {
                setting()->set("Cron.{$k}", trim($val));
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'System tasks schedules saved successfully.'
        ]);
    }

    public function runCronJob()
    {
        $job = $this->request->getPost('job');
        $validJobs = [
            'ml:cluster', 'ml:sweep', 'trash:purge', 'storage:clean-temp',
            'db:backup', 'cloud:sync', 'photos:generate-missing-thumbs',
            'auth:prune-tokens', 'qdrant:sync-vectors', 'logs:prune',
            'photos:migrate-storage'
        ];

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

            $response = $client->get($this->getMlUrl() . '/api/v1/health');

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
                    $mlUrl = $this->getMlUrl();
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
                $client = service('curlrequest', [
                    'connect_timeout' => 2,
                    'timeout'         => 3,
                ]);

                $candidates = array_unique(array_filter([
                    env('QDRANT_URL'),
                    getenv('QDRANT_URL'),
                    $this->getQdrantUrl(),
                    'http://ml-qdrant.railway.internal:6333',
                    'http://qdrant.railway.internal:6333',
                    'http://ml-qdrant:6333',
                    'http://qdrant:6333',
                ]));

                $lastError = '';
                foreach ($candidates as $qdrantUrl) {
                    try {
                        $start = microtime(true);
                        $response = $client->get(rtrim($qdrantUrl, '/') . '/collections');
                        $elapsed = round((microtime(true) - $start) * 1000, 2);
                        if ($response->getStatusCode() === 200) {
                            $body = json_decode($response->getBody(), true);
                            $count = count($body['result']['collections'] ?? []);
                            return $this->response->setJSON([
                                'status'  => 'success',
                                'message' => "Qdrant Vector database is online ({$qdrantUrl}) in {$elapsed}ms. Collections: {$count}."
                            ]);
                        }
                    } catch (\Exception $e) {
                        $lastError = $e->getMessage();
                    }
                }

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Qdrant check failed across candidate endpoints: ' . $lastError
                ]);

            case 'clip':
                try {
                    $client = service('curlrequest', [
                        'connect_timeout' => 2,
                        'timeout'         => 3,
                        'headers'         => [
                            'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                        ]
                    ]);
                    $response = $client->get($this->getMlUrl() . '/api/v1/health');
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
                    $response = $client->get($this->getMlUrl() . '/api/v1/health');
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

    private function getNextCronRun(string $expression, int $fromTime = null): ?int
    {
        $from = $fromTime ?: time();
        $cron = explode(' ', trim($expression));
        if (count($cron) < 5) {
            return null;
        }

        for ($i = 1; $i <= 10080; $i++) {
            $candidate = $from + ($i * 60);
            $candidate -= ($candidate % 60);

            $m   = (int) date('i', $candidate);
            $h   = (int) date('H', $candidate);
            $dom = (int) date('j', $candidate);
            $mon = (int) date('n', $candidate);
            $dow = (int) date('w', $candidate);

            if ($this->matchCronField($cron[0], $m)
                && $this->matchCronField($cron[1], $h)
                && $this->matchCronField($cron[2], $dom)
                && $this->matchCronField($cron[3], $mon)
                && $this->matchCronField($cron[4], $dow)) {
                return $candidate;
            }
        }
        return null;
    }

    private function matchCronField(string $field, int $value): bool
    {
        if ($field === '*') return true;
        if (strpos($field, ',') !== false) {
            foreach (explode(',', $field) as $p) {
                if ($this->matchCronField($p, $value)) return true;
            }
            return false;
        }
        if (strpos($field, '/') !== false) {
            [$range, $step] = explode('/', $field);
            $step = (int) $step;
            if ($step <= 0) return false;
            if ($range === '*') return ($value % $step) === 0;
            [$start, $end] = explode('-', $range);
            return ($value >= (int)$start && $value <= (int)$end && (($value - (int)$start) % $step) === 0);
        }
        if (strpos($field, '-') !== false) {
            [$start, $end] = explode('-', $field);
            return ($value >= (int)$start && $value <= (int)$end);
        }
        return ((int)$field === $value);
    }

    private function describeCron(string $expr): string
    {
        $expr = trim($expr);
        $map = [
            '* * * * *'    => 'Every minute',
            '*/1 * * * *'  => 'Every minute',
            '*/5 * * * *'  => 'Every 5 minutes',
            '*/10 * * * *' => 'Every 10 minutes',
            '*/15 * * * *' => 'Every 15 minutes',
            '*/30 * * * *' => 'Every 30 minutes',
            '0 * * * *'    => 'Hourly (at :00)',
            '0 2 * * *'    => 'Daily at 02:00 AM UTC',
            '30 1 * * *'   => 'Daily at 01:30 AM UTC',
            '0 0 * * *'    => 'Daily at midnight (00:00 UTC)',
            '0 2 * * 0'    => 'Weekly on Sunday at 02:00 AM UTC',
            '0 0 1 * *'    => 'Monthly on the 1st at midnight UTC',
        ];
        return $map[$expr] ?? "Runs on schedule ({$expr})";
    }

    private function getRelativeTimeDiff(int $sec): string
    {
        if ($sec <= 0) return 'due now';
        if ($sec < 60) return 'in < 1 min';
        if ($sec < 3600) return 'in ' . round($sec / 60) . ' mins';
        if ($sec < 86400) return 'in ' . round($sec / 3600, 1) . ' hrs';
        return 'in ' . round($sec / 86400, 1) . ' days';
    }

    private function getRelativeTimeAgo(int $ts): string
    {
        $diff = time() - $ts;
        if ($diff < 5) return 'just now';
        if ($diff < 60) return $diff . 's ago';
        if ($diff < 3600) return round($diff / 60) . 'm ago';
        if ($diff < 86400) return round($diff / 3600) . 'h ago';
        return date('M j, H:i', $ts);
    }
}
