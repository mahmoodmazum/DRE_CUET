<?php

$user = $_SESSION['user'];
$email = $user['email'];

// Check if user is a reviewer
$isReviewer = false;

$stmt = $pdo->prepare("
    SELECT rp.id 
    FROM users u
    INNER JOIN reviewer_pool rp ON u.id = rp.user_id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$isReviewer = $stmt->fetch() ? true : false;
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="/DRE/teacher/dashboard.php" class="brand-link">
    <span class="brand-text font-weight-light">Teacher Portal</span>
  </a>
  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="<?= htmlspecialchars($user['picture'] ?? '/default-avatar.png') ?>" class="img-circle elevation-2" alt="User">
      </div>
      <div class="info">
        <a href="#" class="d-block"><?= htmlspecialchars($user['name']) ?></a>
        <small class="text-white-50"><?= htmlspecialchars($user['role']) ?></small>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
        <li class="nav-item">
          <a href="/DRE/teacher/dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dash board</p></a>
        </li>
        <li class="nav-item">
          <a href="/DRE/teacher/submit_paper.php" class="nav-link"><i class="nav-icon fas fa-file-upload"></i><p>Paper Submission</p></a>
        </li>

        <?php if ($isReviewer): ?>
        <li class="nav-item">
          <a href="/DRE/teacher/review_paper.php" class="nav-link"><i class="nav-icon fas fa-check-circle"></i><p>Review Paper</p></a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
          <a href="/DRE/logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>Log Out</p></a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
