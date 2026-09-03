<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneLogs extends BaseCommand
{
    protected $group       = 'System';
    protected $name        = 'logs:prune';
    protected $description = 'Rotates and prunes old execution logs, email logs, and file logs older than 30 days.';
    protected $usage       = 'logs:prune [--days=30]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db = \Config\Database::connect();

        $days = 30;
        foreach ($params as $p) {
            if (strpos($p, '--days=') === 0) {
                $days = (int) str_replace('--days=', '', $p);
            }
        }

        $cutoff = date('Y-m-d H:i:s', time() - ($days * 86400));
        CLI::write("Pruning system logs older than {$days} days ({$cutoff})...", 'cyan');

        // 1. Prune sys_cron_logs
        $cronLogsPruned = $db->table('sys_cron_logs')
            ->where('run_at <', $cutoff)
            ->delete();

        // 2. Prune sys_email_logs if table exists
        $emailLogsPruned = 0;
        if ($db->tableExists('sys_email_logs')) {
            $emailLogsPruned = $db->table('sys_email_logs')
                ->where('created_at <', $cutoff)
                ->delete();
        }

        // 3. Prune file logs in writable/logs/
        $filesPruned = 0;
        $logDir = WRITEPATH . 'logs/';
        if (is_dir($logDir)) {
            $cutoffTimestamp = time() - ($days * 86400);
            foreach (glob($logDir . 'log-*.log') as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTimestamp) {
                    @unlink($file);
                    $filesPruned++;
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 3);
        $outputMsg = sprintf("Log pruning completed in %ss. Pruned %d cron log(s), %d email log(s), and %d log file(s).", $duration, $cronLogsPruned, $emailLogsPruned, $filesPruned);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'logs:prune',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }
}
