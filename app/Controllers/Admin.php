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

        setting()->set('App.storageLimit', (int) $storageLimit);
        setting()->set('Auth.allowRegistration', $allowRegistration);

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
            ],
            'settings' => [
                'faceModelPack'     => setting('ML.faceModelPack') ?? 'buffalo_l',
                'faceDetThresh'     => setting('ML.faceDetThresh') ?? 0.5,
                'includeSensitive'  => setting('ML.includeSensitive') ?? false,
                'hdbscanMinCluster' => setting('ML.hdbscanMinCluster') ?? 2,
            ],
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/ml', $data);
    }

    public function saveMlSettings()
    {
        $rules = [
            'faceModelPack'     => 'required|in_list[buffalo_l,buffalo_m,buffalo_s,buffalo_sc]',
            'faceDetThresh'     => 'required|numeric|greater_than_equal_to[0.1]|less_than_equal_to[1.0]',
            'hdbscanMinCluster' => 'required|integer|greater_than_equal_to[1]',
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

        setting()->set('ML.faceModelPack', $faceModelPack);
        setting()->set('ML.faceDetThresh', (float) $faceDetThresh);
        setting()->set('ML.includeSensitive', $includeSensitive);
        setting()->set('ML.hdbscanMinCluster', (int) $hdbscanMinCluster);

        // Tell FastAPI service to reload models dynamically
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 60,
            ]);

            $response = $client->post(self::DEFAULT_ML_URL . '/models/reload?model_pack=' . urlencode($faceModelPack));

            if ($response->getStatusCode() === 200) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'ML parameters saved and model pack (' . $faceModelPack . ') reloaded successfully.'
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
            ]);

            $response = $client->post(self::DEFAULT_ML_URL . '/api/v1/faces/cluster');

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

        return $this->response->setJSON(['status' => 'success', 'message' => 'User account and all data permanently deleted.']);
    }

    public function smtp()
    {
        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $emailLogs = $db->table('email_logs')
            ->orderBy('sent_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        $data = [
            'counts' => $this->getSidebarCounts(),
            'smtp' => [
                'fromEmail'   => setting('Email.fromEmail') ?? 'chegeos@chegecache.co.ke',
                'fromName'    => setting('Email.fromName') ?? 'Photos Team',
                'protocol'    => setting('Email.protocol') ?? 'smtp',
                'SMTPHost'    => setting('Email.SMTPHost') ?? 'mail.chegecache.co.ke',
                'SMTPUser'    => setting('Email.SMTPUser') ?? 'chegeos@chegecache.co.ke',
                'SMTPPass'    => setting('Email.SMTPPass') ?? 'wzj1Vvk]7p9l',
                'SMTPPort'    => setting('Email.SMTPPort') ?? 465,
                'SMTPCrypto'  => setting('Email.SMTPCrypto') ?? 'ssl',
            ],
            'emailLogs'      => $emailLogs,
            'storageUsed'    => $this->formatBytes($totalBytes),
            'storagePercent' => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('admin/smtp', $data);
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
        $email->setMessage("Hello,\n\nThis is a sample test email from the Chege Photos WebApp. If you are reading this, your SMTP configuration is verified and working correctly!\n\nTracking ID: " . $trackingId . "\n\nBest regards,\nPhotos Administration Console.");

        if ($email->send()) {
            $db->table('email_logs')->insert([
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
        $db->table('email_logs')->insert([
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

    public function crons()
    {
        $userId     = auth()->id();
        $photoModel = new PhotoModel();
        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $db = \Config\Database::connect();
        $cronLogs = $db->table('cron_logs')
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
        setting()->set('Cron.cleanTemp', $this->request->getPost('cleanTemp'));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'System tasks schedules saved successfully.'
        ]);
    }

    private function getMlHealth(): array
    {
        try {
            $client = service('curlrequest', [
                'connect_timeout' => 2,
                'timeout'         => 3,
            ]);

            $response = $client->get(self::DEFAULT_ML_URL . '/health');

            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody(), true);
                return [
                    'online'  => true,
                    'status'  => $body['status'] ?? 'degraded',
                    'db'      => $body['db_connected'] ?? false,
                    'qdrant'  => $body['qdrant_connected'] ?? false,
                    'models'  => $body['models_loaded'] ?? false,
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
        ];
    }
}
