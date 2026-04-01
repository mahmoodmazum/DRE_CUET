<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';

Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

$pcid = intval($_GET['id'] ?? 0);
if (!$pcid) { exit('Missing paper call ID'); }

$call = $pdo->prepare("SELECT * FROM paper_calls WHERE id=?");
$call->execute([$pcid]);
$call = $call->fetch();
if (!$call) { exit('Paper call not found'); }

// Handle resend POST
$resendMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['resend_ids'])) {
    $resendIds = array_map('intval', $_POST['resend_ids']);
    if (empty($resendIds)) { $resendMsg = 'No users selected.'; goto done; }

    // Rebuild email body
    $atts = $pdo->prepare("SELECT * FROM paper_call_attachments WHERE paper_call_id=?");
    $atts->execute([$pcid]);
    $attachRows = $atts->fetchAll();

    $attachmentsListHtml = '';
    foreach ($attachRows as $a) {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $a['file_path'];
        $attachmentsListHtml .= '<li><a href="'.htmlspecialchars($url).'">'.htmlspecialchars($a['original_name']).'</a></li>';
    }
    $config  = require __DIR__ . '/../config/app.php';
    $appLink = $config['app_url'];
    $subject = "Call For Proposal: Issue {$call['issue_date']} — Submission Deadline {$call['deadline_date']}";
    $bodyHtml = "
<p><strong>Issue Date:</strong> {$call['issue_date']}</p>
<p><strong>Deadline:</strong> {$call['deadline_date']}</p>
<p>{$call['message']}</p>
<p><a href='{$appLink}' style='display:inline-block;padding:10px 16px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold'>Open DRE Portal</a></p>
<p>If the button does not work: <a href='{$appLink}'>{$appLink}</a></p>
<p><strong>Signature:</strong><br>{$call['signature']}</p>
";
    if ($attachmentsListHtml) {
        $bodyHtml .= "<p><strong>Attachments:</strong><ul>{$attachmentsListHtml}</ul></p>";
    }

    // Get target users
    $in  = implode(',', $resendIds);
    $targets = $pdo->query("SELECT id, email, name FROM users WHERE id IN ({$in}) AND status='active'")->fetchAll();

    $logStmt = $pdo->prepare("INSERT INTO paper_call_email_log (paper_call_id, user_id, email, name, status, error_msg) VALUES (?,?,?,?,?,?)");

    $sent = 0; $failed = 0;
    foreach ($targets as $t) {
        $status = 'failed'; $errMsg = null;
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dre_admin@cuet.ac.bd';
            $mail->Password   = 'wgnn ignn hggr jafy';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->setFrom('dre_admin@cuet.ac.bd', 'DRE - CUET');
            $mail->addAddress($t['email'], $t['name']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->send();
            $status = 'sent'; $sent++;
        } catch (Exception $e) {
            $errMsg = $mail->ErrorInfo;
            error_log("Resend email to {$t['email']} failed: " . $errMsg);
            $failed++;
        }
        $logStmt->execute([$pcid, $t['id'], $t['email'], $t['name'], $status, $errMsg]);
    }
    $resendMsg = "Done: {$sent} sent, {$failed} failed.";
}
done:

