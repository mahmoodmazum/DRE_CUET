<?php
/* ---------- File: add_submission.php (UPDATED WITH FILE TYPES) ---------- */

require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

$uid = $user['sub'] ?? $user['id'];
$id = $_POST['id'] ?? null; // for edit

$fields = [
    'department_id','year','phase','project_title','pi','co_pi','keywords',
    'specific_objectives','background','project_status','literature_review','related_research',
    'research_type','beneficiaries','outputs','transfer','organizational_outcomes','national_impacts',
    'external_org','methodology','activities','milestones','start_date','duration_months',
    'other_grants','contractual_obligations','ip_ownership'
];

$data = [];
foreach($fields as $f) $data[$f] = $_POST[$f] ?? null;

$project_team = $_POST['project_team'] ?? '[]';
$staff_costs = $_POST['staff_costs'] ?? '[]';
$direct_expenses = $_POST['direct_expenses'] ?? '[]';
$ack = !empty($_POST['acknowledge']) ? 1 : 0;

/**
 * Save uploaded file, enforce one file per type per submission
 */
function save_attachment($fileField, $submissionId, $type, $pdo) {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$fileField];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;

    // Validate size (max 10MB) and extension
    $maxBytes = 10 * 1024 * 1024;
    if ($f['size'] > $maxBytes) return null;

    $allowed = ['pdf','doc','docx'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;

    // ensure submission folder
    $baseDir = __DIR__ . '/../uploads/submissions';
    if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
    $dir = $baseDir . '/' . intval($submissionId);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // delete existing file of this type if exists
    $stmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ? AND type = ?");
    $stmt->execute([$submissionId, $type]);
    $existing = $stmt->fetch();
    if ($existing) {
        $existingPath = __DIR__ . '/../' . $existing['file_path'];
        if (file_exists($existingPath)) unlink($existingPath);
        $pdo->prepare("DELETE FROM submission_attachments WHERE id = ?")->execute([$existing['id']]);
    }

    // save new file
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name']));
    $target = $dir . '/' . time() . '_' . bin2hex(random_bytes(6)) . '_' . $safeName;

    if (!move_uploaded_file($f['tmp_name'], $target)) return null;

    $relative = 'uploads/submissions/' . intval($submissionId) . '/' . basename($target);

    $stmt = $pdo->prepare("
        INSERT INTO submission_attachments (submission_id, file_path, original_name, type)
        VALUES (:sid, :path, :orig, :type)
    ");
    $stmt->execute([
        ':sid' => $submissionId,
        ':path' => $relative,
        ':orig' => $f['name'],
        ':type' => $type
    ]);

    return $relative;
}

try {
    if ($id) {
        // UPDATE existing submission
        $sql = "UPDATE submissions SET 
            department_id=:department_id, year=:year, phase=:phase, project_title=:project_title, pi=:pi, co_pi=:co_pi, keywords=:keywords,
            specific_objectives=:specific_objectives, background=:background, project_status=:project_status, literature_review=:literature_review, related_research=:related_research,
            research_type=:research_type, beneficiaries=:beneficiaries, outputs=:outputs, transfer=:transfer, organizational_outcomes=:organizational_outcomes, national_impacts=:national_impacts,
            external_org=:external_org, project_team=:project_team, methodology=:methodology, activities=:activities, milestones=:milestones, start_date=:start_date, duration_months=:duration_months,
            staff_costs=:staff_costs, direct_expenses=:direct_expenses, other_grants=:other_grants, contractual_obligations=:contractual_obligations, ip_ownership=:ip_ownership,
            acknowledgement=:acknowledge
            WHERE id=:id AND user_id=:uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, [
            ':project_team'=>$project_team, ':staff_costs'=>$staff_costs, ':direct_expenses'=>$direct_expenses, ':acknowledge'=>$ack,
            ':id'=>$id, ':uid'=>$uid
        ]));

        // handle file uploads (one per type)
        $fileMap = [
            'literature_review_file' => 'l_rev',
            'appendix_a_file' => 'appendA',
            'appendix_b_file' => 'appendB',
            'appendix_c_file' => 'appendC'
        ];
        foreach($fileMap as $ff => $type) {
            save_attachment($ff, $id, $type, $pdo);
        }

        header('Location: /DRE/teacher/dashboard.php?updated=1'); exit;

    } else {
        // INSERT new submission
        $pc = $pdo->query("SELECT id FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();
        if (!$pc) { exit('No active paper call available.'); }
        $pcid = $pc['id'];

        $sql = "INSERT INTO submissions (
            user_id, paper_call_id, department_id, year, phase, project_title, pi, co_pi, keywords,
            specific_objectives, background, project_status, literature_review, related_research, research_type, beneficiaries, outputs, transfer, organizational_outcomes, national_impacts,
            external_org, project_team, methodology, activities, milestones, start_date, duration_months, staff_costs, direct_expenses, other_grants, contractual_obligations, ip_ownership, acknowledgement, status
        ) VALUES (
            :user_id, :paper_call_id, :department_id, :year, :phase, :project_title, :pi, :co_pi, :keywords,
            :specific_objectives, :background, :project_status, :literature_review, :related_research, :research_type, :beneficiaries, :outputs, :transfer, :organizational_outcomes, :national_impacts,
            :external_org, :project_team, :methodology, :activities, :milestones, :start_date, :duration_months, :staff_costs, :direct_expenses, :other_grants, :contractual_obligations, :ip_ownership, :acknowledge, 'submitted'
        )";

        $stmt = $pdo->prepare($sql);
        $params = array_merge($data, [
            ':user_id'=>$uid, ':paper_call_id'=>$pcid, ':project_team'=>$project_team,
            ':staff_costs'=>$staff_costs, ':direct_expenses'=>$direct_expenses, ':acknowledge'=>$ack
        ]);
        $stmt->execute($params);
        $newId = $pdo->lastInsertId();

        // handle file uploads
        $fileMap = [
            'literature_review_file' => 'l_rev',
            'appendix_a_file' => 'appendA',
            'appendix_b_file' => 'appendB',
            'appendix_c_file' => 'appendC'
        ];
        foreach($fileMap as $ff => $type) {
            save_attachment($ff, $newId, $type, $pdo);
        }

        header('Location: /DRE/teacher/dashboard.php?submitted=1'); exit;
    }

} catch (Exception $e) {
    exit('An error occurred: ' . $e->getMessage());
}
