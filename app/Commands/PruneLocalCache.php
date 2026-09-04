<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PhotoModel;

class PruneLocalCache extends BaseCommand
{
    protected $group       = 'Storage';
    protected $name        = 'storage:prune-cache';
    protected $description = 'Safely prunes local cached media files older than X days if they are safely stored in GCP.';
    protected $usage       = 'storage:prune-cache [--days=14]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $days = 14;

        foreach ($params as $param) {
            if (strpos($param, '--days=') === 0) {
                $days = max(1, (int) substr($param, 7));
            }
        }

        $cutoff = time() - ($days * 86400);
        $photoModel = new PhotoModel();
        $db = \Config\Database::connect();

        CLI::write("Scanning local media cache for files older than {$days} days with verified GCP copies...", 'cyan');

        $uploadsDir = FCPATH . 'uploads';
        if (!is_dir($uploadsDir)) {
            CLI::write("Uploads directory not found: {$uploadsDir}", 'yellow');
            return EXIT_SUCCESS;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $prunedCount = 0;
        $freedBytes  = 0;

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $filePath = $fileInfo->getRealPath();
                $mtime = $fileInfo->getMTime();

                if ($mtime < $cutoff) {
                    // Extract relative path from FCPATH
                    $relPath = ltrim(str_replace(FCPATH, '', $filePath), '/');

                    // Check if photo is verified in GCP
                    $photo = $photoModel->where('path', $relPath)->first();
                    if ($photo && !empty($photo['gcp_synced'])) {
                        $size = $fileInfo->getSize();
                        if (@unlink($filePath)) {
                            $prunedCount++;
                            $freedBytes += $size;
                        }
                    }
                }
            }
        }

        $freedMb = round($freedBytes / 1024 / 1024, 2);
        $duration = round(microtime(true) - $startTime, 3);
        $msg = sprintf("Local cache pruning completed in %ss. Pruned: %d file(s), Freed: %s MB.", $duration, $prunedCount, $freedMb);

        CLI::write($msg, 'green');

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'storage:prune-cache',
            'status'           => 'success',
            'output'           => $msg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        return EXIT_SUCCESS;
    }
}
