<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CronRun extends BaseCommand
{
    protected $group       = 'System';
    protected $name        = 'cron:run';
    protected $description = 'Evaluates scheduled cron jobs and runs due targets dynamically.';
    protected $usage       = 'cron:run';

    public function run(array $params)
    {
        $now = time();
        $db  = \Config\Database::connect();

        $jobs = [
            'trash:purge'         => setting('Cron.trashPurge') ?? '0 2 * * *',
            'ml:cluster'          => setting('Cron.mlCluster') ?? '0 * * * *',
            'ml:sweep'            => setting('Cron.mlSweep') ?? '*/5 * * * *',
            'storage:clean-temp'  => setting('Cron.cleanTemp') ?? '30 1 * * *',
        ];

        CLI::write('Checking scheduled tasks at: ' . date('Y-m-d H:i:s', $now), 'cyan');

        foreach ($jobs as $commandName => $expression) {
            if ($this->isDue($expression, $now)) {
                CLI::write(sprintf('Running due job: %s (Expression: %s)...', $commandName, $expression), 'yellow');
                try {
                    // Execute CodeIgniter command programmatically
                    command($commandName);
                } catch (\Exception $e) {
                    log_message('error', 'Cron command execution failed: ' . $commandName . ' - ' . $e->getMessage());
                }
            }
        }

        return EXIT_SUCCESS;
    }

    private function isDue(string $expression, int $time): bool
    {
        $cron = explode(' ', trim($expression));
        if (count($cron) < 5) {
            return false;
        }

        $date = [
            'm'   => (int) date('i', $time),
            'h'   => (int) date('H', $time),
            'dom' => (int) date('j', $time),
            'mon' => (int) date('n', $time),
            'dow' => (int) date('w', $time),
        ];

        return $this->checkField($cron[0], $date['m'])
            && $this->checkField($cron[1], $date['h'])
            && $this->checkField($cron[2], $date['dom'])
            && $this->checkField($cron[3], $date['mon'])
            && $this->checkField($cron[4], $date['dow']);
    }

    private function checkField(string $field, int $value): bool
    {
        if ($field === '*') {
            return true;
        }

        if (strpos($field, ',') !== false) {
            $parts = explode(',', $field);
            foreach ($parts as $part) {
                if ($this->checkField($part, $value)) {
                    return true;
                }
            }
            return false;
        }

        if (strpos($field, '/') !== false) {
            list($range, $step) = explode('/', $field);
            $step = (int) $step;
            if ($range === '*') {
                return ($value % $step) === 0;
            }
            list($start, $end) = explode('-', $range);
            $start = (int) $start;
            $end = (int) $end;
            return ($value >= $start && $value <= $end && (($value - $start) % $step) === 0);
        }

        if (strpos($field, '-') !== false) {
            list($start, $end) = explode('-', $field);
            return ($value >= (int) $start && $value <= (int) $end);
        }

        return ((int) $field === $value);
    }
}
