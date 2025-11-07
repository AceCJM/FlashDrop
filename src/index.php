<?php
// src/index.php - Basic upload form and handler (refactored)

// Load environment variables
require_once 'env.php';

// Start session early (needed for storing one-time links)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $UPLOAD_DIR, $DOMAIN;

// Ensure upload directory exists
if (!is_dir($UPLOAD_DIR)) {
    if (!mkdir($UPLOAD_DIR, 0755, true) && !is_dir($UPLOAD_DIR)) {
        $globalUploadDirError = 'Failed to create upload directory.';
    }
}

$successMessage = '';
$errorMessage = '';
$link = '';

// Configuration: max file size in bytes (example: 200 MB)
$MAX_FILE_SIZE = 200 * 1024 * 1024;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'File upload error (code: ' . (int)$file['error'] . ').';
    } elseif ($file['size'] > $MAX_FILE_SIZE) {
        $errorMessage = 'File is too large. Maximum allowed is ' . ($MAX_FILE_SIZE / (1024 * 1024)) . ' MB.';
    } else {
        // Generate unique ID for the file
        try {
            $uniqueId = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $errorMessage = 'Could not generate a secure ID.';
            $uniqueId = null;
        }

        if ($uniqueId) {
            // Sanitize original filename
            $originalName = isset($file['name']) ? $file['name'] : 'file';
            $originalName = basename($originalName); // strip any path information
            // remove control and potentially unsafe characters
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

            $filePath = rtrim($UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueId . '_' . $safeName;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Store $uniqueId and $filePath in the session for download script
                if (!isset($_SESSION['files']) || !is_array($_SESSION['files'])) {
                    $_SESSION['files'] = [];
                }
                $_SESSION['files'][$uniqueId] = [$filePath, $safeName];

                // Generate one-time link (point to download.php)
                $link = rtrim($DOMAIN, '/') . '/download.php?id=' . $uniqueId;
                $successMessage = 'File uploaded. Share this link once — it will expire after download.';
            } else {
                $errorMessage = 'Failed to move uploaded file.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlashDrop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header class="header">
        <h1>FlashDrop</h1>
        <p>Upload a file, get a link, and it deletes after one download — no accounts, no traces.</p>
    </header>

    <main>
        <div class="card">
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="upload-area">
                        <div class="file-info">
                            <div class="file-label">Select a file to share</div>
                            <div class="file-muted">Maximum size: <?php echo ($MAX_FILE_SIZE / (1024*1024)); ?> MB</div>
                        </div>
                        <input type="file" name="file" id="fileInput" required>
                    </div>
                    <div class="actions">
                        <button type="submit" class="primary">Upload</button>
                        <button type="button" class="ghost" onclick="document.getElementById('fileInput').value = ''">Clear</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($successMessage)): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($successMessage); ?>
                    <?php if (!empty($link)): ?>
                        <div class="link-box">
                            <input class="link-input" type="text" readonly value="<?php echo htmlspecialchars($link); ?>" id="linkInput" aria-label="One-time download link">
                            <button class="copy-btn" id="copyBtn">Copy</button>
                        </div>
                        <div class="small">The link will stop working after a single successful download.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($globalUploadDirError)): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($globalUploadDirError); ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> FlashDrop — Privacy focused one-time file sharing</p>
    </footer>
</div>

<script>
// Small client-side helpers: copy link and show selected filename
document.getElementById('copyBtn')?.addEventListener('click', function(){
    var linkInput = document.getElementById('linkInput');
    if (!linkInput) return;
    linkInput.select();
    try {
        document.execCommand('copy');
        this.textContent = 'Copied';
        setTimeout(()=> this.textContent = 'Copy', 2000);
    } catch (e) {
        this.textContent = 'Copy';
    }
});

var fileInput = document.getElementById('fileInput');
fileInput?.addEventListener('change', function(){
    var label = document.querySelector('.file-label');
    if (this.files && this.files[0]) {
        label.textContent = this.files[0].name;
    } else {
        label.textContent = 'Select a file to share';
    }
});
</script>
</body>
</html>
