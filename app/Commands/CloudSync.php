<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\GcpStorageService;
use App\Models\PhotoModel;

class CloudSync extends BaseCommand
{
    protected $group       = 'System';
    protected $name        = 'cloud:sync';
    protected $description = 'Synchronizes local user uploads and thumbnails to Google Cloud Storage.';
    protected $usage       = 'cloud:sync [--all]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db = \Config\Database::connect();
        $gcpService = new GcpStorageService();

        if (!$gcpService->isConfigured()) {
            $msg = 'GCP Storage is not configured. Cloud synchronization skipped.';
            CLI::write($msg, 'yellow');
            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'cloud:sync',
                'status'           => 'success',
                'output'           => $msg,
                'duration_seconds' => round(microtime(true) - $startTime, 3),
                'run_at'           => date('Y-m-d H:i:s'),
            ]);
            return EXIT_SUCCESS;
        }

        $syncAll = in_array('--all', $params, true);
        $photoModel = new PhotoModel();

        // Get photos to sync: past 24 hours or all un-synced
        $query = $photoModel->orderBy('id', 'DESC');
        if (!$syncAll) {
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $query->where('created_at >=', $yesterday);
        }
        $photos = $query->findAll();

        $synced = 0;
        $failed = 0;

        CLI::write(sprintf("Found %d photo(s) to verify with Google Cloud Storage...", count($photos)), 'cyan');

        foreach ($photos as $photo) {
            $relPath = $photo['path'];
            $fullPath = is_file(WRITEPATH . $relPath) ? WRITEPATH . $relPath : FCPATH . $relPath;

            if (file_exists($fullPath)) {
                $cloudTarget = 'uploads/' . ltrim($relPath, '/');
                $res = $gcpService->uploadFile($fullPath, $cloudTarget, $photo['mime_type'] ?? null);

                if ($res['success']) {
                    $synced++;
                } else {
                    $failed++;
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 3);
        $outputMsg = sprintf("Cloud sync completed in %ss. Synced: %d file(s), Failed/Skipped: %d.", $duration, $synced, $failed);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'cloud:sync',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }
}
