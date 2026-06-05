<?php
/**
 * Database Setup Script
 * Run this once to create all required tables
 */

$conn = mysqli_connect('localhost', 'root', '');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS comment_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, 'comment_system');
mysqli_set_charset($conn, "utf8mb4");

// Comments table
$sql1 = "CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    username VARCHAR(100) NOT NULL,
    avatar_color VARCHAR(7) DEFAULT '#6C63FF',
    city VARCHAR(100) DEFAULT '',
    country VARCHAR(100) DEFAULT '',
    language_code VARCHAR(10) DEFAULT 'en',
    comment_text TEXT NOT NULL,
    like_count INT DEFAULT 0,
    dislike_count INT DEFAULT 0,
    status ENUM('active', 'hidden', 'removed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Likes table
$sql2 = "CREATE TABLE IF NOT EXISTS comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Dislikes table
$sql3 = "CREATE TABLE IF NOT EXISTS comment_dislikes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dislike (comment_id, user_id),
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Moderation logs table
$sql4 = "CREATE TABLE IF NOT EXISTS moderation_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    action ENUM('hidden', 'removed', 'restored', 'spam_blocked', 'char_blocked') NOT NULL,
    reason TEXT,
    performed_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Translations cache table
$sql5 = "CREATE TABLE IF NOT EXISTS translations_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    source_lang VARCHAR(10) NOT NULL,
    target_lang VARCHAR(10) NOT NULL,
    translated_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (comment_id, source_lang, target_lang),
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$queries = [$sql1, $sql2, $sql3, $sql4, $sql5];
$tables = ['comments', 'comment_likes', 'comment_dislikes', 'moderation_logs', 'translations_cache'];
$success = true;

echo "<html><head><title>Comment System Setup</title>";
echo "<style>body{font-family:'Segoe UI',sans-serif;background:#0f0f23;color:#e0e0e0;padding:40px;max-width:700px;margin:0 auto}";
echo ".ok{color:#00ff88;}.err{color:#ff4466;}.box{background:#1a1a2e;border-radius:12px;padding:24px;margin:16px 0;border:1px solid #2a2a4a}";
echo "h1{color:#6C63FF;}a{color:#6C63FF;}</style></head><body>";
echo "<h1>🚀 Comment System Setup</h1>";

foreach ($queries as $i => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "<div class='box'><span class='ok'>✅</span> Table <strong>{$tables[$i]}</strong> created successfully.</div>";
    } else {
        echo "<div class='box'><span class='err'>❌</span> Error creating <strong>{$tables[$i]}</strong>: " . mysqli_error($conn) . "</div>";
        $success = false;
    }
}

if ($success) {
    echo "<div class='box' style='border-color:#6C63FF'><h3>🎉 Setup Complete!</h3>";
    echo "<p>All tables created successfully. Your comment system is ready!</p>";
    echo "<p><a href='index.html'>💬 Open Comment System →</a></p>";
    echo "<p><a href='admin/index.html'>🛡️ Open Admin Dashboard →</a></p></div>";
} else {
    echo "<div class='box' style='border-color:#ff4466'><h3>⚠️ Setup had errors</h3><p>Please fix the errors above and try again.</p></div>";
}

mysqli_close($conn);
echo "</body></html>";
?>
