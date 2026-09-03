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
                'email'              => 'required|valid_email',
                'password'           => 'required',
                'device_name'        => 'permit_empty',
                'device_id'          => 'permit_empty',
                'device_fingerprint' => 'permit_empty',
                'device_uuid'        => 'permit_empty',
                'os_version'         => 'permit_empty',
                'screen_metrics'     => 'permit_empty',
                'locale'             => 'permit_empty',
                'timezone'           => 'permit_empty',
                'kernel_version'     => 'permit_empty',
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
                helper('audit');
                log_security_action('LOGIN_ATTEMPT', 'FAILURE', [
                    'email'  => $credentials['email'],
                    'reason' => $result->reason(),
                    'method' => 'API_PASSWORD'
                ]);
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => $result->reason(),
                ])->setStatusCode(401);
            }

            $user = $result->extraInfo();
            $deviceName        = $this->request->getPost('device_name') ?: 'Android Device';
            $deviceId          = $this->request->getPost('device_id');
            $deviceFingerprint = $this->request->getPost('device_fingerprint');
            $deviceUuid        = $this->request->getPost('device_uuid');
            $osVersion         = $this->request->getPost('os_version');
            $screenMetrics     = $this->request->getPost('screen_metrics');
            $locale            = $this->request->getPost('locale');
            $timezone          = $this->request->getPost('timezone');
            $kernelVersion     = $this->request->getPost('kernel_version');

            helper('audit');
            log_security_action('LOGIN_ATTEMPT', 'SUCCESS', [
                'email'  => $credentials['email'],
                'method' => 'API_PASSWORD'
            ], $user->id);

            $token = $user->generateAccessToken($deviceName);

            // Upsert device record to track device specs and re-link on reinstall
            $authTokenModel = new AuthTokenModel();
            $existingDevice = null;
            if (!empty($deviceId)) {
                $existingDevice = $authTokenModel->where('user_id', $user->id)
                    ->where('device_id', $deviceId)
                    ->first();
            }
            if ($existingDevice) {
                $authTokenModel->update($existingDevice['id'], [
                    'used_at'            => date('Y-m-d H:i:s'),
                    'device_name'        => $deviceName,
                    'device_uuid'        => $deviceUuid,
                    'device_fingerprint' => $deviceFingerprint,
                    'os_version'         => $osVersion,
                    'screen_metrics'     => $screenMetrics,
                    'locale'             => $locale,
                    'timezone'           => $timezone,
                    'kernel_version'     => $kernelVersion,
                ]);
            } else {
                $authTokenModel->insert([
                    'user_id'            => $user->id,
                    'token'              => strtoupper(bin2hex(random_bytes(4))),
                    'description'        => "Login from {$deviceName}",
                    'is_used'            => 1,
                    'used_at'            => date('Y-m-d H:i:s'),
                    'device_id'          => $deviceId,
                    'device_name'        => $deviceName,
                    'device_uuid'        => $deviceUuid,
                    'device_fingerprint' => $deviceFingerprint,
                    'os_version'         => $osVersion,
                    'screen_metrics'     => $screenMetrics,
                    'locale'             => $locale,
                    'timezone'           => $timezone,
                    'kernel_version'     => $kernelVersion,
                    'scopes'             => json_encode(['*']),
                ]);
            }

            log_security_action('NEW_DEVICE_REGISTERED', 'SUCCESS', [
                'device_name' => $deviceName,
                'device_id'   => $deviceId,
                'method'      => 'API_PASSWORD_LOGIN'
            ], $user->id);

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
                'device_uuid'        => 'permit_empty',
                'os_version'         => 'permit_empty',
                'screen_metrics'     => 'permit_empty',
                'locale'             => 'permit_empty',
                'timezone'           => 'permit_empty',
                'kernel_version'     => 'permit_empty',
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
            $deviceUuid = $this->request->getPost('device_uuid');
            $osVersion = $this->request->getPost('os_version');
            $screenMetrics = $this->request->getPost('screen_metrics');
            $locale = $this->request->getPost('locale');
            $timezone = $this->request->getPost('timezone');
            $kernelVersion = $this->request->getPost('kernel_version');

            $model = new AuthTokenModel();
            $record = $model->where('token', $tokenRaw)->where('is_used', 0)->first();

            if (! $record) {
                helper('audit');
                log_security_action('TOKEN_AUTH', 'FAILURE', [
                    'token'  => $tokenRaw,
                    'reason' => 'Invalid or already used token.'
                ]);
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
                'device_uuid'       => $deviceUuid,
                'device_name'       => $deviceName,
                'device_fingerprint' => $deviceFingerprint,
                'os_version'        => $osVersion,
                'screen_metrics'    => $screenMetrics,
                'locale'            => $locale,
                'timezone'          => $timezone,
                'kernel_version'    => $kernelVersion,
            ]);

            // Get the user who generated the token
            $userModel = auth()->getProvider();
            $user = $userModel->findById($record['user_id']);

            if (! $user) {
                helper('audit');
                log_security_action('TOKEN_AUTH', 'FAILURE', [
                    'token'  => $tokenRaw,
                    'reason' => 'Token owner not found.'
                ]);
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Token owner not found.',
                ])->setStatusCode(500);
            }

            $scopes = ['*'];
            if (!empty($record['scopes'])) {
                $decoded = json_decode($record['scopes'], true);
                if (is_array($decoded)) {
                    $scopes = $decoded;
                }
            }

            $token = $user->generateAccessToken($deviceName . ' (token)', $scopes);
            helper('audit');
            log_security_action('NEW_DEVICE_REGISTERED', 'SUCCESS', [
                'token'       => $tokenRaw,
                'device_id'   => $deviceId,
                'device_name' => $deviceName,
                'method'      => 'API_TOKEN_AUTH'
            ], $user->id);

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
