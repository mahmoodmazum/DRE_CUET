<?php
// public/dre/add_paper_call.php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

$issue = $_POST['issue_date'] ?? null;
$deadline = $_POST['deadline_date'] ?? null;
$review_deadline = $_POST['review_deadline_date'] ?? null;
$message = $_POST['message'] ?? '';
$signature = $_POST['signature'] ?? '';

if (!$issue || !$deadline || !$message) {
    exit('Missing required fields.');
}

// insert paper_call
$stmt = $pdo->prepare("INSERT INTO paper_calls (issue_date, deadline_date, review_deadline,message, signature, created_by) VALUES (?, ?, ?, ?, ?,?)");
$stmt->execute([$issue, $deadline,$review_deadline, $message, $signature, $user['sub'] ?? $user['id']]);

$pcid = $pdo->lastInsertId();

// handle attachments
$uploadDir = __DIR__ . '/../uploads/paper_calls/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (!empty($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
    for ($i=0;$i<count($_FILES['attachments']['name']);$i++){
        $orig = $_FILES['attachments']['name'][$i];
        $tmp = $_FILES['attachments']['tmp_name'][$i];
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $new = uniqid('pc_'.$pcid.'_').'.'.$ext;
        $destFull = $uploadDir . $new;
        if (move_uploaded_file($tmp, $destFull)) {
            $pathForWeb = 'uploads/paper_calls/' . $new;
            $ins = $pdo->prepare("INSERT INTO paper_call_attachments (paper_call_id, file_path, original_name) VALUES (?, ?, ?)");
            $ins->execute([$pcid, $pathForWeb, $orig]);
        }
    }
}

// send email to all teachers (status active)
$teachers = $pdo->query("SELECT email, name FROM users WHERE role='teacher' AND status='active'")->fetchAll();
$subject = "New Paper Call: Issue {$issue} — Deadline {$deadline}";
$attachmentsListHtml = '';
$attStmt = $pdo->prepare("SELECT * FROM paper_call_attachments WHERE paper_call_id = ?");
$attStmt->execute([$pcid]);
$atts = $attStmt->fetchAll();
if ($atts) {
    foreach($atts as $a) {
        $attachmentsListHtml .= '<li><a href="'.(isset($_SERVER['HTTP_HOST']) ? 'http://'.$_SERVER['HTTP_HOST'] : '') .$a['file_path'].'">'.htmlspecialchars($a['original_name']).'</a></li>';
    }
}
$bodyHtml = "<p>Issue Date: {$issue}</p><p>Deadline: {$deadline}</p><p>{$message}</p><p>Signature: {$signature}</p>";
if ($attachmentsListHtml) $bodyHtml .= "<p>Attachments:<ul>$attachmentsListHtml</ul></p>";

// NOTE: this uses simple mail(); for production use SMTP and queue.
foreach($teachers as $t) {
    $to = $t['email'];
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: DRE <no-reply@yourdomain.com>\r\n";
    @mail($to, $subject, $bodyHtml, $headers);
}

header('Location: paper_calls.php?created=1');
exit;
