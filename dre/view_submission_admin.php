<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { exit('Submission ID missing'); }

$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name, u.name AS teacher_name
    FROM submissions s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$submission = $stmt->fetch();
if (!$submission) { exit('Submission not found'); }

$staffCosts     = json_decode($submission['staff_costs'],    true) ?: [];
$projectTeam    = json_decode($submission['project_team'],   true) ?: [];
$directExpenses = json_decode($submission['direct_expenses'], true) ?: [];
function rowTotal($r){ return (float)($r['year1']??$r['amount']??0)+(float)($r['year2']??0)+(float)($r['year3']??0); }
$totalStaff  = array_sum(array_map('rowTotal', $staffCosts));
$totalDirect = array_sum(array_map('rowTotal', $directExpenses));
$totalCost   = $totalStaff + $totalDirect;

$revStmt = $pdo->prepare("SELECT * FROM reviews WHERE submission_id = ?");
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll(PDO::FETCH_ASSOC);
$comments = $reviews[0]['comments'] ?? '';
$assignedReviewerIds = array_column($reviews, 'reviewer_id');

$internalReviewers = $pdo->query("
    SELECT rp.id AS rp_id, u.id AS user_id, u.name
    FROM reviewer_pool rp INNER JOIN users u ON rp.user_id = u.id
    WHERE rp.external_name IS NULL AND rp.external_email IS NULL ORDER BY u.name
")->fetchAll();

$externalReviewers = $pdo->query("
    SELECT id, external_name, external_email FROM reviewer_pool
    WHERE user_id IS NULL AND external_name IS NOT NULL AND external_email IS NOT NULL
    ORDER BY external_name
")->fetchAll();

$attStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
$attStmt->execute([$id]);
$atts = [];
foreach ($attStmt->fetchAll() as $a) { $atts[$a['type']] = $a; }

function ev($v){ return htmlspecialchars($v ?? ''); }
?>

<style>
.detail-section{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:var(--sh);margin-bottom:20px;}
.detail-section .ds-header{padding:14px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;gap:10px;}
.detail-section .ds-header .material-icons-round{font-size:18px;color:var(--c-primary);}
.detail-section .ds-header h3{font-size:0.95rem;font-weight:600;margin:0;}
.detail-section .ds-body{padding:0;}
.det-table{width:100%;border-collapse:collapse;}
.det-table th{width:32%;font-size:0.8rem;font-weight:600;color:var(--c-text-muted);padding:10px 16px;background:var(--c-bg);border-bottom:1px solid var(--c-border);text-align:left;vertical-align:top;}
.det-table td{padding:10px 16px;border-bottom:1px solid var(--c-border);font-size:0.875rem;}
.det-table tr:last-child th,.det-table tr:last-child td{border-bottom:none;}
.budget-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:700px){.budget-grid{grid-template-columns:1fr;}}
.grand-total-band{background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;border-radius:var(--r-lg);padding:20px 24px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
.grand-total-band .label{font-size:0.85rem;opacity:.85;}
.grand-total-band .value{font-size:1.5rem;font-weight:700;}
.grand-total-band .sub{font-size:0.78rem;opacity:.7;margin-top:2px;}
</style>

<div class="main-content">
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
      <h1>Proposal Details</h1>
      <div class="breadcrumb">Proposal Calls / Submitted Proposals / Details</div>
    </div>
    <a href="view_active_paper_call.php?id=<?= $submission['paper_call_id'] ?>" class="btn btn-secondary">
      <span class="material-icons-round">arrow_back</span> Back
    </a>
  </div>

  <!-- Submitter Info -->
  <div class="detail-section">
    <div class="ds-header">
      <span class="material-icons-round">person</span>
      <h3>Submitter Information</h3>
      <span class="badge badge-info" style="margin-left:auto;"><?= ev($submission['status']) ?></span>
    </div>
    <div class="ds-body">
      <table class="det-table">
        <tr><th>Submitted by</th><td><?= ev($submission['teacher_name']) ?></td></tr>
        <tr><th>Department</th><td><?= ev($submission['department_name']) ?></td></tr>
        <?php if ($submission['confirm_name']): ?>
        <tr><th>Confirmed by</th><td><?= ev($submission['confirm_name']) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

  <!-- Proposal Details -->
  <div class="detail-section">
    <div class="ds-header">
      <span class="material-icons-round">description</span>
      <h3>Proposal Details</h3>
    </div>
    <div class="ds-body">
      <table class="det-table">
        <tr><th>Project Title</th><td><strong><?= ev($submission['project_title']) ?></strong></td></tr>
        <tr><th>Year / Phase</th><td><?= ev($submission['year']) ?><?= $submission['phase'] ? ' / ' . ev($submission['phase']) : '' ?></td></tr>
        <tr><th>2a. Principal Investigator (PI)</th><td><?= ev($submission['pi']) ?></td></tr>
        <tr><th>2b. Co-Principal Investigator (Co-PI)</th><td><?= ev($submission['co_pi']) ?></td></tr>
        <tr><th>3. Keywords</th><td><?= ev($submission['keywords']) ?></td></tr>
        <tr><th>4. Specific Objectives</th><td><?= nl2br(ev($submission['specific_objectives'])) ?></td></tr>
        <tr><th>5a. Project Status</th><td><?= ev($submission['project_status']) ?></td></tr>
        <tr><th>5b. Project Summary</th><td><?= nl2br(ev($submission['literature_review_text'])) ?></td></tr>
        <tr>
          <th>5c. Literature Review &amp; Related Research</th>
          <td>
            <?php if (isset($atts['l_rev'])): ?>
              <a href="/DRE/<?= ev($atts['l_rev']['file_path']) ?>" target="_blank" class="file-download-link">
                <span class="material-icons-round">download</span> <?= ev($atts['l_rev']['original_name']) ?>
              </a>
            <?php else: ?><em class="text-muted">Not uploaded</em><?php endif; ?>
          </td>
        </tr>
        <tr><th>6. Type of Research</th><td><?= ev($submission['research_type']) ?></td></tr>
        <tr><th>7. Beneficiaries</th><td><?= nl2br(ev($submission['beneficiaries'])) ?></td></tr>
        <tr><th>8. Expected Outcomes from the Project</th><td><?= nl2br(ev($submission['outputs'])) ?></td></tr>
        <tr><th>9. Technology Transfer</th><td><?= nl2br(ev($submission['transfer'])) ?></td></tr>
        <tr><th>10. Organizational Expected Outcomes</th><td><?= nl2br(ev($submission['organizational_outcomes'])) ?></td></tr>
        <tr><th>11. National Impacts</th><td><?= nl2br(ev($submission['national_impacts'])) ?></td></tr>
        <tr><th>12. External Organizations</th><td><?= nl2br(ev($submission['external_org'])) ?></td></tr>
        <tr>
          <th>14. Research Methodology</th>
          <td>
            <?php if (isset($atts['methodology'])): ?>
              <a href="/DRE/<?= ev($atts['methodology']['file_path']) ?>" target="_blank" class="file-download-link">
                <span class="material-icons-round">download</span> <?= ev($atts['methodology']['original_name']) ?>
              </a>
            <?php else: ?><em class="text-muted">Not uploaded</em><?php endif; ?>
          </td>
        </tr>
        <tr><th>15. Project Activities</th><td><?= nl2br(ev($submission['activities'])) ?></td></tr>
        <tr><th>16. Key Milestones</th><td><?= nl2br(ev($submission['milestones'])) ?></td></tr>
        <tr><th>17. Start Date / Duration</th><td><?= ev($submission['start_date'] ?? '—') ?> / <?= ev($submission['duration_months'] ?? '—') ?> months</td></tr>
        <tr><th>21. Other Grants</th><td><?= nl2br(ev($submission['other_grants'])) ?></td></tr>
        <tr><th>22. Contractual Obligations</th><td><?= nl2br(ev($submission['contractual_obligations'])) ?></td></tr>
        <tr><th>23. IP Ownership</th><td><?= nl2br(ev($submission['ip_ownership'])) ?></td></tr>
      </table>
    </div>
  </div>

  <!-- Project Team -->
  <?php if ($projectTeam): ?>
  <div class="detail-section">
    <div class="ds-header">
      <span class="material-icons-round">group</span>
      <h3>13. Project Team</h3>
    </div>
    <div class="ds-body">
      <table class="det-table">
        <thead>
          <tr>
            <th style="width:auto;">Type</th>
            <th>Name</th>
            <th>Count</th>
            <th>Organization</th>
            <th>Man-Month</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($projectTeam as $m): ?>
          <tr>
            <td><?= ev($m['type'] ?? '—') ?></td>
            <td><?= ev($m['name'] ?? '—') ?></td>
            <td><?= ev($m['count'] ?? 1) ?></td>
            <td><?= ev($m['org'] ?? '—') ?></td>
            <td><?= ev($m['mm'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:var(--c-primary-bg);">
            <td colspan="4" style="font-weight:600;padding:10px 16px;border-bottom:none;">Total Man-Month</td>
            <td style="font-weight:700;color:var(--c-primary-dark);padding:10px 16px;border-bottom:none;">
              <?= array_sum(array_column($projectTeam, 'mm')) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Budget -->
  <div class="budget-grid">
    <div class="detail-section" style="margin-bottom:0;">
      <div class="ds-header">
        <span class="material-icons-round">payments</span>
        <h3>18. Staff Costs</h3>
      </div>
      <div class="ds-body">
        <table class="det-table">
          <thead>
            <tr>
              <th>Category</th><th>Year 1</th><th>Year 2</th><th>Year 3</th><th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($staffCosts as $c): ?>
            <tr>
              <td><?= ev($c['category'] ?? '') ?></td>
              <td><?= number_format((float)($c['year1']??$c['amount']??0), 2) ?></td>
              <td><?= number_format((float)($c['year2']??0), 2) ?></td>
              <td><?= number_format((float)($c['year3']??0), 2) ?></td>
              <td style="font-weight:600;">৳ <?= number_format(rowTotal($c), 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--c-primary-bg);">
              <td colspan="4" style="font-weight:600;padding:10px 16px;border-bottom:none;">Sub-Total</td>
              <td style="font-weight:700;color:var(--c-primary-dark);padding:10px 16px;border-bottom:none;">৳ <?= number_format($totalStaff, 2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="detail-section" style="margin-bottom:0;">
      <div class="ds-header">
        <span class="material-icons-round">receipt_long</span>
        <h3>19. Direct Expenses</h3>
      </div>
      <div class="ds-body">
        <table class="det-table">
          <thead>
            <tr>
              <th>Description</th><th>Year 1</th><th>Year 2</th><th>Year 3</th><th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($directExpenses as $d): ?>
            <tr>
              <td><?= ev($d['description'] ?? $d['category'] ?? '') ?></td>
              <td><?= number_format((float)($d['year1']??$d['amount']??0), 2) ?></td>
              <td><?= number_format((float)($d['year2']??0), 2) ?></td>
              <td><?= number_format((float)($d['year3']??0), 2) ?></td>
              <td style="font-weight:600;">৳ <?= number_format(rowTotal($d), 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:var(--c-primary-bg);">
              <td colspan="4" style="font-weight:600;padding:10px 16px;border-bottom:none;">Sub-Total</td>
              <td style="font-weight:700;color:var(--c-primary-dark);padding:10px 16px;border-bottom:none;">৳ <?= number_format($totalDirect, 2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Grand Total -->
  <div class="grand-total-band" style="margin-top:20px;">
    <div>
      <div class="label">20. Grand Total</div>
      <div class="sub">Staff: ৳<?= number_format($totalStaff,2) ?> + Direct: ৳<?= number_format($totalDirect,2) ?></div>
    </div>
    <div class="value">৳ <?= number_format($totalCost, 2) ?></div>
  </div>

  <!-- Uploaded Files -->
  <div class="detail-section">
    <div class="ds-header">
      <span class="material-icons-round">folder_open</span>
      <h3>Uploaded Files</h3>
    </div>
    <div class="ds-body" style="padding:8px 16px;">
      <?php
      $fileLabels = [
          'l_rev'   => '5c. Literature Review & Related Research',
          'appendA' => 'Appendix-A',
          'appendB' => 'Appendix-B',
          'appendC' => 'Appendix-C',
      ];
      foreach ($fileLabels as $type => $label):
      ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-border);">
          <strong style="font-size:0.875rem;"><?= $label ?></strong>
          <?php if (isset($atts[$type])): ?>
            <a href="/DRE/<?= ev($atts[$type]['file_path']) ?>" target="_blank" class="file-download-link">
              <span class="material-icons-round">download</span>
              <?= ev($atts[$type]['original_name']) ?>
            </a>
          <?php else: ?>
            <em class="text-muted fs-sm">Not uploaded</em>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Reviewer Assignment -->
  <div class="detail-section">
    <div class="ds-header">
      <span class="material-icons-round">rate_review</span>
      <h3>Assign Reviewers</h3>
    </div>
    <div class="ds-body" style="padding:20px;">
      <form method="post" action="assign_reviewer.php">
        <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">

        <div class="form-row">
          <div class="form-group">
            <label class="field-label">Internal Reviewer</label>
            <select name="internal_reviewer_id">
              <option value="">-- Select Internal Reviewer --</option>
              <?php foreach ($internalReviewers as $t): ?>
                <option value="<?= $t['rp_id'] ?>" <?= in_array($t['rp_id'], $assignedReviewerIds) ? 'selected' : '' ?>>
                  <?= ev($t['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="field-label">External Reviewer</label>
            <select name="external_reviewer_id">
              <option value="">-- Select External Reviewer --</option>
              <?php foreach ($externalReviewers as $er): ?>
                <option value="<?= $er['id'] ?>" <?= in_array($er['id'], $assignedReviewerIds) ? 'selected' : '' ?>>
                  <?= ev($er['external_name']) ?> (<?= ev($er['external_email']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="field-label">Comments for Reviewers</label>
          <textarea name="comments" rows="3" placeholder="Add comments or instructions for the reviewers..."><?= ev($comments) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
          <span class="material-icons-round">save</span> Save Assignment &amp; Notify
        </button>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
<script>
$(function(){
  $('select:not(.no-select2)').select2({
    theme:'default', width:'100%', allowClear:true
  });
});
</script>
