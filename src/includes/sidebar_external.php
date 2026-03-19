<?php
$email = $_SESSION['reviewer_email'];

$stmt = $pdo->prepare("SELECT rp.id AS reviewer_pool_id FROM reviewer_pool rp WHERE rp.external_email = ? LIMIT 1");
$stmt->execute([$email]);
$isReviewer = $stmt->fetch() ? true : false;
?>

<style>
aside.sidebar{
  width:240px;flex-shrink:0;
  background:linear-gradient(180deg,#0D47A1 0%,#1565C0 100%);
  color:#fff;display:flex;flex-direction:column;min-height:100vh;
  position:sticky;top:0;
}
.sidebar-brand{padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,0.1);}
.sidebar-brand span{color:#fff;font-size:1.05rem;font-weight:700;}
.sidebar-user{padding:14px 18px;border-bottom:1px solid rgba(255,255,255,0.1);}
.sidebar-user .label{font-size:0.75rem;opacity:.6;margin-bottom:2px;}
.sidebar-user .email{font-size:0.82rem;font-weight:500;word-break:break-all;}
.sidebar-nav{flex:1;padding:12px 0;}
.sidebar-nav ul{list-style:none;}
.sidebar-nav li a{
  display:flex;align-items:center;gap:10px;padding:10px 18px;
  font-size:0.875rem;font-weight:500;color:rgba(255,255,255,0.8);
  transition:background .15s,color .15s;
}
.sidebar-nav li a:hover{background:rgba(255,255,255,0.12);color:#fff;}
.sidebar-nav li a .material-icons-round{font-size:18px;}
</style>

<aside class="sidebar">
  <div class="sidebar-brand">
    <span>External Reviewer</span>
  </div>
  <div class="sidebar-user">
    <div class="label">Logged in as</div>
    <div class="email"><?= htmlspecialchars($email) ?></div>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <?php if ($isReviewer): ?>
      <li>
        <a href="/DRE/teacher/review_paper_external.php">
          <span class="material-icons-round">rate_review</span> Review Proposals
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </nav>
</aside>
