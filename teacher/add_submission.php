<?php
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

$project_team = $_POST['project_team'] ? $_POST['project_team'] : '[]';
$staff_costs = $_POST['staff_costs'] ? $_POST['staff_costs'] : '[]';
$direct_expenses = $_POST['direct_expenses'] ? $_POST['direct_expenses'] : '[]';
$ack = !empty($_POST['acknowledge']) ? 1 : 0;

if ($id) {
    // update (only if owner)
    $sql = "UPDATE submissions SET department_id=:department_id, year=:year, phase=:phase, project_title=:project_title, pi=:pi, co_pi=:co_pi, keywords=:keywords,
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
    header('Location: /DRE/teacher/dashboard.php?updated=1'); exit;
} else {
    // find active paper_call
    $pc = $pdo->query("SELECT id FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();
    if (!$pc) { exit('No active paper call available.'); }
    $pcid = $pc['id'];

    $sql = "INSERT INTO submissions (user_id, paper_call_id, department_id, year, phase, project_title, pi, co_pi, keywords,
      specific_objectives, background, project_status, literature_review, related_research, research_type, beneficiaries, outputs, transfer, organizational_outcomes, national_impacts,
      external_org, project_team, methodology, activities, milestones, start_date, duration_months, staff_costs, direct_expenses, other_grants, contractual_obligations, ip_ownership, acknowledgement, status)
      VALUES (:user_id,:paper_call_id,:department_id,:year,:phase,:project_title,:pi,:co_pi,:keywords,
      :specific_objectives,:background,:project_status,:literature_review,:related_research,:research_type,:beneficiaries,:outputs,:transfer,:organizational_outcomes,:national_impacts,
      :external_org,:project_team,:methodology,:activities,:milestones,:start_date,:duration_months,:staff_costs,:direct_expenses,:other_grants,:contractual_obligations,:ip_ownership,:acknowledge,'submitted')";
    $stmt = $pdo->prepare($sql);
    $params = array_merge($data, [
        ':user_id'=>$uid, ':paper_call_id'=>$pcid, ':project_team'=>$project_team,
        ':staff_costs'=>$staff_costs, ':direct_expenses'=>$direct_expenses, ':acknowledge'=>$ack
    ]);
    $stmt->execute($params);
    header('Location: /DRE/teacher/dashboard.php?submitted=1'); exit;
}
