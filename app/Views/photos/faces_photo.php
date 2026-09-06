<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-3 d-flex align-items-center gap-2">
    <a href="<?= base_url('faces') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Faces
    </a>
    <?php if ($highlightPersonId): ?>
        <a href="<?= base_url('faces/person/' . $highlightPersonId) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person"></i> Back to Person
        </a>
    <?php endif; ?>
</div>

<?php if ($photo): ?>
    <?php
    if (!function_exists('formatExifFraction')) {
        function formatExifFraction($fraction) {
            if (empty($fraction)) return '';
            if (strpos($fraction, '/') !== false) {
                list($num, $den) = explode('/', $fraction);
                if ($den != 0) {
                    $val = $num / $den;
                    if ($val < 1 && $val > 0) {
                        return '1/' . round(1 / $val);
                    }
                    return round($val, 2);
                }
            }
            return $fraction;
        }
    }

    $exif = [];
    if (!empty($photo['exif_data'])) {
        $exif = json_decode($photo['exif_data'], true) ?: [];
    }
    // format size
    $sizeFormatted = 'Unknown';
    if (!empty($photo['size'])) {
        $sizeFormatted = round($photo['size'] / (1024 * 1024), 2) . ' MB';
        if ($photo['size'] < 1024 * 1024) {
            $sizeFormatted = round($photo['size'] / 1024, 2) . ' KB';
        }
    }
    ?>
    <div class="row g-4 text-start">
        <!-- Left Column: Photo View -->
        <div class="col-lg-8 text-center">
            <div class="position-relative d-inline-block shadow-sm rounded overflow-hidden" style="background:#1e1e1e;">
                <?php if (count($personPhotos) > 1): ?>
                    <button class="btn btn-dark btn-sm position-absolute top-50 start-0 translate-middle-y ms-2 z-1"
                            onclick="navigatePhoto(-1)" style="opacity:0.7;" title="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="btn btn-dark btn-sm position-absolute top-50 end-0 translate-middle-y me-2 z-1"
                            onclick="navigatePhoto(1)" style="opacity:0.7;" title="Next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                <?php endif; ?>
                <img src="<?= base_url($photo['path']) ?>" alt="Photo" class="img-fluid d-block mx-auto"
                     id="facePhoto" style="max-height:75vh;width:auto;">
                <?php foreach ($faces as $face): ?>
                    <?php
                    $x = $photo['width'] ? ($face['bbox_x'] / $photo['width']) * 100 : 0;
                    $y = $photo['height'] ? ($face['bbox_y'] / $photo['height']) * 100 : 0;
                    $w = $photo['width'] ? ($face['bbox_w'] / $photo['width']) * 100 : 0;
                    $h = $photo['height'] ? ($face['bbox_h'] / $photo['height']) * 100 : 0;
                    $personName = '';
                    if ($face['person_id']) {
                        $person = model('App\Models\PersonModel')->find($face['person_id']);
                        $personName = $person['name'] ?? '';
                    }
                    $isHighlighted = $highlightPersonId && $face['person_id'] == $highlightPersonId;
                    $boxShadow = $isHighlighted ? '0 0 0 3px rgba(255,193,7,0.4),0 0 12px rgba(255,193,7,0.3)' : 'none';
                    ?>
                    <div class="face-bbox position-absolute rounded face-box"
                         style="left:<?= $x ?>%;top:<?= $y ?>%;width:<?= $w ?>%;height:<?= $h ?>%;border:2px <?= $isHighlighted ? 'solid #ffc107' : 'solid #0d6efd' ?>;<?= $isHighlighted ? 'border-width:3px;' : '' ?>box-shadow:<?= $boxShadow ?>;"
                         title="Face #<?= $face['id'] ?><?= $personName ? ' – ' . esc($personName) : '' ?><?= isset($face['detection_score']) && $face['detection_score'] !== null ? ' (score: ' . round((float) $face['detection_score'], 2) . ')' : '' ?>"
                         data-face-id="<?= $face['id'] ?>"
                         data-person-id="<?= $face['person_id'] ?? '' ?>"
                         data-person-name="<?= esc($personName) ?>"
                         data-age="<?= $face['age'] ?? '' ?>"
                         data-gender="<?= $face['gender'] ?? '' ?>"
                         data-emotion="<?= $face['emotion'] ?? '' ?>"
                         data-detection-score="<?= isset($face['detection_score']) && $face['detection_score'] !== null ? round((float) $face['detection_score'], 2) : '' ?>"
                         data-bbox-x="<?= $face['bbox_x'] ?>"
                         data-bbox-y="<?= $face['bbox_y'] ?>"
                         data-bbox-w="<?= $face['bbox_w'] ?>"
                         data-bbox-h="<?= $face['bbox_h'] ?>"
                         data-le-x="<?= $face['landmark_left_eye_x'] ?? '' ?>"
                         data-le-y="<?= $face['landmark_left_eye_y'] ?? '' ?>"
                         data-re-x="<?= $face['landmark_right_eye_x'] ?? '' ?>"
                         data-re-y="<?= $face['landmark_right_eye_y'] ?? '' ?>"
                         data-nt-x="<?= $face['landmark_nose_x'] ?? '' ?>"
                         data-nt-y="<?= $face['landmark_nose_y'] ?? '' ?>"
                         data-lm-x="<?= $face['landmark_left_mouth_x'] ?? '' ?>"
                         data-lm-y="<?= $face['landmark_left_mouth_y'] ?? '' ?>"
                         data-rm-x="<?= $face['landmark_right_mouth_x'] ?? '' ?>"
                         data-rm-y="<?= $face['landmark_right_mouth_y'] ?? '' ?>"
                         data-img-w="<?= $photo['width'] ?: 800 ?>"
                         data-img-h="<?= $photo['height'] ?: 600 ?>"
                         data-bs-toggle="tooltip"
                         onclick="showFaceModal(this)">
                        <span class="position-absolute bottom-0 start-0 bg-dark bg-opacity-75 <?= $personName ? 'text-white' : 'text-white-50' ?> px-1 small face-bbox-name"
                              style="font-size:10px;line-height:1.2;"><?= esc($personName ?: 'Unnamed') ?></span>
                        <!-- Hover Landmark overlays -->
                        <?php if (isset($face['landmark_left_eye_x']) && $face['bbox_w'] > 0): ?>
                            <?php
                            $le_x = (($face['landmark_left_eye_x'] - $face['bbox_x']) / $face['bbox_w']) * 100;
                            $le_y = (($face['landmark_left_eye_y'] - $face['bbox_y']) / $face['bbox_h']) * 100;
                            
                            $re_x = (($face['landmark_right_eye_x'] - $face['bbox_x']) / $face['bbox_w']) * 100;
                            $re_y = (($face['landmark_right_eye_y'] - $face['bbox_y']) / $face['bbox_h']) * 100;
                            
                            $nt_x = (($face['landmark_nose_x'] - $face['bbox_x']) / $face['bbox_w']) * 100;
                            $nt_y = (($face['landmark_nose_y'] - $face['bbox_y']) / $face['bbox_h']) * 100;
                            
                            $lm_x = (($face['landmark_left_mouth_x'] - $face['bbox_x']) / $face['bbox_w']) * 100;
                            $lm_y = (($face['landmark_left_mouth_y'] - $face['bbox_y']) / $face['bbox_h']) * 100;
                            
                            $rm_x = (($face['landmark_right_mouth_x'] - $face['bbox_x']) / $face['bbox_w']) * 100;
                            $rm_y = (($face['landmark_right_mouth_y'] - $face['bbox_y']) / $face['bbox_h']) * 100;
                            ?>
                            <div class="landmark-dot-overlay position-absolute rounded-circle" style="left:calc(<?= $le_x ?>% - 3px);top:calc(<?= $le_y ?>% - 3px);width:6px;height:6px;background:#3b82f6;box-shadow:0 0 4px #fff;opacity:0;transition:opacity 0.2s;" title="Left Eye"></div>
                            <div class="landmark-dot-overlay position-absolute rounded-circle" style="left:calc(<?= $re_x ?>% - 3px);top:calc(<?= $re_y ?>% - 3px);width:6px;height:6px;background:#3b82f6;box-shadow:0 0 4px #fff;opacity:0;transition:opacity 0.2s;" title="Right Eye"></div>
                            <div class="landmark-dot-overlay position-absolute rounded-circle" style="left:calc(<?= $nt_x ?>% - 3px);top:calc(<?= $nt_y ?>% - 3px);width:6px;height:6px;background:#10b981;box-shadow:0 0 4px #fff;opacity:0;transition:opacity 0.2s;" title="Nose Tip"></div>
                            <div class="landmark-dot-overlay position-absolute rounded-circle" style="left:calc(<?= $lm_x ?>% - 3px);top:calc(<?= $lm_y ?>% - 3px);width:6px;height:6px;background:#ef4444;box-shadow:0 0 4px #fff;opacity:0;transition:opacity 0.2s;" title="Left Mouth Corner"></div>
                            <div class="landmark-dot-overlay position-absolute rounded-circle" style="left:calc(<?= $rm_x ?>% - 3px);top:calc(<?= $rm_y ?>% - 3px);width:6px;height:6px;background:#ef4444;box-shadow:0 0 4px #fff;opacity:0;transition:opacity 0.2s;" title="Right Mouth Corner"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Column: Details Panel -->
        <div class="col-lg-4">
            <!-- Details Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-info-circle me-2"></i>Photo Details</h5>
                    <table class="table table-sm table-borderless small mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 35%;">Filename:</td>
                                <td class="text-break"><?= esc($photo['filename']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dimension:</td>
                                <td><?= $photo['width'] && $photo['height'] ? $photo['width'] . ' × ' . $photo['height'] : 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">File Size:</td>
                                <td><?= $sizeFormatted ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mime Type:</td>
                                <td><?= esc($photo['mime_type'] ?: 'Unknown') ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Taken At:</td>
                                <td><?= $photo['taken_at'] ? date('M j, Y g:i A', strtotime($photo['taken_at'])) : 'Unknown' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- EXIF Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-camera me-2"></i>EXIF Metadata</h5>
                    <?php if (!empty($exif)): ?>
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <?php if (!empty($exif['Make']) || !empty($exif['Model'])): ?>
                                    <tr>
                                        <td class="text-muted" style="width: 35%;">Camera:</td>
                                        <td><?= esc(($exif['Make'] ?? '') . ' ' . ($exif['Model'] ?? '')) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($exif['FocalLength'])): ?>
                                    <tr>
                                        <td class="text-muted">Focal Length:</td>
                                        <td><?= esc(formatExifFraction($exif['FocalLength'])) ?> mm</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($exif['FNumber']) || !empty($exif['ApertureFNumber'])): ?>
                                    <tr>
                                        <td class="text-muted">Aperture:</td>
                                        <td>f/<?= esc(formatExifFraction($exif['FNumber'] ?? $exif['ApertureFNumber'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($exif['ExposureTime'])): ?>
                                    <tr>
                                        <td class="text-muted">Exposure:</td>
                                        <td><?= esc(formatExifFraction($exif['ExposureTime'])) ?> s</td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (isset($exif['ISOSpeedRatings']) || isset($exif['ISO'])): ?>
                                    <tr>
                                        <td class="text-muted">ISO:</td>
                                        <td><?= esc($exif['ISOSpeedRatings'] ?? ($exif['ISO'] ?? '')) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-muted small">No camera EXIF data found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Faces Card -->
            <?php if (!empty($faces)): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3"><i class="bi bi-people me-2"></i>Faces in Photo (<?= count($faces) ?>)</h6>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($faces as $face): ?>
                                <?php
                                $personName = '';
                                if ($face['person_id']) {
                                    $person = model('App\Models\PersonModel')->find($face['person_id']);
                                    $personName = $person['name'] ?? '';
                                }
                                $isFaceHighlighted = $highlightPersonId && $face['person_id'] == $highlightPersonId;
                                
                                $fw = $photo['width'] ?: 800;
                                $fh = $photo['height'] ?: 600;
                                $coverage = 0;
                                if ($face['bbox_w'] > 0 && $fw > 0) {
                                    $coverage = (($face['bbox_w'] * $face['bbox_h']) / ($fw * $fh)) * 100;
                                }

                                $px = ($face['bbox_x'] / $fw) * 100;
                                $py = ($face['bbox_y'] / $fh) * 100;
                                ?>
                                <div class="d-flex align-items-center p-2 rounded border <?= $isFaceHighlighted ? 'border-warning bg-warning bg-opacity-10' : '' ?>" 
                                     id="faceListItem-<?= $face['id'] ?>"
                                     onclick="triggerFaceDetails(<?= $face['id'] ?>)" style="cursor:pointer; background: var(--bs-tertiary-bg); transition: background-color 0.2s;">
                                    <div class="rounded-circle overflow-hidden flex-shrink-0 me-3" 
                                         style="width:40px;height:40px;background:url(<?= base_url($photo['path']) ?>) no-repeat;background-size:<?= 100 / ($face['bbox_w'] / $fw) ?>% <?= 100 / ($face['bbox_h'] / $fh) ?>%;background-position:<?= $px ?>% <?= $py ?>%;"></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate small face-item-name"><?= $personName ? esc($personName) : 'Unassigned Face' ?></div>
                                        <div class="text-muted face-item-meta" style="font-size:10px; line-height: 1.2;">
                                            Sex: <span class="meta-val-gender"><?= $face['gender'] ? ucfirst($face['gender']) : 'Unknown' ?></span> | 
                                            Age: <span class="meta-val-age"><?= $face['age'] ? '~' . $face['age'] . 'y' : 'Unknown' ?></span><br>
                                            Emotion: <span class="meta-val-emotion"><?= esc(($face['emotion'] ?? null) ?: 'Auto detect') ?></span> | 
                                            Coverage: <?= round($coverage, 2) ?>%
                                        </div>
                                    </div>
                                    <div class="text-muted small"><i class="bi bi-chevron-right"></i></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Location Card -->
            <?php if ($photo['latitude'] && $photo['longitude']): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="bi bi-geo-alt me-2"></i>Location</h5>
                        <div class="small mb-2">
                            <strong>GPS:</strong> <?= round($photo['latitude'], 5) ?>, <?= round($photo['longitude'], 5) ?>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $photo['latitude'] ?>,<?= $photo['longitude'] ?>" 
                           target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-map me-1"></i>Open Google Maps
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tags Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-tags me-2"></i>Tags</h5>
                    <div id="photoTagsContainer" class="d-flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($tags)): ?>
                            <?php foreach ($tags as $t): ?>
                                <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill" data-tag-val="<?= esc($t['tag']) ?>">
                                    <?= esc($t['tag']) ?>
                                    <i class="bi bi-x-circle-fill text-white-50 cursor-pointer" onclick="removePhotoTag(<?= $photo['id'] ?>, '<?= esc($t['tag'], 'js') ?>')" style="font-size:12px; cursor: pointer;"></i>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small" id="noTagsText">No tags yet.</span>
                        <?php endif; ?>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="newTagInput" class="form-control" placeholder="Add tag (e.g. cat, dog)">
                        <button class="btn btn-primary" type="button" onclick="addPhotoTag(<?= $photo['id'] ?>)">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Controls Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap gap-2 justify-content-center">
                    <button class="btn btn-sm <?= $photo['is_favorite'] ? 'btn-warning' : 'btn-outline-warning' ?> flex-grow-1"
                            onclick="togglePhotoFavorite(<?= $photo['id'] ?>)" id="btnFav">
                        <i class="bi <?= $photo['is_favorite'] ? 'bi-star-fill' : 'bi-star' ?> me-1"></i> Favorite
                    </button>
                    <button class="btn btn-sm <?= $photo['is_archived'] ? 'btn-info text-white' : 'btn-outline-info' ?> flex-grow-1"
                            onclick="togglePhotoArchive(<?= $photo['id'] ?>)" id="btnArch">
                        <i class="bi <?= $photo['is_archived'] ? 'bi-archive-fill' : 'bi-archive' ?> me-1"></i> Archive
                    </button>
                    <button class="btn btn-sm btn-outline-danger flex-grow-1"
                            onclick="deletePhotoItem(<?= $photo['id'] ?>)">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">Photo not found.</div>
<?php endif; ?>

<!-- Face Info Modal -->
<div class="modal fade" id="faceModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Face Analysis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row align-items-center">
                    <div class="col-md-5 text-center border-end">
                        <div id="faceModalThumb" class="rounded-3 mx-auto mb-3 overflow-hidden position-relative" style="width:150px;height:150px;background:var(--card-bg);"></div>
                        <div id="faceNameWrapper" class="px-2">
                            <div id="faceNameDisplay" class="d-flex align-items-center justify-content-center gap-1">
                                <h5 id="faceModalName" class="mb-0 text-truncate" style="max-width: 140px;"></h5>
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted" id="btnQuickRename" title="Rename or assign person" onclick="enterEditMode()">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                            <div id="faceNameEditGroup" class="d-none mt-2">
                                <label class="form-label small text-muted mb-1 text-start d-block">Person Name / Identity:</label>
                                <input type="text" id="editPersonName" class="form-control form-control-sm text-center fw-semibold" placeholder="e.g. John" list="allPersonsList">
                                <div class="text-muted small" style="font-size:10px;">Select existing or type new</div>
                            </div>
                        </div>
                        <datalist id="allPersonsList">
                            <?php if (!empty($allPersons)): ?>
                                <?php foreach ($allPersons as $p): ?>
                                    <?php if (!empty($p['name'])): ?>
                                        <option value="<?= esc($p['name']) ?>"><?= esc($p['name']) ?> (<?= $p['face_count'] ?? 0 ?> faces)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </datalist>
                        <div id="faceModalLink" class="mt-3"></div>
                    </div>
                    <div class="col-md-7">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 45%;">Sex / Gender:</td>
                                    <td id="metaGender" class="fw-medium text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estimated Age:</td>
                                    <td id="metaAge" class="fw-medium text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Confidence:</td>
                                    <td id="metaScore" class="fw-medium text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Expression:</td>
                                    <td id="metaExpression" class="fw-medium text-dark-emphasis"></td>
                                </tr>
                                <tr class="border-top">
                                    <td class="text-muted pt-2">Face Roll (Tilt):</td>
                                    <td id="metaRoll" class="pt-2 text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Eye Distance (IPD):</td>
                                    <td id="metaIPD" class="text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Crop Resolution:</td>
                                    <td id="metaResolution" class="text-dark-emphasis"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Face Coverage:</td>
                                    <td id="metaCoverage" class="text-dark-emphasis"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnOpenMergeSelection"><i class="bi bi-person-plus me-1"></i>Merge/Add Faces</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnEditFaceMeta"><i class="bi bi-pencil-square me-1"></i>Edit Attributes</button>
                <button type="button" class="btn btn-primary btn-sm d-none" id="btnSaveFaceMeta"><i class="bi bi-check-lg me-1"></i>Save</button>
                <button type="button" class="btn btn-secondary btn-sm d-none" id="btnCancelFaceMeta">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Face Selection & Merging Modal -->
<div class="modal fade" id="mergeSelectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Select Faces to Add / Merge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Check the boxes of the faces you want to merge under this person, then click **Merge Selected**.
                </div>
                
                <div id="mergeSelectionGrid" class="row row-cols-3 row-cols-sm-4 row-cols-md-5 g-3 overflow-y-auto" style="max-height: 400px; min-height: 150px; padding: 10px;">
                    <!-- Filled dynamically via JS -->
                </div>
                
                <div id="mergeSelectionEmpty" class="text-center py-5 d-none text-muted">
                    <i class="bi bi-emoji-smile" style="font-size: 2.5rem;"></i>
                    <p class="mt-2 mb-0">No unassigned faces found to merge.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSubmitMergeSelection" disabled>
                    <i class="bi bi-person-check me-1"></i>Merge Selected (<span id="mergeSelectionCount">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.face-box, .face-card-clickable { cursor: pointer; }
.face-box:hover { opacity: 0.85; }
.face-bbox:hover .landmark-dot-overlay {
    opacity: 1 !important;
}
.landmark-dot {
    border: 1px solid #fff;
    box-shadow: 0 0 3px rgba(0,0,0,0.5);
}
.merge-select-card {
    position: relative;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 6px;
    overflow: hidden;
    background: var(--card-bg);
    transition: all 0.2s;
}
.merge-select-card:hover {
    transform: translateY(-2px);
}
.merge-select-card.selected {
    border-color: #0d6efd;
    box-shadow: 0 0 8px rgba(13,110,253,0.3);
}
.merge-select-checkbox {
    position: absolute;
    top: 5px;
    right: 5px;
    z-index: 10;
    width: 18px;
    height: 18px;
    cursor: pointer;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const personPhotos = <?= json_encode(array_map(function($p) {
    return ['id' => $p['id'], 'path' => base_url($p['path']), 'thumbnail_path' => base_url($p['thumbnail_path'] ?: $p['path'])];
}, $personPhotos)) ?>;
let currentIndex = <?= $currentIndex ?>;

function triggerFaceDetails(faceId) {
    const el = document.querySelector(`.face-bbox[data-face-id="${faceId}"]`);
    if (el) {
        showFaceModal(el);
    }
}

function togglePhotoFavorite(photoId) {
    const btn = $('#btnFav');
    btn.prop('disabled', true);
    $.post(BASE_URL + 'photos/favorite/' + photoId, function(res) {
        if (res.status === 'success') {
            // handle both boolean and string representations
            const isFav = res.is_favorite === true || res.is_favorite === 1 || res.is_favorite === '1' || res.is_favorite === 'true';
            if (isFav) {
                btn.removeClass('btn-outline-warning').addClass('btn-warning');
                btn.html('<i class="bi bi-star-fill me-1"></i> Favorite');
                showToast('Added to Favorites');
            } else {
                btn.removeClass('btn-warning').addClass('btn-outline-warning');
                btn.html('<i class="bi bi-star me-1"></i> Favorite');
                showToast('Removed from Favorites');
            }
        } else {
            showToast('Action failed: ' + (res.message || 'Error'), 'danger');
        }
    }, 'json').always(() => btn.prop('disabled', false));
}

function togglePhotoArchive(photoId) {
    const btn = $('#btnArch');
    btn.prop('disabled', true);
    $.post(BASE_URL + 'photos/archive/' + photoId, function(res) {
        if (res.status === 'success') {
            const isArchived = res.is_archived === true || res.is_archived === 1 || res.is_archived === '1' || res.is_archived === 'true';
            if (isArchived) {
                btn.removeClass('btn-outline-info').addClass('btn-info text-white');
                btn.html('<i class="bi bi-archive-fill me-1"></i> Archive');
                showToast('Photo Archived');
            } else {
                btn.removeClass('btn-info text-white').addClass('btn-outline-info');
                btn.html('<i class="bi bi-archive me-1"></i> Archive');
                showToast('Photo Unarchived');
            }
        } else {
            showToast('Action failed: ' + (res.message || 'Error'), 'danger');
        }
    }, 'json').always(() => btn.prop('disabled', false));
}

function deletePhotoItem(photoId) {
    if (!confirm('Are you sure you want to delete this photo? It will be moved to Trash.')) return;
    $.post(BASE_URL + 'photos/delete/' + photoId, function(res) {
        if (res.status === 'success') {
            showToast('Photo moved to Trash');
            setTimeout(() => {
                window.location.href = '<?= base_url('faces') ?>';
            }, 1000);
        } else {
            showToast('Delete failed: ' + (res.message || 'Error'), 'danger');
        }
    }, 'json');
}

function addPhotoTag(photoId) {
    const input = $('#newTagInput');
    const tag = input.val().trim();
    if (!tag) return;

    $.post(BASE_URL + 'photos/tags/add', {
        photo_id: photoId,
        tag: tag
    }, function(res) {
        if (res.status === 'success') {
            input.val('');
            $('#noTagsText').remove();
            
            if ($(`span[data-tag-val="${tag}"]`).length === 0) {
                const badge = `
                    <span class="badge bg-primary d-flex align-items-center gap-1 py-1.5 px-2.5 rounded-pill" data-tag-val="${tag}">
                        ${tag}
                        <i class="bi bi-x-circle-fill text-white-50 cursor-pointer" onclick="removePhotoTag(${photoId}, '${tag}')" style="font-size:12px; cursor: pointer;"></i>
                    </span>
                `;
                $('#photoTagsContainer').append(badge);
            }
            showToast('Tag added successfully!');
        } else {
            showToast('Failed to add tag: ' + (res.message || 'Error'), 'danger');
        }
    }, 'json');
}

function removePhotoTag(photoId, tag) {
    $.post(BASE_URL + 'photos/tags/remove', {
        photo_id: photoId,
        tag: tag
    }, function(res) {
        if (res.status === 'success') {
            $(`span[data-tag-val="${tag}"]`).remove();
            if ($('#photoTagsContainer').children().length === 0) {
                $('#photoTagsContainer').html('<span class="text-muted small" id="noTagsText">No tags yet.</span>');
            }
            showToast('Tag removed successfully!');
        } else {
            showToast('Failed to remove tag: ' + (res.message || 'Error'), 'danger');
        }
    }, 'json');
}

let activeTriggerElement = null;
let currentFaceId = null;
let isEditing = false;
let originalHtmls = {};

function navigatePhoto(dir) {
    const newIdx = currentIndex + dir;
    if (newIdx < 0 || newIdx >= personPhotos.length) return;
    const url = '<?= base_url('faces/photo/') ?>' + personPhotos[newIdx].id + '?person=<?= $highlightPersonId ?>';
    window.location.href = url;
}

function showFaceModal(el) {
    activeTriggerElement = el;
    currentFaceId = el.dataset.faceId;
    
    // Ensure we exit edit mode before drawing the new face modal details
    exitEditMode(false);

    const personId = el.dataset.personId;
    const personName = el.dataset.personName;
    const age = el.dataset.age;
    const gender = el.dataset.gender;
    const score = el.dataset.detectionScore;
    const savedEmotion = el.dataset.emotion;

    const thumbDiv = el.querySelector('div[style*="background:url"]') || el;
    const bg = thumbDiv.style.background || '';

    const thumb = document.getElementById('faceModalThumb');
    if (bg) {
        thumb.style.background = bg;
        thumb.style.backgroundSize = thumbDiv.style.backgroundSize || 'cover';
        thumb.style.backgroundPosition = thumbDiv.style.backgroundPosition || 'center';
        thumb.innerHTML = '';
    } else {
        thumb.style.background = '';
        thumb.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-person" style="font-size:3rem;"></i></div>';
    }

    // Draw landmark keypoint dots inside the modal thumbnail crop
    const leX = parseFloat(el.dataset.leX);
    const leY = parseFloat(el.dataset.leY);
    const reX = parseFloat(el.dataset.reX);
    const reY = parseFloat(el.dataset.reY);
    const ntX = parseFloat(el.dataset.ntX);
    const ntY = parseFloat(el.dataset.ntY);
    const lmX = parseFloat(el.dataset.lmX);
    const lmY = parseFloat(el.dataset.lmY);
    const rmX = parseFloat(el.dataset.rmX);
    const rmY = parseFloat(el.dataset.rmY);

    const bboxX = parseFloat(el.dataset.bboxX);
    const bboxY = parseFloat(el.dataset.bboxY);
    const bboxW = parseFloat(el.dataset.bboxW);
    const bboxH = parseFloat(el.dataset.bboxH);
    const imgW = parseFloat(el.dataset.imgW);
    const imgH = parseFloat(el.dataset.imgH);

    if (bboxW > 0 && bboxH > 0) {
        const landmarks = [
            { x: leX, y: leY, color: '#3b82f6', title: 'Left Eye' },
            { x: reX, y: reY, color: '#3b82f6', title: 'Right Eye' },
            { x: ntX, y: ntY, color: '#10b981', title: 'Nose Tip' },
            { x: lmX, y: lmY, color: '#ef4444', title: 'Left Mouth Corner' },
            { x: rmX, y: rmY, color: '#ef4444', title: 'Right Mouth Corner' }
        ];

        landmarks.forEach(lm => {
            if (!isNaN(lm.x) && !isNaN(lm.y)) {
                const relX = ((lm.x - bboxX) / bboxW) * 100;
                const relY = ((lm.y - bboxY) / bboxH) * 100;

                const dot = document.createElement('div');
                dot.className = 'landmark-dot position-absolute rounded-circle';
                dot.style.width = '6px';
                dot.style.height = '6px';
                dot.style.background = lm.color;
                dot.style.left = `calc(${relX}% - 3px)`;
                dot.style.top = `calc(${relY}% - 3px)`;
                dot.title = lm.title;
                thumb.appendChild(dot);
            }
        });
    }

    document.getElementById('faceModalName').textContent = personName || 'Unassigned Face';

    // Set simple attributes
    document.getElementById('metaGender').innerHTML = gender ? 
        `<span class="badge ${gender.toLowerCase() === 'male' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger'}">${gender.charAt(0).toUpperCase() + gender.slice(1)}</span>` : 
        '<span class="text-muted">Unknown</span>';
        
    document.getElementById('metaAge').innerHTML = age ? 
        `<span class="badge bg-secondary-subtle text-secondary-emphasis">~${age} years</span>` : 
        '<span class="text-muted">Unknown</span>';

    document.getElementById('metaScore').innerHTML = score ? 
        `<div class="d-flex align-items-center gap-2">
            <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                <div class="progress-bar" style="width: ${score * 100}%"></div>
            </div>
            <span>${(score * 100).toFixed(1)}%</span>
         </div>` : 
        '<span class="text-muted">N/A</span>';

    // Calculate advanced biometrics
    let ipdText = '<span class="text-muted">N/A</span>';
    let rollText = '<span class="text-muted">N/A</span>';
    let expressionText = savedEmotion || 'Neutral 🙂';

    if (!isNaN(leX) && !isNaN(reX)) {
        const ipd = Math.hypot(reX - leX, reY - leY);
        ipdText = `${ipd.toFixed(1)} px`;

        const angle = Math.atan2(reY - leY, reX - leX) * (180 / Math.PI);
        rollText = `${angle.toFixed(1)}°`;

        if (!savedEmotion && !isNaN(lmX) && !isNaN(rmX)) {
            const mouthWidth = Math.hypot(rmX - lmX, rmY - lmY);
            const ratio = mouthWidth / ipd;
            if (ratio > 0.82) {
                expressionText = 'Smiling 😊';
            } else if (ratio < 0.62) {
                expressionText = 'Serious/Neutral 😐';
            }
        }
    }
    document.getElementById('metaIPD').innerHTML = ipdText;
    document.getElementById('metaRoll').innerHTML = rollText;
    document.getElementById('metaExpression').textContent = expressionText;

    let resText = '<span class="text-muted">N/A</span>';
    if (!isNaN(bboxW) && !isNaN(bboxH)) {
        let quality = 'Low';
        const minDim = Math.min(bboxW, bboxH);
        if (minDim >= 150) quality = 'High';
        else if (minDim >= 80) quality = 'Medium';
        resText = `${Math.round(bboxW)} × ${Math.round(bboxH)} px (${quality})`;
    }
    document.getElementById('metaResolution').innerHTML = resText;

    let coverageText = '<span class="text-muted">N/A</span>';
    if (!isNaN(bboxW) && !isNaN(imgW)) {
        const coverage = ((bboxW * bboxH) / (imgW * imgH)) * 100;
        coverageText = `${coverage.toFixed(2)}%`;
    }
    document.getElementById('metaCoverage').innerHTML = coverageText;

    const link = document.getElementById('faceModalLink');
    if (personId) {
        link.innerHTML = '<a href="<?= base_url('faces/person/') ?>' + personId + '" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>View Person</a>';
    } else {
        link.innerHTML = '';
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('faceModal'));
    modal.show();
}

function enterEditMode() {
    if (isEditing) return;
    isEditing = true;

    // Show save/cancel, hide edit
    $('#btnEditFaceMeta').addClass('d-none');
    $('#btnSaveFaceMeta, #btnCancelFaceMeta').removeClass('d-none');

    // Show person name input
    $('#faceNameDisplay').addClass('d-none');
    $('#faceNameEditGroup').removeClass('d-none');
    const currentName = activeTriggerElement.dataset.personName || '';
    $('#editPersonName').val(currentName);

    // Get current values
    const currentGender = (activeTriggerElement.dataset.gender || '').toLowerCase();
    const currentAge = activeTriggerElement.dataset.age || '';
    const currentEmotion = activeTriggerElement.dataset.emotion || '';

    // Backup original cells
    originalHtmls['metaGender'] = $('#metaGender').html();
    originalHtmls['metaAge'] = $('#metaAge').html();
    originalHtmls['metaExpression'] = $('#metaExpression').html();

    // Replace Gender
    $('#metaGender').html(`
        <select id="editGender" class="form-select form-select-sm" style="width: auto;">
            <option value="" ${currentGender === '' ? 'selected' : ''}>Unknown</option>
            <option value="male" ${currentGender === 'male' ? 'selected' : ''}>Male</option>
            <option value="female" ${currentGender === 'female' ? 'selected' : ''}>Female</option>
            <option value="other" ${currentGender === 'other' ? 'selected' : ''}>Other</option>
        </select>
    `);

    // Replace Age
    $('#metaAge').html(`
        <input type="number" id="editAge" class="form-control form-control-sm" style="width: 80px;" min="0" max="120" value="${currentAge}" placeholder="Age">
    `);

    // Replace Emotion/Expression
    $('#metaExpression').html(`
        <select id="editEmotion" class="form-select form-select-sm" style="width: auto;">
            <option value="" ${currentEmotion === '' ? 'selected' : ''}>Baseline (Auto) 🙂</option>
            <option value="Smiling 😊" ${currentEmotion.indexOf('Smil') !== -1 || currentEmotion.indexOf('Happy') !== -1 ? 'selected' : ''}>Smiling / Happy 😊</option>
            <option value="Serious/Neutral 😐" ${currentEmotion.indexOf('Neutral') !== -1 || currentEmotion.indexOf('Serious') !== -1 ? 'selected' : ''}>Serious / Neutral 😐</option>
            <option value="Sad 😢" ${currentEmotion.indexOf('Sad') !== -1 ? 'selected' : ''}>Sad 😢</option>
            <option value="Angry 😠" ${currentEmotion.indexOf('Angry') !== -1 ? 'selected' : ''}>Angry 😠</option>
            <option value="Surprised 😮" ${currentEmotion.indexOf('Surpris') !== -1 ? 'selected' : ''}>Surprised 😮</option>
            <option value="Scared 😨" ${currentEmotion.indexOf('Scared') !== -1 || currentEmotion.indexOf('Fear') !== -1 ? 'selected' : ''}>Fearful / Scared 😨</option>
            <option value="Disgusted 🤢" ${currentEmotion.indexOf('Disgust') !== -1 ? 'selected' : ''}>Disgusted 🤢</option>
        </select>
    `);
}

function exitEditMode(save = false) {
    isEditing = false;
    $('#btnEditFaceMeta').removeClass('d-none');
    $('#btnSaveFaceMeta, #btnCancelFaceMeta').addClass('d-none');

    $('#faceNameDisplay').removeClass('d-none');
    $('#faceNameEditGroup').addClass('d-none');

    if (!save && originalHtmls['metaGender']) {
        // Restore backups
        $('#metaGender').html(originalHtmls['metaGender']);
        $('#metaAge').html(originalHtmls['metaAge']);
        $('#metaExpression').html(originalHtmls['metaExpression']);
    }
}

$(function() {
    // Keyboard navigation
    $(document).on('keydown', function(e) {
        if (e.key === 'ArrowLeft') navigatePhoto(-1);
        else if (e.key === 'ArrowRight') navigatePhoto(1);
    });
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Tag input keypress listener
    $('#newTagInput').on('keypress', function(e) {
        if (e.which === 13) {
            addPhotoTag(<?= $photo['id'] ?>);
        }
    });

    // Edit modal listeners
    $('#btnEditFaceMeta').on('click', enterEditMode);
    $('#btnCancelFaceMeta').on('click', () => exitEditMode(false));

    $('#btnSaveFaceMeta').on('click', function() {
        const btn = $(this).prop('disabled', true);
        const origText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

        const g = $('#editGender').val();
        const a = $('#editAge').val();
        const em = $('#editEmotion').val();
        const personName = $('#editPersonName').val().trim();

        $.post(BASE_URL + 'api/v1/faces/update-metadata', {
            face_id: currentFaceId,
            gender: g,
            age: a,
            emotion: em,
            person_name: personName
        }, function(res) {
            if (res.status === 'success') {
                showToast('Face attributes and identity saved successfully!');

                const newName = res.person_name !== undefined ? res.person_name : personName;
                const newPersonId = res.person_id !== undefined ? res.person_id : activeTriggerElement.dataset.personId;

                // Update activeTriggerElement dataset
                activeTriggerElement.dataset.gender = g;
                activeTriggerElement.dataset.age = a;
                activeTriggerElement.dataset.emotion = em;
                activeTriggerElement.dataset.personName = newName;
                activeTriggerElement.dataset.personId = newPersonId || '';

                // Update face bbox badge on photo
                let bboxNameSpan = activeTriggerElement.querySelector('.face-bbox-name');
                if (!bboxNameSpan) {
                    bboxNameSpan = document.createElement('span');
                    bboxNameSpan.className = 'position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white px-1 small face-bbox-name';
                    bboxNameSpan.style.fontSize = '10px';
                    bboxNameSpan.style.lineHeight = '1.2';
                    activeTriggerElement.appendChild(bboxNameSpan);
                }
                bboxNameSpan.textContent = newName || 'Unnamed';
                bboxNameSpan.className = newName ? 'position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white px-1 small face-bbox-name' : 'position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white-50 px-1 small face-bbox-name';
                activeTriggerElement.title = `Face #${currentFaceId}${newName ? ' – ' + newName : ''}`;

                // Update sidebar list item
                const listItem = document.getElementById('faceListItem-' + currentFaceId);
                if (listItem) {
                    const nameEl = listItem.querySelector('.face-item-name');
                    if (nameEl) nameEl.textContent = newName || 'Unassigned Face';
                    const gEl = listItem.querySelector('.meta-val-gender');
                    if (gEl) gEl.textContent = g ? g.charAt(0).toUpperCase() + g.slice(1) : 'Unknown';
                    const aEl = listItem.querySelector('.meta-val-age');
                    if (aEl) aEl.textContent = a ? '~' + a + 'y' : 'Unknown';
                    const emEl = listItem.querySelector('.meta-val-emotion');
                    if (emEl) emEl.textContent = em || 'Auto detect';
                }

                exitEditMode(true);

                // Reload visual attributes in the modal
                showFaceModal(activeTriggerElement);
            } else {
                showToast('Save failed: ' + (res.message || 'Error'), 'danger');
            }
        }, 'json').always(() => {
            btn.prop('disabled', false).html(origText);
        });
    });

    // Merge Selection Modal Logic
    let selectionModal = new bootstrap.Modal(document.getElementById('mergeSelectionModal'));
    let selectedFaceIds = [];

    $('#btnOpenMergeSelection').on('click', function() {
        const mainModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('faceModal'));
        mainModal.hide();

        $('#mergeSelectionGrid').html('<div class="w-100 text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
        $('#mergeSelectionEmpty').addClass('d-none');
        selectedFaceIds = [];
        updateSelectionSubmitButton();

        $.getJSON(BASE_URL + 'faces/unassigned', function(res) {
            if (res.status === 'success') {
                const faces = res.faces;
                if (faces.length === 0 || (faces.length === 1 && faces[0].face_id == currentFaceId)) {
                    $('#mergeSelectionGrid').html('');
                    $('#mergeSelectionEmpty').removeClass('d-none');
                    return;
                }

                let html = '';
                faces.forEach(f => {
                    if (f.face_id == currentFaceId) return;

                    const px = (f.bbox.x / f.photo_width) * 100;
                    const py = (f.bbox.y / f.photo_height) * 100;
                    const sw = (f.bbox.w / f.photo_width) * 100;
                    const sh = (f.bbox.h / f.photo_height) * 100;
                    const bsW = 100 / sw * 100;
                    const bsH = 100 / sh * 100;

                    html += `
                        <div class="col">
                            <div class="merge-select-card" data-face-id="${f.face_id}">
                                <input type="checkbox" class="merge-select-checkbox form-check-input" value="${f.face_id}">
                                <div style="width:100%;height:100px;background:url(${f.photo_path}) no-repeat;background-size:${bsW}% ${bsH}%;background-position:${px}% ${py}%;"></div>
                                <div class="p-1 text-center small text-muted text-truncate" style="font-size:10px;">ID: ${f.face_id}</div>
                            </div>
                        </div>
                    `;
                });
                $('#mergeSelectionGrid').html(html);

                $('.merge-select-card').on('click', function(e) {
                    if (e.target.type === 'checkbox') return;
                    const checkbox = $(this).find('.merge-select-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                });

                $('.merge-select-checkbox').on('change', function() {
                    const val = parseInt($(this).val());
                    const card = $(this).closest('.merge-select-card');
                    if ($(this).is(':checked')) {
                        card.addClass('selected');
                        if (!selectedFaceIds.includes(val)) selectedFaceIds.push(val);
                    } else {
                        card.removeClass('selected');
                        selectedFaceIds = selectedFaceIds.filter(id => id !== val);
                    }
                    updateSelectionSubmitButton();
                });
            } else {
                $('#mergeSelectionGrid').html(`<div class="alert alert-danger w-100">${res.message || 'Error loading faces'}</div>`);
            }
        });

        selectionModal.show();
    });

    function updateSelectionSubmitButton() {
        const count = selectedFaceIds.length;
        $('#mergeSelectionCount').text(count);
        $('#btnSubmitMergeSelection').prop('disabled', count === 0);
    }

    $('#btnSubmitMergeSelection').on('click', function() {
        const targetPersonId = activeTriggerElement.dataset.personId || 'new';
        const btn = $(this).prop('disabled', true);
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Merging...');

        const ids = [...selectedFaceIds];
        if (targetPersonId === 'new') {
            ids.push(parseInt(currentFaceId));
        }

        $.post(BASE_URL + 'faces/bulk-assign', {
            face_ids: ids,
            person_id: targetPersonId
        }, function(res) {
            if (res.status === 'success') {
                showToast(`Successfully merged ${res.updated_count} faces!`);
                selectionModal.hide();
                setTimeout(() => {
                    location.reload();
                }, 800);
            } else {
                showToast('Merge failed: ' + (res.message || 'Error'), 'danger');
                btn.prop('disabled', false).html(originalText);
            }
        }, 'json');
    });
});
</script>
<?= $this->endSection() ?>
