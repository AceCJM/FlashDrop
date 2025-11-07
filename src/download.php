<?php
// src/download.php - Handle file download and deletion
// Load environment variables
require_once __DIR__ . '/env.php';
global $UPLOAD_DIR;
session_start();
// Check if 'id' parameter is provided
if (isset($_GET['id'])) {
    $uniqueId = $_GET['id'];

    // Basic validation: expect 32-hex chars (generated via bin2hex(random_bytes(16)))
    if (!preg_match('/^[0-9a-f]{32}$/', $uniqueId)) {
        http_response_code(400);
        echo "Invalid file ID.";
        exit;
    }

    // Check if the file exists in the session
    if (isset($_SESSION['files'][$uniqueId])) {
        $file = $_SESSION['files'][$uniqueId];
        // Serve the file for download
        if (file_exists($file[0])) {
            $filePath = $file[0];
            $fileName = basename($file[1]);

            // Attempt to detect mime type, fallback to octet-stream
            $mime = 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $detected = @mime_content_type($filePath);
                if ($detected) {
                    $mime = $detected;
                }
            }

            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));

            // Clear output buffers if any to avoid corrupting the file stream
            if (ob_get_level()) {
                ob_end_clean();
            }

            readfile($filePath);
            // Delete the file after download
            @unlink($filePath);
            // Remove the file entry from the session
            unset($_SESSION['files'][$uniqueId]);
            exit;
        } else {
            http_response_code(404);
            echo "File not found.";
            exit;
        }
    } else {
        http_response_code(404);
        echo "Invalid or expired link.";
        exit;
    }
} else {
    http_response_code(400);
    echo "No file ID provided.";
    exit;
}
