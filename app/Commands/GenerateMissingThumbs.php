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

        $photos = $photoModel->orderBy('id', 'DESC')
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

            if (empty($thumbRel) || !file_exists($thumbFull) || (file_exists($thumbFull) && filesize($thumbFull) === 0)) {
                // Needs regeneration
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $isVideo = strpos($photo['mime_type'] ?? '', 'video/') === 0 || in_array($ext, ['mp4', 'mov', 'm4v', 'webm', 'mkv', 'avi']);
                $isHeic = in_array($ext, ['heic', 'heif']);
                $thumbExt = ($isVideo || $isHeic) ? '.jpg' : '.' . $ext;

                $newThumbRel = 'thumbnails/' . pathinfo($fullPath, PATHINFO_FILENAME) . $thumbExt;
                $newThumbFull = FCPATH . $newThumbRel;

                $thumbDir = dirname($newThumbFull);
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0775, true);
                }

                if ($this->makeThumbnail($fullPath, $newThumbFull, $isVideo, $isHeic)) {
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

    private function makeThumbnail(string $sourcePath, string $targetPath, bool $isVideo = false, bool $isHeic = false, int $width = 400, int $height = 400): bool
    {
        try {
            if ($isVideo) {
                return \App\Controllers\Photos::generateVideoThumbnail($sourcePath, $targetPath);
            }

            if ($isHeic) {
                $tmpJpg = sys_get_temp_dir() . '/' . uniqid('heic_cmd_', true) . '.jpg';
                @exec(sprintf('heif-convert %s %s 2>&1', escapeshellarg($sourcePath), escapeshellarg($tmpJpg)));
                if (file_exists($tmpJpg) && filesize($tmpJpg) > 0) {
                    $image = \Config\Services::image();
                    $image->withFile($tmpJpg)
                          ->fit($width, $height, 'center')
                          ->save($targetPath, 80);
                    @unlink($tmpJpg);
                    return file_exists($targetPath) && filesize($targetPath) > 0;
                }
            }

            $image = \Config\Services::image();
            $image->withFile($sourcePath)
                  ->fit($width, $height, 'center')
                  ->save($targetPath, 80);
            return file_exists($targetPath) && filesize($targetPath) > 0;
        } catch (\Throwable $e) {
            log_message('error', 'Thumbnail auto-heal failed for ' . $sourcePath . ': ' . $e->getMessage());
            return false;
        }
    }
}
