<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MlCluster extends BaseCommand
{
    protected $group       = 'ML';
    protected $name        = 'ml:cluster';
    protected $description = 'Triggers HDBSCAN face clustering on the ML engine and records stats.';
    protected $usage       = 'ml:cluster';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db        = \Config\Database::connect();
        helper('ml');
        $mlUrl     = get_ml_url();

        try {
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 180,
                'headers'         => [
                    'X-API-KEY' => get_ml_api_key(),
                ]
            ]);

            $response = $client->post($mlUrl . '/api/v1/faces/cluster');
            $body = json_decode($response->getBody(), true);
            $outputMsg = isset($body['message']) ? $body['message'] : 'Face clustering triggered successfully.';

            $duration = microtime(true) - $startTime;
            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'ml:cluster',
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
                'job_name'         => 'ml:cluster',
                'status'           => 'failed',
                'output'           => 'Failed: ' . $e->getMessage(),
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            CLI::error('Clustering failed: ' . $e->getMessage());
            return EXIT_ERROR;
        }
    }
}
