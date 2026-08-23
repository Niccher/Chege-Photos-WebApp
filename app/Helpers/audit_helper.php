<?php

if (! function_exists('log_security_action')) {
    /**
     * Log a security auditing event in the database.
     *
     * @param string      $action
     * @param string      $status  'SUCCESS' or 'FAILURE'
     * @param array|null  $details
     * @param int|null    $userId
     * @return void
     */
    function log_security_action(string $action, string $status, ?array $details = null, ?int $userId = null): void
    {
        try {
            $db = \Config\Database::connect();
            $request = \Config\Services::request();

            $ip = $request->getIPAddress();
            $agent = $request->getUserAgent()->getAgentString();

            // Default to current authenticated user if not provided
            $finalUserId = $userId ?: (auth()->id() ?: null);

            $db->table('sys_security_logs')->insert([
                'user_id'    => $finalUserId,
                'action'     => strtoupper($action),
                'ip_address' => $ip,
                'user_agent' => $agent,
                'status'     => strtoupper($status),
                'details'    => $details ? json_encode($details) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Audit Logger Exception] ' . $e->getMessage());
        }
    }
}
