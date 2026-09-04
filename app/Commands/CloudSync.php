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
    protected $usage       = 'cloud:sync [--all] [--pending] [--hydrate] [--thumbnails-only]';

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

        $syncAll        = in_array('--all', $params, true);
        $syncPending    = in_array('--pending', $params, true);
        $isHydrate      = in_array('--hydrate', $params, true);
        $thumbnailsOnly = in_array('--thumbnails-only', $params, true);
        $photoModel     = new PhotoModel();

        // -------------------------------------------------------------
        // MODE 1: HYDRATE (Download missing files from GCP to Railway)
        // -------------------------------------------------------------
        if ($isHydrate) {
            $scopeText = $thumbnailsOnly ? 'thumbnails only' : 'media & thumbnails';
            CLI::write("Running GCP Container Hydration ({$scopeText})...", 'cyan');

            $photos = $photoModel->orderBy('id', 'DESC')->findAll();
            $hydratedPhotos = 0;
            $hydratedThumbs = 0;
            $failed = 0;

            foreach ($photos as $photo) {
                $uploadRel = ltrim($photo['path'], '/');
                $uploadLocal = FCPATH . $uploadRel;

                // 1. Download original media (unless thumbnails-only)
                if (!$thumbnailsOnly) {
                    if (!file_exists($uploadLocal) || filesize($uploadLocal) === 0) {
                        if ($gcpService->downloadFile($uploadRel, $uploadLocal)) {
                            $hydratedPhotos++;
                            $photoModel->update($photo['id'], [
                                'gcp_synced'    => 1,
                                'gcp_synced_at' => date('Y-m-d H:i:s')
                            ]);
                        } else {
                            $failed++;
                        }
                    }
                }

                // 2. Download thumbnail
                if (!empty($photo['thumbnail_path'])) {
                    $thumbRel = ltrim($photo['thumbnail_path'], '/');
                    $thumbLocal = FCPATH . $thumbRel;
                    if (!file_exists($thumbLocal) || filesize($thumbLocal) === 0) {
                        if ($gcpService->downloadFile($thumbRel, $thumbLocal)) {
                            $hydratedThumbs++;
                        } elseif (file_exists($uploadLocal) && filesize($uploadLocal) > 0) {
                            // Regenerate thumbnail from local upload if available
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

        // -------------------------------------------------------------
        // MODE 2: UPLOAD / MIRROR (Push local files to GCP)
        // -------------------------------------------------------------
        $query = $photoModel->orderBy('id', 'DESC');
        if ($syncPending) {
            $query->groupStart()
                  ->where('gcp_synced', 0)
                  ->orWhere('gcp_synced IS NULL', null, false)
                  ->groupEnd();
        } elseif (!$syncAll) {
            // Default: past 24 hours
            $yesterday = date('Y-m-d H:i:s', time() - 86400);
            $query->where('created_at >=', $yesterday);
        }

        $photos = $query->findAll();
        $synced = 0;
        $failed = 0;

        CLI::write(sprintf("Found %d photo(s) to mirror to Google Cloud Storage...", count($photos)), 'cyan');

        foreach ($photos as $photo) {
            $relPath = ltrim($photo['path'], '/');
            $fullPath = is_file(WRITEPATH . $relPath) ? WRITEPATH . $relPath : FCPATH . $relPath;
            $uploadSuccess = false;

            if (file_exists($fullPath)) {
                $res = $gcpService->uploadFile($fullPath, $relPath, $photo['mime_type'] ?? null);
                if (!empty($res['success'])) {
                    $synced++;
                    $uploadSuccess = true;
                    $photoModel->update($photo['id'], [
                        'storage_driver' => 'hybrid',
                        'gcp_synced'     => 1,
                        'gcp_synced_at'  => date('Y-m-d H:i:s')
                    ]);
                } else {
                    $failed++;
                }
            }

            // Also mirror thumbnail
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
