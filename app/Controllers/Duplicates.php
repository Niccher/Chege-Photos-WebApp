<?php

namespace App\Controllers;

use App\Models\PhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Duplicates extends BaseController
{
    /**
     * View duplicate photo groups
     */
    public function index()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();

        // Find duplicate hash groups where count > 1
        $groups = $db->table('tbl_photos')
            ->select('file_hash, COUNT(*) as photo_count, SUM(size) as total_bytes, MIN(id) as oldest_id, MAX(id) as newest_id')
            ->where('user_id', $userId)
            ->where('deleted_at IS NULL', null, false)
            ->where('file_hash IS NOT NULL', null, false)
            ->where('file_hash !=', '')
            ->groupBy('file_hash')
            ->having('photo_count >', 1)
            ->orderBy('total_bytes', 'DESC')
            ->get()
            ->getResultArray();

        $duplicateSets = [];
        $totalDuplicateFiles = 0;
        $totalReclaimableBytes = 0;

        foreach ($groups as $g) {
            $hash = $g['file_hash'];
            $photos = $db->table('tbl_photos')
                ->where('user_id', $userId)
                ->where('deleted_at IS NULL', null, false)
                ->where('file_hash', $hash)
                ->orderBy('taken_at', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $count = count($photos);
            if ($count < 2) continue;

            $singleSize = (int)($photos[0]['size'] ?? 0);
            $reclaimable = ($count - 1) * $singleSize;
            $totalDuplicateFiles += ($count - 1);
            $totalReclaimableBytes += $reclaimable;

            $duplicateSets[] = [
                'hash'              => $hash,
                'count'             => $count,
                'single_size'       => $singleSize,
                'reclaimable_bytes' => $reclaimable,
                'photos'            => $photos,
            ];
        }

        $data = [
            'title'                 => 'Duplicate Photos',
            'duplicateSets'         => $duplicateSets,
            'totalGroups'           => count($duplicateSets),
            'totalDuplicates'       => $totalDuplicateFiles,
            'totalReclaimableBytes' => $totalReclaimableBytes,
            'counts'                => $this->getSidebarCounts(),
        ];

        return view('photos/duplicates', $data);
    }

    /**
     * Trash a single duplicate photo
     */
    public function apiTrashDuplicate(int $id)
    {
        $userId = auth()->id();
        $photoModel = new PhotoModel();
        $photo = $photoModel->where('user_id', $userId)->find($id);

        if (! $photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found']);
        }

        $photoModel->delete($id); // soft delete
        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Photo moved to Trash'
        ]);
    }

    /**
     * Automatically clean all duplicates
     * Strategy: 'keep_oldest' (default) or 'keep_newest'
     */
    public function apiAutoClean()
    {
        $userId = auth()->id();
        $strategy = $this->request->getPost('strategy') ?: 'keep_oldest';
        $db = \Config\Database::connect();
        $photoModel = new PhotoModel();

        $groups = $db->table('tbl_photos')
            ->select('file_hash, COUNT(*) as photo_count')
            ->where('user_id', $userId)
            ->where('deleted_at IS NULL', null, false)
            ->where('file_hash IS NOT NULL', null, false)
            ->where('file_hash !=', '')
            ->groupBy('file_hash')
            ->having('photo_count >', 1)
            ->get()
            ->getResultArray();

        $trashedCount = 0;
        $reclaimedBytes = 0;

        foreach ($groups as $g) {
            $order = ($strategy === 'keep_newest') ? 'DESC' : 'ASC';
            $photos = $db->table('tbl_photos')
                ->where('user_id', $userId)
                ->where('deleted_at IS NULL', null, false)
                ->where('file_hash', $g['file_hash'])
                ->orderBy('taken_at', $order)
                ->orderBy('id', $order)
                ->get()
                ->getResultArray();

            if (count($photos) < 2) continue;

            // Keep index 0, trash indices 1..end
            for ($i = 1; $i < count($photos); $i++) {
                $pid = (int)$photos[$i]['id'];
                $size = (int)($photos[$i]['size'] ?? 0);
                $photoModel->delete($pid);
                $trashedCount++;
                $reclaimedBytes += $size;
            }
        }

        return $this->response->setJSON([
            'status'          => 'success',
            'message'         => "Successfully moved {$trashedCount} duplicate photos to Trash.",
            'trashed_count'   => $trashedCount,
            'reclaimed_bytes' => $reclaimedBytes,
        ]);
    }
}
