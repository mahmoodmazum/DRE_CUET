<?php
// public/dre/update_paper_call_deadline.php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

Auth::requireLogin();
$user = $_SESSION['user'];

if ($user['role'] !== 'dre_admin') {
    http_response_code(403);
    exit('Access denied');
}

// ---------------- INPUT ----------------
$id              = $_POST['id'] ?? null;
$deadline        = $_POST['deadline_date'] ?? null;
$deadlineReview  = $_POST['review_deadline'] ?? null;

if (!$id || !$deadline) {
    exit('Invalid request');
}

// ---------------- UPDATE PAPER CALL ----------------
$stmt = $pdo->prepare("
    UPDATE paper_calls 
    SET deadline_date = ?, review_deadline = ?
    WHERE id = ?
");
$stmt->execute([$deadline, $deadlineReview, $id]);

// ---------------- FETCH UPDATED INFO ----------------
$pcStmt = $pdo->prepare("
    SELECT issue_date, deadline_date, review_deadline 
    FROM paper_calls 
    WHERE id = ?
");
$pcStmt->execute([$id]);
$pc = $pcStmt->fetch();

if (!$pc) {
    header('Location: paper_calls.php?updated=1');
    exit;
}

// ---------------- EMAIL CONTENT ----------------
$config = require __DIR__ . '/../config/app.php';
$appLink = $config['app_url'];

$subject = "Deadline Updated: Call For Proposal Submission";

$bodyHtml = "
<p><strong>Notice:</strong> The submission deadline for a project proposal has been updated.</p>

<ul>
    <li><strong>Issue Date:</strong> {$pc['issue_date']}</li>
    <li><strong>New Submission Deadline:</strong> {$pc['deadline_date']}</li>
    <li><strong>Review Deadline:</strong> {$pc['review_deadline']}</li>
</ul>

<p>
    <a href='{$appLink}'
       style='display:inline-block;padding:10px 16px;
              background:#0d6efd;color:#ffffff;
              text-decoration:none;border-radius:4px;
              font-weight:bold'>
        Open DRE Portal
    </a>
</p>

<p>If the button does not work, use the link below:<br>
<a href='{$appLink}'>{$appLink}</a></p>

<p>Regards,<br>DRE-CUET</p>
";

// ---------------- SEND EMAIL ----------------
$teachers = $pdo->query("
    SELECT email, name 
    FROM users 
    WHERE role='teacher' AND status='active'
")->fetchAll();

foreach ($teachers as $t) {
    if (empty($t['email'])) continue;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dre_admin@cuet.ac.bd';
        $mail->Password   = 'wgnn ignn hggr jafy'; // move to .env later
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('dre_admin@cuet.ac.bd', 'DRE – CUET');
        $mail->addAddress($t['email'], $t['name']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();

    } catch (Exception $e) {
        error_log("Deadline update email failed to {$t['email']}: " . $mail->ErrorInfo);
    }
}

// ---------------- REDIRECT ----------------
header('Location: paper_calls.php?updated=1');
exit;