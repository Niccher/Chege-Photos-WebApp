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

    public function run(array $params)
    {
        $startTime  = microtime(true);
        $db         = \Config\Database::connect();
        $photoModel = new \App\Models\PhotoModel();
        helper('ml');
        $mlUrl      = get_ml_url();

        $photos = $photoModel->select('id, scanned_face, scanned_tag, scanned_clip')
            ->groupStart()
                ->where('scanned_face', 0)
                ->orWhere('scanned_tag', 0)
                ->orWhere('scanned_clip', 0)
            ->groupEnd()
            ->limit(50)
            ->findAll();

        $total = count($photos);
        if ($total === 0) {
            CLI::write('No unprocessed photos found.', 'green');
            return EXIT_SUCCESS;
        }

        try {
            $client = service('curlrequest', [
                'connect_timeout' => 4,
                'timeout'         => 8,
                'headers'         => [
                    'X-API-KEY' => get_ml_api_key(),
                ]
            ]);

            $queued = 0;
            foreach ($photos as $p) {
                $photoId = (int) $p['id'];
                $client->post($mlUrl . '/api/v1/faces/encode', [
                    'headers' => [
                        'X-API-KEY' => env('ML_API_KEY') ?: 'my_super_secret_shared_token_key_123!'
                    ],
                    'form_params' => [
                        'photo_id'   => $photoId,
                        'scan_faces' => $p['scanned_face'] == 0 ? 1 : 0,
                        'scan_tags'  => $p['scanned_tag'] == 0 ? 1 : 0,
                        'scan_clip'  => $p['scanned_clip'] == 0 ? 1 : 0,
                        'async_task' => 1,
                    ]
                ]);
                $queued++;
            }

            $duration = microtime(true) - $startTime;
            $outputMsg = "Successfully queued {$queued} of {$total} unprocessed photos for background analysis.";

            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'ml:sweep',
                'status'           => 'success',
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
