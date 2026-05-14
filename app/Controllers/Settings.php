<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Settings extends BaseController
{
    public function index()
    {
        $photoModel = new \App\Models\PhotoModel();
        $userId     = auth()->id();

        $totalBytes = $photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0;

        $videoBytes = $photoModel->where('user_id', $userId)
            ->like('mime_type', 'video/', 'after')
            ->selectSum('size')
            ->first()['size'] ?? 0;
        $photoBytes = $totalBytes - $videoBytes;

        $user = auth()->user();
        $data = [
            'user'    => $user,
            'counts'  => $this->getSidebarCounts(),
            'storage' => [
                'total'   => $totalBytes,
                'photos'  => $photoBytes,
                'videos'  => $videoBytes,
                'percent' => min(100, ($totalBytes / (setting('App.storageLimit') ?: (1024 * 1024 * 1024))) * 100),
                'limit'   => $this->formatBytes(setting('App.storageLimit') ?: (1024 * 1024 * 1024)),
            ],
            'theme'           => setting('App.theme', "user:{$userId}") ?? 'auto',
            'storageUsed'     => $this->formatBytes($totalBytes),
            'storagePercent'  => min(100, ($totalBytes / (1024 * 1024 * 1024 * 1)) * 100),
        ];

        return view('photos/settings', $data);
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
        if ($userModel->save($user)) {
            $this->clearSidebarCountsCache($userId);

            return $this->response->setJSON(['status' => 'success', 'message' => 'Profile updated successfully.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update profile.']);
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
        if (! $userModel->save($user)) {
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
        $userModel->save($user);
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

        if ($userModel->save($user)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Password changed successfully.']);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to update password.']);
    }

    public function updateTheme()
    {
        $theme  = $this->request->getPost('theme');
        $userId = auth()->id();

        if (! in_array($theme, ['auto', 'light', 'dark', 'solarized', 'grey'], true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid theme.']);
        }

        setting()->set('App.theme', $theme, "user:{$userId}");

        return $this->response->setJSON(['status' => 'success', 'message' => 'Theme updated successfully.']);
    }

    public function getSettings()
    {
        $userId = auth()->id();

        return $this->response->setJSON([
            'theme' => setting('App.theme', "user:{$userId}") ?? 'auto',
        ]);
    }
}
