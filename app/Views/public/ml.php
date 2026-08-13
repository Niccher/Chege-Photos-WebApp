<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>ML Backend — Face recognition powered by Insightface and Qdrant<?= $this->endSection() ?>
<?= $this->section('description') ?>ML Chege Photos is a FastAPI microservice for face detection, embedding extraction, similarity search, and HDBSCAN clustering. Powered by Insightface Buffalo-L and Qdrant vector database.<?= $this->endSection() ?>
<?= $this->section('nav-ml') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="ML Chege Photos — Face recognition microservice">
<meta property="og:description" content="FastAPI microservice with Insightface (Buffalo-L) for face detection and 512-d embeddings, Qdrant for vector search, and HDBSCAN for clustering.">
<meta name="keywords" content="face recognition, insightface, qdrant, hdbscan, fastapi, machine learning, python, docker">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>ML Backend</h1>
    <p>Face detection, embedding, clustering, and attribute classification — a FastAPI microservice that powers all face features across the web and Android apps.</p>
    <div class="mt-3 d-flex gap-2 flex-wrap justify-content-center">
        <span class="ml-tag ml-tag-fastapi">FastAPI 0.115</span>
        <span class="ml-tag ml-tag-insight">Insightface 0.7.3</span>
        <span class="ml-tag ml-tag-qdrant">Qdrant 1.13</span>
        <span class="ml-tag ml-tag-hdbscan">HDBSCAN</span>
    </div>
</div>

<section class="section">
    <div class="section-header">
        <h2>What does it do?</h2>
        <p>The ML service turns raw photos into searchable, organised face data. It sits between the web app and two data stores — MySQL for face metadata and Qdrant for vector embeddings.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-eye"></i></div>
                <h5>Detect</h5>
                <p style="font-size:0.85rem;">RetinaFace detector locates every face in a photo, returning bounding boxes and 5-point landmarks (eyes, nose, mouth corners). Configurable confidence threshold.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-vector-pen"></i></div>
                <h5>Embed</h5>
                <p style="font-size:0.85rem;">Each detected face is converted to a 512-dimensional embedding via ArcFace (ResNet-100). Cosine distance between embeddings reflects facial similarity.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-diagram-3"></i></div>
                <h5>Cluster</h5>
                <p style="font-size:0.85rem;">HDBSCAN groups similar embeddings into persons without requiring a predefined number of clusters. Noise points (unassigned faces) remain ungrouped.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="card-icon"><i class="bi bi-search"></i></div>
                <h5>Search</h5>
                <p style="font-size:0.85rem;">Upload any photo and find the most similar faces in your entire library in under a second using Qdrant's approximate nearest neighbour (ANN) index.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Face recognition pipeline</h2>
        <p>The complete flow from raw image to organised person groups.</p>
    </div>
    <div class="diagram-box">
