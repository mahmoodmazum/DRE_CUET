<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$paper_call_id = $_GET['id'] ?? null;
if (!$paper_call_id) { exit('Paper Call ID missing'); }

// Fetch submissions for this paper call
$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS pi_name
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.paper_call_id = ?
    ORDER BY s.created_at DESC
");
$stmt->execute([$paper_call_id]);
$submissions = $stmt->fetchAll();


?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1>Submitted Papers</h1>
      <a href="paper_calls.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">
        <table id="submissionsTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Department</th>
              <th>Principal Investigator (PI)</th>
              <th>Total Cost</th>
              <th>Reviewer Assignment</th>
              <th>Reviewer Reviewed</th>
              <th>Comment</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($submissions as $s): 
                $staffCosts = json_decode($s['staff_costs'], true) ?: [];
                $directExpenses = json_decode($s['direct_expenses'], true) ?: [];
                $totalStaff = array_sum(array_column($staffCosts, 'amount'));
                $totalDirect = array_sum(array_column($directExpenses, 'amount'));
                $totalCost = $totalStaff + $totalDirect;

                // Fetch reviewer info
                $stmt = $pdo->prepare("SELECT * FROM reviews WHERE submission_id = ?");
                $stmt->execute([$s['id']]);
                $rev = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $comments = isset($rev[0]['comments']) ? $rev[0]['comments'] : '';
$assignedReviewerIds = array_column($rev, 'reviewer_id');
// Collect reviewer IDs already assigned
$msgI=$msgX='none';


$internalReviewers = $pdo->query("
    SELECT rp.id AS rp_id, u.id AS user_id, u.name
    FROM reviewer_pool rp
    INNER JOIN users u ON rp.user_id = u.id
    WHERE rp.external_name IS NULL AND rp.external_email IS NULL
    ORDER BY u.name ASC
")->fetchAll();
$externalReviewers = $pdo->query("
    SELECT id, external_name, external_email
    FROM reviewer_pool
    WHERE user_id IS NULL AND external_name IS NOT NULL AND external_email IS NOT NULL
    ORDER BY external_name ASC
")->fetchAll();

foreach ($internalReviewers as $t){
  in_array($t['rp_id'], $assignedReviewerIds) ? $msgI='internal' : $msgI;
}
foreach ($externalReviewers as $er){
  in_array($er['id'], $assignedReviewerIds) ? $msgX='external' : $msgX;
}


            ?>
            <tr>
              <td><?= $s['id'] ?></td>
              <td><?= htmlspecialchars($s['department_name'] ?? 'N/A') ?></td>
              <td><?= htmlspecialchars($s['pi'] ?? 'N/A') ?></td>
              <td><?= number_format($totalCost,2) ?></td>
              <td>
                <?php if ($rev): ?>
                    <span class="badge badge-primary"><?php echo $msgI; ?></span>
                  
                    <span class="badge badge-success"><?php echo $msgX; ?></span>
                  
                <?php else: ?>
                  <span class="badge badge-secondary">None</span>
                <?php endif; ?>
              </td>
              
              <td><?= htmlspecialchars($s['status']); ?></td>
              <td><?= htmlspecialchars($comments); ?></td>
              <td>
                <a href="view_submission_admin.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-info">View</a>
              </td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>

<script>
$(function(){ 
    $('#submissionsTable').DataTable({
        responsive:true
    }); 
});
</script>
