<?php

namespace App\Controllers;

use App\Services\GcpStorageService;
use CodeIgniter\HTTP\ResponseInterface;

class MediaFallback extends BaseController
{
    /**
     * Serves an upload file or fetches it from GCP if missing locally.
     */
    public function serveUpload(...$segments)
    {
        return $this->serveMedia('uploads', $segments);
    }

    /**
     * Serves a thumbnail file or fetches/regenerates it if missing locally.
     */
    public function serveThumbnail(...$segments)
    {
        return $this->serveMedia('thumbnails', $segments);
    }

    /**
     * Core handler for on-demand media serving, GCP hydration, and automatic thumbnail regeneration.
     */
    protected function serveMedia(string $type, array $segments)
    {
        // 1. Sanitize segments to prevent directory traversal attacks while preserving directory hierarchy
        $cleanSegments = [];
        foreach ($segments as $segment) {
            $parts = explode('/', (string) $segment);
            foreach ($parts as $part) {
                $part = str_replace(["\0", '\\'], '', trim($part));
                if ($part === '' || $part === '.' || $part === '..') {
                    continue;
                }
                $cleanSegments[] = basename($part);
            }
        }

        if (empty($cleanSegments)) {
            return $this->response->setStatusCode(400)->setBody('Bad Request: Invalid media path');
        }

        $subPath = implode('/', $cleanSegments);
        $relativePath = "{$type}/{$subPath}";
        $localPath = FCPATH . $relativePath;

        // 2. If file already exists locally on disk, stream it immediately
        if (file_exists($localPath) && is_file($localPath) && filesize($localPath) > 0) {
            return $this->streamFile($localPath);
        }

        // 3. File is missing locally (e.g. fresh Railway container startup) -> Check GCP
        $gcp = new GcpStorageService();

        if ($gcp->isConfigured()) {
            $targetDir = dirname($localPath);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0777, true);
            }

            // Attempt to hydrate requested file directly from GCP
            $downloaded = $gcp->downloadFile($relativePath, $localPath);
            if ($downloaded && file_exists($localPath) && filesize($localPath) > 0) {
                if ($type === 'uploads') {
                    try {
                        $photoModel = new \App\Models\PhotoModel();
                        $photoModel->where('path', $relativePath)->set(['gcp_synced' => 1, 'gcp_synced_at' => date('Y-m-d H:i:s')])->update();
                    } catch (\Throwable $e) {
                        // Non-blocking
                    }
                }
                return $this->streamFile($localPath);
            }
        }

        // 4. If missing item is a thumbnail, attempt automatic regeneration from original upload
        if ($type === 'thumbnails') {
            $origRel = "uploads/{$subPath}";
            $origLocal = FCPATH . $origRel;

            // If original upload not local, try downloading original from GCP
            if (!file_exists($origLocal) && $gcp->isConfigured()) {
                $gcp->downloadFile($origRel, $origLocal);
            }

            if (file_exists($origLocal) && is_file($origLocal) && filesize($origLocal) > 0) {
                $targetDir = dirname($localPath);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }

                $thumbGenerated = false;
                try {
                    \Config\Services::image()
                        ->withFile($origLocal)
                        ->resize(400, 400, true, 'height')
                        ->save($localPath);
                    $thumbGenerated = file_exists($localPath);
                } catch (\Throwable $e) {
                    $thumbGenerated = @copy($origLocal, $localPath);
                }

                if ($thumbGenerated && file_exists($localPath)) {
                    // Mirror regenerated thumbnail to GCP in background
                    if ($gcp->isConfigured()) {
                        try {
                            $gcp->uploadFile($localPath, $relativePath);
                        } catch (\Throwable $e) {
                            log_message('warning', 'Failed to mirror regenerated thumb to GCP: ' . $e->getMessage());
                        }
                    }
                    return $this->streamFile($localPath);
                }
            }
        }

        // 5. File not found anywhere
        return $this->response->setStatusCode(404)->setBody('Media file not found');
    }

    /**
     * Efficiently streams file to client with HTTP 304 caching and HTTP 206 Partial Content (Byte Range) support.
     */
    protected function streamFile(string $filePath)
    {
        $fileSize = filesize($filePath);
        $fileMtime = filemtime($filePath);
        $etag = '"' . md5($fileMtime . '_' . $fileSize) . '"';

        // Conditional HTTP caching check
        $clientEtag = $this->request->getHeaderLine('If-None-Match');
        $clientSince = $this->request->getHeaderLine('If-Modified-Since');

        if ($clientEtag === $etag || ($clientSince && strtotime($clientSince) >= $fileMtime)) {
            return $this->response->setStatusCode(304);
        }

        $mimeType = $this->detectMimeType($filePath);

        // Handle HTTP Range header (crucial for video seeking and playback in modern browsers and mobile apps)
        $rangeHeader = $this->request->getHeaderLine('Range');
        if (!empty($rangeHeader) && preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $rangeHeader, $matches)) {
            $start = (int) $matches[1];
            $end   = !empty($matches[2]) ? (int) $matches[2] : ($fileSize - 1);

            if ($start > $end || $start >= $fileSize) {
                return $this->response
                    ->setStatusCode(416)
                    ->setHeader('Content-Range', "bytes */{$fileSize}");
            }

            $length = $end - $start + 1;

            $this->response
                ->setStatusCode(206)
                ->setHeader('Content-Type', $mimeType)
                ->setHeader('Content-Range', "bytes {$start}-{$end}/{$fileSize}")
                ->setHeader('Content-Length', (string) $length)
                ->setHeader('Accept-Ranges', 'bytes')
                ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
                ->setHeader('ETag', $etag)
                ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $fileMtime));

            $this->response->sendHeaders();

            // Direct stream slice
            $fp = fopen($filePath, 'rb');
            if ($fp) {
                fseek($fp, $start);
                $remaining = $length;
                while (!feof($fp) && $remaining > 0 && connection_status() === CONNECTION_NORMAL) {
                    $chunk = fread($fp, min(65536, $remaining));
                    echo $chunk;
                    flush();
                    $remaining -= strlen($chunk);
                }
                fclose($fp);
            }
            exit;
        }

        // Full file stream
        $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Length', (string) $fileSize)
            ->setHeader('Accept-Ranges', 'bytes')
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setHeader('ETag', $etag)
            ->setHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $fileMtime));

        $this->response->sendHeaders();
        readfile($filePath);
        exit;
    }

    /**
     * Determines accurate MIME type from extension or file content.
     */
    protected function detectMimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'svg'  => 'image/svg+xml',
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'mov'  => 'video/quicktime',
            'm4v'  => 'video/x-m4v',
            'mkv'  => 'video/x-matroska',
            'avi'  => 'video/x-msvideo',
        ];

        if (isset($mimes[$ext])) {
            return $mimes[$ext];
        }

        $detected = @mime_content_type($filePath);
        return $detected ?: 'application/octet-stream';
    }
}