┌──────────┐    ┌─────────────┐    ┌──────────┐    ┌──────────┐    ┌─────────┐
│  Photo   │───▶│  RetinaFace  │───▶│  ArcFace  │───▶│  Qdrant  │───▶│HDBSCAN  │
│ Uploaded │    │  Detector    │    │  Encoder  │    │  (ANN)   │    │Cluster  │
└──────────┘    └──────┬──────┘    └─────┬────┘    └──────────┘    └────┬────┘
                       │                 │                              │
                       ▼                 ▼                              ▼
                Bounding boxes      512-d vector                  Person groups
                5 landmarks         cosine distance               + centroids
                Confidence          age / gender
    </div>

    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <h5 style="font-weight:600; color: var(--text-primary);">Detection</h5>
            <table class="table table-sm table-pub">
                <thead><tr><th>Property</th><th>Value</th></tr></thead>
                <tbody>
                    <tr><td>Detector</td><td>RetinaFace (MobileNet0.25 backbone)</td></tr>
                    <tr><td>Threshold</td><td>Configurable via <code>FACE_DET_THRESH</code> (default 0.5)</td></tr>
                    <tr><td>Landmarks</td><td>5-point (left eye, right eye, nose, left mouth, right mouth)</td></tr>
                    <tr><td>Output</td><td>Bounding box (x, y, w, h) + score + landmarks</td></tr>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h5 style="font-weight:600; color: var(--text-primary);">Embedding</h5>
            <table class="table table-sm table-pub">
                <thead><tr><th>Property</th><th>Value</th></tr></thead>
                <tbody>
                    <tr><td>Model</td><td>Insightface (<?= esc($faceModelPack) ?>)</td></tr>
                    <tr><td>Loss</td><td>ArcFace (Additive Angular Margin)</td></tr>
                    <tr><td>Dimension</td><td>512</td></tr>
                    <tr><td>Training data</td><td>MS1MV3 (5.8M images, 93K IDs)</td></tr>
                    <tr><td>Distance metric</td><td>Cosine (range 0.0 — identical, 2.0 — orthogonal)</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Clustering & Person Management</h2>
        <p>Once embeddings are stored, the service can group them into persons and manage those groups over time.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>HDBSCAN Clustering</h5>
                <table class="table table-sm table-pub mb-0">
                    <thead><tr><th>Parameter</th><th>Default</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>min_cluster_size</code></td><td>2</td><td>Smallest group considered a cluster</td></tr>
                        <tr><td><code>min_samples</code></td><td>1</td><td>Number of neighbours for core point</td></tr>
                        <tr><td><code>metric</code></td><td>cosine</td><td>Distance metric for embedding comparison</td></tr>
                    </tbody>
                </table>
                <p class="mt-3 mb-0" style="color:var(--text-muted);font-size:0.85rem;">Faces labelled as noise (-1) remain unassigned. The web app displays these under "Unknown" for manual review.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Centroid Management</h5>
                <p style="font-size:0.9rem;">After clustering, each person gets a <strong>centroid</strong> — the mean embedding of all their assigned faces. Centroid vectors are stored in a separate Qdrant collection (<code>face_embeddings_centroids</code>) for fast person-level similarity queries.</p>
                <p style="font-size:0.9rem;" class="mb-0">The service supports <strong>merge</strong> (combine two persons, recompute centroid) and <strong>split</strong> (re-cluster faces within a person).</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>ML vs Heuristics</h2>
        <p>Why a learned embedding approach outperforms traditional metadata-based organisation.</p>
    </div>
    <div class="card-pub">
        <table class="table table-sm table-pub mb-0">
            <thead><tr><th>Capability</th><th>Heuristic / EXIF</th><th>ML (this service)</th></tr></thead>
            <tbody>
                <tr><td>Works across varied poses</td><td style="color:var(--text-muted);">No</td><td>Yes — ArcFace is pose-invariant</td></tr>
                <tr><td>Works across lighting conditions</td><td style="color:var(--text-muted);">No</td><td>Yes — trained on 5.8M diverse images</td></tr>
                <tr><td>Handles occlusions (sunglasses, masks)</td><td style="color:var(--text-muted);">No</td><td>Yes — robust embedding model</td></tr>
                <tr><td>Automatic person grouping</td><td style="color:var(--text-muted);">Manual albums only</td><td>Unsupervised HDBSCAN clustering</td></tr>
                <tr><td>Similarity search ("find this face")</td><td style="color:var(--text-muted);">Not possible</td><td>Sub-second cosine ANN search</td></tr>
                <tr><td>Per-face granularity</td><td style="color:var(--text-muted);">Photo-level tags</td><td>Per-face bounding box + embedding</td></tr>
                <tr><td>Age / gender estimation</td><td style="color:var(--text-muted);">Not possible</td><td>Insightface attribute heads (gated)</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>API Overview</h2>
        <p>The ML service exposes REST endpoints under <code>/api/v1/faces</code>. The web app's Faces controller proxies most calls via cURL.</p>
    </div>
    <div class="card-pub">
        <table class="table table-sm table-pub mb-0">
            <thead><tr><th>Method</th><th>Path</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>POST</code></td><td><code>/api/v1/faces/encode</code></td><td>Detect + embed + persist to Qdrant + MySQL for a photo</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/faces/search</code></td><td>Upload query image, return top-N similar faces</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/faces/cluster</code></td><td>HDBSCAN cluster all embeddings, create persons</td></tr>
                <tr><td><code>GET</code></td><td><code>/api/v1/faces/by-photo/{id}</code></td><td>List faces for a photo with bbox and attributes</td></tr>
                <tr><td><code>GET</code></td><td><code>/api/v1/faces/persons</code></td><td>List all persons with face count and thumbnail</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/faces/{id}/reclassify</code></td><td>Re-run clustering for a single face</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/faces/delete-by-photo-ids</code></td><td>Bulk delete faces, encodings, persons, clusters</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/scan/{photo_id}</code></td><td>Trigger single-photo scan (async)</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/scan</code></td><td>Start batch scan with progress tracking</td></tr>
                <tr><td><code>GET</code></td><td><code>/api/v1/clusters</code></td><td>List face clusters with centroids</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/clusters/{id}/merge</code></td><td>Merge two clusters</td></tr>
                <tr><td><code>POST</code></td><td><code>/api/v1/clusters/{id}/split</code></td><td>Split cluster into sub-clusters</td></tr>
                <tr><td><code>GET</code></td><td><code>/health</code></td><td>Liveness check (DB + Qdrant + models)</td></tr>
                <tr><td><code>POST</code></td><td><code>/models/reload</code></td><td>Dynamically reload model pack weights in memory without container restarts</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Configuration</h2>
        <p>The ML service is configured via environment variables in <code>.env</code>.</p>
    </div>
    <div class="card-pub">
        <table class="table table-sm table-pub mb-0">
            <thead><tr><th>Variable</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>DB_HOST</code></td><td><code>mysql</code></td><td>MySQL hostname (Docker service name)</td></tr>
                <tr><td><code>DB_NAME</code></td><td><code>ml_chege_photos</code></td><td>MySQL database name</td></tr>
                <tr><td><code>QDRANT_HOST</code></td><td><code>qdrant</code></td><td>Qdrant hostname</td></tr>
                <tr><td><code>QDRANT_COLLECTION</code></td><td><code>face_embeddings</code></td><td>Qdrant collection for face vectors</td></tr>
                <tr><td><code>FACE_MODEL_PACK</code></td><td><code>buffalo_l</code></td><td>Insightface model pack name</td></tr>
                <tr><td><code>FACE_DET_THRESH</code></td><td><code>0.5</code></td><td>Detection confidence threshold</td></tr>
                <tr><td><code>INCLUDE_SENSITIVE_ATTRIBUTES</code></td><td><code>false</code></td><td>Enable age/gender estimation</td></tr>
                <tr><td><code>HDBSCAN_MIN_CLUSTER_SIZE</code></td><td><code>2</code></td><td>Minimum faces per cluster</td></tr>
                <tr><td><code>CLUSTER_METRIC</code></td><td><code>cosine</code></td><td>Distance metric for clustering</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Deployment</h2>
        <p>The ML service runs in Docker alongside Qdrant. It mounts the web app's uploads directory read-only.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Docker Compose</h5>
                <pre class="code-block mt-2">services:
  ml-service:
    build: .
    ports:
      - "9051:8000"
    env_file: .env
    volumes:
      - uploads:/app/uploads:ro
      - insightface_models:/app/models
    depends_on:
      - ml-qdrant

  ml-qdrant:
    image: qdrant/qdrant:latest
    ports:
      - "9052:6333"   # HTTP
      - "9053:6334"   # gRPC
    volumes:
      - qdrant_data:/qdrant/storage</pre>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Quick Start</h5>
                <pre class="code-block mt-2"># From the ML service directory:
docker compose up --build -d

# Verify health:
curl http://localhost:9051/health

# Expected response:
{"status":"healthy",
 "db_connected":true,
 "qdrant_connected":true,
 "models_loaded":true}</pre>
                <p class="mt-2 mb-0" style="color:var(--text-muted);font-size:0.85rem;">The service starts even if MySQL or Qdrant is unavailable — health endpoint reports degraded status.</p>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
