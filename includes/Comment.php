<?php
/**
 * Comment Model
 * Handles all comment CRUD operations
 */

class Comment {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Create a new comment
     */
    public function create($data) {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO comments (user_id, username, avatar_color, city, country, language_code, comment_text) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssssss",
            $data['user_id'],
            $data['username'],
            $data['avatar_color'],
            $data['city'],
            $data['country'],
            $data['language_code'],
            $data['comment_text']
        );
        
        $success = mysqli_stmt_execute($stmt);
        $commentId = mysqli_insert_id($this->conn);
        mysqli_stmt_close($stmt);

        if ($success) {
            return $this->getById($commentId);
        }
        return null;
    }

    /**
     * Get a comment by ID
     */
    public function getById($id) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM comments WHERE comment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comment = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $comment;
    }

    /**
     * Get all active comments (paginated)
     */
    public function getAll($page = 1, $limit = 20, $includeHidden = false) {
        $offset = ($page - 1) * $limit;
        
        if ($includeHidden) {
            $sql = "SELECT * FROM comments ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        } else {
            $sql = "SELECT * FROM comments WHERE status = 'active' ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        mysqli_stmt_close($stmt);

        // Get total count
        if ($includeHidden) {
            $countResult = mysqli_query($this->conn, "SELECT COUNT(*) as total FROM comments");
        } else {
            $countResult = mysqli_query($this->conn, "SELECT COUNT(*) as total FROM comments WHERE status = 'active'");
        }
        $total = mysqli_fetch_assoc($countResult)['total'];

        return [
            'comments' => $comments,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ];
    }

    /**
     * Get comments by status (for admin)
     */
    public function getByStatus($status) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM comments WHERE status = ? ORDER BY created_at DESC");
        mysqli_stmt_bind_param($stmt, "s", $status);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $comments;
    }

    /**
     * Update comment status
     */
    public function updateStatus($commentId, $status) {
        $stmt = mysqli_prepare($this->conn, "UPDATE comments SET status = ? WHERE comment_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status, $commentId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    /**
     * Delete comment permanently
     */
    public function delete($commentId) {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM comments WHERE comment_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $commentId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    /**
     * Add like
     */
    public function addLike($commentId, $userId) {
        // Check if already liked
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            // Remove like
            $stmt = mysqli_prepare($this->conn, "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Decrement count
            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET like_count = GREATEST(like_count - 1, 0) WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['action' => 'removed', 'liked' => false];
        } else {
            // Remove dislike if exists (mutual exclusion)
            $this->removeDislike($commentId, $userId);

            // Add like
            $stmt = mysqli_prepare($this->conn, "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Increment count
            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET like_count = like_count + 1 WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['action' => 'added', 'liked' => true];
        }
    }

    /**
     * Add dislike
     */
    public function addDislike($commentId, $userId) {
        // Check if already disliked
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_dislikes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            // Remove dislike
            $stmt = mysqli_prepare($this->conn, "DELETE FROM comment_dislikes WHERE comment_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Decrement count
            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET dislike_count = GREATEST(dislike_count - 1, 0) WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['action' => 'removed', 'disliked' => false];
        } else {
            // Remove like if exists (mutual exclusion)
            $this->removeLike($commentId, $userId);

            // Add dislike
            $stmt = mysqli_prepare($this->conn, "INSERT INTO comment_dislikes (comment_id, user_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Increment count
            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET dislike_count = dislike_count + 1 WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            return ['action' => 'added', 'disliked' => true];
        }
    }

    /**
     * Remove like silently (used when adding dislike)
     */
    private function removeLike($commentId, $userId) {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $stmt = mysqli_prepare($this->conn, "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET like_count = GREATEST(like_count - 1, 0) WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /**
     * Remove dislike silently (used when adding like)
     */
    private function removeDislike($commentId, $userId) {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_dislikes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $stmt = mysqli_prepare($this->conn, "DELETE FROM comment_dislikes WHERE comment_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($this->conn, "UPDATE comments SET dislike_count = GREATEST(dislike_count - 1, 0) WHERE comment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $commentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /**
     * Check if user has liked/disliked a comment
     */
    public function getUserReaction($commentId, $userId) {
        $liked = false;
        $disliked = false;

        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) $liked = true;
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($this->conn, "SELECT id FROM comment_dislikes WHERE comment_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "is", $commentId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) $disliked = true;
        mysqli_stmt_close($stmt);

        return ['liked' => $liked, 'disliked' => $disliked];
    }

    /**
     * Search comments
     */
    public function search($query) {
        $searchTerm = '%' . $query . '%';
        $stmt = mysqli_prepare($this->conn, 
            "SELECT * FROM comments WHERE (comment_text LIKE ? OR username LIKE ?) ORDER BY created_at DESC LIMIT 50"
        );
        mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $comments;
    }

    /**
     * Get stats for admin dashboard
     */
    public function getStats() {
        $stats = [];
        
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM comments");
        $stats['total'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM comments WHERE status = 'active'");
        $stats['active'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM comments WHERE status = 'hidden'");
        $stats['hidden'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM comments WHERE status = 'removed'");
        $stats['removed'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT SUM(like_count) as c FROM comments");
        $stats['totalLikes'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT SUM(dislike_count) as c FROM comments");
        $stats['totalDislikes'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT COUNT(*) as c FROM moderation_logs");
        $stats['moderationActions'] = (int)mysqli_fetch_assoc($result)['c'];

        $result = mysqli_query($this->conn, "SELECT COUNT(DISTINCT language_code) as c FROM comments");
        $stats['languages'] = (int)mysqli_fetch_assoc($result)['c'];

        return $stats;
    }
}
?>
