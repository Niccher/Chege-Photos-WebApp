<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MlSweep extends BaseCommand
{
    protected $group       = 'ML';
    protected $name        = 'ml:sweep';
    protected $description = 'Sweeps database for unprocessed images and queues them for ML pipeline processing.';
    protected $usage       = 'ml:sweep';

    private const BATCH_SIZE = 100; // photos per iteration

    public function run(array $params)
    {
        $startTime  = microtime(true);
        $db         = \Config\Database::connect();
        $photoModel = new \App\Models\PhotoModel();
        helper('ml');
        $mlUrl      = get_ml_url();
        $mlKey      = get_ml_api_key();

        $photos = $photoModel->select('id, scanned_face, scanned_tag, scanned_clip, scanned_nsfw')
            ->groupStart()
                ->where('scanned_face', 0)
                ->orWhere('scanned_tag', 0)
                ->orWhere('scanned_clip', 0)
                ->orWhere('scanned_nsfw', 0)
            ->groupEnd()
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll(); // no limit — process all pending

        $total = count($photos);
        if ($total === 0) {
            CLI::write('No unprocessed photos found.', 'green');
            return EXIT_SUCCESS;
        }

        $queued = 0;
        $errors = 0;

        try {
            helper('ml');
            $webappUrl = function_exists('get_webapp_url') ? get_webapp_url() : rtrim(base_url(), '/');

            // Use a very short timeout (fire-and-forget): the ML service queues the job
            // asynchronously so we only need the request to be accepted, not completed.
            $client = service('curlrequest', [
                'connect_timeout' => 2,
                'timeout'         => 3,  // fire-and-forget — ML handles it async
                'headers'         => [
                    'X-API-KEY'    => $mlKey,
                    'X-Webapp-Url' => $webappUrl,
                ]
            ]);

            foreach ($photos as $p) {
                try {
                    $client->post($mlUrl . '/api/v1/faces/encode', [
                        'form_params' => [
                            'photo_id'   => (int) $p['id'],
                            'scan_faces' => $p['scanned_face'] == 0 ? 1 : 0,
                            'scan_tags'  => $p['scanned_tag']  == 0 ? 1 : 0,
                            'scan_clip'  => $p['scanned_clip'] == 0 ? 1 : 0,
                            'scan_nsfw'  => ($p['scanned_nsfw'] ?? 0) == 0 ? 1 : 0,
                            'async_task' => 1,
                            'webapp_url' => $webappUrl,
                        ]
                    ]);
                    $queued++;
                } catch (\Exception $e) {
                    // Timeout is expected (fire-and-forget) — only count real errors
                    if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Operation timed out')) {
                        $queued++;  // accepted but timed out waiting — treat as queued
                    } else {
                        $errors++;
                        log_message('warning', "ml:sweep could not queue photo {$p['id']}: " . $e->getMessage());
                    }
                }
            }

            $duration  = microtime(true) - $startTime;
            $outputMsg = "Queued {$queued}/{$total} photos for background ML processing." .
                         ($errors > 0 ? " ({$errors} errors)" : '');

            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'ml:sweep',
                'status'           => $errors === $total ? 'failed' : 'success',
                'output'           => $outputMsg,
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            CLI::write($outputMsg, 'green');
            return EXIT_SUCCESS;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'ml:sweep',
                'status'           => 'failed',
                'output'           => 'Sweep execution failed: ' . $e->getMessage(),
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            CLI::error('Sweep failed: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}
