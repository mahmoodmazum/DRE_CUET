<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

// quick stats: total teachers, total active calls, committee size, reviewer size
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$activeCalls = $pdo->query("SELECT COUNT(*) FROM paper_calls WHERE deadline_date >= CURDATE()")->fetchColumn();
$committee = $pdo->query("SELECT COUNT(*) FROM committee_pool")->fetchColumn();
$reviewers = $pdo->query("SELECT COUNT(*) FROM reviewer_pool")->fetchColumn();
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Dashboard</h1></div></section>
  <section class="content">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3><?= $totalTeachers ?></h3><p>Teachers</p></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-success"><div class="inner"><h3><?= $activeCalls ?></h3><p>Active Paper Calls</p></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3><?= $committee ?></h3><p>Committee Members</p></div></div></div>
          <div class="col-lg-3 col-6"><div class="small-box bg-danger"><div class="inner"><h3><?= $reviewers ?></h3><p>Reviewers</p></div></div></div>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
