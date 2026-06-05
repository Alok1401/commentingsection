<?php
/**
 * Like API Endpoint
 * POST /api/like.php - Toggle like on a comment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Comment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['comment_id']) || empty($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'comment_id and user_id required']);
    exit;
}

$comment = new Comment($conn);
$result = $comment->addLike((int)$input['comment_id'], $input['user_id']);

// Get updated comment
$updatedComment = $comment->getById((int)$input['comment_id']);

echo json_encode([
    'success' => true,
    'data' => array_merge($result, [
        'like_count' => (int)$updatedComment['like_count'],
        'dislike_count' => (int)$updatedComment['dislike_count']
    ])
]);

mysqli_close($conn);
?>
