<?php
/**
 * Comments API Endpoint
 * GET  /api/comments.php              - Get all comments
 * GET  /api/comments.php?id=1         - Get single comment
 * GET  /api/comments.php?search=text  - Search comments
 * POST /api/comments.php              - Create comment
 * DELETE /api/comments.php?id=1       - Delete comment
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Comment.php';
require_once __DIR__ . '/../includes/Moderator.php';

$comment = new Comment($conn);
$moderator = new Moderator($conn);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single comment
            $result = $comment->getById((int)$_GET['id']);
            if ($result) {
                // Add user reaction if user_id provided
                if (isset($_GET['user_id'])) {
                    $result['user_reaction'] = $comment->getUserReaction($result['comment_id'], $_GET['user_id']);
                }
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comment not found']);
            }
        } elseif (isset($_GET['search'])) {
            // Search comments
            $results = $comment->search($_GET['search']);
            echo json_encode(['success' => true, 'data' => $results]);
        } elseif (isset($_GET['status'])) {
            // Get by status (admin)
            $results = $comment->getByStatus($_GET['status']);
            echo json_encode(['success' => true, 'data' => $results]);
        } elseif (isset($_GET['stats'])) {
            // Get stats (admin)
            $stats = $comment->getStats();
            echo json_encode(['success' => true, 'data' => $stats]);
        } else {
            // Get all comments
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
            $includeHidden = isset($_GET['include_hidden']) && $_GET['include_hidden'] === 'true';
            
            $results = $comment->getAll($page, $limit, $includeHidden);
            
            // Add user reactions if user_id provided
            if (isset($_GET['user_id'])) {
                foreach ($results['comments'] as &$c) {
                    $c['user_reaction'] = $comment->getUserReaction($c['comment_id'], $_GET['user_id']);
                }
            }
            
            echo json_encode(['success' => true, 'data' => $results]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            break;
        }

        // Validate required fields
        $required = ['user_id', 'username', 'comment_text'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '{$field}' is required"]);
                exit;
            }
        }

        // Sanitize input
        $input['username'] = htmlspecialchars(strip_tags($input['username']), ENT_QUOTES, 'UTF-8');
        $input['comment_text'] = htmlspecialchars($input['comment_text'], ENT_QUOTES, 'UTF-8');
        $input['city'] = htmlspecialchars($input['city'] ?? '', ENT_QUOTES, 'UTF-8');
        $input['country'] = htmlspecialchars($input['country'] ?? '', ENT_QUOTES, 'UTF-8');
        $input['language_code'] = $input['language_code'] ?? 'en';
        $input['avatar_color'] = $input['avatar_color'] ?? '#6C63FF';

        // Run moderation check
        $modCheck = $moderator->checkComment($input['comment_text']);
        if (!$modCheck['passed']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $modCheck['reason'], 'moderated' => true]);
            break;
        }

        // Rate limiting: max 5 comments per minute per user
        $stmt = mysqli_prepare($conn, 
            "SELECT COUNT(*) as cnt FROM comments WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)"
        );
        mysqli_stmt_bind_param($stmt, "s", $input['user_id']);
        mysqli_stmt_execute($stmt);
        $rateResult = mysqli_stmt_get_result($stmt);
        $rateCount = mysqli_fetch_assoc($rateResult)['cnt'];
        mysqli_stmt_close($stmt);

        if ($rateCount >= 5) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'Rate limit exceeded. Please wait before posting again.']);
            break;
        }

        // Create comment
        $result = $comment->create($input);
        if ($result) {
            http_response_code(201);
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to create comment']);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment ID required']);
            break;
        }

        $commentId = (int)$_GET['id'];
        $performedBy = $_GET['by'] ?? 'admin';

        // Update status to removed instead of deleting
        $success = $comment->updateStatus($commentId, 'removed');
        if ($success) {
            $moderator->logAction($commentId, 'removed', 'Manually removed by admin', $performedBy);
            echo json_encode(['success' => true, 'message' => 'Comment removed']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to remove comment']);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment ID required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = (int)$_GET['id'];

        if (isset($input['status'])) {
            $success = $comment->updateStatus($commentId, $input['status']);
            if ($success) {
                $action = $input['status'] === 'active' ? 'restored' : $input['status'];
                $moderator->logAction($commentId, $action, 'Status changed by admin', 'admin');
                echo json_encode(['success' => true, 'message' => 'Comment updated']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to update comment']);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

mysqli_close($conn);
?>
