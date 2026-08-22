<?php

namespace App\Models;

use CodeIgniter\Model;

class FaceEncodingModel extends Model
{
    protected $table            = 'face_encoding';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'photo_id', 'person_id', 'qdrant_point_id',
        'bbox_x', 'bbox_y', 'bbox_w', 'bbox_h',
        'landmark_left_eye_x', 'landmark_left_eye_y',
        'landmark_right_eye_x', 'landmark_right_eye_y',
        'landmark_nose_x', 'landmark_nose_y',
        'landmark_left_mouth_x', 'landmark_left_mouth_y',
        'landmark_right_mouth_x', 'landmark_right_mouth_y',
        'detection_score', 'face_image_path', 'age', 'gender', 'emotion',
    ];

    protected $useTimestamps = false;

    public function getFacesByPhoto(int $photoId): array
    {
        return $this->where('photo_id', $photoId)->orderBy('id')->findAll();
    }

    public function getUnassigned(): array
    {
        return $this->where('person_id', null)->orderBy('id')->findAll();
    }
}
