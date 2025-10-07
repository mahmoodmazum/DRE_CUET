<?php
$user = $_SESSION['user'];
$email = $user['email'];

// Check if user is a reviewer
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

<aside style="width:250px; background:#1976d2; color:white; display:flex; flex-direction:column; padding:16px;">
  <div style="margin-bottom:24px;">
    <a href="/DRE/teacher/dashboard.php" style="text-decoration:none; color:white; font-size:1.2rem; font-weight:500;">
      Teacher Portal
    </a>
  </div>

  <div style="display:flex; align-items:center; margin-bottom:24px;">
    
    <div>
      <div style="font-weight:500;"><?= htmlspecialchars($user['name']) ?></div>
      <small style="opacity:0.8;"><?= htmlspecialchars($user['role']) ?></small>
    </div>
  </div>

  <nav style="flex:1;">
    <ul style="list-style:none; padding:0; margin:0;">
      <li style="margin-bottom:12px;">
        <a href="/DRE/teacher/dashboard.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
          <span class="material-icons">dashboard</span> Dashboard
        </a>
      </li>
      <li style="margin-bottom:12px;">
        <a href="/DRE/teacher/submit_paper.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
          <span class="material-icons">upload_file</span> Paper Submission
        </a>
      </li>

      <?php if ($isReviewer): ?>
      <li style="margin-bottom:12px;">
        <a href="/DRE/teacher/review_paper.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
          <span class="material-icons">check_circle</span> Review Paper
        </a>
      </li>
      <?php endif; ?>

      <li>
        <a href="/DRE/logout.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
          <span class="material-icons">logout</span> Log Out
        </a>
      </li>
    </ul>
  </nav>
</aside>
