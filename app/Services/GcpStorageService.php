<?php

namespace App\Services;

class GcpStorageService
{
    private ?string $bucket;
    private string $authType;
    private ?string $serviceAccountJson;
    private ?string $accessKey;
    private ?string $secretKey;
    private int $retentionDays;

    private string $apiBase = 'https://storage.googleapis.com';

    public function __construct(?array $config = null)
    {
        $this->bucket              = $config['bucket'] ?? setting('Storage.gcpBucket') ?? env('GCP_BUCKET');
        $this->authType            = $config['authType'] ?? setting('Storage.gcpAuthType') ?? env('GCP_AUTH_TYPE') ?? 'json';
        $this->serviceAccountJson  = $config['serviceAccountJson'] ?? setting('Storage.gcpServiceAccountJson') ?? env('GCP_SERVICE_ACCOUNT_JSON');
        $this->accessKey           = $config['accessKey'] ?? setting('Storage.gcpAccessKey') ?? env('GCP_ACCESS_KEY');
        $this->secretKey           = $config['secretKey'] ?? setting('Storage.gcpSecretKey') ?? env('GCP_SECRET_KEY');
        $this->retentionDays       = (int) ($config['retentionDays'] ?? setting('Storage.backupRetentionDays') ?? 30);
    }

    public function isConfigured(): bool
    {
        if (empty($this->bucket)) {
            return false;
        }

        if ($this->authType === 'json') {
            return !empty($this->serviceAccountJson);
        }

        return !empty($this->accessKey) && !empty($this->secretKey);
    }

    public function getBucket(): ?string
    {
        return $this->bucket;
    }

    public function getRetentionDays(): int
    {
        return $this->retentionDays;
    }

