<?php
/**
 * Moderator Class
 * Handles auto-moderation: spam detection, special char blocking, dislike threshold
 */

class Moderator {
    private $conn;
    private $dislikeThreshold = 2;
    
    // Patterns that indicate spam/unwanted content
    private $blockedPatterns = [
        '/(.)\1{4,}/',                          // Same char repeated 5+ times
        '/[!@#$%^&*]{4,}/',                     // 4+ consecutive special chars
        '/[\x{0000}-\x{001F}]{3,}/u',          // Control characters
        '/(\$){3,}/',                            // Repeated dollar signs
        '/([@]){3,}/',                           // Repeated @ signs
        '/([%]){3,}/',                           // Repeated percent signs
        '/([!]){4,}/',                           // 4+ exclamation marks
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Check if comment text passes moderation
     * Returns: ['passed' => bool, 'reason' => string]
     */
    public function checkComment($text) {
        // Empty check
        if (empty(trim($text))) {
            return ['passed' => false, 'reason' => 'Comment cannot be empty.'];
        }

        // Length check
        if (mb_strlen($text) > 500) {
            return ['passed' => false, 'reason' => 'Comment exceeds 500 character limit.'];
        }

        // Special character blocking
        foreach ($this->blockedPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'passed' => false, 
                    'reason' => 'Comment contains unsupported characters.'
                ];
            }
        }

        // Check for pure symbol comments (less than 20% alphanumeric/unicode letters)
        $letterCount = preg_match_all('/[\p{L}\p{N}]/u', $text);
        $totalCount = mb_strlen(trim($text));
        if ($totalCount > 3 && $letterCount / $totalCount < 0.2) {
            return [
                'passed' => false,
                'reason' => 'Comment contains unsupported characters.'
            ];
        }

        return ['passed' => true, 'reason' => ''];
    }

    /**
     * Check dislike threshold and auto-hide if needed
     */
    public function checkDislikeThreshold($commentId) {
        $stmt = mysqli_prepare($this->conn, "SELECT dislike_count, status FROM comments WHERE comment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $commentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comment = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$comment) return false;

        if ($comment['dislike_count'] >= $this->dislikeThreshold && $comment['status'] === 'active') {
            // Auto-hide the comment
            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET status = 'hidden' WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Log the moderation action
            $this->logAction($commentId, 'hidden', 'Auto-hidden: reached ' . $this->dislikeThreshold . ' dislikes threshold');

            return true;
        }

        return false;
    }

    /**
     * Log a moderation action
     */
    public function logAction($commentId, $action, $reason, $performedBy = 'system') {
        $stmt = mysqli_prepare($this->conn, 
            "INSERT INTO moderation_logs (comment_id, action, reason, performed_by) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "isss", $commentId, $action, $reason, $performedBy);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
