<?php

/**
 * Chege Photos ML Helper
 * Centralized ML URL resolution, candidate discovery, and health probing.
 */

if (! function_exists('get_ml_api_key')) {
    function get_ml_api_key(): string
    {
        return setting('ML.apiKey') ?: (env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!');
    }
}

if (! function_exists('get_ml_candidate_urls')) {
    /**
     * Returns an array of candidate ML endpoints in priority order.
     *
     * @return array<string>
     */
    function get_ml_candidate_urls(): array
    {
        $urls = [];

        // 1. Explicit database setting
        if ($dbUrl = setting('ML.url')) {
            $urls[] = rtrim(trim($dbUrl), '/');
        }

        // 2. Explicit environment variable
        if ($envUrl = env('ML_URL') ?: getenv('ML_URL')) {
            $urls[] = rtrim(trim($envUrl), '/');
        }

        // 3. Cached verified working URL
        if ($cached = cache('active_ml_url')) {
            $urls[] = rtrim(trim($cached), '/');
        }

        // 4. Railway internal networking candidates
        if (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) {
            $railwayHosts = [
                'ml-chege-photos.railway.internal',
                'chege-photos-ml.railway.internal',
                'ml.railway.internal',
                'ml-service.railway.internal',
                'chege-photos-ai.railway.internal',
                'ml-chege.railway.internal',
                'ml-chege-photos',
                'chege-photos-ml',
                'ml',
            ];

            foreach ($railwayHosts as $host) {
                $urls[] = "http://{$host}:8000";
            }
        }

        // 5. Local Docker / Development defaults
        $urls[] = 'http://ml-chege-photos:8000';
        $urls[] = 'http://127.0.0.1:8000';
        $urls[] = 'http://localhost:9051';
        $urls[] = 'http://127.0.0.1:9051';

        return array_values(array_unique(array_filter($urls)));
    }
}

if (! function_exists('get_ml_url')) {
    /**
     * Resolves the primary active ML Service URL.
     */
    function get_ml_url(): string
    {
        // 1. Explicit DB setting has highest priority
        if ($url = setting('ML.url')) {
            $trimmed = rtrim(trim($url), '/');
            if (! empty($trimmed)) {
                return $trimmed;
            }
        }

        // 2. Explicit environment variable
        if ($url = env('ML_URL') ?: getenv('ML_URL')) {
            $trimmed = rtrim(trim($url), '/');
            if (! empty($trimmed)) {
                return $trimmed;
            }
        }

        // 3. Check memory/cache for verified working candidate
        if ($cached = cache('active_ml_url')) {
            $trimmed = rtrim(trim($cached), '/');
            if (! empty($trimmed)) {
                return $trimmed;
            }
        }

        // 4. In Railway, quick DNS resolution check against common candidates
        if (getenv('RAILWAY_ENVIRONMENT') || getenv('RAILWAY_PROJECT_ID')) {
            $candidates = [
                'ml-chege-photos.railway.internal',
                'chege-photos-ml.railway.internal',
                'ml.railway.internal',
                'ml-service.railway.internal',
                'chege-photos-ai.railway.internal',
                'ml-chege.railway.internal',
                'ml-chege-photos',
                'chege-photos-ml',
                'ml',
            ];

            foreach ($candidates as $host) {
                if (@gethostbyname($host) !== $host) {
                    $resolved = "http://{$host}:8000";
                    cache()->save('active_ml_url', $resolved, 300);
                    return $resolved;
                }
                if (function_exists('dns_get_record')) {
                    $records = @dns_get_record($host, DNS_A | DNS_AAAA);
                    if (! empty($records)) {
                        $resolved = "http://{$host}:8000";
                        cache()->save('active_ml_url', $resolved, 300);
                        return $resolved;
                    }
                }
            }

            return 'http://ml-chege-photos.railway.internal:8000';
        }

        return 'http://ml-chege-photos:8000';
    }
}

if (! function_exists('get_webapp_url')) {
    /**
     * Resolves the canonical WebApp base URL for inter-service communication.
     */
    function get_webapp_url(): string
    {
        $base = rtrim(base_url(), '/');
        if (empty($base) || $base === 'http://localhost' || $base === 'http://localhost:8080') {
            $envAppUrl = env('app.baseURL') ?: getenv('APP_BASE_URL') ?: getenv('BASE_URL');
            if ($envAppUrl) {
                return rtrim(trim($envAppUrl), '/');
            }
        }
        return $base;
    }
}

if (! function_exists('probe_ml_url')) {
    /**
     * Probes candidate ML URLs to find a responsive endpoint.
     *
     * @param string|null $explicitUrl Optional specific URL to probe
     * @return array
     */
    function probe_ml_url(?string $explicitUrl = null): array
    {
        $apiKey    = get_ml_api_key();
        $webappUrl = get_webapp_url();
        $candidates = $explicitUrl ? [rtrim(trim($explicitUrl), '/')] : get_ml_candidate_urls();

        $client = service('curlrequest', [
            'connect_timeout' => 4,
            'timeout'         => 6,
            'headers'         => [
                'X-API-KEY'    => $apiKey,
                'X-Webapp-Url' => $webappUrl,
            ],
            'http_errors'     => false,
        ]);

        $attempted = [];
        $lastError = 'No candidates probed';

        foreach ($candidates as $targetUrl) {
            $healthEndpoint = rtrim($targetUrl, '/') . '/api/v1/health';
            $attempted[] = $targetUrl;
            $start = microtime(true);

            try {
                $response = $client->get($healthEndpoint);
                $latencyMs = round((microtime(true) - $start) * 1000, 2);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    $body = json_decode($response->getBody(), true) ?? [];
                    // Cache working URL for 10 minutes
                    cache()->save('active_ml_url', $targetUrl, 600);

                    return [
                        'online'     => true,
                        'url'        => $targetUrl,
                        'status'     => $body['status'] ?? 'healthy',
                        'latency_ms' => $latencyMs,
                        'data'       => $body,
                        'error'      => null,
                        'attempted'  => $attempted,
                    ];
                }

                $lastError = "HTTP {$statusCode} from {$targetUrl}";
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return [
            'online'     => false,
            'url'        => $explicitUrl ?: get_ml_url(),
            'status'     => 'offline',
            'latency_ms' => null,
            'data'       => null,
            'error'      => $lastError,
            'attempted'  => $attempted,
        ];
    }
}
