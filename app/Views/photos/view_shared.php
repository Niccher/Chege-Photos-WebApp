<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Photo — Photos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            color: #fff;
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .viewer-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .photo-wrapper {
            max-width: 100%;
            max-height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        img, video {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            border-radius: 8px;
        }
        .overlay-header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 2rem;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.5px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand span { color: #4285f4; }
        .footer-info {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 2rem;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            z-index: 10;
            text-align: center;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
        }
    </style>
</head>
<body>

<div class="viewer-container">
    <div class="overlay-header">
        <a href="#" class="brand"><span>Photos</span> Shared</a>
    </div>

    <?php if (!empty($passwordRequired)): ?>
        <div class="card p-4 text-white shadow-lg" style="background: rgba(30, 30, 30, 0.95); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 12px; max-width: 400px; width: 90%;">
            <div class="text-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#4285f4" class="bi bi-shield-lock mb-2" viewBox="0 0 16 16">
                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                    <path d="M9.5 6.5a1.5 1.5 0 0 1-1 1.415l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 1 1 9.5 6.5"/>
                </svg>
                <h5 class="fw-bold mb-1">Protected Photo</h5>
                <p class="text-secondary small mb-0">Enter the password to view this shared photo.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('s/' . esc($token)) ?>">
                <div class="mb-3">
                    <input type="password" name="password" class="form-control bg-black text-white border-secondary" placeholder="Password" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Unlock Photo</button>
            </form>
        </div>
    <?php else: ?>
        <div class="photo-wrapper">
            <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                <video src="<?= base_url($photo['path']) ?>" controls autoplay></video>
            <?php else: ?>
                <img src="<?= base_url($photo['path']) ?>" alt="Shared Photo">
            <?php endif; ?>
        </div>

        <div class="footer-info">
            Shared via Photos App &bull; <?= date('F j, Y', strtotime($photo['taken_at'])) ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
