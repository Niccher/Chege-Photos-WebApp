<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FaceEncodingModel;
use App\Models\PersonModel;
use CodeIgniter\HTTP\ResponseInterface;

class Faces extends BaseController
{
    private const ML_BASE = 'http://ml-chege-photos:8000';

    private function mlProxy(string $method, string $path, array $options = []): array
    {
        $client = service('curlrequest', [
            'connect_timeout' => 30,
            'timeout'        => 120,
        ]);
        $url = self::ML_BASE . $path;

        try {
            $response = $client->request($method, $url, $options);
            $body = $response->getBody();
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['error' => 'Invalid ML response', 'raw' => $body];
            }
            return $data ?: [];
        } catch (\Exception $e) {
            log_message('error', 'ML proxy failed: ' . $e->getMessage());
            return ['error' => 'ML service unavailable: ' . $e->getMessage()];
        }
    }

    // ── Web UI ─────────────────────────────────────────────────

    public function index()
    {
        $personModel = new PersonModel();
        $faceModel   = new FaceEncodingModel();
        $photoModel  = new \App\Models\PhotoModel();

        $persons = $personModel->getPersonsWithFaceCount();
        $unassignedCount = $faceModel->where('person_id', null)->countAllResults();

        // Attach thumbnail data for each person (first face's photo + bbox + attributes)
        foreach ($persons as &$person) {
            $firstFace = $faceModel->where('person_id', $person['id'])->orderBy('id', 'ASC')->first();
            $person['thumbnail'] = null;
            $person['age'] = null;
            $person['gender'] = null;
            if ($firstFace) {
                $photo = $photoModel->find($firstFace['photo_id']);
                $person['age'] = $firstFace['age'] ?? null;
                $person['gender'] = $firstFace['gender'] ?? null;
                if ($photo) {
                    $pw = (float) ($photo['width'] ?: 800);
                    $ph = (float) ($photo['height'] ?: 600);
                    $person['thumbnail'] = [
                        'url'  => base_url($photo['path']),
                        'x'    => (float) $firstFace['bbox_x'],
                        'y'    => (float) $firstFace['bbox_y'],
                        'w'    => (float) $firstFace['bbox_w'],
                        'h'    => (float) $firstFace['bbox_h'],
                        'pw'   => $pw,
                        'ph'   => $ph,
                    ];
                }
            }
        }
        unset($person);

        // Attach thumbnail data for unassigned faces
        $unassigned = $faceModel->where('person_id', null)->orderBy('id')->findAll();
        foreach ($unassigned as &$uface) {
            $uface['thumbnail'] = null;
            $photo = $photoModel->find($uface['photo_id']);
            if ($photo) {
                $pw = (float) ($photo['width'] ?: 800);
                $ph = (float) ($photo['height'] ?: 600);
                $uface['thumbnail'] = [
                    'url'  => base_url($photo['path']),
                    'x'    => (float) $uface['bbox_x'],
                    'y'    => (float) $uface['bbox_y'],
                    'w'    => (float) $uface['bbox_w'],
                    'h'    => (float) $uface['bbox_h'],
                    'pw'   => $pw,
                    'ph'   => $ph,
                ];
            }
        }
        unset($uface);

        $data = [
            'persons'         => $persons,
            'unassigned'      => $unassigned,
            'unassignedCount' => $unassignedCount,
            'faceModel'       => $faceModel,
            'counts'          => $this->getSidebarCounts(),
        ];
        return view('photos/faces', $data);
    }

    public function personPhotos(int $personId)
    {
        $personModel = new PersonModel();
        $person = $personModel->find($personId);
        if (!$person) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->where('person_id', $personId)->orderBy('id', 'ASC')->findAll();

        $photoIds = array_unique(array_column($faces, 'photo_id'));
        $photoModel = new \App\Models\PhotoModel();
        $photos = [];
        foreach ($photoIds as $pid) {
            $p = $photoModel->find($pid);
            if ($p) $photos[] = $p;
        }

        $label = $person['name'] ?: 'Person ' . $person['id'];

        return view('photos/faces_person', [
            'person'  => $person,
            'label'   => $label,
            'photos'  => $photos,
            'counts'  => $this->getSidebarCounts(),
        ]);
    }

    public function photo(int $photoId)
    {
        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->getFacesByPhoto($photoId);

        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->find($photoId);

        $highlightPersonId = $this->request->getGet('person');
        if ($highlightPersonId) {
            $highlightPersonId = (int) $highlightPersonId;
        }

        $personPhotos = [];
        $currentIndex = 0;
        if ($highlightPersonId) {
            $allFaces = $faceModel->where('person_id', $highlightPersonId)->orderBy('id', 'ASC')->findAll();
            $allPhotoIds = array_unique(array_column($allFaces, 'photo_id'));
            $idx = 0;
            foreach ($allPhotoIds as $pid) {
                $p = $photoModel->find($pid);
                if ($p) {
                    $personPhotos[] = $p;
                    if ($pid == $photoId) {
                        $currentIndex = $idx;
                    }
                    $idx++;
                }
            }
        }

        return view('photos/faces_photo', [
            'photo'              => $photo,
            'faces'              => $faces,
            'highlightPersonId'  => $highlightPersonId,
            'personPhotos'       => $personPhotos,
            'currentIndex'       => $currentIndex,
            'counts'             => $this->getSidebarCounts(),
        ]);
    }

    // ── API: read from DB directly ──────────────────────────────

    public function apiFaces(int $photoId): ResponseInterface
    {
        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->getFacesByPhoto($photoId);

        $result = [];
        foreach ($faces as $f) {
            $personName = null;
            if ($f['person_id']) {
                $person = model('App\Models\PersonModel')->find($f['person_id']);
                $personName = $person['name'] ?? null;
            }
            $result[] = [
                'face_id'      => (int) $f['id'],
                'photo_id'     => (int) $f['photo_id'],
                'person_id'    => $f['person_id'] ? (int) $f['person_id'] : null,
                'person_name'  => $personName,
                'bbox'         => [
                    'x' => (float) $f['bbox_x'],
                    'y' => (float) $f['bbox_y'],
                    'w' => (float) $f['bbox_w'],
                    'h' => (float) $f['bbox_h'],
                ],
                'detection_score' => (float) $f['detection_score'],
                'age'   => $f['age'] ? (int) $f['age'] : null,
                'gender' => $f['gender'],
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'faces'  => $result,
        ]);
    }

    public function apiPersons(): ResponseInterface
    {
        $personModel = new PersonModel();
        $faceModel   = new FaceEncodingModel();
        $photoModel  = new \App\Models\PhotoModel();
        $persons = $personModel->getPersonsWithFaceCount();

        return $this->response->setJSON([
            'status'  => 'success',
            'persons' => array_map(function($p) use ($faceModel, $photoModel) {
                $thumb = null;
                $firstFace = $faceModel->where('person_id', $p['id'])->orderBy('id', 'ASC')->first();
                if ($firstFace) {
                    $photo = $photoModel->find($firstFace['photo_id']);
                    if ($photo) {
                        $thumb = [
                            'path' => $photo['path'],
                            'thumbnail_path' => $photo['thumbnail_path'],
                            'bbox_x' => (float) $firstFace['bbox_x'],
                            'bbox_y' => (float) $firstFace['bbox_y'],
                            'bbox_w' => (float) $firstFace['bbox_w'],
                            'bbox_h' => (float) $firstFace['bbox_h'],
                            'photo_width' => (float) ($photo['width'] ?: 800),
                            'photo_height' => (float) ($photo['height'] ?: 600),
                        ];
                    }
                }
                return [
                    'id'            => (int) $p['id'],
                    'name'          => $p['name'],
                    'cluster_label' => $p['cluster_label'],
                    'face_count'    => (int) $p['face_count'],
                    'thumbnail_face_id' => $p['thumbnail_face_id'] ? (int) $p['thumbnail_face_id'] : null,
                    'thumbnail'     => $thumb,
                ];
            }, $persons),
        ]);
    }

    public function apiUnassigned(): ResponseInterface
    {
        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->getUnassigned();

        return $this->response->setJSON([
            'status' => 'success',
            'faces'  => array_map(fn($f) => [
                'face_id'  => (int) $f['id'],
                'photo_id' => (int) $f['photo_id'],
                'bbox'     => [
                    'x' => (float) $f['bbox_x'],
                    'y' => (float) $f['bbox_y'],
                    'w' => (float) $f['bbox_w'],
                    'h' => (float) $f['bbox_h'],
                ],
            ], $faces),
        ]);
    }

    public function apiPersonPhotos(int $personId): ResponseInterface
    {
        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->where('person_id', $personId)->orderBy('id', 'ASC')->findAll();

        $photoIds = array_unique(array_column($faces, 'photo_id'));
        $photoModel = new \App\Models\PhotoModel();
        $photos = [];
        foreach ($photoIds as $pid) {
            $p = $photoModel->find($pid);
            if ($p) $photos[] = $p;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'photos' => array_map(fn($p) => [
                'id'     => (int) $p['id'],
                'path'   => $p['path'],
                'thumbnail_path' => $p['thumbnail_path'],
                'filename' => $p['filename'],
                'width'  => $p['width'] ? (int) $p['width'] : null,
                'height' => $p['height'] ? (int) $p['height'] : null,
            ], $photos),
        ]);
    }

    // ── API: proxy to ML service ────────────────────────────────

    public function apiScan(int $photoId): ResponseInterface
    {
        $result = $this->mlProxy('POST', '/api/v1/faces/encode', [
            'form_params' => ['photo_id' => $photoId],
        ]);

        return $this->response->setJSON([
            'status' => isset($result['error']) ? 'error' : 'success',
            'data'   => $result,
        ]);
    }

    public function apiCluster(): ResponseInterface
    {
        $result = $this->mlProxy('POST', '/api/v1/faces/cluster');

        return $this->response->setJSON([
            'status' => isset($result['error']) ? 'error' : 'success',
            'data'   => $result,
        ]);
    }

    public function apiSearch(): ResponseInterface
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No valid file uploaded',
            ])->setStatusCode(400);
        }

        $limit = $this->request->getPost('limit') ?: 20;
        $result = $this->mlProxy('POST', '/api/v1/faces/search', [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => fopen($file->getTempName(), 'r'),
                    'filename' => $file->getName(),
                ],
                [
                    'name'     => 'limit',
                    'contents' => (string) $limit,
                ],
            ],
        ]);

        return $this->response->setJSON([
            'status' => isset($result['error']) ? 'error' : 'success',
            'data'   => $result,
        ]);
    }

    // ── API: write to DB directly ───────────────────────────────

    public function apiNamePerson(int $personId): ResponseInterface
    {
        $name = $this->request->getPost('name');
        if (!$name) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Name required',
            ])->setStatusCode(400);
        }

        $personModel = new PersonModel();
        $person = $personModel->find($personId);
        if (!$person) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Person not found',
            ])->setStatusCode(404);
        }

        $personModel->update($personId, ['name' => $name]);

        return $this->response->setJSON([
            'status' => 'success',
            'person' => ['id' => (int) $personId, 'name' => $name],
        ]);
    }

    public function apiMergePersons(): ResponseInterface
    {
        $sourceId = (int) $this->request->getPost('source_person_id');
        $targetId = (int) $this->request->getPost('target_person_id');

        if (!$sourceId || !$targetId || $sourceId === $targetId) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Invalid person IDs',
            ])->setStatusCode(400);
        }

        $personModel = new PersonModel();
        $source = $personModel->find($sourceId);
        $target = $personModel->find($targetId);
        if (!$source || !$target) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Person not found',
            ])->setStatusCode(404);
        }

        $faceModel = new FaceEncodingModel();
        $faceModel->where('person_id', $sourceId)->set(['person_id' => $targetId])->update();

        $personModel->delete($sourceId);

        return $this->response->setJSON([
            'status' => 'success',
            'merged_into' => $targetId,
        ]);
    }

    public function apiScanAll(): ResponseInterface
    {
        try {
            $faceModel = new FaceEncodingModel();
            $photoModel = new \App\Models\PhotoModel();

            $photos = $photoModel
                ->like('mime_type', 'image/', 'after')
                ->where('deleted_at', null)
                ->orderBy('id', 'ASC')
                ->findAll();

            $processed = 0;
            $skipped = 0;
            $errors = [];

            foreach ($photos as $photo) {
                $existing = $faceModel->where('photo_id', $photo['id'])->countAllResults();
                if ($existing > 0) {
                    $skipped++;
                    continue;
                }

                $result = $this->mlProxy('POST', '/api/v1/faces/encode', [
                    'form_params' => ['photo_id' => $photo['id']],
                ]);

                if (isset($result['error'])) {
                    $errors[] = ['photo_id' => $photo['id'], 'error' => $result['error']];
                } else {
                    $processed++;
                }
            }

            return $this->response->setJSON([
                'status'    => 'success',
                'processed' => $processed,
                'skipped'   => $skipped,
                'errors'    => $errors,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'apiScanAll crashed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function apiResetScans(): ResponseInterface
    {
        $result = $this->mlProxy('DELETE', '/api/v1/faces/reset');

        return $this->response->setJSON([
            'status' => isset($result['error']) ? 'error' : 'success',
            'data'   => $result,
        ]);
    }

    public function apiForceScanAll(): ResponseInterface
    {
        $resetResult = $this->mlProxy('DELETE', '/api/v1/faces/reset');
        if (isset($resetResult['error'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Reset failed: ' . $resetResult['error'],
            ]);
        }

        $faceModel = new FaceEncodingModel();
        $photoModel = new \App\Models\PhotoModel();

        $photos = $photoModel
            ->like('mime_type', 'image/', 'after')
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();

        $processed = 0;
        $errors = [];

        foreach ($photos as $photo) {
            $result = $this->mlProxy('POST', '/api/v1/faces/encode', [
                'form_params' => ['photo_id' => $photo['id']],
            ]);

            if (isset($result['error'])) {
                $errors[] = ['photo_id' => $photo['id'], 'error' => $result['error']];
            } else {
                $processed++;
            }
        }

        return $this->response->setJSON([
            'status'    => 'success',
            'processed' => $processed,
            'errors'    => $errors,
        ]);
    }

    public function apiScanJobStatus(int $jobId): ResponseInterface
    {
        $result = $this->mlProxy('GET', "/api/v1/scan/batch/{$jobId}/status");

        return $this->response->setJSON([
            'status' => isset($result['error']) ? 'error' : 'success',
            'data'   => $result,
        ]);
    }

    public function apiBulkScan(): ResponseInterface
    {
        $photoIds = $this->request->getPost('photo_ids');
        if (!$photoIds || !is_array($photoIds)) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'photo_ids array required',
            ])->setStatusCode(400);
        }

        $results = [];
        foreach ($photoIds as $id) {
            $r = $this->mlProxy('POST', '/api/v1/faces/encode', [
                'form_params' => ['photo_id' => (int) $id],
            ]);
            $results[(int) $id] = $r;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'results' => $results,
        ]);
    }
}
