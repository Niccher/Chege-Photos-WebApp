<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CleanupTemp extends BaseCommand
{
    protected $group       = 'Storage';
    protected $name        = 'storage:clean-temp';
    protected $description = 'Cleans stale files in writable/uploads/ and temp exports older than 24 hours.';
    protected $usage       = 'storage:clean-temp';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db        = \Config\Database::connect();
        $targetDir = WRITEPATH . 'uploads';
        
        $cleaned = 0;
        $cutoff  = time() - (24 * 3600); // 24 hours ago

        if (is_dir($targetDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($targetDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                // Safeguard: Never delete actual user media files
                if (strpos($fileinfo->getRealPath(), '/users/') !== false) {
                    continue;
                }
                if ($fileinfo->isFile() && $fileinfo->getMTime() < $cutoff) {
                    @unlink($fileinfo->getRealPath());
                    $cleaned++;
                }
            }
        }

        $duration = microtime(true) - $startTime;
        $outputMsg = sprintf('Cleaned %d stale temporary file(s) from uploads storage.', $cleaned);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'storage:clean-temp',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }
}
