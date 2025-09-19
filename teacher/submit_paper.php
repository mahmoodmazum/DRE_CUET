<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();

if ($user['role'] !== 'teacher' || !$call) { http_response_code(403); exit('Access denied'); }

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();



// If edit mode
$editing = false;
$editData = null;
if (!empty($_GET['edit'])) {
    $editing = true;
    $id = intval($_GET['edit']);
    $editData = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND user_id = ?");
    $editData->execute([$id, $user['sub'] ?? $user['id']]);
    $editData = $editData->fetch();
    if (!$editData) { exit('Not found or access denied'); }
}

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid"><h1><?= $editing ? 'Edit' : 'Submit' ?> Paper</h1></div></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <form method="post" action="add_submission.php" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editing ? $editData['id'] : '' ?>">

        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Department</label>
            <select name="department_id" class="form-control" required>
              <option value="">Select</option>
              <?php foreach($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $editing && $editData['department_id']==$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-2"><label>Year</label><input type="number" name="year" class="form-control" value="<?= $editing ? $editData['year'] : '' ?>"></div>
          <div class="form-group col-md-2"><label>Phase</label><input type="text" name="phase" class="form-control" value="<?= $editing ? $editData['phase'] : '' ?>"></div>
        </div>

        <div class="form-group"><label>Project Title</label><input type="text" name="project_title" class="form-control" value="<?= $editing ? htmlspecialchars($editData['project_title']) : '' ?>" required></div>

        <div class="form-group"><label>Principal Investigator (PI)</label><input type="text" name="pi" class="form-control" value="<?= $editing ? htmlspecialchars($editData['pi']) : '' ?>"></div>
        <div class="form-group"><label>Co-PI</label><input type="text" name="co_pi" class="form-control" value="<?= $editing ? htmlspecialchars($editData['co_pi']) : '' ?>"></div>
        <div class="form-group"><label>Key Words (max 5)</label><input type="text" name="keywords" class="form-control" value="<?= $editing ? htmlspecialchars($editData['keywords']) : '' ?>"></div>

        <div class="form-group"><label>Specific Objectives</label><textarea name="specific_objectives" class="form-control" rows="4"><?= $editing ? htmlspecialchars($editData['specific_objectives']) : '' ?></textarea></div>

        <div class="form-group"><label>Research Background</label><textarea name="background" class="form-control" rows="4"><?= $editing ? htmlspecialchars($editData['background']) : '' ?></textarea></div>

        <div class="form-group"><label>Project Status</label>
          <select name="project_status" class="form-control">
            <option value="New" <?= $editing && $editData['project_status']=='New' ? 'selected' : '' ?>>New</option>
            <option value="Modification" <?= $editing && $editData['project_status']=='Modification' ? 'selected' : '' ?>>Modification</option>
            <option value="Extension" <?= $editing && $editData['project_status']=='Extension' ? 'selected' : '' ?>>Extension</option>
          </select>
        </div>

        <div class="form-group"><label>Literature Review Summary</label><textarea name="literature_review" class="form-control" rows="4"><?= $editing ? htmlspecialchars($editData['literature_review']) : '' ?></textarea></div>

        <div class="form-group"><label>Related Research</label><textarea name="related_research" class="form-control" rows="4"><?= $editing ? htmlspecialchars($editData['related_research']) : '' ?></textarea></div>

        <div class="form-group"><label>Type of Research</label>
          <select name="research_type" class="form-control">
            <option value="Scientific" <?= $editing && $editData['research_type']=='Scientific' ? 'selected':'' ?>>Scientific Research (Fundamental)</option>
            <option value="Technology" <?= $editing && $editData['research_type']=='Technology' ? 'selected':'' ?>>Technology Development (Applied)</option>
            <option value="Product" <?= $editing && $editData['research_type']=='Product' ? 'selected':'' ?>>Product/Process Development</option>
          </select>
        </div>

        <!-- multiple large text areas -->
        <?php
        $areas = ['beneficiaries'=>'Direct Customers/Beneficiaries','outputs'=>'Outputs Expected','transfer'=>'Technology Transfer/Diffusion','organizational_outcomes'=>'Organizational Outcomes','national_impacts'=>'National Impacts','external_org'=>'Outside Research Organizations involved','methodology'=>'Research Methodology','activities'=>'Project Activities','milestones'=>'Key Milestones','other_grants'=>'Any Other Research Grant','contractual_obligations'=>'Contractual Obligations','ip_ownership'=>'Ownership of IP'];
        foreach($areas as $k=>$label): ?>
          <div class="form-group"><label><?= $label ?></label><textarea name="<?= $k ?>" class="form-control" rows="3"><?= $editing ? htmlspecialchars($editData[$k]) : '' ?></textarea></div>
        <?php endforeach; ?>

        <div class="form-row">
          <div class="form-group col-md-4"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="<?= $editing ? $editData['start_date'] : '' ?>"></div>
          <div class="form-group col-md-4"><label>Duration (months)</label><input type="number" name="duration_months" class="form-control" value="<?= $editing ? $editData['duration_months'] : '' ?>"></div>
        </div>

        <!-- Project Team (JSON): we'll use textarea to hold JSON created by JS -->
        <div class="form-group">
          <label>Project Team (add rows)</label>
          <div id="teamRows"></div>
          <button type="button" id="addTeamBtn" class="btn btn-sm btn-secondary mb-2">Add Team Member</button>
          <input type="hidden" name="project_team" id="project_team" value='<?= $editing ? htmlspecialchars($editData['project_team']) : '[]' ?>'>
        </div>

        <!-- Staff costs (JSON) -->
        <div class="form-group">
          <label>Staff Costs (year-wise)</label>
          <div id="staffCosts"></div>
          <button type="button" id="addStaffBtn" class="btn btn-sm btn-secondary mb-2">Add Staff Cost Row</button>
          <input type="hidden" name="staff_costs" id="staff_costs" value='<?= $editing ? htmlspecialchars($editData['staff_costs']) : '[]' ?>'>
        </div>

        <!-- Direct expenses (JSON) -->
        <div class="form-group">
          <label>Direct Expenses</label>
          <div id="directExpenses"></div>
          <button type="button" id="addExpenseBtn" class="btn btn-sm btn-secondary mb-2">Add Expense Row</button>
          <input type="hidden" name="direct_expenses" id="direct_expenses" value='<?= $editing ? htmlspecialchars($editData['direct_expenses']) : '[]' ?>'>
        </div>

        <div class="form-group">
          <label>Acknowledgement</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="acknowledge" id="ack" value="1" <?= $editing && $editData['acknowledgement'] ? 'checked':'' ?>>
            <label class="form-check-label" for="ack">I hereby acknowledge that all information is true and correct.</label>
          </div>
        </div>

        <div class="form-group">
          <button class="btn btn-success" type="submit"><?= $editing ? 'Update' : 'Submit' ?></button>
          <a href="/teacher/dashboard.php" class="btn btn-default">Cancel</a>
        </div>

      </form>
    </div></div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>

<script>
$(function(){
  // minimal JS to manage JSON collections
  const teamInput = $('#project_team');
  const staffInput = $('#staff_costs');
  const expenseInput = $('#direct_expenses');

  let team = JSON.parse(teamInput.val() || '[]');
  let staff = JSON.parse(staffInput.val() || '[]');
  let expenses = JSON.parse(expenseInput.val() || '[]');

  function renderTeam(){
    $('#teamRows').html('');
    team.forEach((t,i)=>{
      $('#teamRows').append(`<div class="form-row mb-2">
        <div class="col"><input class="form-control" placeholder="Name" value="${t.name||''}" data-i="${i}" data-key="name"></div>
        <div class="col"><input class="form-control" placeholder="Organization" value="${t.org||''}" data-i="${i}" data-key="org"></div>
        <div class="col"><input type="number" class="form-control" placeholder="Man month" value="${t.mm||0}" data-i="${i}" data-key="mm"></div>
        <div class="col-auto"><button class="btn btn-danger rmTeam" data-i="${i}">Remove</button></div>
      </div>`);
    });
    teamInput.val(JSON.stringify(team));
  }

  function renderStaff(){
    $('#staffCosts').html('');
    staff.forEach((s,i)=>{
      $('#staffCosts').append(`<div class="form-row mb-2">
        <div class="col"><select class="form-control" data-i="${i}" data-key="category"><option>Salaried Paid</option><option>Temporary</option><option>Contract personnel</option></select></div>
        <div class="col"><select class="form-control" data-i="${i}" data-key="year">${[2024,2025,2026,2027].map(y=>`<option ${s.year==y?'selected':''}>${y}</option>`).join('')}</select></div>
        <div class="col"><input type="number" step="0.01" class="form-control" placeholder="Amount" value="${s.amount||0}" data-i="${i}" data-key="amount"></div>
        <div class="col-auto"><button class="btn btn-danger rmStaff" data-i="${i}">Remove</button></div>
      </div>`);
    });
    staffInput.val(JSON.stringify(staff));
  }

  function renderExpenses(){
    $('#directExpenses').html('');
    expenses.forEach((e,i)=>{
      $('#directExpenses').append(`<div class="form-row mb-2">
        <div class="col"><select class="form-control" data-i="${i}" data-key="category"><option>Travel and Transportation</option><option>Rentals</option><option>Research Materials and Supplies</option><option>Minor Modifications and Repairs</option><option>Special Services</option><option>Special Equipment and Accessories</option></select></div>
        <div class="col"><select class="form-control" data-i="${i}" data-key="year">${[2024,2025,2026].map(y=>`<option ${e.year==y?'selected':''}>${y}</option>`).join('')}</select></div>
        <div class="col"><input type="number" step="0.01" class="form-control" placeholder="Amount" value="${e.amount||0}" data-i="${i}" data-key="amount"></div>
        <div class="col-auto"><button class="btn btn-danger rmExp" data-i="${i}">Remove</button></div>
      </div>`);
    });
    expenseInput.val(JSON.stringify(expenses));
  }

  // initial render
  renderTeam(); renderStaff(); renderExpenses();

  // add handlers
  $('#addTeamBtn').click(()=>{ team.push({name:'',org:'',mm:0}); renderTeam(); });
  $('#addStaffBtn').click(()=>{ staff.push({category:'Salaried Paid',year:2024,amount:0}); renderStaff(); });
  $('#addExpenseBtn').click(()=>{ expenses.push({category:'Travel and Transportation',year:2024,amount:0}); renderExpenses(); });

  // delegate changes
  $(document).on('input change', '#teamRows [data-key]', function(){
    const i = $(this).data('i'); const key = $(this).data('key'); team[i][key] = $(this).val(); teamInput.val(JSON.stringify(team));
  });
  $(document).on('click', '.rmTeam', function(){ team.splice($(this).data('i'),1); renderTeam(); });

  $(document).on('input change', '#staffCosts [data-key]', function(){
    const i = $(this).data('i'), key = $(this).data('key');
    staff[i][key] = $(this).val(); staffInput.val(JSON.stringify(staff));
  });
  $(document).on('click', '.rmStaff', function(){ staff.splice($(this).data('i'),1); renderStaff(); });

  $(document).on('input change', '#directExpenses [data-key]', function(){
    const i = $(this).data('i'), key = $(this).data('key');
    expenses[i][key] = $(this).val(); expenseInput.val(JSON.stringify(expenses));
  });
  $(document).on('click', '.rmExp', function(){ expenses.splice($(this).data('i'),1); renderExpenses(); });
});
</script>
