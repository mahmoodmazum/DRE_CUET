<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$id = $_GET['id'] ?? null;
if (!$id) { exit('Submission ID missing'); }

// Fetch submission
$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS teacher_name
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$submission = $stmt->fetch();

if (!$submission) { exit("Submission not found"); }

// Decode JSON for costs
$staffCosts = json_decode($submission['staff_costs'], true) ?: [];

$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
//print_r($directExpenses);
$totalStaff = array_sum(array_column($staffCosts, 'amount'));
$totalDirect = array_sum(array_column($directExpenses, 'amount'));
$totalCost = $totalStaff + $totalDirect;

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE submission_id = ?");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$comments = isset($reviews[0]['comments']) ? $reviews[0]['comments'] : '';

// Collect reviewer IDs already assigned
$assignedReviewerIds = array_column($reviews, 'reviewer_id');

// Fetch internal reviewers (teachers)
$internalReviewers = $pdo->query("
    SELECT rp.id AS rp_id, u.id AS user_id, u.name
    FROM reviewer_pool rp
    INNER JOIN users u ON rp.user_id = u.id
    WHERE rp.external_name IS NULL AND rp.external_email IS NULL
    ORDER BY u.name ASC
")->fetchAll();

// Fetch external reviewers
$externalReviewers = $pdo->query("
    SELECT id, external_name, external_email
    FROM reviewer_pool
    WHERE user_id IS NULL AND external_name IS NOT NULL AND external_email IS NOT NULL
    ORDER BY external_name ASC
")->fetchAll();
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1>Submission Details</h1></div></section>
  <section class="content">
    <div class="card">
      <div class="card-body">

        <h5>Submitted by: <?= htmlspecialchars($submission['teacher_name']) ?></h5>
        <h5>Department: <?= htmlspecialchars($submission['department_name']) ?></h5>

        <table class="table table-bordered">
          <tbody>
            <tr><th>Project Title</th><td><?= htmlspecialchars($submission['project_title']) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($submission['status']) ?></td></tr>
            <tr><th>PI</th><td><?= htmlspecialchars($submission['pi']) ?></td></tr>
            <tr><th>Co-PI</th><td><?= htmlspecialchars($submission['co_pi']) ?></td></tr>
            <tr><th>Year</th><td><?= htmlspecialchars($submission['year']) ?></td></tr>
            <tr><th>Phase</th><td><?= htmlspecialchars($submission['phase']) ?></td></tr>
            <tr><th>Keywords</th><td><?= htmlspecialchars($submission['keywords']) ?></td></tr>
            <tr><th>Specific Objectives</th><td><?= nl2br(htmlspecialchars($submission['specific_objectives'])) ?></td></tr>
            <tr><th>Background</th><td><?= nl2br(htmlspecialchars($submission['background'])) ?></td></tr>
            <tr><th>Project Status</th><td><?= htmlspecialchars($submission['project_status']) ?></td></tr>
          </tbody>
        </table>

        <!-- Staff Costs -->
        <h5>Staff Costs</h5>
        <table class="table table-bordered table-sm">
          <thead class="thead-dark"><tr><th>Name</th><th>Monthly Cost</th><th>Total Month</th></tr></thead>
          <tbody>
            <?php foreach ($staffCosts as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['monthly']) ?></td>
              <td><?= number_format($c['months'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="2">Total Staff Costs</th><th><?= number_format($totalStaff,2) ?></th></tr>
          </tfoot>
        </table>

        <!-- Direct Expenses -->
        <h5>Direct Expenses</h5>
        <table class="table table-bordered table-sm">
          <thead class="thead-dark"><tr><th>Name</th><th>Amount</th></tr></thead>
          <tbody>
            <?php foreach ($directExpenses as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['name']) ?></td>
              <td><?= number_format($d['amount'],2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="1">Total Direct Expenses</th><th><?= number_format($totalDirect,2) ?></th></tr>
          </tfoot>
        </table>

        <h5>Total Cost: <?= number_format($totalCost,2) ?></h5>

        <!-- Reviewer Assignment -->
        <h4 class="mt-4">Reviewer Assignment</h4>
        <form method="post" action="assign_reviewer.php">
          <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">

          <div class="form-group">
            <label>Internal Reviewer</label>
            <select name="internal_reviewer_id" class="form-control">
              <option value="">-- Select Internal Reviewer --</option>
              <?php foreach ($internalReviewers as $t): ?>

                <option value="<?= $t['rp_id'] ?>" <?= in_array($t['rp_id'], $assignedReviewerIds) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>External Reviewer</label>
            <select name="external_reviewer_id" class="form-control">
              <option value="">-- Select External Reviewer --</option>
              <?php foreach ($externalReviewers as $er): ?>
                <option value="<?= $er['id'] ?>" 
                  <?= in_array($er['id'], $assignedReviewerIds) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($er['external_name']) ?> (<?= htmlspecialchars($er['external_email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>


          <div class="form-group">
            <label>Comments</label>
            <textarea class="form-control" name="comments"><?php echo htmlspecialchars($comments); ?></textarea>
          </div>

          <button type="submit" class="btn btn-success">Save Assignment</button>
        </form>

        <div class="mt-3">
          <a href="view_active_paper_call.php?id=<?= $submission['paper_call_id'] ?>" class="btn btn-secondary">Back</a>
        </div>

      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
