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
        $webappUrl = rtrim(base_url(), '/');
        $defaultHeaders = [
            'X-API-KEY'    => $this->getMlApiKey(),
            'X-Webapp-Url' => $webappUrl,
        ];
        $options['headers'] = array_merge($defaultHeaders, $options['headers'] ?? []);

        $client = service('curlrequest', [
            'connect_timeout' => $options['connect_timeout'] ?? 10,
            'timeout'         => $options['timeout'] ?? 60,
        ]);
        $baseUrl = $this->getMlUrl();
        $url = $baseUrl . $path;

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
        $userId      = auth()->id();
        $personModel = new PersonModel();
        $faceModel   = new FaceEncodingModel();
        $photoModel  = new \App\Models\PhotoModel();

        // Only persons whose faces appear in the current user's photos
        $persons = $personModel->getPersonsWithFaceCountForUser($userId);

        // Only unassigned faces that belong to the current user's photos
        $db = \Config\Database::connect();
        $unassignedCount = (int) $db->table('tbl_face_encodings fe')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->where('fe.person_id IS NULL')
            ->countAllResults();

        // Attach thumbnail data for each person in ONE batch query using thumbnail_path
        if (!empty($persons)) {
            $personIds = array_column($persons, 'id');
            $personFaces = $db->table('tbl_face_encodings fe')
                ->select('fe.person_id, fe.bbox_x, fe.bbox_y, fe.bbox_w, fe.bbox_h, fe.age, fe.gender, p.path, p.thumbnail_path, p.width, p.height')
                ->join('tbl_photos p', 'p.id = fe.photo_id')
                ->whereIn('fe.person_id', $personIds)
                ->orderBy('fe.id', 'ASC')
                ->get()
                ->getResultArray();

            $personFaceMap = [];
            foreach ($personFaces as $pf) {
                $pid = (int) $pf['person_id'];
                if (!isset($personFaceMap[$pid])) {
                    $personFaceMap[$pid] = $pf;
                }
            }

            foreach ($persons as &$person) {
                $pid = (int) $person['id'];
                $pf = $personFaceMap[$pid] ?? null;
                $person['thumbnail'] = null;
                $person['age'] = null;
                $person['gender'] = null;
                if ($pf) {
                    $person['age'] = $pf['age'] ?? null;
                    $person['gender'] = $pf['gender'] ?? null;
                    $pw = (float) ($pf['width'] ?: 800);
                    $ph = (float) ($pf['height'] ?: 600);
                    $thumbUrl = !empty($pf['thumbnail_path']) ? base_url($pf['thumbnail_path']) : base_url($pf['path']);
                    $person['thumbnail'] = [
                        'url'  => $thumbUrl,
                        'x'    => (float) $pf['bbox_x'],
                        'y'    => (float) $pf['bbox_y'],
                        'w'    => (float) $pf['bbox_w'],
                        'h'    => (float) $pf['bbox_h'],
                        'pw'   => $pw,
                        'ph'   => $ph,
                    ];
                }
            }
            unset($person);
        }

        // Attach thumbnail data for unassigned faces scoped to user (single JOIN, capped at 48 for fast DOM rendering)
        $unassignedRows = $db->table('tbl_face_encodings fe')
            ->select('fe.id, fe.photo_id, fe.bbox_x, fe.bbox_y, fe.bbox_w, fe.bbox_h, fe.age, fe.gender, fe.detection_score, p.path, p.thumbnail_path, p.width, p.height')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->where('fe.person_id IS NULL')
            ->orderBy('fe.id', 'DESC')
            ->limit(48)
            ->get()
            ->getResultArray();

        $unassigned = [];
        foreach ($unassignedRows as $uface) {
            $pw = (float) ($uface['width'] ?: 800);
            $ph = (float) ($uface['height'] ?: 600);
            $thumbUrl = !empty($uface['thumbnail_path']) ? base_url($uface['thumbnail_path']) : base_url($uface['path']);
            $uface['thumbnail'] = [
                'url'  => $thumbUrl,
                'x'    => (float) $uface['bbox_x'],
                'y'    => (float) $uface['bbox_y'],
                'w'    => (float) $uface['bbox_w'],
                'h'    => (float) $uface['bbox_h'],
                'pw'   => $pw,
                'ph'   => $ph,
            ];
            $unassigned[] = $uface;
        }

        // Storage metrics for sidebar bar
        $totalBytes     = (int)($photoModel->where('user_id', $userId)->selectSum('size')->first()['size'] ?? 0);
        $storageMetrics = self::calculateStorageMetrics($totalBytes);

        $data = [
            'persons'         => $persons,
            'unassigned'      => $unassigned,
            'unassignedCount' => $unassignedCount,
            'faceModel'       => $faceModel,
            'counts'          => $this->getSidebarCounts(),
            'storageUsed'     => $storageMetrics['storageUsed'],
            'storagePercent'  => $storageMetrics['storagePercent'],
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

        $photoIds = array_unique(array_filter(array_column($faces, 'photo_id')));
        $photos = [];
        if (!empty($photoIds)) {
            $photoModel = new \App\Models\PhotoModel();
            $photos = $photoModel->whereIn('id', $photoIds)
                ->where('user_id', auth()->id())
                ->where('is_archived', false)
                ->orderBy('taken_at', 'DESC')
                ->findAll();
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
        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($photoId);
        if (!$photo) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->getFacesByPhoto($photoId);

        $tagModel = new \App\Models\PhotoTagModel();
        $tags = $tagModel->where('photo_id', $photoId)->orderBy('tag', 'ASC')->findAll();

        $highlightPersonId = $this->request->getGet('person');
        if ($highlightPersonId) {
            $highlightPersonId = (int) $highlightPersonId;
        }

        $personPhotos = [];
        $currentIndex = 0;
        if ($highlightPersonId) {
            $allFaces = $faceModel->where('person_id', $highlightPersonId)->orderBy('id', 'ASC')->findAll();
            $allPhotoIds = array_unique(array_filter(array_column($allFaces, 'photo_id')));
            if (!empty($allPhotoIds)) {
                $personPhotos = $photoModel->whereIn('id', $allPhotoIds)
                    ->where('user_id', auth()->id())
                    ->where('is_archived', false)
                    ->orderBy('taken_at', 'DESC')
                    ->findAll();
                foreach ($personPhotos as $idx => $p) {
                    if ((int)$p['id'] === $photoId) {
                        $currentIndex = $idx;
                        break;
                    }
                }
            }
        }

        return view('photos/faces_photo', [
            'photo'              => $photo,
            'faces'              => $faces,
            'tags'               => $tags,
            'highlightPersonId'  => $highlightPersonId,
            'personPhotos'       => $personPhotos,
            'currentIndex'       => $currentIndex,
            'counts'             => $this->getSidebarCounts(),
        ]);
    }

    // ── API: read from DB directly ──────────────────────────────

    public function apiFaces(int $photoId): ResponseInterface
    {
        $photoModel = new \App\Models\PhotoModel();
        $photo = $photoModel->where('user_id', auth()->id())->find($photoId);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Photo not found'])->setStatusCode(404);
        }

        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->getFacesByPhoto($photoId);

        $personIds = array_unique(array_filter(array_column($faces, 'person_id')));
        $personMap = [];
        if (!empty($personIds)) {
            $persons = model('App\Models\PersonModel')->whereIn('id', $personIds)->findAll();
            foreach ($persons as $p) {
                $personMap[$p['id']] = $p['name'];
            }
        }

        $result = [];
        foreach ($faces as $f) {
            $personName = $f['person_id'] ? ($personMap[$f['person_id']] ?? null) : null;
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
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Unauthorized',
            ])->setStatusCode(401);
        }

        $db = \Config\Database::connect();
        $faces = $db->table('tbl_face_encodings fe')
            ->select('fe.id, fe.photo_id, fe.bbox_x, fe.bbox_y, fe.bbox_w, fe.bbox_h, fe.age, fe.gender, fe.detection_score, p.path, p.thumbnail_path, p.width, p.height')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->where('fe.person_id IS NULL')
            ->orderBy('fe.id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($faces as $f) {
            $thumbUrl = !empty($f['thumbnail_path']) ? base_url($f['thumbnail_path']) : base_url($f['path']);
            $result[] = [
                'face_id'         => (int) $f['id'],
                'photo_id'        => (int) $f['photo_id'],
                'photo_path'      => $thumbUrl,
                'bbox'            => [
                    'x' => (float) $f['bbox_x'],
                    'y' => (float) $f['bbox_y'],
                    'w' => (float) $f['bbox_w'],
                    'h' => (float) $f['bbox_h'],
                ],
                'detection_score' => isset($f['detection_score']) && $f['detection_score'] !== null ? (float) $f['detection_score'] : null,
                'age'             => isset($f['age']) && $f['age'] !== null ? (int) $f['age'] : null,
                'gender'          => $f['gender'] ?? null,
                'photo_width'     => (float) ($f['width'] ?: 800),
                'photo_height'    => (float) ($f['height'] ?: 600),
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'faces'  => $result,
        ]);
    }

    public function apiPersonPhotos(int $personId): ResponseInterface
    {
        $faceModel = new FaceEncodingModel();
        $faces = $faceModel->where('person_id', $personId)->orderBy('id', 'ASC')->findAll();

        $photoIds = array_unique(array_filter(array_column($faces, 'photo_id')));
        $photos = [];
        if (!empty($photoIds)) {
            $photoModel = new \App\Models\PhotoModel();
            $photos = $photoModel->whereIn('id', $photoIds)
                ->where('user_id', auth()->id())
                ->where('is_archived', false)
                ->orderBy('taken_at', 'DESC')
                ->findAll();
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
        $mode = $this->request->getGet('mode') ?: 'incremental';
        $result = $this->mlProxy('POST', "/api/v1/faces/cluster?mode={$mode}");

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

        $limit  = $this->request->getPost('limit') ?: 20;
        $userId = auth()->id() ?: 0;

        // user_id is a Query param on the ML endpoint (not multipart), so we
        // append it directly to the URL to ensure per-user Qdrant filtering.
        $result = $this->mlProxy('POST', "/api/v1/faces/search?user_id={$userId}", [
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

    public function apiAssignFaceToPerson(): ResponseInterface
    {
        $faceId = (int) $this->request->getPost('face_id');
        $personIdInput = $this->request->getPost('person_id');

        $faceModel = new FaceEncodingModel();
        $face = $faceModel->find($faceId);
        if (!$face) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Face not found',
            ])->setStatusCode(404);
        }

        // Verify photo ownership
        $photo = model('App\Models\PhotoModel')->where('user_id', auth()->id())->find($face['photo_id']);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $newPersonId = null;
        if ($personIdInput === 'new') {
            $personModel = new PersonModel();
            $newPersonId = $personModel->insert(['name' => null]);
        } elseif ($personIdInput !== null && $personIdInput !== '' && $personIdInput !== 'null') {
            $newPersonId = (int) $personIdInput;
            $personModel = new PersonModel();
            if (!$personModel->find($newPersonId)) {
                return $this->response->setJSON([
                    'status' => 'error', 'message' => 'Target person not found',
                ])->setStatusCode(404);
            }
        }

        $oldPersonId = $face['person_id'];

        $faceModel->update($faceId, ['person_id' => $newPersonId]);

        // Human-in-the-loop Active Learning Integration
        $userId = auth()->id() ?: 0;
        if ($newPersonId !== null) {
            // Confirm/pin this face to the new person
            $this->mlProxy('POST', "/api/v1/faces/{$faceId}/annotate", [
                'form_params' => [
                    'person_id'    => $newPersonId,
                    'action'       => 'confirm',
                    'annotated_by' => $userId,
                ],
            ]);
        } else {
            // Face is unassigned. Remove any previous confirmation pins
            // and optionally record a reject annotation for the old person
            if ($oldPersonId) {
                // Remove previous confirm annotation
                $this->mlProxy('DELETE', "/api/v1/faces/{$faceId}/annotate/{$oldPersonId}");

                // Record a reject annotation to prevent HDBSCAN re-assigning it back
                $this->mlProxy('POST', "/api/v1/faces/{$faceId}/annotate", [
                    'form_params' => [
                        'person_id'    => $oldPersonId,
                        'action'       => 'reject',
                        'annotated_by' => $userId,
                    ],
                ]);
            }
        }

        // Cleanup empty persons
        if ($oldPersonId) {
            $count = $faceModel->where('person_id', $oldPersonId)->countAllResults();
            if ($count === 0) {
                model('App\Models\PersonModel')->delete($oldPersonId);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'face_id' => $faceId,
            'person_id' => $newPersonId,
        ]);
    }

    public function apiUpdateFaceMetadata(): ResponseInterface
    {
        $faceId = (int) $this->request->getPost('face_id');
        $gender = $this->request->getPost('gender');
        $age = $this->request->getPost('age');
        $emotion = $this->request->getPost('emotion');

        $faceModel = new FaceEncodingModel();
        $face = $faceModel->find($faceId);
        if (!$face) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'Face not found',
            ])->setStatusCode(404);
        }

        // Verify photo ownership
        $photo = model('App\Models\PhotoModel')->where('user_id', auth()->id())->find($face['photo_id']);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $updateData = [];
        if ($gender !== null) {
            $updateData['gender'] = ($gender === '' || $gender === 'null') ? null : strtolower($gender);
        }
        if ($age !== null) {
            $updateData['age'] = ($age === '' || $age === 'null') ? null : (int)$age;
        }
        if ($emotion !== null) {
            $updateData['emotion'] = ($emotion === '' || $emotion === 'null') ? null : $emotion;
        }

        if (!empty($updateData)) {
            $faceModel->update($faceId, $updateData);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'face_id' => $faceId,
            'updated' => $updateData,
        ]);
    }

    public function apiBulkAssign(): ResponseInterface
    {
        $faceIds = $this->request->getPost('face_ids');
        $personIdInput = $this->request->getPost('person_id');

        if (empty($faceIds) || !is_array($faceIds)) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'No face IDs provided',
            ])->setStatusCode(400);
        }

        // Verify that the faces belong to photos owned by the authenticated user
        $userId = auth()->id() ?: 0;
        $db = \Config\Database::connect();
        $ownedFaces = $db->table('tbl_face_encodings fe')
            ->select('fe.id')
            ->join('tbl_photos p', 'p.id = fe.photo_id')
            ->where('p.user_id', $userId)
            ->whereIn('fe.id', array_map('intval', $faceIds))
            ->get()->getResultArray();
        $validFaceIds = array_column($ownedFaces, 'id');

        if (empty($validFaceIds)) {
            return $this->response->setJSON([
                'status' => 'error', 'message' => 'No valid faces found or unauthorized',
            ])->setStatusCode(403);
        }

        $newPersonId = null;
        if ($personIdInput === 'new') {
            $personModel = new PersonModel();
            $newPersonId = $personModel->insert(['name' => null]);
        } elseif ($personIdInput !== null && $personIdInput !== '' && $personIdInput !== 'null') {
            $newPersonId = (int) $personIdInput;
            $personModel = new PersonModel();
            if (!$personModel->find($newPersonId)) {
                return $this->response->setJSON([
                    'status' => 'error', 'message' => 'Target person not found',
                ])->setStatusCode(404);
            }
        }

        $faceModel = new FaceEncodingModel();
        $oldPersonIds = [];

        foreach ($validFaceIds as $faceId) {
            $face = $faceModel->find((int)$faceId);
            if ($face) {
                $oldPersonId = $face['person_id'];
                if ($oldPersonId && $oldPersonId != $newPersonId) {
                    $oldPersonIds[] = $oldPersonId;
                }
                $faceModel->update($face['id'], ['person_id' => $newPersonId]);

                // Record confirm/reject in the ML service
                if ($newPersonId !== null) {
                    $this->mlProxy('POST', "/api/v1/faces/{$face['id']}/annotate", [
                        'form_params' => [
                            'person_id'    => $newPersonId,
                            'action'       => 'confirm',
                            'annotated_by' => $userId,
                        ],
                    ]);
                } else {
                    if ($oldPersonId) {
                        $this->mlProxy('DELETE', "/api/v1/faces/{$face['id']}/annotate/{$oldPersonId}");
                        $this->mlProxy('POST', "/api/v1/faces/{$face['id']}/annotate", [
                            'form_params' => [
                                'person_id'    => $oldPersonId,
                                'action'       => 'reject',
                                'annotated_by' => $userId,
                            ],
                        ]);
                    }
                }
            }
        }

        if (!empty($oldPersonIds)) {
            $oldPersonIds = array_unique($oldPersonIds);
            foreach ($oldPersonIds as $oldId) {
                $count = $faceModel->where('person_id', $oldId)->countAllResults();
                if ($count === 0) {
                    model('App\Models\PersonModel')->delete($oldId);
                }
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'updated_count' => count($validFaceIds),
            'person_id' => $newPersonId,
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

        // Notify ML service to merge cluster centroids in Qdrant
        try {
            $this->mlProxy('POST', '/api/v1/faces/persons/merge', [
                'form_params' => [
                    'source_person_id' => $sourceId,
                    'target_person_id' => $targetId,
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('warning', "ML persons/merge failed for {$sourceId} -> {$targetId}: " . $e->getMessage());
        }

        return $this->response->setJSON([
            'status' => 'success',
            'merged_into' => $targetId,
        ]);
    }

    public function apiScanAll(): ResponseInterface
    {
        try {
            $photoModel = new \App\Models\PhotoModel();
            $faceModel  = new FaceEncodingModel();

            // Get IDs of photos that have NO face encoding records yet
            $allPhotos = $photoModel
                ->like('mime_type', 'image/', 'after')
                ->where('deleted_at', null)
                ->select('id')
                ->findAll();

            $toQueue = [];
            foreach ($allPhotos as $photo) {
                $existing = $faceModel->where('photo_id', $photo['id'])->countAllResults();
                if ($existing === 0) {
                    $toQueue[] = (int) $photo['id'];
                }
            }

            if (empty($toQueue)) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'queued'  => 0,
                    'message' => 'All photos already scanned.',
                ]);
            }

            // Attempt high-performance batch queuing first
            $mlUrl    = $this->getMlUrl();
            $mlKey    = $this->getMlApiKey();
            $queued   = 0;
            $chunks   = array_chunk($toQueue, 500);
            $batchFailed = false;

            $webappUrl = rtrim(base_url(), '/');
            $client = service('curlrequest', [
                'connect_timeout' => 5,
                'timeout'         => 30,
                'headers'         => [
                    'X-API-KEY'    => $mlKey,
                    'X-Webapp-Url' => $webappUrl,
                ],
            ]);

            foreach ($chunks as $chunk) {
                try {
                    $resp = $client->post($mlUrl . '/api/v1/faces/encode-batch', [
                        'form_params' => [
                            'photo_ids'   => $chunk,
                            'scan_faces'  => 1,
                            'scan_tags'   => 1,
                            'scan_clip'   => 1,
                            'webapp_url'  => $webappUrl,
                        ],
                    ]);
                    $body = json_decode($resp->getBody(), true);
                    if (isset($body['queued'])) {
                        $queued += (int) $body['queued'];
                    } else {
                        $queued += count($chunk);
                    }
                } catch (\Exception $e) {
                    $batchFailed = true;
                    break;
                }
            }

            // Fallback to individual requests if batch endpoint is not supported
            if ($batchFailed) {
                $clientShort = service('curlrequest', [
                    'connect_timeout' => 2,
                    'timeout'         => 2,
                    'headers'         => [
                        'X-API-KEY'    => $mlKey,
                        'X-Webapp-Url' => $webappUrl,
                    ],
                ]);
                $queued = 0;
                foreach ($toQueue as $photoId) {
                    try {
                        $clientShort->post($mlUrl . '/api/v1/faces/encode', [
                            'form_params' => [
                                'photo_id'   => $photoId,
                                'async_task' => 1,
                                'webapp_url' => $webappUrl,
                            ],
                        ]);
                        $queued++;
                    } catch (\Exception $e) {
                        if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Operation timed out')) {
                            $queued++;
                        }
                    }
                }
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'queued'  => $queued,
                'total'   => count($toQueue),
                'message' => "Queued {$queued} photos for background face scanning.",
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

        $photoModel = new \App\Models\PhotoModel();

        $photos = $photoModel
            ->like('mime_type', 'image/', 'after')
            ->where('deleted_at', null)
            ->select('id')
            ->orderBy('id', 'ASC')
            ->findAll();

        $photoIds = array_map(fn($p) => (int) $p['id'], $photos);
        $total = count($photoIds);
        if ($total === 0) {
            return $this->response->setJSON([
                'status'    => 'success',
                'processed' => 0,
                'errors'    => [],
            ]);
        }

        // Try batch queuing first
        $mlUrl    = $this->getMlUrl();
        $mlKey    = $this->getMlApiKey();
        $processed = 0;
        $errors    = [];
        $chunks    = array_chunk($photoIds, 500);
        $batchFailed = false;

        $webappUrl = rtrim(base_url(), '/');
        $client = service('curlrequest', [
            'connect_timeout' => 5,
            'timeout'         => 30,
            'headers'         => [
                'X-API-KEY'    => $mlKey,
                'X-Webapp-Url' => $webappUrl,
            ],
        ]);

        foreach ($chunks as $chunk) {
            try {
                $resp = $client->post($mlUrl . '/api/v1/faces/encode-batch', [
                    'form_params' => [
                        'photo_ids'   => $chunk,
                        'scan_faces'  => 1,
                        'scan_tags'   => 1,
                        'scan_clip'   => 1,
                        'webapp_url'  => $webappUrl,
                    ],
                ]);
                $body = json_decode($resp->getBody(), true);
                $processed += isset($body['queued']) ? (int) $body['queued'] : count($chunk);
            } catch (\Exception $e) {
                $batchFailed = true;
                break;
            }
        }

        if ($batchFailed) {
            $processed = 0;
            foreach ($photoIds as $pid) {
                $result = $this->mlProxy('POST', '/api/v1/faces/encode', [
                    'form_params' => [
                        'photo_id'   => $pid,
                        'async_task' => 1,
                        'webapp_url' => $webappUrl,
                    ],
                ]);

                if (isset($result['error'])) {
                    $errors[] = ['photo_id' => $pid, 'error' => $result['error']];
                } else {
                    $processed++;
                }
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
                'form_params' => [
                    'photo_id'   => (int) $id,
                    'async_task' => 1,
                ],
            ]);
            $results[(int) $id] = $r;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'results' => $results,
        ]);
    }

    public function apiSetPersonCover(): ResponseInterface
    {
        $personId = (int) $this->request->getPost('person_id');
        $faceId   = (int) $this->request->getPost('face_id');

        if (!$personId || !$faceId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'person_id and face_id are required',
            ])->setStatusCode(400);
        }

        $personModel = new PersonModel();
        $faceModel   = new FaceEncodingModel();

        $person = $personModel->find($personId);
        $face   = $faceModel->find($faceId);

        if (!$person || !$face) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Person or Face not found',
            ])->setStatusCode(404);
        }

        // Verify photo ownership
        $photo = model('App\Models\PhotoModel')->where('user_id', auth()->id())->find($face['photo_id']);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $personModel->update($personId, [
            'thumbnail_face_id' => $faceId,
        ]);

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Person cover face updated successfully.',
            'person_id'=> $personId,
            'face_id'  => $faceId,
        ]);
    }

    public function apiDetachFace(): ResponseInterface
    {
        $faceId = (int) $this->request->getPost('face_id');
        if (!$faceId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'face_id is required',
            ])->setStatusCode(400);
        }

        $faceModel = new FaceEncodingModel();
        $face = $faceModel->find($faceId);
        if (!$face) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Face not found',
            ])->setStatusCode(404);
        }

        // Verify photo ownership
        $photo = model('App\Models\PhotoModel')->where('user_id', auth()->id())->find($face['photo_id']);
        if (!$photo) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $faceModel->update($faceId, [
            'person_id' => null,
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Face detached and moved to unassigned.',
            'face_id' => $faceId,
        ]);
    }

    public function apiScanStatus(): ResponseInterface
    {
        $userId = auth()->id();
        $photoModel = new \App\Models\PhotoModel();
        $totalPhotos = $photoModel->where('user_id', $userId)->countAllResults();
        $scannedFaces = $photoModel->where('user_id', $userId)->where('scanned_face', 1)->countAllResults();
        $unscanned = max(0, $totalPhotos - $scannedFaces);

        $queueSize = 0;
        $isProcessing = false;
        try {
            $h = cache('ml_health_status');
            if ($h === null) {
                $h = $this->mlProxy('GET', '/api/v1/health', [
                    'connect_timeout' => 2,
                    'timeout'         => 3,
                ]);
                if (!empty($h) && !isset($h['error'])) {
                    cache()->save('ml_health_status', $h, 3);
                }
            }
            if (isset($h['queue_size'])) {
                $queueSize = (int)$h['queue_size'];
                $isProcessing = (bool)($h['is_processing'] ?? false);
            }
        } catch (\Exception $e) {}

        return $this->response->setJSON([
            'status'        => 'success',
            'total'         => $totalPhotos,
            'scanned'       => $scannedFaces,
            'unscanned'     => $unscanned,
            'queue_size'    => $queueSize,
            'is_processing' => $isProcessing,
        ]);
    }
}