// Fetch log summary
$logRows = $pdo->prepare("
    SELECT l.*, u.name AS user_name
    FROM paper_call_email_log l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.paper_call_id = ?
    ORDER BY l.sent_at DESC
");
$logRows->execute([$pcid]);
$logs = $logRows->fetchAll();

// Users who never received email for this call
$notSent = $pdo->prepare("
    SELECT u.id, u.email, u.name
    FROM users u
    WHERE u.role='teacher' AND u.status='active'
      AND u.id NOT IN (
          SELECT DISTINCT user_id FROM paper_call_email_log
          WHERE paper_call_id = ? AND status='sent' AND user_id IS NOT NULL
      )
    ORDER BY u.name
");
$notSent->execute([$pcid]);
$notSentUsers = $notSent->fetchAll();

// Users with failed status (latest attempt failed)
$failedUsers = $pdo->prepare("
    SELECT DISTINCT l.user_id, l.email, l.name
    FROM paper_call_email_log l
    WHERE l.paper_call_id = ? AND l.status='failed'
      AND l.user_id NOT IN (
          SELECT DISTINCT user_id FROM paper_call_email_log
          WHERE paper_call_id = ? AND status='sent' AND user_id IS NOT NULL
      )
    ORDER BY l.name
");
$failedUsers->execute([$pcid, $pcid]);
$failedUsersList = $failedUsers->fetchAll();

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

function ev2($v){ return htmlspecialchars($v ?? ''); }
?>

<div class="main-content">
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
    <div>
      <h1>Email Log — Proposal Call</h1>
      <div class="breadcrumb">Proposal Calls / Email Log</div>
    </div>
    <a href="paper_calls.php" class="btn btn-secondary">
      <span class="material-icons-round">arrow_back</span> Back
    </a>
  </div>

  <?php if ($resendMsg): ?>
    <div class="alert alert-success">
      <span class="material-icons-round">check_circle</span> <?= ev2($resendMsg) ?>
    </div>
  <?php endif; ?>

  <!-- Call Info -->
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>Call Details</h3></div>
    <div class="card-body" style="padding:0;">
      <table class="info-table">
        <tr><th>Issue Date</th><td><?= ev2($call['issue_date']) ?></td></tr>
        <tr><th>Submission Deadline</th><td><?= ev2($call['deadline_date']) ?></td></tr>
        <tr><th>Total Sent (all time)</th><td><?= count(array_filter($logs, fn($l)=>$l['status']==='sent')) ?></td></tr>
        <tr><th>Total Failed</th><td><?= count(array_filter($logs, fn($l)=>$l['status']==='failed')) ?></td></tr>
        <tr><th>Active Teachers Not Reached</th><td><?= count($notSentUsers) ?></td></tr>
      </table>
    </div>
  </div>

  <!-- Teachers not reached -->
  <?php if ($notSentUsers): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header">
      <h3>Teachers Not Yet Reached (<?= count($notSentUsers) ?>)</h3>
    </div>
    <div class="card-body">
      <form method="post">
        <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <button type="button" class="btn btn-outline btn-sm" onclick="toggleAll(true,'not_sent')">Select All</button>
          <button type="button" class="btn btn-outline btn-sm" onclick="toggleAll(false,'not_sent')">Deselect All</button>
        </div>
        <table class="data-table" style="width:100%;">
          <thead><tr><th style="width:40px;"><input type="checkbox" onchange="toggleAll(this.checked,'not_sent')"></th><th>Name</th><th>Email</th></tr></thead>
          <tbody>
            <?php foreach($notSentUsers as $u): ?>
            <tr>
              <td><input type="checkbox" name="resend_ids[]" value="<?= $u['id'] ?>" class="not_sent_cb"></td>
              <td><?= ev2($u['name']) ?></td>
              <td><?= ev2($u['email']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="margin-top:14px;">
          <button type="submit" class="btn btn-primary">
            <span class="material-icons-round">send</span> Send Email to Selected
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($failedUsersList): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header">
      <h3>Failed Deliveries (<?= count($failedUsersList) ?>)</h3>
    </div>
    <div class="card-body">
      <form method="post">
        <table class="data-table" style="width:100%;">
          <thead><tr><th style="width:40px;"><input type="checkbox" onchange="toggleAll(this.checked,'failed')"></th><th>Name</th><th>Email</th></tr></thead>
          <tbody>
            <?php foreach($failedUsersList as $u): ?>
            <tr>
              <td><input type="checkbox" name="resend_ids[]" value="<?= $u['user_id'] ?>" class="failed_cb"></td>
              <td><?= ev2($u['name']) ?></td>
              <td><?= ev2($u['email']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="margin-top:14px;">
          <button type="submit" class="btn btn-warning">
            <span class="material-icons-round">replay</span> Retry Failed Emails
          </button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Full Log -->
  <div class="card">
    <div class="card-header"><h3>Full Email Log</h3></div>
    <div class="card-body" style="padding:0;">
      <table id="logTable" class="data-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Error</th>
            <th>Sent At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($logs as $i=>$l): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= ev2($l['name']) ?></td>
            <td><?= ev2($l['email']) ?></td>
            <td>
              <span class="badge <?= $l['status']==='sent' ? 'badge-success' : 'badge-danger' ?>">
                <?= ev2($l['status']) ?>
              </span>
            </td>
            <td style="font-size:0.78rem;color:var(--c-danger);"><?= ev2($l['error_msg']) ?></td>
            <td><?= ev2($l['sent_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
<script>
function toggleAll(checked, group){
  document.querySelectorAll('.'+group+'_cb').forEach(cb=>cb.checked=checked);
}
$(function(){
  $('#logTable').DataTable({responsive:true,order:[[5,'desc']],pageLength:25});
});
</script>
