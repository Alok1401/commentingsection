<?php
/**
 * Moderation API Endpoint (Admin)
 * GET  /api/moderation.php              - Get moderation logs
 * POST /api/moderation.php              - Perform moderation action
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Comment.php';
require_once __DIR__ . '/../includes/Moderator.php';

$comment = new Comment($conn);
$moderator = new Moderator($conn);
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
        
        $stmt = mysqli_prepare($conn, 
            "SELECT ml.*, c.username, c.comment_text 
             FROM moderation_logs ml 
             LEFT JOIN comments c ON ml.comment_id = c.comment_id 
             ORDER BY ml.created_at DESC 
             LIMIT ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $logs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $logs[] = $row;
        }
        mysqli_stmt_close($stmt);

        echo json_encode(['success' => true, 'data' => $logs]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['comment_id']) || empty($input['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'comment_id and action required']);
            break;
        }

        $commentId = (int)$input['comment_id'];
        $action = $input['action'];
        $reason = $input['reason'] ?? 'Admin action';

        switch ($action) {
            case 'remove':
                $comment->updateStatus($commentId, 'removed');
                $moderator->logAction($commentId, 'removed', $reason, 'admin');
                echo json_encode(['success' => true, 'message' => 'Comment removed']);
                break;

            case 'hide':
                $comment->updateStatus($commentId, 'hidden');
                $moderator->logAction($commentId, 'hidden', $reason, 'admin');
                echo json_encode(['success' => true, 'message' => 'Comment hidden']);
                break;

            case 'restore':
                $comment->updateStatus($commentId, 'active');
                $moderator->logAction($commentId, 'restored', $reason, 'admin');
                echo json_encode(['success' => true, 'message' => 'Comment restored']);
                break;

            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action. Use: remove, hide, restore']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

mysqli_close($conn);
?>
