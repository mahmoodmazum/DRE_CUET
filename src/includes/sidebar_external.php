<?php

$email = $_SESSION['reviewer_email'];

// Check if user is a reviewer
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id
    FROM reviewer_pool rp where 
    rp.external_email = ?
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
      <div style="font-weight:500;"><?= htmlspecialchars($email) ?></div>
    </div>
  </div>

  <nav style="flex:1;">
    <ul style="list-style:none; padding:0; margin:0;">
      
      
      <?php if ($isReviewer): ?>
      <li style="margin-bottom:12px;">
        <a href="/DRE/teacher/review_paper_external.php" style="color:white; text-decoration:none; display:flex; align-items:center; gap:8px;">
          <span class="material-icons">check_circle</span> Review Paper
        </a>
      </li>
      <?php endif; ?>

      
    </ul>
  </nav>
</aside>
