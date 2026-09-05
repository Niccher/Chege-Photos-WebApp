<?php

namespace App\Commands;

use App\Models\PhotoModel;
use App\Services\GcpStorageService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessPendingMedia extends BaseCommand
{
    protected $group       = 'Storage';
    protected $name        = 'media:process-pending';
    protected $description = 'Calculates missing SHA-256 hashes and mirrors pending media to Google Cloud Storage.';
    protected $usage       = 'media:process-pending [--id=<id>] [--limit=<limit>]';
    protected $options     = [
        '--id'    => 'Specific photo ID to process',
        '--limit' => 'Maximum number of records to process (default 25)',
    ];

    public function run(array $params)
    {
        $startTime  = microtime(true);
        $photoModel = new PhotoModel();
        $gcpService = new GcpStorageService();
        $gcpReady   = $gcpService->isConfigured();

        $targetId = $params['id'] ?? CLI::getOption('id');
        $limit    = (int) ($params['limit'] ?? CLI::getOption('limit') ?? 25);

        if ($targetId) {
            $photos = $photoModel->where('id', (int) $targetId)->findAll();
        } else {
            // Find photos missing SHA-256 or missing GCP sync
            $photos = $photoModel
                ->groupStart()
                    ->where('file_hash IS NULL', null, false)
                    ->orWhere('file_hash', '')
                    ->orWhere('gcp_synced', 0)
                    ->orWhere('gcp_synced IS NULL', null, false)
                ->groupEnd()
                ->where('deleted_at IS NULL', null, false)
                ->orderBy('id', 'DESC')
                ->findAll($limit);
        }

        if (empty($photos)) {
            CLI::write('No pending media items to process.', 'green');
            return EXIT_SUCCESS;
        }

        CLI::write(sprintf('Processing %d pending media item(s)...', count($photos)), 'cyan');

        $processedHashes = 0;
        $syncedGcp       = 0;

        foreach ($photos as $photo) {
            $photoId  = (int) $photo['id'];
            $relPath  = ltrim($photo['path'], '/');
            $fullPath = is_file(FCPATH . $relPath) ? FCPATH . $relPath : WRITEPATH . $relPath;

            if (! file_exists($fullPath)) {
                CLI::write("Photo #{$photoId}: Local file not found at {$fullPath}", 'yellow');
                continue;
            }

            $updates = [];

            // 1. Calculate SHA-256 if missing
            if (empty($photo['file_hash'])) {
                $hash = hash_file('sha256', $fullPath);
                if ($hash) {
                    $updates['file_hash'] = $hash;
                    $processedHashes++;
                    CLI::write("Photo #{$photoId}: SHA-256 calculated ({$hash})", 'green');
                }
            }

            // 2. Mirror to GCP if not yet synced
            if ($gcpReady && (empty($photo['gcp_synced']) || (int) $photo['gcp_synced'] === 0)) {
                $res = $gcpService->uploadFile($fullPath, $relPath, $photo['mime_type'] ?? null);
                if (! empty($res['success'])) {
                    $updates['storage_driver'] = 'hybrid';
                    $updates['gcp_synced']     = 1;
                    $updates['gcp_synced_at']  = date('Y-m-d H:i:s');
                    $syncedGcp++;
                    CLI::write("Photo #{$photoId}: Mirrored to Google Cloud Storage", 'green');

                    // Mirror thumbnail if present
                    if (! empty($photo['thumbnail_path'])) {
                        $thumbRel  = ltrim($photo['thumbnail_path'], '/');
                        $thumbFull = FCPATH . $thumbRel;
                        if (file_exists($thumbFull)) {
                            $gcpService->uploadFile($thumbFull, $thumbRel, 'image/jpeg');
                        }
                    }
                } else {
                    CLI::write("Photo #{$photoId}: GCP upload failed - " . ($res['error'] ?? 'Unknown error'), 'red');
                }
            }

            if (! empty($updates)) {
                $photoModel->update($photoId, $updates);
            }
        }

        $duration  = round(microtime(true) - $startTime, 2);
        $outputMsg = sprintf(
            'Processed %d item(s): %d SHA hashes computed, %d mirrored to GCP in %ss.',
            count($photos),
            $processedHashes,
            $syncedGcp,
            $duration
        );

        CLI::write($outputMsg, 'green');

        try {
            $db = \Config\Database::connect();
            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'media:process-pending',
                'status'           => 'success',
                'output'           => $outputMsg,
                'duration_seconds' => $duration,
                'executed_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {}

        return EXIT_SUCCESS;
    }
}
