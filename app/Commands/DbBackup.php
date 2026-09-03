<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\GcpStorageService;

class DbBackup extends BaseCommand
{
    protected $group       = 'System';
    protected $name        = 'db:backup';
    protected $description = 'Creates a compressed MySQL database backup and syncs to Google Cloud Storage with auto-pruning.';
    protected $usage       = 'db:backup [--no-cloud]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $now = date('Y-m-d_His');
        $db = \Config\Database::connect();

        $backupDir = WRITEPATH . 'backups/database/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $filename = "db_backup_{$now}.sql.gz";
        $localPath = $backupDir . $filename;

        CLI::write("Starting database backup: {$filename}...", 'cyan');

        try {
            // 1. Dump database to gzipped archive
            $this->dumpDatabase($localPath);
            $fileSize = filesize($localPath);
            $formattedSize = $this->formatBytes($fileSize);
            CLI::write("Database archive created locally: {$formattedSize}", 'green');

            $cloudStatus = 'Local only';
            $gcpService = new GcpStorageService();

            // 2. Sync to GCP if configured
            if ($gcpService->isConfigured() && !in_array('--no-cloud', $params, true)) {
                CLI::write("Uploading to Google Cloud Storage (Bucket: {$gcpService->getBucket()})...", 'yellow');
                $cloudPath = "backups/database/{$filename}";
                $uploadRes = $gcpService->uploadFile($localPath, $cloudPath, 'application/gzip');

                if ($uploadRes['success']) {
                    CLI::write("Successfully streamed to gs://{$gcpService->getBucket()}/{$cloudPath}", 'green');
                    $cloudStatus = "Synced to GCP (gs://{$gcpService->getBucket()}/{$cloudPath})";

                    // 3. Prune GCP cloud backups older than retention days
                    $pruneRes = $gcpService->pruneBackups('backups/database/');
                    if (!empty($pruneRes['deleted_count'])) {
                        CLI::write(sprintf("Pruned %d expired cloud backup(s) older than %d days.", $pruneRes['deleted_count'], $pruneRes['retention_days']), 'yellow');
                    }
                } else {
                    CLI::error("GCP upload failed: " . ($uploadRes['error'] ?? 'Unknown error'));
                    $cloudStatus = "GCP upload failed: " . ($uploadRes['error'] ?? 'Unknown');
                }
            }

            // 4. Prune local backups older than retention days
            $retentionDays = (int) (setting('Storage.backupRetentionDays') ?? 30);
            $this->pruneLocalBackups($backupDir, $retentionDays);

            $duration = round(microtime(true) - $startTime, 3);
            $outputMsg = "Database backup completed in {$duration}s. Size: {$formattedSize}. Status: {$cloudStatus}";

            // 5. Log execution
            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'db:backup',
                'status'           => 'success',
                'output'           => $outputMsg,
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            CLI::write($outputMsg, 'green');
            return EXIT_SUCCESS;
        } catch (\Throwable $e) {
            $duration = round(microtime(true) - $startTime, 3);
            $errMsg = "Database backup failed: " . $e->getMessage();

            $db->table('sys_cron_logs')->insert([
                'job_name'         => 'db:backup',
                'status'           => 'failed',
                'output'           => $errMsg,
                'duration_seconds' => $duration,
                'run_at'           => date('Y-m-d H:i:s'),
            ]);

            CLI::error($errMsg);
            return EXIT_ERROR;
        }
    }

    /**
     * Dumps database either via mysqldump or direct PDO exporter.
     */
    private function dumpDatabase(string $outputPath): void
    {
        $db = \Config\Database::connect();
        $hostname = $db->hostname;
        $port     = $db->port ?: 3306;
        $username = $db->username;
        $password = $db->password;
        $database = $db->database;

        // Try native mysqldump CLI first if binary exists
        $mysqldump = shell_exec('which mysqldump 2>/dev/null');
        if (!empty($mysqldump)) {
            $cmd = sprintf(
                'mysqldump --host=%s --port=%d --user=%s --password=%s --single-transaction --quick %s | gzip > %s',
                escapeshellarg($hostname),
                $port,
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($outputPath)
            );
            $returnVar = 0;
            system($cmd, $returnVar);
            if ($returnVar === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
                return;
            }
        }

        // Reliable pure PHP PDO fallback dump
        $gz = gzopen($outputPath, 'w9');
        if (!$gz) {
            throw new \Exception("Cannot write to backup file: {$outputPath}");
        }

        gzwrite($gz, "-- Chege Photos Database Backup\n");
        gzwrite($gz, "-- Generated at: " . date('Y-m-d H:i:s') . "\n");
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $db->listTables();
        foreach ($tables as $table) {
            // Get CREATE TABLE
            $createTableQuery = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            $createSql = $createTableQuery['Create Table'] ?? '';
            gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");
            gzwrite($gz, $createSql . ";\n\n");

            // Chunk and dump data
            $rows = $db->table($table)->get()->getResultArray();
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $columns) . '`';

                foreach (array_chunk($rows, 100) as $chunk) {
                    $valuesList = [];
                    foreach ($chunk as $row) {
                        $escaped = array_map(function ($val) use ($db) {
                            if ($val === null) return 'NULL';
                            return $db->escape($val);
                        }, $row);
                        $valuesList[] = '(' . implode(', ', $escaped) . ')';
                    }
                    gzwrite($gz, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valuesList) . ";\n");
                }
                gzwrite($gz, "\n");
            }
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
    }

    private function pruneLocalBackups(string $dir, int $days): void
    {
        if ($days <= 0) return;
        $cutoff = time() - ($days * 86400);

        foreach (glob($dir . '*.sql.gz') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
