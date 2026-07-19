<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use App\Models\PhotoModel;
use App\Models\AuthTokenModel;

class Auth extends BaseController
{
    /**
     * Authenticate user and return access token.
     *
     * @return ResponseInterface
     */
    public function login()
    {
        try {
            log_message('debug', 'API Login attempt for: ' . $this->request->getPost('email'));
            $rules = [
                'email'       => 'required|valid_email',
                'password'    => 'required',
                'device_name' => 'required',
            ];

            if (! $this->validate($rules)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => $this->validator->getErrors(),
                ])->setStatusCode(400);
            }

            $credentials = [
                'email'    => $this->request->getPost('email'),
                'password' => $this->request->getPost('password'),
            ];

            // Authenticate (use session authenticator for email/password check)
            $authenticator = auth('session')->getAuthenticator();
            $result = $authenticator->check($credentials);

            if (! $result->isOK()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => $result->reason(),
                ])->setStatusCode(401);
            }

            $user = $result->extraInfo();
            $token = $user->generateAccessToken($this->request->getPost('device_name'));

            $photoModel = new PhotoModel();
            $lastPhoto = $photoModel->where('user_id', $user->id)
                                    ->orderBy('created_at', 'DESC')
                                    ->first();

            return $this->response->setJSON([
                'status'       => 'success',
                'access_token' => $token->raw_token,
                'user'         => [
                    'id'          => $user->id,
                    'email'       => $user->email,
                    'username'    => $user->username,
                    'created_at'  => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                    'last_upload' => $lastPhoto ? ($lastPhoto['created_at'] ?? null) : null,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[API Login Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Server Error: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    public function authWithToken()
    {
        try {
            $rules = [
                'token'              => 'required|alpha_numeric|exact_length[8]',
                'device_id'          => 'required',
                'device_fingerprint' => 'required',
            ];

            if (! $this->validate($rules)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => $this->validator->getErrors(),
                ])->setStatusCode(400);
            }

            $tokenRaw = strtoupper($this->request->getPost('token'));
            $deviceId = $this->request->getPost('device_id');
            $deviceFingerprint = $this->request->getPost('device_fingerprint');
            $deviceName = $this->request->getPost('device_name') ?? 'Unknown Device';

            $model = new AuthTokenModel();
            $record = $model->where('token', $tokenRaw)->where('is_used', 0)->first();

            if (! $record) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid or already used token.',
                ])->setStatusCode(401);
            }

            // Mark token as used
            $model->update($record['id'], [
                'is_used'           => 1,
                'used_at'           => date('Y-m-d H:i:s'),
                'device_id'         => $deviceId,
                'device_name'       => $deviceName,
                'device_fingerprint' => $deviceFingerprint,
            ]);

            // Get the user who generated the token
            $userModel = auth()->getProvider();
            $user = $userModel->findById($record['user_id']);

            if (! $user) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Token owner not found.',
                ])->setStatusCode(500);
            }

            $token = $user->generateAccessToken($deviceName . ' (token)');

            $photoModel = new PhotoModel();
            $lastPhoto = $photoModel->where('user_id', $user->id)
                                    ->orderBy('created_at', 'DESC')
                                    ->first();

            return $this->response->setJSON([
                'status'       => 'success',
                'access_token' => $token->raw_token,
                'user'         => [
                    'id'          => $user->id,
                    'email'       => $user->email,
                    'username'    => $user->username,
                    'created_at'  => $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : null,
                    'last_upload' => $lastPhoto ? ($lastPhoto['created_at'] ?? null) : null,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[API Token Auth Error] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Server Error: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }
}
