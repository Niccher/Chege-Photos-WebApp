<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PhotoModel;

class MigrateStorageStructure extends BaseCommand
{
    protected $group       = 'Media';
    protected $name        = 'photos:migrate-storage';
    protected $description = 'Migrates existing photo and thumbnail files into the structured /writable/uploads/users/{id}/{YYYY}/{MM}/ hierarchy.';
    protected $usage       = 'photos:migrate-storage [--dry-run]';

    public function run(array $params)
    {
        $dryRun = in_array('--dry-run', $params, true);
        if ($dryRun) {
            CLI::write('Running in DRY-RUN mode. No files will be moved.', 'yellow');
        }

        $photoModel = new PhotoModel();
        $photos = $photoModel->findAll();
        CLI::write(sprintf("Found %d photo record(s) to inspect for storage migration...", count($photos)), 'cyan');

        $migrated = 0;
        $skipped = 0;
        $missing = 0;

        foreach ($photos as $photo) {
            $userId = (int) ($photo['user_id'] ?? 1);
            $takenAt = !empty($photo['taken_at']) ? strtotime($photo['taken_at']) : (!empty($photo['created_at']) ? strtotime($photo['created_at']) : time());
            $yearMonth = date('Y/m', $takenAt);

            $oldPath = $photo['path'];
            // Check if already in user hierarchy
            if (strpos($oldPath, 'users/') !== false) {
                $skipped++;
                continue;
            }

            // Find current source file location
            $sourceFile = null;
            if (is_file(FCPATH . $oldPath)) {
                $sourceFile = FCPATH . $oldPath;
            } elseif (is_file(WRITEPATH . $oldPath)) {
                $sourceFile = WRITEPATH . $oldPath;
            } elseif (is_file(WRITEPATH . 'uploads/' . basename($oldPath))) {
                $sourceFile = WRITEPATH . 'uploads/' . basename($oldPath);
            }

            if (!$sourceFile || !file_exists($sourceFile)) {
                $missing++;
                continue;
            }

            // Determine new target paths
            $filename = basename($sourceFile);
            $newRelPath = "uploads/users/{$userId}/{$yearMonth}/{$filename}";
            $newFullDir = WRITEPATH . "uploads/users/{$userId}/{$yearMonth}";
            $newFullPath = "{$newFullDir}/{$filename}";

            // Migrate thumbnail
            $oldThumb = $photo['thumbnail_path'] ?? '';
            $sourceThumb = null;
            if (!empty($oldThumb)) {
                if (is_file(FCPATH . $oldThumb)) {
                    $sourceThumb = FCPATH . $oldThumb;
                } elseif (is_file(WRITEPATH . $oldThumb)) {
                    $sourceThumb = WRITEPATH . $oldThumb;
                }
            }

            $newThumbRel = null;
            if ($sourceThumb && file_exists($sourceThumb)) {
                $thumbFilename = basename($sourceThumb);
                $newThumbRel = "uploads/thumbs/users/{$userId}/{$yearMonth}/{$thumbFilename}";
                $newThumbDir = WRITEPATH . "uploads/thumbs/users/{$userId}/{$yearMonth}";
                $newThumbFull = "{$newThumbDir}/{$thumbFilename}";

                if (!$dryRun) {
                    if (!is_dir($newThumbDir)) {
                        mkdir($newThumbDir, 0775, true);
                    }
                    @copy($sourceThumb, $newThumbFull);
                }
            }

            if (!$dryRun) {
                if (!is_dir($newFullDir)) {
                    mkdir($newFullDir, 0775, true);
                }
                if (@copy($sourceFile, $newFullPath)) {
                    $updateData = ['path' => $newRelPath];
                    if ($newThumbRel) {
                        $updateData['thumbnail_path'] = $newThumbRel;
                    }
                    $photoModel->update($photo['id'], $updateData);
                    $migrated++;
                }
            } else {
                $migrated++;
            }
        }

        CLI::write(sprintf("Storage migration complete. %s %d photo(s), Skipped (already migrated): %d, Missing source: %d.",
            $dryRun ? 'Would migrate' : 'Successfully migrated',
            $migrated,
            $skipped,
            $missing
        ), 'green');

        return EXIT_SUCCESS;
    }
}
