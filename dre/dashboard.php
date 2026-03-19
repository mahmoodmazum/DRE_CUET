<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$activeCalls   = $pdo->query("SELECT COUNT(*) FROM paper_calls WHERE deadline_date >= CURDATE()")->fetchColumn();
$committee     = $pdo->query("SELECT COUNT(*) FROM committee_pool")->fetchColumn();
$reviewers     = $pdo->query("SELECT COUNT(*) FROM reviewer_pool")->fetchColumn();
$totalSubs     = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
?>

<div class="main-content">
  <div class="page-header">
    <h1>Dashboard</h1>
    <div class="breadcrumb">DRE Admin Overview</div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-primary);">
        <span class="material-icons-round">school</span>
      </div>
      <div>
        <div class="stat-label">Total Teachers</div>
        <div class="stat-value"><?= $totalTeachers ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-success);">
        <span class="material-icons-round">campaign</span>
      </div>
      <div>
        <div class="stat-label">Active Proposal Calls</div>
        <div class="stat-value"><?= $activeCalls ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-warning);">
        <span class="material-icons-round">groups</span>
      </div>
      <div>
        <div class="stat-label">Committee Members</div>
        <div class="stat-value"><?= $committee ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:var(--c-info);">
        <span class="material-icons-round">rate_review</span>
      </div>
      <div>
        <div class="stat-label">Reviewers</div>
        <div class="stat-value"><?= $reviewers ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#7C3AED;">
        <span class="material-icons-round">folder_open</span>
      </div>
      <div>
        <div class="stat-label">Total Proposals Submitted</div>
        <div class="stat-value"><?= $totalSubs ?></div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
    <a href="/DRE/dre/paper_calls.php" class="card" style="padding:20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:var(--c-text);transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='var(--sh)'">
      <span class="material-icons-round" style="font-size:28px;color:var(--c-primary);">campaign</span>
      <div><div style="font-weight:600;">Proposal Calls</div><div class="text-muted fs-sm">Create & manage calls</div></div>
    </a>
    <a href="/DRE/dre/committee.php" class="card" style="padding:20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:var(--c-text);transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='var(--sh)'">
      <span class="material-icons-round" style="font-size:28px;color:var(--c-warning);">groups</span>
      <div><div style="font-weight:600;">Committee</div><div class="text-muted fs-sm">Manage committee members</div></div>
    </a>
    <a href="/DRE/dre/reviewer.php" class="card" style="padding:20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:var(--c-text);transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='var(--sh)'">
      <span class="material-icons-round" style="font-size:28px;color:var(--c-info);">rate_review</span>
      <div><div style="font-weight:600;">Reviewer Pool</div><div class="text-muted fs-sm">Internal & external reviewers</div></div>
    </a>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
