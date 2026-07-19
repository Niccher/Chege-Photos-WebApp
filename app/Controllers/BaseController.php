<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float) $bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1024 ** $pow);

        return round($bytes, $precision) . ' ' . $units[(int) $pow];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSidebarCounts(): array
    {
        $userId   = auth()->id();
        $cacheKey = "sidebar_counts_{$userId}";

        if ($cached = cache($cacheKey)) {
            return $cached;
        }

        $photoModel = new \App\Models\PhotoModel();
        $linkModel  = new \App\Models\SharedLinkModel();
        $shareModel = new \App\Models\PhotoShareModel();

        $photosCount = $photoModel->where('user_id', $userId)->where('is_archived', false)->countAllResults();
        $exploreCount = $photoModel->where('user_id', $userId)->where('is_archived', false)
            ->where('latitude IS NOT NULL')->where('longitude IS NOT NULL')->countAllResults();

        $publicLinkCount   = $linkModel->join('photos', 'photos.id = shared_links.photo_id')
            ->where('photos.user_id', $userId)->countAllResults();
        $sharedWithMeCount = $shareModel->where('shared_with', $userId)->countAllResults();

        $archiveCount   = $photoModel->where('user_id', $userId)->where('is_archived', true)->countAllResults();
        $trashCount     = $photoModel->where('user_id', $userId)->onlyDeleted()->countAllResults();
        $favoritesCount = $photoModel->where('user_id', $userId)->where('is_favorite', true)->where('is_archived', false)->countAllResults();

        $today        = date('m-d');
        $thisYear     = date('Y');
        $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));

        $memoriesCount = $photoModel->where('user_id', $userId)
            ->where('is_archived', false)
            ->groupStart()
            ->where("DATE_FORMAT(taken_at, '%m-%d') =", $today)
            ->where('YEAR(taken_at) <', $thisYear)
            ->groupEnd()
            ->orWhere('DATE(taken_at) =', $sixMonthsAgo)
            ->countAllResults();

        $albumModel  = new \App\Models\AlbumModel();
        $albumsCount = $albumModel->where('user_id', $userId)->countAllResults();

        $counts = [
            'photos'          => $photosCount,
            'explore'         => $exploreCount,
            'sharing'         => $publicLinkCount + $sharedWithMeCount,
            'favorites'       => $favoritesCount,
            'albums'          => $albumsCount,
            'memories'        => $memoriesCount,
            'archive'         => $archiveCount,
            'trash'           => $trashCount,
            'sidebar_albums'  => $albumModel->where('user_id', $userId)->orderBy('name', 'ASC')->findAll(),
        ];

        cache()->save($cacheKey, $counts, 10);

        return $counts;
    }

    protected function clearSidebarCountsCache(?int $userId = null): void
    {
        $userId ??= auth()->id();
        if ($userId) {
            cache()->delete("sidebar_counts_{$userId}");
        }
    }
}
