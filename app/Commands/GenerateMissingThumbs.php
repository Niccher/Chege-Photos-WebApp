<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PhotoModel;

class GenerateMissingThumbs extends BaseCommand
{
    protected $group       = 'Media';
    protected $name        = 'photos:generate-missing-thumbs';
    protected $description = 'Detects photos with missing thumbnails on disk and automatically heals/regenerates them.';
    protected $usage       = 'photos:generate-missing-thumbs [--limit=50]';

    public function run(array $params)
    {
        $startTime = microtime(true);
        $db = \Config\Database::connect();
        $photoModel = new PhotoModel();

        $limit = 100;
        foreach ($params as $p) {
            if (strpos($p, '--limit=') === 0) {
                $limit = (int) str_replace('--limit=', '', $p);
            }
        }

        $photos = $photoModel->where('mime_type NOT LIKE', 'video/%')
                             ->orderBy('id', 'DESC')
                             ->limit($limit * 3)
                             ->findAll();

        $healed = 0;
        $checked = 0;

        foreach ($photos as $photo) {
            $relPath = $photo['path'];
            $fullPath = is_file(WRITEPATH . $relPath) ? WRITEPATH . $relPath : FCPATH . $relPath;

            if (!file_exists($fullPath)) {
                continue;
            }

            $thumbRel = $photo['thumbnail_path'];
            $thumbFull = !empty($thumbRel) ? (is_file(WRITEPATH . $thumbRel) ? WRITEPATH . $thumbRel : FCPATH . $thumbRel) : null;

            if (empty($thumbRel) || !file_exists($thumbFull)) {
                // Needs regeneration
                $newThumbRel = 'thumbnails/' . basename($fullPath);
                $newThumbFull = FCPATH . $newThumbRel;

                $thumbDir = dirname($newThumbFull);
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0775, true);
                }

                if ($this->makeThumbnail($fullPath, $newThumbFull)) {
                    $photoModel->update($photo['id'], ['thumbnail_path' => $newThumbRel]);
                    $healed++;
                    if ($healed >= $limit) {
                        break;
                    }
                }
            }
            $checked++;
        }

        $duration = round(microtime(true) - $startTime, 3);
        $outputMsg = sprintf("Thumbnail health check finished in %ss. Evaluated: %d, Regenerated: %d missing thumbnail(s).", $duration, $checked, $healed);

        $db->table('sys_cron_logs')->insert([
            'job_name'         => 'photos:generate-missing-thumbs',
            'status'           => 'success',
            'output'           => $outputMsg,
            'duration_seconds' => $duration,
            'run_at'           => date('Y-m-d H:i:s'),
        ]);

        CLI::write($outputMsg, 'green');
        return EXIT_SUCCESS;
    }

    private function makeThumbnail(string $sourcePath, string $targetPath, int $width = 300, int $height = 300): bool
    {
        try {
            $image = \Config\Services::image();
            $image->withFile($sourcePath)
                  ->fit($width, $height, 'center')
                  ->save($targetPath, 80);
            return file_exists($targetPath);
        } catch (\Throwable $e) {
            log_message('error', 'Thumbnail auto-heal failed for ' . $sourcePath . ': ' . $e->getMessage());
            return false;
        }
    }
}
