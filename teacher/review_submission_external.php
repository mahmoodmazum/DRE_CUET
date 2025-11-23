<?php
session_start();
require __DIR__ . '/../src/db.php';

// Get reviewer_pool ID for this user
$stmt = $pdo->prepare("
    SELECT rp.id AS reviewer_pool_id from
    reviewer_pool rp 
    WHERE rp.external_email = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['reviewer_email']]);
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

$filesStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
$filesStmt->execute([$submission_id]);
$attachments = $filesStmt->fetchAll();

// Fetch existing marks for this review
$stmt = $pdo->prepare("SELECT * FROM review_marks WHERE review_id = ? ORDER BY criterion_index ASC");
$stmt->execute([$review_id]);
$existingMarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
$existingMarksMap = [];
foreach ($existingMarks as $m) {
    $existingMarksMap[$m['criterion_index']] = $m;
}

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

// Material-UI 8-color palette
$colors = [
    'primary' => '#1E88E5',
    'secondary' => '#D81B60',
    'success' => '#43A047',
    'info' => '#00ACC1',
    'warning' => '#FDD835',
    'error' => '#E53935',
    'light' => '#F5F5F5',
    'dark' => '#424242'
];
$staffCosts = json_decode($submission['staff_costs'], true) ?: [];
$projectTeam = json_decode($submission['project_team'], true) ?: [];
$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
$totalStaff = array_sum(array_column($staffCosts, 'amount'));
$totalTeam = array_sum(array_column($projectTeam, 'mm'));
$totalDirect = array_sum(array_column($directExpenses, 'amount'));
$totalCost = $totalStaff + $totalDirect;

?>

<?php include __DIR__ . '/../src/includes/header.php'; ?>
<?php include __DIR__ . '/../src/includes/sidebar_external.php'; ?>

