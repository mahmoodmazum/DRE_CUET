<?php
// public/dre/add_paper_call.php

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
$issue           = $_POST['issue_date'] ?? null;
$deadline        = $_POST['deadline_date'] ?? null;
$review_deadline = $_POST['review_deadline_date'] ?? null;
$message         = $_POST['message'] ?? '';
$signature       = $_POST['signature'] ?? '';

if (!$issue || !$deadline || !$message) {
    exit('Missing required fields.');
}

// ---------------- INSERT PAPER CALL ----------------
$stmt = $pdo->prepare("
    INSERT INTO paper_calls 
    (issue_date, deadline_date, review_deadline, message, signature, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $issue,
    $deadline,
    $review_deadline,
    $message,
    $signature,
    $user['sub'] ?? $user['id']
]);

$pcid = $pdo->lastInsertId();

// ---------------- FILE UPLOAD ----------------
$uploadDir = __DIR__ . '/../uploads/paper_calls/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES['attachments']['name'][0])) {
    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
        $orig = $_FILES['attachments']['name'][$i];
        $tmp  = $_FILES['attachments']['tmp_name'][$i];
        if (!$tmp) continue;

        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $new = uniqid('pc_'.$pcid.'_') . '.' . $ext;
        $dest = $uploadDir . $new;

        if (move_uploaded_file($tmp, $dest)) {
            $pathForWeb = 'uploads/paper_calls/' . $new;
            $ins = $pdo->prepare("
                INSERT INTO paper_call_attachments
                (paper_call_id, file_path, original_name)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$pcid, $pathForWeb, $orig]);
        }
    }
}

// ---------------- EMAIL CONTENT ----------------
$teachers = $pdo->query("
    SELECT id, email, name
    FROM users
    WHERE role='teacher' AND status='active'
")->fetchAll();

$subject = "Call For Proposal: Issue {$issue} — Submission Deadline {$deadline}";

$attachmentsListHtml = '';
$attStmt = $pdo->prepare("SELECT * FROM paper_call_attachments WHERE paper_call_id = ?");
$attStmt->execute([$pcid]);
$atts = $attStmt->fetchAll();

if ($atts) {
    foreach ($atts as $a) {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $a['file_path'];
        $attachmentsListHtml .= '<li><a href="'.$url.'">'.htmlspecialchars($a['original_name']).'</a></li>';
    }
}
$config = require __DIR__ . '/../config/app.php';
$appLink = $config['app_url'];
$bodyHtml = "
<p><strong>Issue Date:</strong> {$issue}</p>
<p><strong>Deadline:</strong> {$deadline}</p>

<p>{$message}</p>

<p>
    <a href='{$appLink}'
       style='display:inline-block;padding:10px 16px;
              background:#0d6efd;color:#ffffff;
              text-decoration:none;border-radius:4px;
              font-weight:bold'>
        Open DRE Portal
    </a>
</p>

<p>If the button does not work, copy and paste this link into your browser:<br>
<a href='{$appLink}'>{$appLink}</a></p>

<p><strong>Signature:</strong><br>{$signature}</p>
";

if ($attachmentsListHtml) {
    $bodyHtml .= "<p><strong>Attachments:</strong><ul>{$attachmentsListHtml}</ul></p>";
}

// ---------------- SEND EMAIL (GMAIL SMTP) ----------------
$logStmt = $pdo->prepare("
    INSERT INTO paper_call_email_log (paper_call_id, user_id, email, name, status, error_msg)
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($teachers as $t) {
    if (empty($t['email'])) continue;

    $status   = 'failed';
    $errorMsg = null;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dre_admin@cuet.ac.bd';
        $mail->Password   = 'wgnn ignn hggr jafy'; // 🔴 16-char app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('dre_admin@cuet.ac.bd', 'DRE - CUET');
        $mail->addAddress($t['email'], $t['name']);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        $status = 'sent';

    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo;
        error_log("Email to {$t['email']} failed: " . $errorMsg);
    }

    // Log this send attempt
    $logStmt->execute([
        $pcid,
        $t['id'] ?? null,
        $t['email'],
        $t['name'],
        $status,
        $errorMsg
    ]);
}

// ---------------- REDIRECT ----------------
header('Location: paper_calls.php?created=1');
exit;