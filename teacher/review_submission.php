<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];

// Get reviewer_pool ID for this user
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id 
    FROM users u
    INNER JOIN reviewer_pool rp ON u.id = rp.user_id
    WHERE u.email = ?
    LIMIT 1
");
$stmt->execute([$user['email']]);
$reviewer = $stmt->fetch();
if (!$reviewer) exit('You are not a registered reviewer.');

$reviewerPoolId = $reviewer['reviewer_pool_id'];

$submission_id = $_GET['id'] ?? null;
$review_id = $_GET['review_id'] ?? null;
if (!$submission_id || !$review_id) exit('Submission ID or Review ID missing');

// Fetch submission info
$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS submitter_name, pc.deadline_date
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();
if (!$submission) exit('Submission not found');

// Fetch existing marks for this review
$stmt = $pdo->prepare("SELECT * FROM review_marks WHERE review_id = ? ORDER BY criterion_index ASC");
$stmt->execute([$review_id]);
$existingMarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
$existingMarksMap = [];
foreach ($existingMarks as $m) {
    $existingMarksMap[$m['criterion_index']] = $m;
}

// Decode JSON fields
$staffCosts = json_decode($submission['staff_costs'], true) ?: [];
$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
$totalStaff = array_sum(array_column($staffCosts, 'amount'));
$totalDirect = array_sum(array_column($directExpenses, 'amount'));
$totalCost = $totalStaff + $totalDirect;

// Evaluation criteria
$evaluationCriteria = [
    ["Linkage of the objectives related to the title", 10],
    ["Research Gaps identification and link to the aims and objectives", 15],
    ["Novelty and innovation", 10],
    ["Institutional/Local/Regional/National/International impact", 10],
    ["Methods proposed are quantifiable, rational and implementable", 15],
    ["Activities planned are justifiable and related to the methods proposed in the timeline", 10],
    ["Budget allocation as per DRE Rules", 10],
    ["Implication of the projects", 10],
    ["Overall organization of the proposal (ignore PI/Co-PI, Project Team, PI Declaration)", 10]
];
?>

<?php include __DIR__ . '/../src/includes/header.php'; ?>
<?php include __DIR__ . '/../src/includes/sidebar_teacher.php'; ?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid"><h1>Review Submission</h1></div>
  </section>

  <section class="content">
    <div class="card">
      <div class="card-body">

        <!-- Submission Details -->
        <h4>Submission Information</h4>
        <table class="table table-bordered">
          <tbody>
            <tr><th>Project Title</th><td><?= htmlspecialchars($submission['project_title']) ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($submission['status']) ?></td></tr>
            <tr><th>Submitted By</th><td><?= htmlspecialchars($submission['submitter_name']) ?></td></tr>
            <tr><th>Department</th><td><?= htmlspecialchars($submission['department_name']) ?></td></tr>
            <tr><th>Year</th><td><?= htmlspecialchars($submission['year']) ?></td></tr>
            <tr><th>Phase</th><td><?= htmlspecialchars($submission['phase']) ?></td></tr>
            <tr><th>PI</th><td><?= htmlspecialchars($submission['pi']) ?></td></tr>
            <tr><th>Co-PI</th><td><?= htmlspecialchars($submission['co_pi']) ?></td></tr>
            <tr><th>Keywords</th><td><?= htmlspecialchars($submission['keywords']) ?></td></tr>
            <tr><th>Specific Objectives</th><td><?= nl2br(htmlspecialchars($submission['specific_objectives'])) ?></td></tr>
            <tr><th>Background</th><td><?= nl2br(htmlspecialchars($submission['background'])) ?></td></tr>
            <tr><th>Project Status</th><td><?= htmlspecialchars($submission['project_status']) ?></td></tr>
            <tr><th>Project Team</th>
              <td>
                <?php
                $team = json_decode($submission['project_team'], true);
                if ($team):
                ?>
                  <table class="table table-sm table-bordered">
                    <thead>
                      <tr><th>Name</th><th>Organization</th><th>MM</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach($team as $member): ?>
                        <tr>
                          <td><?= htmlspecialchars($member['name']) ?></td>
                          <td><?= htmlspecialchars($member['org']) ?></td>
                          <td><?= htmlspecialchars($member['mm']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Reviewer Evaluation Form -->
        <h4 class="mt-4">Reviewer Evaluation Marks</h4>
        <form id="reviewForm" method="post" action="submit_review.php">
          <input type="hidden" name="review_id" value="<?= $review_id ?>">
          <input type="hidden" name="submission_id" value="<?= $submission_id ?>">
          
          <table class="table table-bordered">
            <thead>
              <tr><th>Serial</th><th>Criteria</th><th>Allocated Marks</th><th>Evaluated Marks</th><th>Comments</th></tr>
            </thead>
            <tbody>
              <?php foreach($evaluationCriteria as $idx => $c): 
                $existing = $existingMarksMap[$idx] ?? null;
              ?>
                <tr>
                  <td><?= $idx + 1 ?></td>
                  <td><?= htmlspecialchars($c[0]) ?></td>
                  <td><?= $c[1] ?></td>
                  <td>
                    <input type="number" name="marks[<?= $idx ?>]" max="<?= $c[1] ?>" min="0" class="form-control mark-field" data-allocated="<?= $c[1] ?>" value="<?= $existing['evaluated_marks'] ?? '' ?>" required>
                  </td>
                  <td>
                    <input type="text" name="comments[<?= $idx ?>]" class="form-control comment-field" value="<?= htmlspecialchars($existing['comment'] ?? '') ?>" placeholder="Required if marks >80% or <50%">
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <button type="submit" class="btn btn-success">Submit Review</button>
          <a href="review_paper.php" class="btn btn-secondary">Back</a>
        </form>

      </div>
    </div>
  </section>
</div>

<script>
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    let markFields = document.querySelectorAll('.mark-field');
    let commentFields = document.querySelectorAll('.comment-field');
    let valid = true;

    markFields.forEach((m, idx) => {
        let mark = parseFloat(m.value);
        let allocated = parseFloat(m.dataset.allocated);
        let comment = commentFields[idx].value.trim();
        if ((mark >= 0.8 * allocated || mark <= 0.5 * allocated) && comment === '') {
            alert('Comment is required for criterion #' + (idx+1) + ' because mark is >=80% or <=50%.');
            commentFields[idx].focus();
            valid = false;
            e.preventDefault();
            return false;
        }
    });

    if (!valid) e.preventDefault();
});
</script>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
