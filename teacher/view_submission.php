<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { exit('Submission ID missing'); }

$stmt = $pdo->prepare("
    SELECT s.*, pc.deadline_date, d.name AS department_name
    FROM submissions s
    LEFT JOIN paper_calls pc ON s.paper_call_id = pc.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE s.id = ? AND s.user_id = ?
");
$stmt->execute([$id, $user['sub'] ?? $user['id']]);
$sub = $stmt->fetch();
if (!$sub) { exit('Submission not found'); }

$canEdit = ($sub['deadline_date'] >= date('Y-m-d'));

// Decode JSON
$staffCosts    = json_decode($sub['staff_costs'],    true) ?: [];
$directExpenses = json_decode($sub['direct_expenses'], true) ?: [];
$projectTeam   = json_decode($sub['project_team'],   true) ?: [];

// Total helpers — support both old (amount) and new (year1/year2/year3) structures
function rowTotal($r){ return (float)($r['year1']??$r['amount']??0)+(float)($r['year2']??0)+(float)($r['year3']??0); }
$totalStaff  = array_sum(array_map('rowTotal', $staffCosts));
$totalDirect = array_sum(array_map('rowTotal', $directExpenses));
$totalCost   = $totalStaff + $totalDirect;

// Attachments
$attStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
$attStmt->execute([$id]);
$atts = [];
foreach ($attStmt->fetchAll() as $a) { $atts[$a['type']] = $a; }

function fmtMoney($v){ return '৳ '.number_format((float)$v, 2); }
function ev($v){ return htmlspecialchars($v ?? 'N/A'); }
?>

<div class="main-content">
  <div class="page-header">
    <h1>Proposal Details</h1>
    <div class="breadcrumb">Dashboard / Proposal #<?= $id ?></div>
  </div>

  <div class="page-body">

    <!-- Actions -->
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
      <?php if ($canEdit): ?>
        <a href="submit_paper.php?edit=<?= $id ?>" class="btn btn-primary">
          <span class="material-icons-round">edit</span> Edit Proposal
        </a>
        <a href="delete_submission.php?id=<?= $id ?>" class="btn btn-danger"
           onclick="return confirm('Delete this proposal?')">
          <span class="material-icons-round">delete</span> Delete
        </a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn btn-success">
        <span class="material-icons-round">print</span> Print
      </button>
      <a href="dashboard.php" class="btn btn-secondary">
        <span class="material-icons-round">arrow_back</span> Back
      </a>
    </div>

    <!-- Status Banner -->
    <div class="alert alert-info" style="margin-bottom:16px;">
      <span class="material-icons-round">info</span>
      <div>
        <strong>Submission Status:</strong> <?= ev($sub['status']) ?> &nbsp;|&nbsp;
        <strong>Submitted:</strong> <?= ev($sub['created_at']) ?>
      </div>
    </div>

    <!-- Basic Info -->
    <div class="card">
      <div class="card-header"><h3>Proposal Overview</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="info-table">
          <tr><th>Project Title</th><td><strong><?= ev($sub['project_title']) ?></strong></td></tr>
          <tr><th>Department</th><td><?= ev($sub['department_name']) ?></td></tr>
          <tr><th>Year / Phase</th><td><?= ev($sub['year']) ?> / <?= ev($sub['phase']) ?></td></tr>
          <tr><th>Principal Investigator (PI)</th><td><?= ev($sub['pi']) ?></td></tr>
          <tr><th>Co-Principal Investigator (Co-PI)</th><td><?= ev($sub['co_pi']) ?></td></tr>
          <tr><th>Keywords</th><td><?= ev($sub['keywords']) ?></td></tr>
          <tr><th>Project Status</th><td><?= ev($sub['project_status']) ?></td></tr>
          <tr><th>Type of Research</th><td><?= ev($sub['research_type']) ?></td></tr>
          <tr><th>Start Date</th><td><?= ev($sub['start_date']) ?></td></tr>
          <tr><th>Duration (Months)</th><td><?= ev($sub['duration_months']) ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Detailed Sections -->
    <div class="card">
      <div class="card-header"><h3>Proposal Content</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="info-table">
          <tr><th>4. Specific Objectives</th><td><?= nl2br(ev($sub['specific_objectives'])) ?></td></tr>
          <tr><th>5b. Project Summary</th><td><?= nl2br(ev($sub['literature_review_text'])) ?></td></tr>
          <tr><th>5c. Literature Review &amp; Related Research</th>
              <td>
                <?php if (isset($atts['l_rev'])): ?>
                  <a href="/DRE/<?= htmlspecialchars($atts['l_rev']['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                    <span class="material-icons-round">download</span> <?= ev($atts['l_rev']['original_name']) ?>
                  </a>
                <?php else: echo '<em class="text-muted">Not uploaded</em>'; endif; ?>
              </td>
          </tr>
          <tr><th>7. Beneficiaries</th><td><?= nl2br(ev($sub['beneficiaries'])) ?></td></tr>
          <tr><th>8. Expected Outcomes</th><td><?= nl2br(ev($sub['outputs'])) ?></td></tr>
          <tr><th>9. Technology Transfer</th><td><?= nl2br(ev($sub['transfer'])) ?></td></tr>
          <tr><th>10. Organizational Expected Outcomes</th><td><?= nl2br(ev($sub['organizational_outcomes'])) ?></td></tr>
          <tr><th>11. National Impacts</th><td><?= nl2br(ev($sub['national_impacts'])) ?></td></tr>
          <tr><th>12. External Organizations</th><td><?= nl2br(ev($sub['external_org'])) ?></td></tr>
          <tr><th>14. Research Methodology</th>
              <td>
                <?php if (isset($atts['methodology'])): ?>
                  <a href="/DRE/<?= htmlspecialchars($atts['methodology']['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                    <span class="material-icons-round">download</span> <?= ev($atts['methodology']['original_name']) ?>
                  </a>
                <?php else: echo '<em class="text-muted">Not uploaded</em>'; endif; ?>
              </td>
          </tr>
          <tr><th>15. Project Activities</th><td><?= nl2br(ev($sub['activities'])) ?></td></tr>
          <tr><th>16. Key Milestones</th><td><?= nl2br(ev($sub['milestones'])) ?></td></tr>
          <tr><th>21. Other Grants</th><td><?= nl2br(ev($sub['other_grants'])) ?></td></tr>
          <tr><th>22. Contractual Obligations</th><td><?= nl2br(ev($sub['contractual_obligations'])) ?></td></tr>
          <tr><th>23. IP Ownership</th><td><?= nl2br(ev($sub['ip_ownership'])) ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Project Team -->
    <?php if ($projectTeam): ?>
    <div class="card">
      <div class="card-header"><h3>13. Project Team</h3></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr><th>Type</th><th>Name</th><th>Count</th><th>Organization</th><th>Man-Month</th></tr>
          </thead>
          <tbody>
            <?php foreach($projectTeam as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['type'] ?? '—') ?></td>
              <td><?= htmlspecialchars($m['name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($m['count'] ?? 1) ?></td>
              <td><?= htmlspecialchars($m['org'] ?? '—') ?></td>
              <td><?= htmlspecialchars($m['mm'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="4"><strong>Total Man-Month</strong></td>
                <td><strong><?= array_sum(array_column($projectTeam,'mm')) ?></strong></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Staff Costs -->
    <div class="card">
      <div class="card-header"><h3>18. Additional Staff Costs</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if ($staffCosts): ?>
        <table class="data-table">
          <thead><tr><th>Staff Category</th><th>Year 1 (৳)</th><th>Year 2 (৳)</th><th>Year 3 (৳)</th><th>Row Total</th></tr></thead>
          <tbody>
            <?php foreach($staffCosts as $c): $rt = rowTotal($c); ?>
            <tr>
              <td><?= htmlspecialchars($c['category'] ?? '') ?></td>
              <td><?= number_format((float)($c['year1']??$c['amount']??0),2) ?></td>
              <td><?= number_format((float)($c['year2']??0),2) ?></td>
              <td><?= number_format((float)($c['year3']??0),2) ?></td>
              <td><strong><?= number_format($rt,2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="4"><strong>Sub-Total Staff Costs</strong></td>
                <td><strong><?= fmtMoney($totalStaff) ?></strong></td></tr>
          </tfoot>
        </table>
        <?php else: ?>
          <div style="padding:20px;color:var(--c-text-muted);">No staff costs entered.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Direct Expenses -->
    <div class="card">
      <div class="card-header"><h3>19. Direct Project Expenses</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if ($directExpenses): ?>
        <table class="data-table">
          <thead><tr><th>Description</th><th>Year 1 (৳)</th><th>Year 2 (৳)</th><th>Year 3 (৳)</th><th>Row Total</th></tr></thead>
          <tbody>
            <?php foreach($directExpenses as $d): $rt = rowTotal($d); ?>
            <tr>
              <td><?= htmlspecialchars($d['description'] ?? $d['category'] ?? '') ?></td>
              <td><?= number_format((float)($d['year1']??$d['amount']??0),2) ?></td>
              <td><?= number_format((float)($d['year2']??0),2) ?></td>
              <td><?= number_format((float)($d['year3']??0),2) ?></td>
              <td><strong><?= number_format($rt,2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="4"><strong>Sub-Total Direct Expenses</strong></td>
                <td><strong><?= fmtMoney($totalDirect) ?></strong></td></tr>
          </tfoot>
        </table>
        <?php else: ?>
          <div style="padding:20px;color:var(--c-text-muted);">No direct expenses entered.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Total Cost -->
    <div class="total-box" style="margin-bottom:20px;">
      <div class="label">20. Total Project Cost</div>
      <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:0.9rem;opacity:.85;">
        <span>Sub-Total Staff Costs</span><span><?= fmtMoney($totalStaff) ?></span>
      </div>
      <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:0.9rem;opacity:.85;">
        <span>Sub-Total Direct Expenses</span><span><?= fmtMoney($totalDirect) ?></span>
      </div>
      <div class="value" style="margin-top:12px;border-top:1px solid rgba(255,255,255,.3);padding-top:12px;display:flex;justify-content:space-between;">
        <span style="font-size:1rem;">Grand Total</span><span><?= fmtMoney($totalCost) ?></span>
      </div>
    </div>

    <!-- Uploaded Files -->
    <div class="card">
      <div class="card-header"><h3>Uploaded Files</h3></div>
      <div class="card-body">
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
              <a href="/DRE/<?= htmlspecialchars($atts[$type]['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
                <span class="material-icons-round">download</span>
                <?= ev($atts[$type]['original_name']) ?>
              </a>
            <?php else: ?>
              <span class="text-muted fs-sm">Not uploaded</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Confirmation -->
    <?php if ($sub['acknowledgement']): ?>
    <div class="alert alert-success">
      <span class="material-icons-round">verified_user</span>
      <span>Declared and confirmed by: <strong><?= ev($sub['confirm_name'] ?? $user['name']) ?></strong></span>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
