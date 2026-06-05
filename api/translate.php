<?php
/**
 * Translate API Endpoint
 * POST /api/translate.php - Translate a comment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Translator.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['languages'])) {
    echo json_encode([
        'success' => true,
        'data' => Translator::getSupportedLanguages()
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['comment_id']) || empty($input['target_lang'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'comment_id and target_lang required']);
    exit;
}

// Get the comment
$stmt = mysqli_prepare($conn, "SELECT comment_text, language_code FROM comments WHERE comment_id = ?");
mysqli_stmt_bind_param($stmt, "i", $input['comment_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$comment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$comment) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Comment not found']);
    exit;
}

$translator = new Translator($conn);
$translation = $translator->translate(
    (int)$input['comment_id'],
    $comment['comment_text'],
    $comment['language_code'],
    $input['target_lang']
);

if (isset($translation['error'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $translation['error']]);
} else {
    echo json_encode([
        'success' => true,
        'data' => [
            'original_text' => $comment['comment_text'],
            'translated_text' => $translation['translated'],
            'source_lang' => $comment['language_code'],
            'target_lang' => $input['target_lang'],
            'cached' => $translation['cached']
        ]
    ]);
}

mysqli_close($conn);
?>
