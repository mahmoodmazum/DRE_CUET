<?php
session_start();
require __DIR__ . '/../src/db.php';

$stmt = $pdo->prepare("SELECT rp.id AS reviewer_pool_id FROM reviewer_pool rp WHERE rp.external_email = ? LIMIT 1");
$stmt->execute([$_SESSION['reviewer_email']]);
$reviewer = $stmt->fetch();
if (!$reviewer) exit('You are not a registered reviewer.');
$reviewerPoolId = $reviewer['reviewer_pool_id'];

$submission_id = $_GET['id'] ?? null;
$review_id     = $_GET['review_id'] ?? null;
if (!$submission_id || !$review_id) exit('Submission ID or Review ID missing');

$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS submitter_name, pc.deadline_date, pc.review_deadline
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();
if (!$submission) exit('Submission not found');

$filesStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
$filesStmt->execute([$submission_id]);
$atts = [];
foreach ($filesStmt->fetchAll() as $f) { $atts[$f['type']] = $f; }

$stmt = $pdo->prepare("SELECT * FROM review_marks WHERE review_id = ? ORDER BY criterion_index ASC");
$stmt->execute([$review_id]);
$existingMarksMap = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) { $existingMarksMap[$m['criterion_index']] = $m; }

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

$staffCosts     = json_decode($submission['staff_costs'],    true) ?: [];
$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
$projectTeam    = json_decode($submission['project_team'],   true) ?: [];
function rowTotal($r){ return (float)($r['year1']??$r['amount']??0)+(float)($r['year2']??0)+(float)($r['year3']??0); }
$totalStaff  = array_sum(array_map('rowTotal', $staffCosts));
$totalDirect = array_sum(array_map('rowTotal', $directExpenses));
$totalCost   = $totalStaff + $totalDirect;

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_external.php';
?>

