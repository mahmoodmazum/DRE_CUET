<?php

require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

Auth::requireLogin();
$user = $_SESSION['user'];

if ($user['role'] !== 'dre_admin') {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request');
}

// ---------------- INPUT ----------------
$submission_id        = $_POST['submission_id'] ?? null;
$internal_reviewer_id = $_POST['internal_reviewer_id'] ?? null; // reviewer_poll.id
$external_reviewer_id = $_POST['external_reviewer_id'] ?? null; // reviewer_poll.id
$comments             = $_POST['comments'] ?? null;

if (!$submission_id) exit('Submission ID missing');

// ---------------- APP LINK ----------------
$config = require __DIR__ . '/../config/app.php';
$appLink = $config['app_url'];

// ---------------- TRANSACTION ----------------
try {
    $pdo->beginTransaction();

    // --- INTERNAL REVIEWER ---
    if ($internal_reviewer_id) {
        
            $stmt = $pdo->prepare("
    INSERT INTO reviews (submission_id, reviewer_id, comments, created_at, updated_at)
    VALUES (?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        reviewer_id = VALUES(reviewer_id),
        comments = VALUES(comments),
        updated_at = NOW()
");

$stmt->execute([$submission_id, $internal_reviewer_id, $comments]);

            // --- SEND EMAIL TO INTERNAL REVIEWER ---
            $userStmt = $pdo->prepare("
                SELECT email, name FROM users 
                WHERE id = (SELECT user_id FROM reviewer_pool WHERE id = ?)
            ");
            $userStmt->execute([$internal_reviewer_id]);
            $internal = $userStmt->fetch();

            if ($internal && $internal['email']) {
                $subject = "You have been assigned as internal reviewer";
                $bodyHtml = "
                    <p>Dear {$internal['name']},</p>
                    <p>You have been assigned as an <strong>internal reviewer</strong> for submission ID: {$submission_id}.</p>
                    <p>Comments (if any): {$comments}</p>
                    <p>Please access your reviewer portal here: 
                    <a href='{$appLink}'>{$appLink}</a></p>
                    <p>Regards,<br>DRE – CUET</p>
                ";

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'dre_admin@cuet.ac.bd';
                    $mail->Password   = 'wgnn ignn hggr jafy'; // move to config/env later
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('dre_admin@cuet.ac.bd', 'DRE – CUET');
                    $mail->addAddress($internal['email'], $internal['name']);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $bodyHtml;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Internal reviewer email failed: " . $mail->ErrorInfo);
                }
            }
        
    }

    // --- EXTERNAL REVIEWER ---
    if ($external_reviewer_id) {
        
            $stmt = $pdo->prepare("
    INSERT INTO reviews (submission_id, reviewer_id, comments, created_at, updated_at)
    VALUES (?, ?, ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
        reviewer_id = VALUES(reviewer_id),
        comments = VALUES(comments),
        updated_at = NOW()
");

$stmt->execute([$submission_id, $external_reviewer_id, $comments]);

            // --- SEND EMAIL TO EXTERNAL REVIEWER ---
            $extStmt = $pdo->prepare("SELECT external_email FROM reviewer_pool WHERE id = ?");
            $extStmt->execute([$external_reviewer_id]);
            $external = $extStmt->fetch();

            if ($external && $external['external_email']) {
                $reviewLink = "{$config['app_url_external']}DRE/teacher/external.php?email={$external['external_email']}";
                $subject = "You have been assigned as external reviewer";
                $bodyHtml = "
                    <p>Dear Sir,</p>
                    <p>You have been assigned as an <strong>external reviewer</strong> for submission ID: {$submission_id}.</p>
                    <p>Comments (if any): {$comments}</p>
                    <p>Please access your review link here: 
                    <a href='{$reviewLink}'>{$reviewLink}</a></p>
                    <p>Regards,<br>DRE – CUET</p>
                ";

                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'dre_admin@cuet.ac.bd';
                    $mail->Password   = 'wgnn ignn hggr jafy'; // move to config/env later
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('dre_admin@cuet.ac.bd', 'DRE – CUET');
                    $mail->addAddress($external['external_email'], $external['name']);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $bodyHtml;
                    $mail->send();
                } catch (Exception $e) {
                    error_log("External reviewer email failed: " . $mail->ErrorInfo);
                }
            }
        
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    exit("Error: " . $e->getMessage());
}

// Redirect back
header("Location: view_submission_admin.php?id=" . urlencode($submission_id));
exit;