<?php

namespace App\Libraries;

use App\Models\PhotoModel;
use CodeIgniter\Model;

class SmartAlbumRules
{
    public const MIME_ANY = 'any';

    public const MIME_IMAGE = 'image';

    public const MIME_VIDEO = 'video';

    /**
     * Preset AI collections definition
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getPresets(): array
    {
        return [
            'pets' => [
                'name'        => 'Pets & Animals',
                'description' => 'Photos featuring dogs, cats, birds, and other animals',
                'icon'        => 'bi-feather',
                'color'       => 'warning',
                'rules'       => [
                    'ai_tags' => ['dog', 'cat', 'bird', 'horse', 'sheep', 'cow', 'elephant', 'bear', 'zebra', 'giraffe'],
                ],
            ],
            'vehicles' => [
                'name'        => 'Vehicles & Transport',
                'description' => 'Cars, motorcycles, planes, trains, and boats',
                'icon'        => 'bi-car-front',
                'color'       => 'info',
                'rules'       => [
                    'ai_tags' => ['car', 'motorcycle', 'airplane', 'bus', 'train', 'truck', 'boat', 'bicycle'],
                ],
            ],
            'food' => [
                'name'        => 'Food & Dining',
                'description' => 'Meals, dishes, drinks, and dining moments',
                'icon'        => 'bi-cup-hot',
                'color'       => 'danger',
                'rules'       => [
                    'ai_tags' => ['bottle', 'wine glass', 'cup', 'fork', 'knife', 'spoon', 'bowl', 'banana', 'apple', 'sandwich', 'orange', 'broccoli', 'carrot', 'hot dog', 'pizza', 'donut', 'cake'],
                ],
            ],
            'documents' => [
                'name'        => 'Documents & Notes',
                'description' => 'Books, papers, receipts, and text documents',
                'icon'        => 'bi-file-earmark-text',
                'color'       => 'secondary',
                'rules'       => [
                    'ai_tags' => ['book', 'paper', 'document', 'text'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'date_from'         => null,
            'date_to'           => null,
            'camera_contains'   => '',
            'has_gps'           => false,
            'min_latitude'      => null,
            'max_latitude'      => null,
            'min_longitude'     => null,
            'max_longitude'     => null,
            'favorite_only'     => false,
            'mime_kind'         => self::MIME_ANY,
            'ai_tags'           => [],
            'person_id'         => null,
        ];
    }

    /**
     * @param array<string, mixed>|null $raw
     * @return array<string, mixed>
     */
    public static function fromArray(?array $raw): array
    {
        $d = self::defaults();
        if ($raw === null || $raw === []) {
            return $d;
        }

        $mime = $raw['mime_kind'] ?? self::MIME_ANY;
        if (! in_array($mime, [self::MIME_ANY, self::MIME_IMAGE, self::MIME_VIDEO], true)) {
            $mime = self::MIME_ANY;
        }

        $aiTags = $raw['ai_tags'] ?? [];
        if (is_string($aiTags)) {
            $aiTags = array_filter(array_map('trim', explode(',', $aiTags)));
        }

        return [
            'date_from'       => self::sanitizeDate($raw['date_from'] ?? null),
            'date_to'         => self::sanitizeDate($raw['date_to'] ?? null),
            'camera_contains' => self::sanitizeCamera($raw['camera_contains'] ?? ''),
            'has_gps'         => filter_var($raw['has_gps'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'min_latitude'    => self::sanitizeFloat($raw['min_latitude'] ?? null, -90, 90),
            'max_latitude'    => self::sanitizeFloat($raw['max_latitude'] ?? null, -90, 90),
            'min_longitude'   => self::sanitizeFloat($raw['min_longitude'] ?? null, -180, 180),
            'max_longitude'   => self::sanitizeFloat($raw['max_longitude'] ?? null, -180, 180),
            'favorite_only'   => filter_var($raw['favorite_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'mime_kind'       => $mime,
            'ai_tags'         => is_array($aiTags) ? array_values($aiTags) : [],
            'person_id'       => !empty($raw['person_id']) ? (int)$raw['person_id'] : null,
        ];
    }

    public static function fromJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return self::defaults();
        }
        $decoded = json_decode($json, true);

        return self::fromArray(is_array($decoded) ? $decoded : null);
    }

    /**
     * @param array<string, mixed> $r
     */
    public static function hasActiveCriteria(array $r): bool
    {
        if (! empty($r['date_from']) || ! empty($r['date_to'])) {
            return true;
        }
        if (trim((string) ($r['camera_contains'] ?? '')) !== '') {
            return true;
        }
        if (! empty($r['has_gps'])) {
            return true;
        }
        if ($r['min_latitude'] !== null || $r['max_latitude'] !== null
            || $r['min_longitude'] !== null || $r['max_longitude'] !== null) {
            return true;
        }
        if (! empty($r['favorite_only'])) {
            return true;
        }
        if (($r['mime_kind'] ?? self::MIME_ANY) !== self::MIME_ANY) {
            return true;
        }
        if (! empty($r['ai_tags']) || ! empty($r['person_id'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $r
     */
    public static function validateForSave(array $r): ?string
    {
        if (! self::hasActiveCriteria($r)) {
            return 'Choose at least one rule: a date range, camera text, GPS, favorites only, photo/video type, or AI tags.';
        }
        $from = $r['date_from'] ?? null;
        $to   = $r['date_to'] ?? null;
        if ($from && $to && $from > $to) {
            return 'The start date must be on or before the end date.';
        }
        if (($r['min_latitude'] ?? null) !== null && ($r['max_latitude'] ?? null) !== null
            && $r['min_latitude'] > $r['max_latitude']) {
            return 'Minimum latitude must be on or below the maximum latitude.';
        }
        if (($r['min_longitude'] ?? null) !== null && ($r['max_longitude'] ?? null) !== null
            && $r['min_longitude'] > $r['max_longitude']) {
            return 'Minimum longitude must be on or below the maximum longitude.';
        }

        return null;
    }

    /**
     * Apply rules to a photo query (caller must scope user_id).
     *
     * @param array<string, mixed> $rules
     */
    public static function apply(Model $photoModel, array $rules): void
    {
        $photoModel->where('is_archived', false);
        $photoModel->where('is_vault', 0);

        if (! empty($rules['date_from'])) {
            $photoModel->where('taken_at >=', $rules['date_from'] . ' 00:00:00');
        }
        if (! empty($rules['date_to'])) {
            $photoModel->where('taken_at <=', $rules['date_to'] . ' 23:59:59');
        }

        $cam = trim((string) ($rules['camera_contains'] ?? ''));
        if ($cam !== '') {
            $photoModel->like('exif_data', $cam, 'both');
        }

        if (! empty($rules['has_gps'])) {
            $photoModel->where('latitude IS NOT NULL', null, false)
                ->where('longitude IS NOT NULL', null, false);
        }

        if ($rules['min_latitude'] !== null) {
            $photoModel->where('latitude >=', (string) $rules['min_latitude']);
        }
        if ($rules['max_latitude'] !== null) {
            $photoModel->where('latitude <=', (string) $rules['max_latitude']);
        }
        if ($rules['min_longitude'] !== null) {
            $photoModel->where('longitude >=', (string) $rules['min_longitude']);
        }
        if ($rules['max_longitude'] !== null) {
            $photoModel->where('longitude <=', (string) $rules['max_longitude']);
        }

        if (! empty($rules['favorite_only'])) {
            $photoModel->where('is_favorite', 1);
        }

        $mime = $rules['mime_kind'] ?? self::MIME_ANY;
        if ($mime === self::MIME_IMAGE) {
            $photoModel->like('mime_type', 'image/', 'after');
        } elseif ($mime === self::MIME_VIDEO) {
            $photoModel->like('mime_type', 'video/', 'after');
        }

        // ── AI Tag Rules (YOLO detections) ──────────────────────
        if (! empty($rules['ai_tags'])) {
            $tags = is_array($rules['ai_tags']) ? $rules['ai_tags'] : array_map('trim', explode(',', (string)$rules['ai_tags']));
            $tags = array_values(array_filter($tags));
            if (! empty($tags)) {
                $db = \Config\Database::connect();
                $subRows = $db->table('tbl_photo_tags')
                    ->select('photo_id')
                    ->whereIn('tag', $tags)
                    ->get()
                    ->getResultArray();
                $matchedIds = array_unique(array_column($subRows, 'photo_id'));
                if (! empty($matchedIds)) {
                    $photoModel->whereIn('id', $matchedIds);
                } else {
                    $photoModel->where('id', -1); // Force empty result if no photos match tags
                }
            }
        }

        // ── Person Recognition Rule ─────────────────────────────
        if (! empty($rules['person_id'])) {
            $db = \Config\Database::connect();
            $subRows = $db->table('tbl_face_encodings')
                ->select('photo_id')
                ->where('person_id', (int)$rules['person_id'])
                ->get()
                ->getResultArray();
            $matchedIds = array_unique(array_column($subRows, 'photo_id'));
            if (! empty($matchedIds)) {
                $photoModel->whereIn('id', $matchedIds);
            } else {
                $photoModel->where('id', -1);
            }
        }
    }

    /**
     * @param array<string, mixed> $rules
     */
    public static function countMatching(int $userId, array $rules): int
    {
        $photoModel = new PhotoModel();
        $photoModel->where('user_id', $userId);
        self::apply($photoModel, $rules);

        return $photoModel->countAllResults();
    }

    private static function sanitizeDate(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = is_string($v) ? trim($v) : '';
        if ($s === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $s);

        return $dt instanceof \DateTimeInterface ? $dt->format('Y-m-d') : null;
    }

    private static function sanitizeCamera(mixed $v): string
    {
        $s = is_string($v) ? strip_tags($v) : '';
        $s = trim($s);
        if (strlen($s) > 200) {
            $s = substr($s, 0, 200);
        }

        return $s;
    }

    private static function sanitizeFloat(mixed $v, float $min, float $max): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (! is_numeric($v)) {
            return null;
        }
        $f = (float) $v;
        if ($f < $min || $f > $max) {
            return null;
        }

        return $f;
    }
}
