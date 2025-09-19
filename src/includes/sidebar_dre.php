<?php
// src/includes/sidebar_dre.php
$user = $_SESSION['user'];
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="/public/dre/dashboard.php" class="brand-link">
    <span class="brand-text font-weight-light">DRE Portal</span>
  </a>
  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="<?= htmlspecialchars($user['picture'] ?? '/public/default-avatar.png') ?>" class="img-circle elevation-2" alt="User">
      </div>
      <div class="info">
        <a href="#" class="d-block"><?= htmlspecialchars($user['name']) ?></a>
        <small class="text-white-50"><?= htmlspecialchars($user['role']) ?></small>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
        <li class="nav-item">
          <a href="/DRE/dre/dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dash board</p></a>
        </li>
        <li class="nav-item">
          <a href="/DRE/dre/paper_calls.php" class="nav-link"><i class="nav-icon fas fa-file-alt"></i><p>Paper Call</p></a>
        </li>
        <li class="nav-item">
          <a href="/DRE/dre/committee.php" class="nav-link"><i class="nav-icon fas fa-users"></i><p>Committee</p></a>
        </li>
        <li class="nav-item">
          <a href="/DRE/dre/reviewer.php" class="nav-link"><i class="nav-icon fas fa-user-check"></i><p>Reviewer</p></a>
        </li>
        <li class="nav-item">
          <a href="/DRE/logout.php" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>log out</p></a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
