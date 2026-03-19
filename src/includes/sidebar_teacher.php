<?php
$user = $_SESSION['user'];
$email = $user['email'];

$stmt = $pdo->prepare("
    SELECT rp.id
    FROM users u
    INNER JOIN reviewer_pool rp ON u.id = rp.user_id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$isReviewer = $stmt->fetch() ? true : false;

$currentPage = basename($_SERVER['PHP_SELF']);
function navActive($page, $current){ return ($page === $current) ? 'nav-active' : ''; }
?>

<style>
aside.sidebar{
  width:240px;flex-shrink:0;
  background:linear-gradient(180deg,#0D47A1 0%,#1565C0 100%);
  color:#fff;display:flex;flex-direction:column;
  min-height:100vh;position:sticky;top:0;
}
.sidebar-brand{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.1);}
.sidebar-brand a{color:#fff;font-size:1.05rem;font-weight:700;display:flex;align-items:center;gap:8px;text-decoration:none;}
.sidebar-brand .material-icons-round{font-size:22px;background:rgba(255,255,255,0.15);border-radius:6px;padding:4px;}
.sidebar-user{padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;gap:10px;}
.sidebar-user .avatar{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;color:#fff;flex-shrink:0;}
.sidebar-user .info .name{font-weight:600;font-size:0.9rem;line-height:1.2;}
.sidebar-user .info .role{font-size:0.75rem;opacity:.7;text-transform:capitalize;}
.sidebar-nav{flex:1;padding:12px 0;}
.sidebar-nav ul{list-style:none;}
.sidebar-nav li a{
  display:flex;align-items:center;gap:10px;
  padding:10px 18px;font-size:0.875rem;font-weight:500;
  color:rgba(255,255,255,0.8);border-radius:0;
  transition:background .15s,color .15s;
}
.sidebar-nav li a .material-icons-round{font-size:18px;opacity:.9;}
.sidebar-nav li a:hover,.sidebar-nav li a.nav-active{
  background:rgba(255,255,255,0.12);color:#fff;
}
.sidebar-nav li a.nav-active{border-left:3px solid rgba(255,255,255,0.7);}
.sidebar-nav .nav-section{
  padding:10px 18px 4px;
  font-size:0.68rem;font-weight:700;letter-spacing:.08em;
  text-transform:uppercase;color:rgba(255,255,255,0.45);
  margin-top:6px;
}
.sidebar-footer{padding:14px 18px;border-top:1px solid rgba(255,255,255,0.1);}
.sidebar-footer a{display:flex;align-items:center;gap:8px;font-size:0.875rem;color:rgba(255,255,255,0.7);}
.sidebar-footer a:hover{color:#fff;}
@media(max-width:768px){
  aside.sidebar{display:none;}
}
</style>

<aside class="sidebar">
  <div class="sidebar-brand">
    <a href="/DRE/teacher/dashboard.php">
      <span class="material-icons-round">science</span>
      DRE Portal
    </a>
  </div>

  <div class="sidebar-user">
    <div class="avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div class="info">
      <div class="name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="role"><?= htmlspecialchars($user['role']) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <li class="nav-section">Main</li>
      <li>
        <a href="/DRE/teacher/dashboard.php" class="<?= navActive('dashboard.php',$currentPage) ?>">
          <span class="material-icons-round">dashboard</span> Dashboard
        </a>
      </li>
      <li>
        <a href="/DRE/teacher/submit_paper.php" class="<?= navActive('submit_paper.php',$currentPage) ?>">
          <span class="material-icons-round">upload_file</span> Submit Proposal
        </a>
      </li>

      <?php if ($isReviewer): ?>
      <li class="nav-section">Review</li>
      <li>
        <a href="/DRE/teacher/review_paper.php" class="<?= navActive('review_paper.php',$currentPage) ?>">
          <span class="material-icons-round">rate_review</span> Review Proposal
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="/DRE/logout.php">
      <span class="material-icons-round">logout</span> Log Out
    </a>
  </div>
</aside>
