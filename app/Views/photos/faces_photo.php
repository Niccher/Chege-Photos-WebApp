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
    <div class="d-flex justify-content-center">
        <div class="position-relative d-inline-block">
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
            <img src="<?= base_url($photo['path']) ?>" alt="Photo" class="img-fluid rounded d-block mx-auto"
                 id="facePhoto" style="max-height:85vh;width:auto;">
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
                     title="Face #<?= $face['id'] ?><?= $personName ? ' – ' . esc($personName) : '' ?> (score: <?= round($face['detection_score'], 2) ?>)"
                     data-face-id="<?= $face['id'] ?>"
                     data-person-id="<?= $face['person_id'] ?? '' ?>"
                     data-person-name="<?= esc($personName) ?>"
                     data-age="<?= $face['age'] ?? '' ?>"
                     data-gender="<?= $face['gender'] ?? '' ?>"
                     data-detection-score="<?= round($face['detection_score'], 2) ?>"
                     data-bs-toggle="tooltip"
                     onclick="showFaceModal(this)">
                    <?php if ($personName): ?>
                        <span class="position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white px-1 small"
                              style="font-size:10px;line-height:1.2;"><?= esc($personName) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!empty($faces)): ?>
        <div class="mt-3 text-center">
            <h6>Detected Faces (<?= count($faces) ?>)</h6>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <?php foreach ($faces as $face): ?>
                    <?php
                    $personName = '';
                    if ($face['person_id']) {
                        $person = model('App\Models\PersonModel')->find($face['person_id']);
                        $personName = $person['name'] ?? '';
                    }
                    $isHighlighted = $highlightPersonId && $face['person_id'] == $highlightPersonId;
                    ?>
                    <div class="card border-0 face-card-clickable text-start" style="width:160px;background:var(--card-bg);cursor:pointer;<?= $isHighlighted ? 'outline:3px solid #ffc107;outline-offset:2px;' : '' ?>"
                         onclick="showFaceModal(this)"
                         data-face-id="<?= $face['id'] ?>"
                         data-person-id="<?= $face['person_id'] ?? '' ?>"
                         data-person-name="<?= esc($personName) ?>"
                         data-age="<?= $face['age'] ?? '' ?>"
                         data-gender="<?= $face['gender'] ?? '' ?>"
                         data-detection-score="<?= round($face['detection_score'], 2) ?>">
                        <?php
                        $pw = $photo['width'] ?: 800;
                        $ph = $photo['height'] ?: 600;
                        $px = ($face['bbox_x'] / $pw) * 100;
                        $py = ($face['bbox_y'] / $ph) * 100;
                        $fw = ($face['bbox_w'] / $pw) * 100;
                        $fh = ($face['bbox_h'] / $ph) * 100;
                        ?>
                        <div style="width:100%;height:110px;background:url(<?= base_url($photo['path']) ?>) no-repeat;background-size:<?= 100 / ($face['bbox_w'] / $pw) ?>% <?= 100 / ($face['bbox_h'] / $ph) ?>%;background-position:<?= $px ?>% <?= $py ?>%;border-radius:8px 8px 0 0;"></div>
                        <div class="p-2 text-center small">
                            <?php if ($personName): ?>
                                <div class="fw-medium"><?= esc($personName) ?></div>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                            <div class="text-muted" style="font-size:10px;">score: <?= round($face['detection_score'], 2) ?></div>
                            <?php if ($face['age'] || $face['gender']): ?>
                                <div class="text-muted" style="font-size:10px;">
                                    <?= $face['age'] ? 'Age: ~' . $face['age'] . 'y' : '' ?>
                                    <?= $face['age'] && $face['gender'] ? ' | ' : '' ?>
                                    <?= $face['gender'] ? ucfirst($face['gender']) : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="text-center py-5 text-muted">Photo not found.</div>
<?php endif; ?>

<!-- Face Info Modal -->
<div class="modal fade" id="faceModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="faceModalThumb" class="rounded-3 mx-auto mb-3 overflow-hidden" style="width:120px;height:120px;background:var(--card-bg);"></div>
                <h5 id="faceModalName" class="mb-0"></h5>
                <div id="faceModalAttrs" class="small text-muted mt-1"></div>
                <div id="faceModalLink" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.face-box, .face-card-clickable { cursor: pointer; }
.face-box:hover { opacity: 0.85; }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const personPhotos = <?= json_encode(array_map(function($p) {
    return ['id' => $p['id'], 'path' => base_url($p['path']), 'thumbnail_path' => base_url($p['thumbnail_path'] ?: $p['path'])];
}, $personPhotos)) ?>;
let currentIndex = <?= $currentIndex ?>;

function navigatePhoto(dir) {
    const newIdx = currentIndex + dir;
    if (newIdx < 0 || newIdx >= personPhotos.length) return;
    const url = '<?= base_url('faces/photo/') ?>' + personPhotos[newIdx].id + '?person=<?= $highlightPersonId ?>';
    window.location.href = url;
}

function showFaceModal(el) {
    const personId = el.dataset.personId;
    const personName = el.dataset.personName;
    const age = el.dataset.age;
    const gender = el.dataset.gender;
    const score = el.dataset.detectionScore;

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

    document.getElementById('faceModalName').textContent = personName || 'Unassigned Face';

    let attrs = 'score: ' + score;
    if (age) attrs += ' | ~' + age + 'y';
    if (gender) attrs += ' | ' + gender.charAt(0).toUpperCase() + gender.slice(1);
    document.getElementById('faceModalAttrs').textContent = attrs;

    const link = document.getElementById('faceModalLink');
    if (personId) {
        link.innerHTML = '<a href="<?= base_url('faces/person/') ?>' + personId + '" class="btn btn-outline-primary btn-sm"><i class="bi bi-people me-1"></i>View Person</a>';
    } else {
        link.innerHTML = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('faceModal'));
    modal.show();
}

$(function() {
    // Keyboard navigation
    $(document).on('keydown', function(e) {
        if (e.key === 'ArrowLeft') navigatePhoto(-1);
        else if (e.key === 'ArrowRight') navigatePhoto(1);
    });
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
<?= $this->endSection() ?>