<div class="main-content">
  <div class="page-header">
    <h1>Review Proposal (External)</h1>
    <div class="breadcrumb">External Review &rarr; <?= htmlspecialchars($submission['project_title']) ?></div>
  </div>

  <div class="page-body">

    <!-- Proposal Info -->
    <div class="card">
      <div class="card-header"><h3>Proposal Information</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="info-table">
          <tr><th>Department</th><td><?= htmlspecialchars($submission['department_name'] ?? 'N/A') ?></td></tr>
          <tr><th>Year / Phase</th><td><?= htmlspecialchars($submission['year']??'') ?> / <?= htmlspecialchars($submission['phase']??'') ?></td></tr>
          <tr><th>1. Project Title</th><td><strong><?= htmlspecialchars($submission['project_title']) ?></strong></td></tr>
          <tr><th>2a. Principal Investigator (PI)</th><td><?= htmlspecialchars($submission['pi'] ?? 'N/A') ?></td></tr>
          <tr><th>2b. Co-Principal Investigator (Co-PI)</th><td><?= htmlspecialchars($submission['co_pi'] ?? 'N/A') ?></td></tr>
          <tr><th>3. Key Words</th><td><?= htmlspecialchars($submission['keywords'] ?? 'N/A') ?></td></tr>
          <tr><th>4a. Specific Objectives of the Project</th><td><?= $submission['specific_objectives'] ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>4b. General Objective of the Project</th><td><?= $submission['general_objectives'] ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>5a. Project Status</th><td><?= htmlspecialchars($submission['project_status'] ?? 'N/A') ?></td></tr>
          <tr><th>5b. Project Summary</th><td><?= nl2br(htmlspecialchars($submission['literature_review_text'] ?? '')) ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>5c. Literature Review &amp; Related Research</th>
              <td><?php if (isset($atts['l_rev'])): ?>
                <a href="/DRE/<?= htmlspecialchars($atts['l_rev']['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                  <span class="material-icons-round">download</span> <?= htmlspecialchars($atts['l_rev']['original_name']) ?>
                </a>
              <?php elseif (!empty($submission['literature_review'])): ?>
                <div style="font-size:0.875rem;"><?= $submission['literature_review'] ?></div>
              <?php else: ?><em class="text-muted">No submission provided</em><?php endif; ?></td>
          </tr>
          <tr><th>8. Expected Outcomes</th><td><?= $submission['outputs'] ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>10. Organizational Expected Outcomes</th><td><?= nl2br(htmlspecialchars($submission['organizational_outcomes'] ?? '')) ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>11. National Impacts</th><td><?= nl2br(htmlspecialchars($submission['national_impacts'] ?? '')) ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>14. Research Methodology</th>
              <td><?php if (isset($atts['methodology'])): ?>
                <a href="/DRE/<?= htmlspecialchars($atts['methodology']['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                  <span class="material-icons-round">download</span> <?= htmlspecialchars($atts['methodology']['original_name']) ?>
                </a>
              <?php else: ?><em class="text-muted">Not uploaded</em><?php endif; ?></td>
          </tr>
          <tr><th>15. Project Activities</th><td><?= nl2br(htmlspecialchars($submission['activities'] ?? '')) ?: '<em class="text-muted">—</em>' ?></td></tr>
          <tr><th>16. Key Milestones</th><td><?= nl2br(htmlspecialchars($submission['milestones'] ?? '')) ?: '<em class="text-muted">—</em>' ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Budget Summary -->
    <div class="card">
      <div class="card-header"><h3>Budget Summary</h3></div>
      <div class="card-body">
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
          <div style="flex:1;min-width:200px;">
            <strong class="fs-sm text-muted">Staff Sub-Total:</strong>
            <div style="font-size:1.1rem;font-weight:600;color:var(--c-primary);">৳ <?= number_format($totalStaff,2) ?></div>
          </div>
          <div style="flex:1;min-width:200px;">
            <strong class="fs-sm text-muted">Direct Expenses Sub-Total:</strong>
            <div style="font-size:1.1rem;font-weight:600;color:var(--c-primary);">৳ <?= number_format($totalDirect,2) ?></div>
          </div>
          <div style="flex:1;min-width:200px;">
            <strong class="fs-sm text-muted">Grand Total:</strong>
            <div style="font-size:1.3rem;font-weight:700;color:var(--c-primary-dark);">৳ <?= number_format($totalCost,2) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Uploaded Files -->
    <div class="card">
      <div class="card-header"><h3>Uploaded Files</h3></div>
      <div class="card-body">
        <?php
        $fileLabels = ['l_rev'=>'5c. Literature Review & Related Research','appendA'=>'Appendix-A','appendB'=>'Appendix-B','appendC'=>'Appendix-C','methodology'=>'14. Research Methodology'];
        foreach ($fileLabels as $type => $label):
        ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--c-border);">
            <strong style="font-size:0.875rem;"><?= $label ?></strong>
            <?php if (isset($atts[$type])): ?>
              <a href="/DRE/<?= htmlspecialchars($atts[$type]['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                <span class="material-icons-round">download</span> <?= htmlspecialchars($atts[$type]['original_name']) ?>
              </a>
            <?php else: ?><span class="text-muted fs-sm">Not uploaded</span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Evaluation Form -->
    <div class="card">
      <div class="card-header" style="background:var(--c-primary);color:#fff;border-radius:var(--r-lg) var(--r-lg) 0 0;">
        <h3 style="color:#fff;">Reviewer Evaluation</h3>
        <span class="fs-sm" style="opacity:.8;">Total: 100 marks</span>
      </div>
      <div class="card-body">
        <form id="reviewForm" method="post" action="submit_review_external.php">
          <input type="hidden" name="review_id" value="<?= $review_id ?>">
          <input type="hidden" name="submission_id" value="<?= $submission_id ?>">

          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th>Evaluation Criteria</th>
                  <th style="width:100px;">Allocated</th>
                  <th style="width:110px;">Given</th>
                  <th>Comment</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($evaluationCriteria as $idx => $c):
                  $existing = $existingMarksMap[$idx] ?? null; ?>
                <tr>
                  <td><?= $idx+1 ?></td>
                  <td style="font-size:0.875rem;"><?= htmlspecialchars($c[0]) ?></td>
                  <td style="text-align:center;font-weight:600;"><?= $c[1] ?></td>
                  <td>
                    <input type="number" name="marks[<?= $idx ?>]"
                           max="<?= $c[1] ?>" min="0" class="mark-field"
                           data-allocated="<?= $c[1] ?>"
                           value="<?= $existing['evaluated_marks'] ?? '' ?>"
                           style="width:80px;" required>
                  </td>
                  <td>
                    <input type="text" name="comments[<?= $idx ?>]" class="comment-field"
                           value="<?= htmlspecialchars($existing['comment'] ?? '') ?>"
                           placeholder="Required if ≥80% or ≤50%">
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <p class="fs-sm text-muted mt-2">** Comments required if evaluated marks are ≥80% or ≤50% of allocated marks.</p>

          <div class="form-group mt-3">
            <label class="field-label">Overall Comment on Proposal</label>
            <?php $last = end($existingMarksMap); ?>
            <textarea name="final_comment" rows="4" placeholder="Write your overall assessment..."><?= htmlspecialchars($last['final_comment'] ?? '') ?></textarea>
          </div>

          <div class="btn-group mt-3">
            <button type="submit" class="btn btn-success btn-lg">
              <span class="material-icons-round">send</span> Submit Review
            </button>
            <a href="review_paper_external.php" class="btn btn-secondary">
              <span class="material-icons-round">arrow_back</span> Back
            </a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
document.getElementById('reviewForm').addEventListener('submit', function(e){
    const marks    = document.querySelectorAll('.mark-field');
    const comments = document.querySelectorAll('.comment-field');
    for (let i = 0; i < marks.length; i++){
        const m = parseFloat(marks[i].value);
        const a = parseFloat(marks[i].dataset.allocated);
        if ((m >= 0.8*a || m <= 0.5*a) && !comments[i].value.trim()){
            alert('Comment required for criterion #'+(i+1)+' (mark ≥80% or ≤50% of allocated).');
            comments[i].focus(); e.preventDefault(); return;
        }
    }
});
</script>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
