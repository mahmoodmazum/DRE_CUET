<?php
$user = $_SESSION['user'];
$cur = $_SERVER['PHP_SELF'];
function dreNavActive($path){ global $cur; return strpos($cur, $path) !== false ? 'style="background:rgba(255,255,255,0.18);color:#fff;"' : ''; }
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
.sidebar-user .name{font-size:0.9rem;font-weight:600;}
.sidebar-user .role{font-size:0.75rem;opacity:.7;margin-top:2px;}
.sidebar-nav{flex:1;padding:12px 0;}
.sidebar-nav ul{list-style:none;}
.sidebar-nav li a{
  display:flex;align-items:center;gap:10px;padding:10px 18px;
  font-size:0.875rem;font-weight:500;color:rgba(255,255,255,0.8);
  transition:background .15s,color .15s;
}
.sidebar-nav li a:hover{background:rgba(255,255,255,0.12);color:#fff;text-decoration:none;}
.sidebar-nav li a .material-icons-round{font-size:18px;}
.sidebar-footer{padding:14px 18px;border-top:1px solid rgba(255,255,255,0.1);}
.sidebar-footer a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.7);font-size:0.82rem;font-weight:500;}
.sidebar-footer a:hover{color:#fff;text-decoration:none;}
</style>

<aside class="sidebar">
  <div class="sidebar-brand">
    <span>DRE Admin Portal</span>
  </div>
  <div class="sidebar-user">
    <div class="label">Logged in as</div>
    <div class="name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
    <div class="role">DRE Administrator</div>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <li>
        <a href="/DRE/dre/dashboard.php" <?= dreNavActive('dashboard') ?>>
          <span class="material-icons-round">dashboard</span> Dashboard
        </a>
      </li>
      <li>
        <a href="/DRE/dre/paper_calls.php" <?= dreNavActive('paper_calls') ?>>
          <span class="material-icons-round">campaign</span> Proposal Calls
        </a>
      </li>
      <li>
        <a href="/DRE/dre/committee.php" <?= dreNavActive('committee') ?>>
          <span class="material-icons-round">groups</span> Committee
        </a>
      </li>
      <li>
        <a href="/DRE/dre/reviewer.php" <?= dreNavActive('reviewer') ?>>
          <span class="material-icons-round">rate_review</span> Reviewer Pool
        </a>
      </li>
    </ul>
  </nav>
  <div class="sidebar-footer">
    <a href="/DRE/logout.php">
      <span class="material-icons-round">logout</span> Log Out
    </a>
  </div>
</aside>

<div class="main-area">
