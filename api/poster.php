<?php
/**
 * api/poster.php — Serve a stored Canva poster image from the LONGBLOB column.
 * Requires authentication. Outputs raw image bytes with correct Content-Type.
 */
require_once __DIR__ . '/../config/db.php';
require_auth();

$designId = (int)($_GET['design_id'] ?? 0);
if ($designId <= 0) {
    http_response_code(400);
    echo 'Missing design_id';
    exit;
}

$row = db_fetch_one(
    "SELECT canva_bg_data, canva_bg_mime FROM invitation_designs WHERE id = :id LIMIT 1",
    ['id' => $designId]
);

if (!$row || empty($row['canva_bg_data'])) {
    http_response_code(404);
    echo 'Poster not found';
    exit;
}

$mime = $row['canva_bg_mime'] ?: 'image/png';

// Stream binary back to JS as a data-URL via JSON response
// (simpler than CORS/blob handling in JS)
$b64 = base64_encode(is_resource($row['canva_bg_data']) ? stream_get_contents($row['canva_bg_data']) : $row['canva_bg_data']);

header('Content-Type: application/json');
header('Cache-Control: private, max-age=3600');
echo json_encode(['dataUrl' => 'data:' . $mime . ';base64,' . $b64]);

