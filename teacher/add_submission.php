<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'teacher') { http_response_code(403); exit('Access denied'); }

$uid = $user['sub'] ?? $user['id'];
$id  = $_POST['id'] ?? null;

$fields = [
    'department_id','year','phase','project_title','pi','co_pi','keywords',
    'specific_objectives','background','project_status','literature_review_text',
    'research_type','beneficiaries','outputs','transfer','organizational_outcomes',
    'national_impacts','external_org','activities','milestones',
    'start_date','duration_months','other_grants','contractual_obligations','ip_ownership',
    'confirm_name'
];

$data = [];
foreach ($fields as $f) $data[$f] = $_POST[$f] ?? null;

$project_team   = $_POST['project_team']   ?? '[]';
$staff_costs    = $_POST['staff_costs']    ?? '[]';
$direct_expenses = $_POST['direct_expenses'] ?? '[]';
$ack            = !empty($_POST['acknowledge']) ? 1 : 0;

/**
 * Save uploaded file — one per type per submission
 */
function save_attachment($fileField, $submissionId, $type, $pdo, $extraExts = []) {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$fileField];
    if ($f['error'] !== UPLOAD_ERR_OK) return null;

    if ($f['size'] > 10 * 1024 * 1024) return null;

    $allowed = array_merge(['pdf','doc','docx'], $extraExts);
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;

    $baseDir = __DIR__ . '/../uploads/submissions';
    if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
    $dir = $baseDir . '/' . intval($submissionId);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Remove existing of this type
    $stmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ? AND type = ?");
    $stmt->execute([$submissionId, $type]);
    $existing = $stmt->fetch();
    if ($existing) {
        $ep = __DIR__ . '/../' . $existing['file_path'];
        if (file_exists($ep)) unlink($ep);
        $pdo->prepare("DELETE FROM submission_attachments WHERE id = ?")->execute([$existing['id']]);
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($f['name']));
    $target   = $dir . '/' . time() . '_' . bin2hex(random_bytes(6)) . '_' . $safeName;
    if (!move_uploaded_file($f['tmp_name'], $target)) return null;

    $relative = 'uploads/submissions/' . intval($submissionId) . '/' . basename($target);
    $pdo->prepare("INSERT INTO submission_attachments (submission_id, file_path, original_name, type) VALUES (?,?,?,?)")
        ->execute([$submissionId, $relative, $f['name'], $type]);

    return $relative;
}

try {
    if ($id) {
        // ── UPDATE ──────────────────────────────────────────────
        $sql = "UPDATE submissions SET
            department_id=:department_id, year=:year, phase=:phase,
            project_title=:project_title, pi=:pi, co_pi=:co_pi, keywords=:keywords,
            specific_objectives=:specific_objectives, background=:background,
            project_status=:project_status, literature_review_text=:literature_review_text,
            research_type=:research_type, beneficiaries=:beneficiaries, outputs=:outputs,
            transfer=:transfer, organizational_outcomes=:organizational_outcomes,
            national_impacts=:national_impacts, external_org=:external_org,
            project_team=:project_team, activities=:activities, milestones=:milestones,
            start_date=:start_date, duration_months=:duration_months,
            staff_costs=:staff_costs, direct_expenses=:direct_expenses,
            other_grants=:other_grants, contractual_obligations=:contractual_obligations,
            ip_ownership=:ip_ownership, confirm_name=:confirm_name,
            acknowledgement=:acknowledge
            WHERE id=:id AND user_id=:uid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, [
            ':project_team'    => $project_team,
            ':staff_costs'     => $staff_costs,
            ':direct_expenses' => $direct_expenses,
            ':acknowledge'     => $ack,
            ':id'              => $id,
            ':uid'             => $uid
        ]));

        $fileMap = [
            'related_research_file' => 'l_rev',
            'appendix_a_file'       => 'appendA',
            'appendix_b_file'       => 'appendB',
            'appendix_c_file'       => 'appendC',
            'methodology_file'      => 'methodology',
        ];
        foreach ($fileMap as $ff => $type) {
            save_attachment($ff, $id, $type, $pdo);
        }

        header('Location: /DRE/teacher/dashboard.php?updated=1');
        exit;

    } else {
        // ── INSERT ──────────────────────────────────────────────
        $pc = $pdo->query("SELECT id FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();
        if (!$pc) { exit('No active proposal call available.'); }
        $pcid = $pc['id'];

        $sql = "INSERT INTO submissions (
            user_id, paper_call_id, department_id, year, phase, project_title, pi, co_pi, keywords,
            specific_objectives, background, project_status, literature_review_text,
            research_type, beneficiaries, outputs, transfer, organizational_outcomes,
            national_impacts, external_org, project_team, activities, milestones,
            start_date, duration_months, staff_costs, direct_expenses,
            other_grants, contractual_obligations, ip_ownership, confirm_name,
            acknowledgement, status
        ) VALUES (
            :user_id, :paper_call_id, :department_id, :year, :phase, :project_title,
            :pi, :co_pi, :keywords, :specific_objectives, :background, :project_status,
            :literature_review_text, :research_type, :beneficiaries, :outputs, :transfer,
            :organizational_outcomes, :national_impacts, :external_org, :project_team,
            :activities, :milestones, :start_date, :duration_months,
            :staff_costs, :direct_expenses, :other_grants, :contractual_obligations,
            :ip_ownership, :confirm_name, :acknowledge, 'submitted'
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, [
            ':user_id'         => $uid,
            ':paper_call_id'   => $pcid,
            ':project_team'    => $project_team,
            ':staff_costs'     => $staff_costs,
            ':direct_expenses' => $direct_expenses,
            ':acknowledge'     => $ack,
        ]));
        $newId = $pdo->lastInsertId();

        $fileMap = [
            'related_research_file' => 'l_rev',
            'appendix_a_file'       => 'appendA',
            'appendix_b_file'       => 'appendB',
            'appendix_c_file'       => 'appendC',
            'methodology_file'      => 'methodology',
        ];
        foreach ($fileMap as $ff => $type) {
            save_attachment($ff, $newId, $type, $pdo);
        }

        header('Location: /DRE/teacher/dashboard.php?submitted=1');
        exit;
    }

} catch (Exception $e) {
    exit('An error occurred: ' . $e->getMessage());
}
