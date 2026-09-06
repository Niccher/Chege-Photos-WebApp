<?php

namespace App\Controllers;

use App\Models\PhotoModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Vault extends BaseController
{
    private function isVaultUnlocked(): bool
    {
        $unlockedUntil = (int) (session()->get('vault_unlocked_until') ?? 0);
        return $unlockedUntil > time();
    }

    public function index()
    {
        if (! auth()->loggedIn()) {
            return redirect()->to(url_to('login'));
        }

        $user = auth()->user();
        $db   = \Config\Database::connect();
        $usersTable = $db->tableExists('users') ? 'users' : 'tbl_users';

        // Fetch fresh user record for PIN and lockout state
        $userRow = $db->table($usersTable)->where('id', $user->id)->get()->getRowArray();
        $hasPin  = ! empty($userRow['vault_pin_hash']);

        // Check lockout
        $isLockedOut = false;
        $lockoutRemaining = 0;
        if (! empty($userRow['vault_locked_until'])) {
            $lockoutTs = strtotime($userRow['vault_locked_until']);
            if ($lockoutTs > time()) {
                $isLockedOut = true;
                $lockoutRemaining = $lockoutTs - time();
            }
        }

        $isUnlocked = $this->isVaultUnlocked() && ! $isLockedOut;
        $unlockedRemaining = $isUnlocked ? max(0, ((int) session()->get('vault_unlocked_until')) - time()) : 0;

        $photoModel = new PhotoModel();
        $photos     = [];
        $stats      = [
            'total'   => 0,
            'nsfw'    => 0,
            'manual'  => 0,
            'storage' => '0 B',
        ];

        $currentTab = $this->request->getGet('tab') ?? 'all';
        if (! in_array($currentTab, ['all', 'nsfw', 'manual'], true)) {
            $currentTab = 'all';
        }

        if ($isUnlocked) {
            // Compute counts
            $baseQuery = $photoModel->where('user_id', $user->id)->where('is_vault', 1);
            $totalCount = (clone $baseQuery)->countAllResults();
            $nsfwCount  = (clone $baseQuery)->where('is_nsfw', 1)->countAllResults();
            $manualCount = max(0, $totalCount - $nsfwCount);
            $totalBytes = (clone $baseQuery)->selectSum('size')->first()['size'] ?? 0;

            $stats = [
                'total'   => $totalCount,
                'nsfw'    => $nsfwCount,
                'manual'  => $manualCount,
                'storage' => $this->formatBytes($totalBytes),
            ];

            // Filtered photo list
            $listQuery = $photoModel->where('user_id', $user->id)->where('is_vault', 1);
            if ($currentTab === 'nsfw') {
                $listQuery->where('is_nsfw', 1);
            } elseif ($currentTab === 'manual') {
                $listQuery->where('is_nsfw', 0);
            }

            $photos = $listQuery->orderBy('vault_locked_at', 'DESC')
                                ->orderBy('taken_at', 'DESC')
                                ->findAll();

            // Decorate URLs to use protected vault streaming proxy
            foreach ($photos as &$p) {
                $p['thumb_url'] = base_url("vault/media/{$p['id']}?type=thumb");
                $p['full_url']  = base_url("vault/media/{$p['id']}?type=full");
                if (!empty($p['nsfw_details'])) {
                    $p['nsfw_info'] = is_string($p['nsfw_details']) ? json_decode($p['nsfw_details'], true) : $p['nsfw_details'];
                } else {
                    $p['nsfw_info'] = null;
                }
            }
            unset($p);

            // Fetch persons for "Hide a Face to Vault" feature
            $personModel = new \App\Models\PersonModel();
            $availablePersons = $personModel->getPersonsWithFaceCountForUser($user->id);
            $vaultedPersons   = $personModel->getPersonsWithVaultCountForUser($user->id);

            $db = \Config\Database::connect();
            if (!empty($availablePersons)) {
                $pIds = array_column($availablePersons, 'id');
                $faceRows = $db->table('tbl_face_encodings fe')
                    ->select('fe.person_id, p.path, p.thumbnail_path')
                    ->join('tbl_photos p', 'p.id = fe.photo_id')
                    ->whereIn('fe.person_id', $pIds)
                    ->where('p.is_vault', 0)
                    ->orderBy('fe.id', 'ASC')
                    ->get()
                    ->getResultArray();
                $faceMap = [];
                foreach ($faceRows as $fr) {
                    if (!isset($faceMap[$fr['person_id']])) {
                        $faceMap[$fr['person_id']] = !empty($fr['thumbnail_path']) ? base_url($fr['thumbnail_path']) : base_url($fr['path']);
                    }
                }
                foreach ($availablePersons as &$ap) {
                    $ap['avatar_url'] = $faceMap[$ap['id']] ?? null;
                }
                unset($ap);
            }

            if (!empty($vaultedPersons)) {
                $vpIds = array_column($vaultedPersons, 'id');
                $vFaceRows = $db->table('tbl_face_encodings fe')
                    ->select('fe.person_id, p.id AS photo_id')
                    ->join('tbl_photos p', 'p.id = fe.photo_id')
                    ->whereIn('fe.person_id', $vpIds)
                    ->where('p.is_vault', 1)
                    ->orderBy('fe.id', 'ASC')
                    ->get()
                    ->getResultArray();
                $vFaceMap = [];
                foreach ($vFaceRows as $vfr) {
                    if (!isset($vFaceMap[$vfr['person_id']])) {
                        $vFaceMap[$vfr['person_id']] = base_url("vault/media/{$vfr['photo_id']}?type=thumb");
                    }
                }
                foreach ($vaultedPersons as &$vp) {
                    $vp['avatar_url'] = $vFaceMap[$vp['id']] ?? null;
                }
                unset($vp);
            }
        }

        $data = [
            'hasPin'             => $hasPin,
            'isUnlocked'         => $isUnlocked,
            'isLockedOut'        => $isLockedOut,
            'lockoutRemaining'   => $lockoutRemaining,
            'unlockedRemaining'  => $unlockedRemaining,
            'photos'             => $photos,
            'stats'              => $stats,
            'currentTab'         => $currentTab,
            'availablePersons'   => $availablePersons ?? [],
            'vaultedPersons'     => $vaultedPersons ?? [],
            'counts'             => $this->getSidebarCounts(),
        ];

        return view('photos/vault', $data);
    }

    public function setupPin()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $pin = trim($this->request->getPost('pin') ?? '');
        if (! preg_match('/^[0-9]{4,8}$/', $pin)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'PIN must be between 4 and 8 numeric digits.',
            ])->setStatusCode(400);
        }

        $userId = auth()->id();
        $db     = \Config\Database::connect();
        $table  = $db->tableExists('users') ? 'users' : 'tbl_users';

        $db->table($table)->where('id', $userId)->update([
            'vault_pin_hash'        => password_hash($pin, PASSWORD_DEFAULT),
            'vault_failed_attempts' => 0,
            'vault_locked_until'    => null,
        ]);

        // Auto-unlock vault upon creation for 5 minutes
        session()->set('vault_unlocked_until', time() + 300);

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Private Vault PIN configured successfully!',
            'redirect' => base_url('vault'),
        ]);
    }

    public function unlock()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $userId = auth()->id();
        $user   = auth()->user();
        $db     = \Config\Database::connect();
        $table  = $db->tableExists('users') ? 'users' : 'tbl_users';
        $userRow = $db->table($table)->where('id', $userId)->get()->getRowArray();

        // 1. Check Lockout
        if (! empty($userRow['vault_locked_until'])) {
            $lockoutTs = strtotime($userRow['vault_locked_until']);
            if ($lockoutTs > time()) {
                $seconds = $lockoutTs - time();
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => "Vault is temporarily locked due to too many failed attempts. Try again in {$seconds} seconds.",
                ])->setStatusCode(429);
            }
        }

        $pin      = trim($this->request->getPost('pin') ?? '');
        $password = trim($this->request->getPost('password') ?? '');
        $isValid  = false;

        // 2. Validate PIN
        if ($pin !== '' && ! empty($userRow['vault_pin_hash'])) {
            if (password_verify($pin, $userRow['vault_pin_hash'])) {
                $isValid = true;
            }
        }

        // 3. Fallback: Validate master account password
        if (! $isValid && $password !== '') {
            $auth = service('auth');
            $authCheck = $auth->check([
                'email'    => $user->email,
                'password' => $password,
            ]);
            if ($authCheck->isOK()) {
                $isValid = true;
            }
        }

        if ($isValid) {
            // Reset failure counter
            $db->table($table)->where('id', $userId)->update([
                'vault_failed_attempts' => 0,
                'vault_locked_until'    => null,
            ]);

            // Set 5-minute unlock timer
            session()->set('vault_unlocked_until', time() + 300);

            return $this->response->setJSON([
                'status'   => 'success',
                'message'  => 'Vault unlocked successfully.',
                'redirect' => base_url('vault'),
            ]);
        }

        // Failed attempt: increment counter
        $attempts = ((int) ($userRow['vault_failed_attempts'] ?? 0)) + 1;
        $updates = ['vault_failed_attempts' => $attempts];

        if ($attempts >= 5) {
            $updates['vault_locked_until'] = date('Y-m-d H:i:s', time() + 900); // 15-minute lock
            $msg = 'Too many failed attempts. Vault locked for 15 minutes.';
        } else {
            $remaining = 5 - $attempts;
            $msg = "Incorrect PIN or password. {$remaining} attempt(s) remaining before temporary lockout.";
        }

        $db->table($table)->where('id', $userId)->update($updates);

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => $msg,
        ])->setStatusCode(401);
    }

    public function lock()
    {
        session()->remove('vault_unlocked_until');

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'   => 'success',
                'message'  => 'Vault locked.',
                'redirect' => base_url('vault'),
            ]);
        }

        return redirect()->to(base_url('vault'));
    }

    public function move()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $userId = auth()->id();
        $rawIds = $this->request->getPost('photo_ids');
        $photoIds = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        $photoIds = array_filter(array_map('intval', $photoIds));

        if (empty($photoIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No photos specified'])->setStatusCode(400);
        }

        $photoModel = new PhotoModel();
        $updated = $photoModel->where('user_id', $userId)
                              ->whereIn('id', $photoIds)
                              ->set([
                                  'is_vault'        => 1,
                                  'vault_locked_at' => date('Y-m-d H:i:s'),
                              ])
                              ->update();

        // Privacy Shield: revoke any existing shares and album associations
        $db = \Config\Database::connect();
        if ($db->tableExists('tbl_photo_shares')) {
            $db->table('tbl_photo_shares')->whereIn('photo_id', $photoIds)->delete();
        }
        if ($db->tableExists('tbl_album_photos')) {
            $db->table('tbl_album_photos')->whereIn('photo_id', $photoIds)->delete();
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Photo(s) safely moved into the Private Locked Vault.',
            'count'   => count($photoIds),
        ]);
    }

    public function restore()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked to restore photos'])->setStatusCode(403);
        }

        $userId = auth()->id();
        $rawIds = $this->request->getPost('photo_ids');
        $photoIds = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        $photoIds = array_filter(array_map('intval', $photoIds));

        if (empty($photoIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No photos specified'])->setStatusCode(400);
        }

        $photoModel = new PhotoModel();
        $photoModel->where('user_id', $userId)
                   ->whereIn('id', $photoIds)
                   ->set([
                       'is_vault'        => 0,
                       'is_nsfw'         => 0,
                       'vault_locked_at' => null,
                   ])
                   ->update();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Photo(s) restored back to the main library.',
            'count'   => count($photoIds),
        ]);
    }

    public function delete()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked to delete photos'])->setStatusCode(403);
        }

        $userId = auth()->id();
        $rawIds = $this->request->getPost('photo_ids');
        $photoIds = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
        $photoIds = array_filter(array_map('intval', $photoIds));

        if (empty($photoIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No photos selected'])->setStatusCode(400);
        }

        $photoModel = new PhotoModel();
        $photos = $photoModel->where('user_id', $userId)->whereIn('id', $photoIds)->findAll();

        foreach ($photos as $p) {
            // Remove physical files
            if (! empty($p['path']) && file_exists(FCPATH . $p['path'])) {
                @unlink(FCPATH . $p['path']);
            }
            if (! empty($p['thumbnail_path']) && file_exists(FCPATH . $p['thumbnail_path'])) {
                @unlink(FCPATH . $p['thumbnail_path']);
            }
            $photoModel->delete($p['id'], true); // Hard permanent delete
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Selected photo(s) permanently deleted.',
        ]);
    }

    public function media(int $photoId)
    {
        if (! auth()->loggedIn()) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setStatusCode(403)->setBody('Vault is locked. Please unlock to view media.');
        }

        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $photo = $photoModel->where('id', $photoId)->where('user_id', $userId)->first();

        if (! $photo) {
            return $this->response->setStatusCode(404)->setBody('Media not found.');
        }

        $type = $this->request->getGet('type') ?? 'full';
        $relPath = ($type === 'thumb' && ! empty($photo['thumbnail_path']))
            ? $photo['thumbnail_path']
            : $photo['path'];

        $fullPath = FCPATH . ltrim($relPath, '/');
        if (! file_exists($fullPath)) {
            // Fallback to original path if thumbnail missing
            $fullPath = FCPATH . ltrim($photo['path'], '/');
        }

        if (! file_exists($fullPath)) {
            return $this->response->setStatusCode(404)->setBody('File not found on storage.');
        }

        $mime = $photo['mime_type'] ?: mime_content_type($fullPath) ?: 'image/jpeg';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) filesize($fullPath))
            ->setHeader('Cache-Control', 'private, no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody(file_get_contents($fullPath));
    }

    public function hidePerson()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked to hide faces'])->setStatusCode(403);
        }

        $userId = auth()->id();
        $personId = (int) $this->request->getPost('person_id');
        if ($personId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid person selected'])->setStatusCode(400);
        }

        $personModel = new \App\Models\PersonModel();
        $person = $personModel->find($personId);
        $personName = !empty($person['name']) ? $person['name'] : "Person #{$personId}";

        // Get all photos of this user containing this person's face that are NOT currently in vault
        $photoIds = $personModel->getPersonPhotoIds($personId, $userId, 0);

        if (empty($photoIds)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "No unvaulted photos found for {$personName}."
            ])->setStatusCode(404);
        }

        $photoModel = new PhotoModel();
        $photoModel->where('user_id', $userId)
                   ->whereIn('id', $photoIds)
                   ->set([
                       'is_vault'        => 1,
                       'vault_locked_at' => date('Y-m-d H:i:s'),
                   ])
                   ->update();

        // Privacy Shield: revoke any existing shares and album associations
        $db = \Config\Database::connect();
        if ($db->tableExists('tbl_photo_shares')) {
            $db->table('tbl_photo_shares')->whereIn('photo_id', $photoIds)->delete();
        }
        if ($db->tableExists('tbl_album_photos')) {
            $db->table('tbl_album_photos')->whereIn('photo_id', $photoIds)->delete();
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "All " . count($photoIds) . " photo(s) of {$personName} have been safely moved to the Private Locked Vault.",
            'count'   => count($photoIds),
        ]);
    }

    public function restorePerson()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked to restore faces'])->setStatusCode(403);
        }

        $userId = auth()->id();
        $personId = (int) $this->request->getPost('person_id');
        if ($personId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid person selected'])->setStatusCode(400);
        }

        $personModel = new \App\Models\PersonModel();
        $person = $personModel->find($personId);
        $personName = !empty($person['name']) ? $person['name'] : "Person #{$personId}";

        // Get all vaulted photos of this user containing this person's face
        $photoIds = $personModel->getPersonPhotoIds($personId, $userId, 1);

        if (empty($photoIds)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "No vaulted photos found for {$personName}."
            ])->setStatusCode(404);
        }

        $photoModel = new PhotoModel();
        $photoModel->where('user_id', $userId)
                   ->whereIn('id', $photoIds)
                   ->set([
                       'is_vault'        => 0,
                       'vault_locked_at' => null,
                   ])
                   ->update();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "All " . count($photoIds) . " photo(s) of {$personName} have been restored from your Private Locked Vault.",
            'count'   => count($photoIds),
        ]);
    }

    public function persons()
    {
        if (! auth()->loggedIn() || ! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $userId = auth()->id();
        $personModel = new \App\Models\PersonModel();
        $available = $personModel->getPersonsWithFaceCountForUser($userId);
        $vaulted   = $personModel->getPersonsWithVaultCountForUser($userId);

        $db = \Config\Database::connect();
        if (!empty($available)) {
            $pIds = array_column($available, 'id');
            $faceRows = $db->table('tbl_face_encodings fe')
                ->select('fe.person_id, p.path, p.thumbnail_path')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->whereIn('fe.person_id', $pIds)
                ->where('p.is_vault', 0)
                ->orderBy('fe.id', 'ASC')
                ->get()
                ->getResultArray();
            $faceMap = [];
            foreach ($faceRows as $fr) {
                if (!isset($faceMap[$fr['person_id']])) {
                    $faceMap[$fr['person_id']] = !empty($fr['thumbnail_path']) ? base_url($fr['thumbnail_path']) : base_url($fr['path']);
                }
            }
            foreach ($available as &$ap) {
                $ap['avatar_url'] = $faceMap[$ap['id']] ?? null;
            }
            unset($ap);
        }

        if (!empty($vaulted)) {
            $vpIds = array_column($vaulted, 'id');
            $vFaceRows = $db->table('tbl_face_encodings fe')
                ->select('fe.person_id, p.id AS photo_id')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->whereIn('fe.person_id', $vpIds)
                ->where('p.is_vault', 1)
                ->orderBy('fe.id', 'ASC')
                ->get()
                ->getResultArray();
            $vFaceMap = [];
            foreach ($vFaceRows as $vfr) {
                if (!isset($vFaceMap[$vfr['person_id']])) {
                    $vFaceMap[$vfr['person_id']] = base_url("vault/media/{$vfr['photo_id']}?type=thumb");
                }
            }
            foreach ($vaulted as &$vp) {
                $vp['avatar_url'] = $vFaceMap[$vp['id']] ?? null;
            }
            unset($vp);
        }

        return $this->response->setJSON([
            'status'    => 'success',
            'available' => $available,
            'vaulted'   => $vaulted,
        ]);
    }

    public function unflag()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked'])->setStatusCode(403);
        }

        $userId  = auth()->id();
        $photoId = (int) $this->request->getPost('photo_id');
        $restore = $this->request->getPost('restore_to_library') !== '0'; // default true

        if ($photoId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid photo selected'])->setStatusCode(400);
        }

        $photoModel = new PhotoModel();
        $photo = $photoModel->where('user_id', $userId)->find($photoId);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found'])->setStatusCode(404);
        }

        $updateData = [
            'is_nsfw'      => 0,
            'nsfw_score'   => 0.0,
            'nsfw_details' => json_encode([
                'override'     => 'user_unflagged_safe',
                'unflagged_at' => date('Y-m-d H:i:s'),
                'prior_score'  => (float) ($photo['nsfw_score'] ?? 0.0),
            ]),
        ];

        if ($restore) {
            $updateData['is_vault'] = 0;
            $updateData['vault_locked_at'] = null;
        }

        $photoModel->update($photoId, $updateData);

        $msg = $restore 
            ? 'Photo unflagged as safe and restored to your main library.' 
            : 'Photo removed from sensitive classification and kept in manual vault.';

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => $msg,
            'photo_id' => $photoId,
            'restored' => $restore,
        ]);
    }

    public function bulkUnflag()
    {
        if (! auth()->loggedIn()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        if (! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Vault must be unlocked'])->setStatusCode(403);
        }

        $userId   = auth()->id();
        $photoIds = (array) $this->request->getPost('photo_ids');
        $restore  = $this->request->getPost('restore_to_library') !== '0';

        $photoIds = array_filter(array_map('intval', $photoIds));
        if (empty($photoIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No photos selected'])->setStatusCode(400);
        }

        $photoModel = new PhotoModel();
        $updateData = [
            'is_nsfw'      => 0,
            'nsfw_score'   => 0.0,
            'nsfw_details' => json_encode([
                'override'     => 'user_bulk_unflagged_safe',
                'unflagged_at' => date('Y-m-d H:i:s'),
            ]),
        ];

        if ($restore) {
            $updateData['is_vault'] = 0;
            $updateData['vault_locked_at'] = null;
        }

        $photoModel->where('user_id', $userId)
                   ->whereIn('id', $photoIds)
                   ->set($updateData)
                   ->update();

        $count = count($photoIds);
        $msg = $restore 
            ? "{$count} photo(s) unflagged as safe and restored to your main library." 
            : "{$count} photo(s) unflagged and moved to manually locked.";

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => $msg,
            'count'    => $count,
            'restored' => $restore,
        ]);
    }

    public function photoReason(int $photoId)
    {
        if (! auth()->loggedIn() || ! $this->isVaultUnlocked()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $photo = $photoModel->where('user_id', $userId)->find($photoId);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found'])->setStatusCode(404);
        }

        $details = null;
        if (!empty($photo['nsfw_details'])) {
            $details = is_string($photo['nsfw_details']) ? json_decode($photo['nsfw_details'], true) : $photo['nsfw_details'];
        }

        return $this->response->setJSON([
            'status'     => 'success',
            'photo_id'   => $photoId,
            'filename'   => $photo['filename'],
            'is_nsfw'    => (int) $photo['is_nsfw'],
            'nsfw_score' => (float) $photo['nsfw_score'],
            'details'    => $details,
        ]);
    }
}
