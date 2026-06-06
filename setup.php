<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = mysqli_connect('sql206.infinityfree.com', 'if0_42102202', 'HF7xAqaubjweV', 'if0_42102202_comments');
$connected = $conn ? true : false;
if ($connected) mysqli_set_charset($conn, "utf8");

$tables = ['comments','comment_likes','comment_dislikes','moderation_logs','translations_cache'];
$results = [];

if ($connected) {
    $sqls = [
        "CREATE TABLE IF NOT EXISTS comments (comment_id INT AUTO_INCREMENT PRIMARY KEY,user_id VARCHAR(50) NOT NULL,username VARCHAR(100) NOT NULL,avatar_color VARCHAR(7) DEFAULT '#6C63FF',city VARCHAR(100) DEFAULT '',country VARCHAR(100) DEFAULT '',language_code VARCHAR(10) DEFAULT 'en',comment_text TEXT NOT NULL,like_count INT DEFAULT 0,dislike_count INT DEFAULT 0,status ENUM('active','hidden','removed') DEFAULT 'active',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS comment_likes (id INT AUTO_INCREMENT PRIMARY KEY,comment_id INT NOT NULL,user_id VARCHAR(50) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY unique_like(comment_id,user_id),FOREIGN KEY(comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS comment_dislikes (id INT AUTO_INCREMENT PRIMARY KEY,comment_id INT NOT NULL,user_id VARCHAR(50) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY unique_dislike(comment_id,user_id),FOREIGN KEY(comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS moderation_logs (log_id INT AUTO_INCREMENT PRIMARY KEY,comment_id INT NOT NULL,action ENUM('hidden','removed','restored','spam_blocked','char_blocked') NOT NULL,reason TEXT,performed_by VARCHAR(100) DEFAULT 'system',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS translations_cache (id INT AUTO_INCREMENT PRIMARY KEY,comment_id INT NOT NULL,source_lang VARCHAR(10) NOT NULL,target_lang VARCHAR(10) NOT NULL,translated_text TEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY unique_translation(comment_id,source_lang,target_lang),FOREIGN KEY(comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    ];
    foreach ($sqls as $i => $sql) {
        $results[] = mysqli_query($conn, $sql) ? 'ok' : mysqli_error($conn);
    }
    mysqli_close($conn);
}
$allOk = $connected && !in_array(false, array_map(function($r){return $r==='ok';}, $results));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — Multilingual Comment System</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#030014;color:#F0EDFF;min-height:100vh;overflow-x:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 600px 400px at 20% 20%,rgba(139,92,246,0.1),transparent),radial-gradient(ellipse 500px 400px at 80% 70%,rgba(0,229,160,0.07),transparent);pointer-events:none;animation:aur 10s ease-in-out infinite alternate}
@keyframes aur{0%{opacity:.7}50%{opacity:1}100%{opacity:.8}}

.nav{position:fixed;top:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:16px 28px;background:rgba(3,0,20,0.7);backdrop-filter:blur(20px);border-bottom:1px solid rgba(139,92,246,0.1);z-index:100}
.nav-logo{font-weight:900;font-size:1.1rem;background:linear-gradient(135deg,#8B5CF6,#06D6A0);-webkit-background-clip:text;-webkit-text-fill-color:transparent;display:flex;align-items:center;gap:8px}
.nav-links{display:flex;gap:10px}
.nav-btn{padding:9px 22px;border-radius:12px;font-family:inherit;font-size:0.82rem;font-weight:700;cursor:pointer;transition:all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);text-decoration:none;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden}
.nav-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);transform:translateX(-100%);transition:transform 0.6s}
.nav-btn:hover::after{transform:translateX(100%)}
.btn-primary{background:linear-gradient(135deg,#8B5CF6,#06D6A0);color:#fff;border:none;box-shadow:0 4px 20px rgba(139,92,246,0.3)}
.btn-primary:hover{transform:translateY(-3px) scale(1.03);box-shadow:0 8px 30px rgba(139,92,246,0.4)}
.btn-primary:active{transform:scale(0.95);transition:transform 0.1s}
.btn-outline{background:transparent;color:#9090BB;border:1px solid rgba(139,92,246,0.15)}
.btn-outline:hover{border-color:rgba(139,92,246,0.4);color:#F0EDFF;background:rgba(139,92,246,0.06)}
.btn-outline:active{transform:scale(0.95);transition:transform 0.1s}

.card{background:rgba(15,15,40,0.6);backdrop-filter:blur(20px);border:1px solid rgba(139,92,246,0.1);border-radius:24px;padding:44px;max-width:560px;width:100%;text-align:center;animation:cardPop 0.7s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#8B5CF6,#06D6A0,#FF3CAC)}
@keyframes cardPop{from{opacity:0;transform:scale(0.85) translateY(30px)}to{opacity:1;transform:scale(1) translateY(0)}}

.icon-big{font-size:3.5rem;margin-bottom:16px;animation:bounce 2s ease-in-out infinite}
@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}

h1{font-size:1.6rem;font-weight:900;margin-bottom:8px;background:linear-gradient(135deg,#8B5CF6,#06D6A0);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.subtitle{color:#9090BB;font-size:0.88rem;margin-bottom:32px}

.table-row{display:flex;align-items:center;gap:14px;padding:14px 20px;border-radius:14px;margin-bottom:10px;background:rgba(139,92,246,0.04);border:1px solid rgba(139,92,246,0.06);animation:rowSlide 0.5s ease forwards;opacity:0;transition:all 0.3s}
.table-row:hover{background:rgba(139,92,246,0.08);border-color:rgba(139,92,246,0.15);transform:translateX(4px)}
.table-row .icon{font-size:1.3rem;width:32px;text-align:center}
.table-row .name{flex:1;text-align:left;font-weight:600;font-size:0.88rem}
.table-row .status{font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:8px}
.status-ok{background:rgba(6,214,160,0.12);color:#06D6A0}
.status-err{background:rgba(255,60,172,0.12);color:#FF3CAC}

@keyframes rowSlide{from{opacity:0;transform:translateX(-20px)}to{opacity:1;transform:translateX(0)}}
.table-row:nth-child(1){animation-delay:0.1s}
.table-row:nth-child(2){animation-delay:0.2s}
.table-row:nth-child(3){animation-delay:0.3s}
.table-row:nth-child(4){animation-delay:0.4s}
.table-row:nth-child(5){animation-delay:0.5s}

.success-box{margin-top:28px;padding:24px;border-radius:16px;background:rgba(6,214,160,0.06);border:1px solid rgba(6,214,160,0.15);animation:rowSlide 0.5s ease 0.7s forwards;opacity:0}
.success-box h3{color:#06D6A0;font-size:1rem;margin-bottom:8px}
.success-box p{color:#9090BB;font-size:0.82rem;line-height:1.6}

.action-btns{display:flex;gap:12px;justify-content:center;margin-top:22px;flex-wrap:wrap}
.action-btn{padding:12px 28px;border-radius:14px;font-family:inherit;font-size:0.88rem;font-weight:700;cursor:pointer;transition:all 0.4s cubic-bezier(0.25,0.46,0.45,0.94);text-decoration:none;display:inline-flex;align-items:center;gap:8px;position:relative;overflow:hidden}
.action-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.15),transparent);transform:translateX(-100%);transition:transform 0.6s}
.action-btn:hover::after{transform:translateX(100%)}
.action-btn:active{transform:scale(0.92)!important;transition:transform 0.1s!important}
.btn-go{background:linear-gradient(135deg,#8B5CF6,#06D6A0);color:#fff;border:none;box-shadow:0 4px 20px rgba(139,92,246,0.3)}
.btn-go:hover{transform:translateY(-3px) scale(1.03);box-shadow:0 8px 30px rgba(139,92,246,0.4)}
.btn-admin{background:transparent;color:#9090BB;border:1px solid rgba(139,92,246,0.15)}
.btn-admin:hover{border-color:rgba(139,92,246,0.4);color:#F0EDFF;transform:translateY(-2px)}

.err-box{margin-top:28px;padding:24px;border-radius:16px;background:rgba(255,60,172,0.06);border:1px solid rgba(255,60,172,0.15)}
.err-box h3{color:#FF3CAC;font-size:1rem;margin-bottom:6px}

@media(max-width:500px){.card{padding:28px 20px}.action-btns{flex-direction:column}.nav-links{gap:6px}.nav-btn{padding:7px 14px;font-size:0.75rem}}
</style>
</head>
<body>
<nav class="nav">
  <div class="nav-logo">💬 Comment System</div>
  <div class="nav-links">
    <a href="index.html" class="nav-btn btn-primary">🚀 Start App</a>
    <a href="admin/index.html" class="nav-btn btn-outline">🛡️ Admin</a>
  </div>
</nav>

<div class="card">
  <?php if (!$connected): ?>
    <div class="icon-big">❌</div>
    <h1>Connection Failed</h1>
    <p class="subtitle">Could not connect to the database. Check credentials.</p>
  <?php else: ?>
    <div class="icon-big">🚀</div>
    <h1>Database Setup</h1>
    <p class="subtitle">Creating tables for your comment system</p>

    <?php foreach ($results as $i => $r): ?>
    <div class="table-row">
      <div class="icon"><?= $r === 'ok' ? '✅' : '❌' ?></div>
      <div class="name"><?= $tables[$i] ?></div>
      <div class="status <?= $r === 'ok' ? 'status-ok' : 'status-err' ?>"><?= $r === 'ok' ? 'READY' : 'ERROR' ?></div>
    </div>
    <?php endforeach; ?>

    <?php if ($allOk): ?>
    <div class="success-box">
      <h3>🎉 Setup Complete!</h3>
      <p>All 5 tables created. Your multilingual comment system is ready to go live!</p>
      <div class="action-btns">
        <a href="index.html" class="action-btn btn-go">💬 Open Comment System</a>
        <a href="admin/index.html" class="action-btn btn-admin">🛡️ Admin Dashboard</a>
      </div>
    </div>
    <?php else: ?>
    <div class="err-box">
      <h3>⚠️ Some errors occurred</h3>
      <p>Fix the errors above and reload this page.</p>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