<div style="padding:24px; font-family: 'Roboto', sans-serif; background-color: <?= $colors['light'] ?>; min-height:100vh;">

  <h2 style="color: <?= $colors['primary'] ?>; font-weight:500; margin-bottom:16px;">Review Submission</h2>

  <!-- Submission Details -->
  <div style="background:white; padding:16px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:24px;">
    <h4 style="color: <?= $colors['dark'] ?>; margin-bottom:12px;">Submission Information</h4>
    <div style="display:flex; flex-wrap:wrap; gap:16px;">
      <?php 
      $fields = [
        'Project Title' => $submission['project_title'],
        'Year' => $submission['year'],
        'Phase' => $submission['phase'],
        'Keywords' => $submission['keywords'],
        'Specific Objectives' => nl2br(htmlspecialchars($submission['specific_objectives'])),
        'Project Status' => $submission['project_status'],
        'Related Research' => nl2br(htmlspecialchars($submission['related_research'])),
        'Project Status' => $submission['research_type'],
        'Beneficiaries' => $submission['beneficiaries'],
        'Outputs' => $submission['outputs'],
        'Technology Transfer' => $submission['transfer'],
        'Organizational Outcomes' => $submission['organizational_outcomes'],
        'National impacts' => $submission['national_impacts'],
        'External Organization' => $submission['external_org'],
        'Methodology' => $submission['methodology'],
        'Activities' => $submission['activities'],
        'Milestones' => $submission['milestones'],
        'Start Date' => $submission['start_date'],
        'Duration (Months)' => $submission['duration_months'],
        'Other Grants' => $submission['other_grants'],
        'Contractual Obligations' => $submission['contractual_obligations'],
        'IP Ownership' => $submission['ip_ownership'],
      ];
      foreach ($fields as $label => $value): ?>
      <div style="flex:1 1 45%; background: <?= $colors['light'] ?>; padding:12px; border-radius:6px;">
        <strong style="color: <?= $colors['dark'] ?>;"><?= $label ?>:</strong>
        <div><?= $value ?></div>
      </div>
      <?php endforeach; ?>
    </div>

        <!-- Project Team -->
    <h5 style="margin-top:16px; color: <?= $colors['dark'] ?>;">Project Team</h5>
    <?php
    
    if ($projectTeam): ?>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse: collapse; margin-top:8px;">
          <thead style="background-color: <?= $colors['primary'] ?>; color:white;">
            <tr>
              <th style="padding:8px;">Name</th>
              <th style="padding:8px;">Organization</th>
              <th style="padding:8px;">MM</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($projectTeam as $member): ?>
              <tr style="border-bottom:1px solid <?= $colors['light'] ?>; transition: background 0.2s;">
                <td style="padding:8px;"><?= htmlspecialchars($member['name']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($member['org']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($member['mm']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th>Total Costs</th><th><?= number_format($totalTeam,2) ?></th></tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      N/A
    <?php endif; ?>
    

<div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:8px;">
  
  <!-- Staff Costs Table -->
  <div style="flex:1 1 48%; background:white; border:1px solid <?= $colors['light'] ?>; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05); padding:12px;">
    <h5 style="margin-bottom:8px; color: <?= $colors['primary'] ?>;">Staff Costs</h5>
    <?php if ($staffCosts): ?>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead style="background-color: <?= $colors['primary'] ?>; color:white;">
            <tr>
              <th style="padding:8px;">Category</th>
              <th style="padding:8px;">Year</th>
              <th style="padding:8px;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($staffCosts as $member): ?>
              <tr style="border-bottom:1px solid <?= $colors['light'] ?>;">
                <td style="padding:8px;"><?= htmlspecialchars($member['category']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($member['year']) ?></td>
                <td style="padding:8px; text-align:right;"><?= number_format($member['amount'],2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background-color: <?= $colors['light'] ?>; font-weight:600;">
              <td colspan="2" style="padding:8px;">Total Staff Costs</td>
              <td style="padding:8px; text-align:right;"><?= number_format($totalStaff,2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <p style="margin:0;">N/A</p>
    <?php endif; ?>
  </div>

  <!-- Direct Expenses Table -->
  <div style="flex:1 1 48%; background:white; border:1px solid <?= $colors['light'] ?>; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05); padding:12px;">
    <h5 style="margin-bottom:8px; color: <?= $colors['primary'] ?>;">Direct Project Expenses</h5>
    <?php if ($directExpenses): ?>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead style="background-color: <?= $colors['primary'] ?>; color:white;">
            <tr>
              <th style="padding:8px;">Category</th>
              <th style="padding:8px;">Year</th>
              <th style="padding:8px;">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($directExpenses as $member): ?>
              <tr style="border-bottom:1px solid <?= $colors['light'] ?>;">
                <td style="padding:8px;"><?= htmlspecialchars($member['category']) ?></td>
                <td style="padding:8px;"><?= htmlspecialchars($member['year']) ?></td>
                <td style="padding:8px; text-align:right;"><?= number_format($member['amount'],2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background-color: <?= $colors['light'] ?>; font-weight:600;">
              <td colspan="2" style="padding:8px;">Total Direct Expenses</td>
              <td style="padding:8px; text-align:right;"><?= number_format($totalDirect,2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <p style="margin:0;">N/A</p>
    <?php endif; ?>
  </div>
</div>
<!-- Uploaded Files -->
      <h4>Uploaded Files</h4>
      <?php
      $typeLabels = [
          'l_rev'    => 'Literature Review',
          'appendA'  => 'Appendix A',
          'appendB'  => 'Appendix B',
          'appendC'  => 'Appendix C'
      ];
      $attachmentsByType = [];
      foreach ($attachments as $file) {
          $attachmentsByType[$file['type']] = $file;
      }
      ?>
      <ul>
        <?php foreach ($typeLabels as $type => $label): ?>
          <li>
            <strong><?= $label ?>:</strong>
            <?php if (isset($attachmentsByType[$type])): 
              $file = $attachmentsByType[$type]; ?>
              <a href="/DRE/<?= htmlspecialchars($file['file_path']) ?>" target="_blank">
                <?= htmlspecialchars($file['original_name']) ?>
              </a>
              <small>(uploaded at <?= htmlspecialchars($file['uploaded_at']) ?>)</small>
            <?php else: ?>
              <em>Not uploaded</em>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>

  </div>

  <!-- Reviewer Evaluation Form -->
  <div style="background:white; padding:16px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
    <h4 style="color: <?= $colors['dark'] ?>; margin-bottom:16px;">Reviewer Evaluation Marks</h4>
    <form id="reviewForm" method="post" action="submit_review_external.php">
      <input type="hidden" name="review_id" value="<?= $review_id ?>">
      <input type="hidden" name="submission_id" value="<?= $submission_id ?>">

      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse: collapse;">
          <thead style="background-color: <?= $colors['info'] ?>; color:white;">
            <tr>
              <th style="padding:8px;">#</th>
              <th style="padding:8px;">Criteria</th>
              <th style="padding:8px;">Allocated Marks</th>
              <th style="padding:8px;">Evaluated Marks</th>
              <th style="padding:8px;">Comments</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($evaluationCriteria as $idx => $c): 
              $existing = $existingMarksMap[$idx] ?? null;
            ?>
            <tr style="border-bottom:1px solid <?= $colors['light'] ?>; transition: background 0.2s;">
              <td style="padding:8px;"><?= $idx+1 ?></td>
              <td style="padding:8px;"><?= htmlspecialchars($c[0]) ?></td>
              <td style="padding:8px;"><?= $c[1] ?></td>
              <td style="padding:8px;">
                <input type="number" name="marks[<?= $idx ?>]" max="<?= $c[1] ?>" min="0" class="mark-field" data-allocated="<?= $c[1] ?>" 
                  value="<?= $existing['evaluated_marks'] ?? '' ?>" style="width:80px; padding:6px; border:1px solid <?= $colors['dark'] ?>; border-radius:4px;" required>
              </td>
              <td style="padding:8px;">
                <input type="text" name="comments[<?= $idx ?>]" class="comment-field" value="<?= htmlspecialchars($existing['comment'] ?? '') ?>" 
                  placeholder="Required if marks >80% or <50%" style="width:100%; padding:6px; border:1px solid <?= $colors['dark'] ?>; border-radius:4px;">
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:16px; display:flex; gap:12px;">
        <button type="submit" style="background-color: <?= $colors['success'] ?>; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Submit Review</button>
        <a href="review_paper_external.php" style="background-color: <?= $colors['secondary'] ?>; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:500;">Back</a>
      </div>
    </form>
  </div>
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