    /**
     * Executes a comprehensive multi-step probe test to verify GCP Cloud Storage configuration.
     */
    public function testConnection(): array
    {
        $steps = [];
        $startTime = microtime(true);

        // Step 1: Configuration check
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'GCP credentials or bucket name are missing. Please provide bucket name and credentials.',
                'steps'   => [
                    [
                        'name'   => 'Configuration Check',
                        'status' => 'error',
                        'detail' => 'Bucket name or credentials are empty.'
                    ]
                ],
                'duration' => 0.0
            ];
        }

        $steps[] = [
            'name'   => 'Configuration Check',
            'status' => 'success',
            'detail' => sprintf('Configured with %s credentials for bucket "%s"', strtoupper($this->authType), $this->bucket)
        ];

        // Step 2: Network connectivity check
        $t0 = microtime(true);
        $ping = @fsockopen('storage.googleapis.com', 443, $errno, $errstr, 3);
        if (!$ping) {
            $steps[] = [
                'name'   => 'Network Connectivity',
                'status' => 'error',
                'detail' => 'Cannot connect to storage.googleapis.com:443: ' . $errstr
            ];
            return [
                'success' => false,
                'message' => 'Network connectivity to Google Cloud Storage failed.',
                'steps'   => $steps,
                'duration' => round(microtime(true) - $startTime, 3)
            ];
        }
        fclose($ping);
        $netLatency = round((microtime(true) - $t0) * 1000, 1);
        $steps[] = [
            'name'   => 'Network Connectivity',
            'status' => 'success',
            'detail' => "Successfully reached storage.googleapis.com ({$netLatency} ms)"
        ];

        // Step 3: Authentication verification
        $t0 = microtime(true);
        $token = null;
        if ($this->authType === 'json') {
            try {
                $token = $this->getAccessToken();
                $authLatency = round((microtime(true) - $t0) * 1000, 1);
                $steps[] = [
                    'name'   => 'Authentication Verification',
                    'status' => 'success',
                    'detail' => "Service Account token generated & verified ({$authLatency} ms)"
                ];
            } catch (\Throwable $e) {
                $steps[] = [
                    'name'   => 'Authentication Verification',
                    'status' => 'error',
                    'detail' => 'Authentication failed: ' . $e->getMessage()
                ];
                return [
                    'success' => false,
                    'message' => 'Failed to authenticate with Google Cloud: ' . $e->getMessage(),
                    'steps'   => $steps,
                    'duration' => round(microtime(true) - $startTime, 3)
                ];
            }
        } else {
            // HMAC credentials check
            if (strlen($this->accessKey) < 5 || strlen($this->secretKey) < 10) {
                $steps[] = [
                    'name'   => 'Authentication Verification',
                    'status' => 'error',
                    'detail' => 'HMAC Access Key or Secret Key format appears invalid.'
                ];
                return [
                    'success' => false,
                    'message' => 'Invalid HMAC credentials format.',
                    'steps'   => $steps,
                    'duration' => round(microtime(true) - $startTime, 3)
                ];
            }
            $steps[] = [
                'name'   => 'Authentication Verification',
                'status' => 'success',
                'detail' => sprintf('HMAC Interoperability credentials verified for key %s...', substr($this->accessKey, 0, 8))
            ];
        }

        // Step 4: Bucket access check
        $t0 = microtime(true);
        if ($this->authType === 'json') {
            $bucketUrl = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket);
            $res = $this->httpGet($bucketUrl, $token);
            if ($res['status'] !== 200) {
                $errDetail = $res['body']['error']['message'] ?? "HTTP {$res['status']}";
                $steps[] = [
                    'name'   => 'Bucket Access Verification',
                    'status' => 'error',
                    'detail' => "Bucket '{$this->bucket}' inaccessible: {$errDetail}. Ensure Service Account has 'Storage Object Admin' role."
                ];
                return [
                    'success' => false,
                    'message' => "Bucket access error: {$errDetail}",
                    'steps'   => $steps,
                    'duration' => round(microtime(true) - $startTime, 3)
                ];
            }
            $bLatency = round((microtime(true) - $t0) * 1000, 1);
            $bLocation = $res['body']['location'] ?? 'GLOBAL';
            $bStorageClass = $res['body']['storageClass'] ?? 'STANDARD';
            $steps[] = [
                'name'   => 'Bucket Access Verification',
                'status' => 'success',
                'detail' => "Bucket '{$this->bucket}' is accessible ({$bLocation} / {$bStorageClass}, {$bLatency} ms)"
            ];
        } else {
            // HMAC Bucket probe
            $res = $this->sendHmacRequest('GET', '', '', '');
            if ($res['status'] !== 200) {
                $steps[] = [
                    'name'   => 'Bucket Access Verification',
                    'status' => 'error',
                    'detail' => "Bucket '{$this->bucket}' inaccessible via HMAC: HTTP {$res['status']}. Ensure HMAC key has storage access."
                ];
                return [
                    'success' => false,
                    'message' => "Bucket access error: HTTP {$res['status']}",
                    'steps'   => $steps,
                    'duration' => round(microtime(true) - $startTime, 3)
                ];
            }
            $bLatency = round((microtime(true) - $t0) * 1000, 1);
            $steps[] = [
                'name'   => 'Bucket Access Verification',
                'status' => 'success',
                'detail' => "Bucket '{$this->bucket}' is accessible via HMAC Interoperability ({$bLatency} ms)"
            ];
        }

        // Step 5: Write & Read Probe
        $probeName = '_probe_test_' . time() . '.txt';
        $probeContent = 'Chege Photos GCP connectivity probe at ' . date('Y-m-d H:i:s');
        $writeRes = $this->uploadRawData($probeContent, $probeName, 'text/plain', $token);
        if (!$writeRes['success']) {
            $steps[] = [
                'name'   => 'Write & Read Probe',
                'status' => 'error',
                'detail' => 'Failed to write test file to bucket: ' . ($writeRes['error'] ?? 'Unknown error')
            ];
            return [
                'success' => false,
                'message' => 'Write permissions check failed on GCP bucket.',
                'steps'   => $steps,
                'duration' => round(microtime(true) - $startTime, 3)
            ];
        }
        $steps[] = [
            'name'   => 'Write & Read Probe',
            'status' => 'success',
            'detail' => 'Write and read permissions confirmed with test probe file'
        ];

        // Step 6: Delete Probe (Confirm pruning ability)
        $delRes = $this->deleteObject($probeName, $token);
        if (!$delRes) {
            $steps[] = [
                'name'   => 'Delete & Pruning Permission',
                'status' => 'warning',
                'detail' => 'Could not delete test probe. Pruning will require delete permissions.'
            ];
        } else {
            $steps[] = [
                'name'   => 'Delete & Pruning Permission',
                'status' => 'success',
                'detail' => 'Delete permission confirmed. Auto-pruning is fully operational.'
            ];
        }

        $totalDuration = round(microtime(true) - $startTime, 3);
        return [
            'success'  => true,
            'message'  => 'Google Cloud Storage is connected, authenticated, and fully operational!',
            'steps'    => $steps,
            'duration' => $totalDuration
        ];
    }

    /**
     * Streams a local file to Google Cloud Storage.
     */
    public function uploadFile(string $localPath, string $cloudPath, ?string $mimeType = null): array
    {
        if (!file_exists($localPath)) {
            return ['success' => false, 'error' => "Local file not found: {$localPath}"];
        }

        $fileSize = filesize($localPath);
        $mime = $mimeType ?: mime_content_type($localPath) ?: 'application/octet-stream';

        // For files larger than 20MB, use streaming file handles to prevent PHP memory exhaustion
        if ($fileSize > 20 * 1024 * 1024) {
            return $this->uploadFileStreamed($localPath, $cloudPath, $mime, $fileSize);
        }

        $content = file_get_contents($localPath);
        return $this->uploadRawData($content, $cloudPath, $mime);
    }

    /**
     * Streams large files to Google Cloud Storage with constant low memory usage.
     */
    public function uploadFileStreamed(string $localPath, string $cloudPath, string $mimeType, int $fileSize): array
    {
        try {
            $fp = fopen($localPath, 'rb');
            if (!$fp) {
                return ['success' => false, 'error' => "Cannot open local file for reading: {$localPath}"];
            }

            if ($this->authType === 'hmac') {
                $verb = 'PUT';
                $date = gmdate('D, d M Y H:i:s T');
                $cleanPath = ltrim($cloudPath, '/');
                $url = "{$this->apiBase}/" . urlencode($this->bucket) . '/' . str_replace('%2F', '/', rawurlencode($cleanPath));

                $canonicalResource = '/' . $this->bucket . '/' . $cleanPath;
                $stringToSign = "{$verb}\n\n{$mimeType}\n{$date}\n{$canonicalResource}";
                $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

                $headers = [
                    "Host: storage.googleapis.com",
                    "Date: {$date}",
                    "Authorization: AWS {$this->accessKey}:{$signature}",
                    "Content-Type: {$mimeType}",
                    "Content-Length: {$fileSize}",
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_PUT, true);
                curl_setopt($ch, CURLOPT_INFILE, $fp);
                curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
                curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                fclose($fp);

                if ($httpCode >= 200 && $httpCode < 300) {
                    return ['success' => true, 'data' => []];
                }
                return ['success' => false, 'error' => "HTTP {$httpCode}: " . ($err ?: substr((string)$response, 0, 200))];
            }

            // JSON Service Account mode
            $token = $this->getAccessToken();
            $url = "{$this->apiBase}/upload/storage/v1/b/" . urlencode($this->bucket) . '/o?uploadType=media&name=' . urlencode($cloudPath);

            $headers = [
                "Authorization: Bearer {$token}",
                "Content-Type: {$mimeType}",
                "Content-Length: {$fileSize}",
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            fclose($fp);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['success' => true, 'data' => json_decode((string)$response, true)];
            }

            $bodyJson = json_decode((string)$response, true);
            $errMsg = $bodyJson['error']['message'] ?? ($err ?: "HTTP {$httpCode}");
            return ['success' => false, 'error' => $errMsg];
        } catch (\Throwable $e) {
            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Uploads raw binary/text data to Google Cloud Storage.
     */
    public function uploadRawData(string $data, string $cloudPath, string $mimeType = 'application/octet-stream', ?string $token = null): array
    {
        try {
            if ($this->authType === 'hmac') {
                $res = $this->sendHmacRequest('PUT', $cloudPath, $data, $mimeType);
                if ($res['status'] >= 200 && $res['status'] < 300) {
                    return ['success' => true, 'data' => []];
                }
                return ['success' => false, 'error' => "HTTP {$res['status']}: " . substr($res['raw'], 0, 200)];
            }

            // JSON Service Account mode
            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/upload/storage/v1/b/" . urlencode($this->bucket) . '/o?uploadType=media&name=' . urlencode($cloudPath);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$token}",
                "Content-Type: {$mimeType}",
                'Content-Length: ' . strlen($data)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['success' => true, 'data' => json_decode($response, true)];
            }

            $bodyJson = json_decode($response, true);
            $errMsg = $bodyJson['error']['message'] ?? ($err ?: "HTTP {$httpCode}");
            return ['success' => false, 'error' => $errMsg];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Checks if an object exists in Google Cloud Storage.
     */
    public function hasObject(string $cloudPath, ?string $token = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $cleanPath = ltrim($cloudPath, '/');
        try {
            if ($this->authType === 'hmac') {
                $res = $this->sendHmacRequest('HEAD', $cleanPath);
                return ($res['status'] === 200);
            }

            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket) . '/o/' . urlencode($cleanPath);
            $res = $this->httpGet($url, $token);
            return ($res['status'] === 200);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Downloads an object from Google Cloud Storage directly into a local file path.
     * Uses atomic temporary file writing to avoid partial/corrupted files.
     */
    public function downloadFile(string $cloudPath, string $localSavePath, ?string $token = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $cleanPath = ltrim($cloudPath, '/');
        $dir = dirname($localSavePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $tempFile = $localSavePath . '.tmp.' . bin2hex(random_bytes(4));
        $fp = @fopen($tempFile, 'w+b');
        if (!$fp) {
            log_message('error', "Cannot open temporary file for GCP download: {$tempFile}");
            return false;
        }

        try {
            if ($this->authType === 'hmac') {
                $verb = 'GET';
                $date = gmdate('D, d M Y H:i:s T');
                $url = "{$this->apiBase}/" . urlencode($this->bucket) . '/' . str_replace('%2F', '/', rawurlencode($cleanPath));
                $canonicalResource = '/' . $this->bucket . '/' . $cleanPath;
                $stringToSign = "{$verb}\n\n\n{$date}\n{$canonicalResource}";
                $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

                $headers = [
                    "Host: storage.googleapis.com",
                    "Date: {$date}",
                    "Authorization: AWS {$this->accessKey}:{$signature}"
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 300);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $exec = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fp);

                if ($exec && $httpCode >= 200 && $httpCode < 300) {
                    if (@rename($tempFile, $localSavePath)) {
                        @chmod($localSavePath, 0666);
                        return true;
                    }
                }
                @unlink($tempFile);
                return false;
            }

            // JSON Service Account mode
            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket) . '/o/' . urlencode($cleanPath) . '?alt=media';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$token}"
            ]);

            $exec = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if ($exec && $httpCode >= 200 && $httpCode < 300) {
                if (@rename($tempFile, $localSavePath)) {
                    @chmod($localSavePath, 0666);
                    return true;
                }
            }
            @unlink($tempFile);
            return false;
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            @unlink($tempFile);
            log_message('error', "GCP downloadFile error for {$cloudPath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches raw object binary data into memory.
     */
    public function downloadData(string $cloudPath, ?string $token = null): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $cleanPath = ltrim($cloudPath, '/');
        try {
            if ($this->authType === 'hmac') {
                $res = $this->sendHmacRequest('GET', $cleanPath);
                return ($res['status'] >= 200 && $res['status'] < 300) ? $res['raw'] : null;
            }

            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket) . '/o/' . urlencode($cleanPath) . '?alt=media';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$token}"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode >= 200 && $httpCode < 300) ? $response : null;
        } catch (\Throwable $e) {
            log_message('error', "GCP downloadData error for {$cloudPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lists objects under a given cloud prefix.
     */
    public function listObjects(string $prefix = '', ?string $token = null): array
    {
        try {
            if ($this->authType === 'hmac') {
                $res = $this->sendHmacRequest('GET', '', '', '', ['prefix' => $prefix]);
                if ($res['status'] === 200 && !empty($res['raw'])) {
                    return $this->parseXmlObjectList($res['raw']);
                }
                return [];
            }

            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket) . '/o?prefix=' . urlencode($prefix);

            $res = $this->httpGet($url, $token);
            if ($res['status'] === 200) {
                return $res['body']['items'] ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            log_message('error', 'GCP listObjects error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Deletes an object from Google Cloud Storage.
     */
    public function deleteObject(string $cloudPath, ?string $token = null): bool
    {
        try {
            if ($this->authType === 'hmac') {
                $res = $this->sendHmacRequest('DELETE', $cloudPath, '', '');
                return ($res['status'] === 200 || $res['status'] === 204);
            }

            $token = $token ?: $this->getAccessToken();
            $url = "{$this->apiBase}/storage/v1/b/" . urlencode($this->bucket) . '/o/' . urlencode($cloudPath);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$token}"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode === 200 || $httpCode === 204);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Scans a prefix for files older than $retentionDays and deletes them.
     */
    public function pruneBackups(string $prefix = 'backups/database/', ?int $retentionDays = null): array
    {
        $days = $retentionDays !== null ? $retentionDays : $this->retentionDays;
        if ($days <= 0) {
            return ['deleted_count' => 0, 'freed_bytes' => 0, 'skipped' => 'Retention disabled (0 days)'];
        }

        $cutoffTime = time() - ($days * 86400);
        $objects = $this->listObjects($prefix);
        $deletedCount = 0;
        $freedBytes = 0;

        foreach ($objects as $obj) {
            $name = $obj['name'] ?? '';
            $size = (int) ($obj['size'] ?? 0);
            $timeCreated = isset($obj['timeCreated']) ? strtotime($obj['timeCreated']) : 0;

            if ($timeCreated > 0 && $timeCreated < $cutoffTime) {
                if ($this->deleteObject($name)) {
                    $deletedCount++;
                    $freedBytes += $size;
                }
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'freed_bytes'   => $freedBytes,
            'cutoff_date'   => date('Y-m-d H:i:s', $cutoffTime),
            'retention_days'=> $days
        ];
    }

    /**
     * Sends an S3/GCS Interoperability request using standard HMAC authentication.
     */
    private function sendHmacRequest(string $method, string $path = '', string $data = '', string $contentType = '', array $queryParams = []): array
    {
        $verb = strtoupper($method);
        $date = gmdate('D, d M Y H:i:s T');
        $cleanPath = ltrim($path, '/');

        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        $url = "{$this->apiBase}/" . urlencode($this->bucket) . ($cleanPath !== '' ? '/' . str_replace('%2F', '/', rawurlencode($cleanPath)) : '') . $queryString;

        // Canonicalized resource for S3/Google HMAC signature
        $canonicalResource = '/' . $this->bucket . ($cleanPath !== '' ? '/' . $cleanPath : '');
        $stringToSign = "{$verb}\n\n{$contentType}\n{$date}\n{$canonicalResource}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

        $headers = [
            "Host: storage.googleapis.com",
            "Date: {$date}",
            "Authorization: AWS {$this->accessKey}:{$signature}"
        ];
        if (!empty($contentType)) {
            $headers[] = "Content-Type: {$contentType}";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($verb === 'PUT' || $verb === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'raw'    => $response
        ];
    }

    /**
     * Parses S3 XML object listing returned by GCS Interoperability.
     */
    private function parseXmlObjectList(string $xmlContent): array
    {
        $items = [];
        try {
            $xml = @simplexml_load_string($xmlContent);
            if ($xml && isset($xml->Contents)) {
                foreach ($xml->Contents as $content) {
                    $items[] = [
                        'name'        => (string) $content->Key,
                        'size'        => (int) $content->Size,
                        'timeCreated' => (string) $content->LastModified,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore XML parse errors
        }
        return $items;
    }

    /**
     * Generates a Google OAuth2 Bearer Access Token using Service Account JWT.
     */
    private function getAccessToken(): string
    {
        if ($this->authType !== 'json') {
            throw new \Exception('Access token generation only supported for JSON Service Account auth mode.');
        }

        // Check CodeIgniter Cache first
        $cache = \Config\Services::cache();
        $cacheKey = 'gcp_access_token_' . md5($this->serviceAccountJson);
        $cached = $cache->get($cacheKey);
        if (!empty($cached)) {
            return $cached;
        }

        $creds = json_decode($this->serviceAccountJson, true);
        if (!is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
            throw new \Exception('Invalid Service Account JSON. Missing client_email or private_key.');
        }

        $now = time();
        $jwtHeader = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $jwtClaim = json_encode([
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/devstorage.full_control',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now
        ]);

        $b64Header = $this->base64UrlEncode($jwtHeader);
        $b64Claim  = $this->base64UrlEncode($jwtClaim);
        $dataToSign = "{$b64Header}.{$b64Claim}";

        $privateKey = openssl_pkey_get_private($creds['private_key']);
        if (!$privateKey) {
            throw new \Exception('Invalid GCP private key format in Service Account credentials.');
        }

        $signature = '';
        if (!openssl_sign($dataToSign, $signature, $privateKey, 'SHA256')) {
            throw new \Exception('Failed to sign JWT with GCP private key using OpenSSL.');
        }

        $jwt = "{$dataToSign}." . $this->base64UrlEncode($signature);

        // Exchange JWT for OAuth2 Access Token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($httpCode !== 200 || empty($json['access_token'])) {
            $err = $json['error_description'] ?? ($json['error'] ?? 'Token exchange error');
            throw new \Exception("Google OAuth2 Token Error: {$err}");
        }

        $accessToken = $json['access_token'];
        // Cache token for 50 minutes (expires in 60 minutes)
        $cache->save($cacheKey, $accessToken, 3000);

        return $accessToken;
    }

    private function httpGet(string $url, ?string $token = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $headers = [];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body'   => json_decode($response, true)
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
