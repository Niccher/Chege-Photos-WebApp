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
    protected $description = 'Synchronizes local media to Google Cloud Storage or hydrates missing media from GCP.';
    protected $usage       = 'cloud:sync [--all] [--hydrate]';

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
        $isHydrate = in_array('--hydrate', $params, true);
        $photoModel = new PhotoModel();

        if ($isHydrate) {
            CLI::write("Running GCP Container Hydration: Downloading missing media from GCP...", 'cyan');
            $photos = $photoModel->orderBy('id', 'DESC')->findAll();
            $hydratedPhotos = 0;
            $hydratedThumbs = 0;
            $failed = 0;

            foreach ($photos as $photo) {
                // Check original media
                $uploadRel = ltrim($photo['path'], '/');
                $uploadLocal = FCPATH . $uploadRel;
                if (!file_exists($uploadLocal) || filesize($uploadLocal) === 0) {
                    if ($gcpService->downloadFile($uploadRel, $uploadLocal)) {
                        $hydratedPhotos++;
                    } else {
                        $failed++;
                    }
                }

                // Check thumbnail
                if (!empty($photo['thumbnail_path'])) {
                    $thumbRel = ltrim($photo['thumbnail_path'], '/');
                    $thumbLocal = FCPATH . $thumbRel;
                    if (!file_exists($thumbLocal) || filesize($thumbLocal) === 0) {
                        if ($gcpService->downloadFile($thumbRel, $thumbLocal)) {
                            $hydratedThumbs++;
                        } elseif (file_exists($uploadLocal) && filesize($uploadLocal) > 0) {
                            // Regenerate thumbnail from local upload
                            $thumbDir = dirname($thumbLocal);
                            if (!is_dir($thumbDir)) {
                                @mkdir($thumbDir, 0777, true);
                            }
                            try {
                                \Config\Services::image()->withFile($uploadLocal)->resize(400, 400, true, 'height')->save($thumbLocal);
                                $hydratedThumbs++;
                                $gcpService->uploadFile($thumbLocal, $thumbRel, 'image/jpeg');
                            } catch (\Throwable $e) {
                                @copy($uploadLocal, $thumbLocal);
                            }
                        }
                    }
                }
            }

            $duration = round(microtime(true) - $startTime, 3);
            $msg = sprintf("Hydration completed in %ss. Restored: %d photo(s), %d thumbnail(s). Missing on cloud: %d.", $duration, $hydratedPhotos, $hydratedThumbs, $failed);
            CLI::write($msg, 'green');

            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'cloud:hydrate',
                'status'           => 'success',
                'output'           => $msg,
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            return EXIT_SUCCESS;
        }

        // Upload mode: Sync local files to GCP
        $query = $photoModel->orderBy('id', 'DESC');
        if (!$syncAll) {
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $query->where('created_at >=', $yesterday);
        }
        $photos = $query->findAll();

        $synced = 0;
        $failed = 0;

        CLI::write(sprintf("Found %d photo(s) to mirror to Google Cloud Storage...", count($photos)), 'cyan');

        foreach ($photos as $photo) {
            // 1. Upload original media
            $relPath = ltrim($photo['path'], '/');
            $fullPath = is_file(WRITEPATH . $relPath) ? WRITEPATH . $relPath : FCPATH . $relPath;

            if (file_exists($fullPath)) {
                $res = $gcpService->uploadFile($fullPath, $relPath, $photo['mime_type'] ?? null);
                if ($res['success']) {
                    $synced++;
                } else {
                    $failed++;
                }
            }

            // 2. Upload thumbnail
            if (!empty($photo['thumbnail_path'])) {
                $thumbRel = ltrim($photo['thumbnail_path'], '/');
                $thumbFull = FCPATH . $thumbRel;
                if (file_exists($thumbFull)) {
                    $gcpService->uploadFile($thumbFull, $thumbRel, 'image/jpeg');
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
