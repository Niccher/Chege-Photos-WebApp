<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneAuthTokens extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:prune-tokens';
    protected $description = 'Purges expired, revoked, and long-abandoned authentication tokens and device sessions.';
    protected $usage       = 'auth:prune-tokens [--days=90]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db = \Config\Database::connect();

        $days = 90;
        foreach ($params as $p) {
            if (strpos($p, '--days=') === 0) {
                $days = (int) str_replace('--days=', '', $p);
            }
        }

        $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
        $now = date('Y-m-d H:i:s');

        // 1. Delete explicitly expired tokens (expires_at is set and past)
        $expiredCount = $db->table('tbl_auth_tokens')
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <', $now)
            ->delete();

        // 2. Delete abandoned inactive tokens (not used in over X days)
        $abandonedCount = $db->table('tbl_auth_tokens')
            ->where('used_at IS NOT NULL')
            ->where('used_at <', $cutoff)
            ->delete();

        // 3. Delete tokens created over X days ago and never used
        $neverUsedCount = $db->table('tbl_auth_tokens')
            ->where('used_at IS NULL')
            ->where('created_at <', $cutoff)
            ->delete();

        $totalPurged = $expiredCount + $abandonedCount + $neverUsedCount;
        $duration = round(microtime(true) - $startTime, 3);
        $outputMsg = sprintf("Auth token pruning completed in %ss. Purged %d expired/abandoned tokens (threshold: %d days).", $duration, $totalPurged, $days);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'auth:prune-tokens',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }
}
