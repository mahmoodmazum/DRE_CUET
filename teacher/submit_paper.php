<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();

if ($user['role'] !== 'teacher' || !$call) { http_response_code(403); exit('Access denied'); }

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

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

<style>
  .form-container { max-width:900px; margin:auto; padding:24px; background:#f9f9f9; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
  .form-group { margin-top:16px; display:flex; flex-direction:column; }
  .form-group label { font-weight:600; margin-bottom:6px; }
  .form-group small { color:#555; margin-bottom:4px; font-size:0.85rem; }
  input, select, textarea { padding:10px 12px; border-radius:6px; border:1px solid #ccc; font-size:1rem; }
  input:focus, select:focus, textarea:focus { outline:none; border-color:#1976d2; box-shadow:0 0 4px rgba(25,118,210,0.3); }
  .flex-row { display:flex; gap:16px; flex-wrap:wrap; }
  .flex-row > div { flex:1; min-width:120px; }
  button { padding:10px 18px; border:none; border-radius:6px; font-weight:600; cursor:pointer; }
  .btn-add { background:#4caf50; color:white; margin-top:8px; }
  .btn-remove { background:#f44336; color:white; }
  .btn-submit { background:#1976d2; color:white; margin-top:16px; margin-right:8px; }
  .btn-cancel { background:#9e9e9e; color:white; text-decoration:none; display:inline-block; line-height:1.8; padding:10px 18px; border-radius:6px; }
</style>

<div class="form-container">
  <h2><?= $editing ? 'Edit' : 'Submit' ?> Paper</h2>
  <form id="submissionForm" method="post" action="add_submission.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $editing ? $editData['id'] : '' ?>">

    <!-- Department, Year, Phase -->
    <div class="flex-row">
      <div class="form-group">
        <label>Department</label>
        <select name="department_id" required>
          <option value="">Select</option>
          <?php foreach($departments as $d): ?>
            <option value="<?= $d['id'] ?>" <?= $editing && $editData['department_id']==$d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Year</label>
        <input type="number" name="year" value="<?= $editing ? $editData['year'] : '' ?>">
      </div>
      <div class="form-group">
        <label>Phase</label>
        <input type="text" name="phase" value="<?= $editing ? $editData['phase'] : '' ?>">
      </div>
    </div>

    <!-- Project Title -->
    <div class="form-group">
      <label>1. Project Title</label>
      <small>(Title should be short and concise)</small>
      <input type="text" name="project_title" value="<?= $editing ? htmlspecialchars($editData['project_title']) : '' ?>" required>
    </div>

    <!-- PI & Co-PI -->
    <div class="form-group">
      <label>2a) Principal Investigator (PI)</label>
      <small>(Name and Department/Organization)</small>
      <input type="text" name="pi" value="<?= $editing ? htmlspecialchars($editData['pi']) : '' ?>">
    </div>
    <div class="form-group">
      <label>2b) Co-Principal Investigator (Co-PI)</label>
      <small>(Name and Department/Organization)</small>
      <input type="text" name="co_pi" value="<?= $editing ? htmlspecialchars($editData['co_pi']) : '' ?>">
    </div>

    <!-- Keywords -->
    <div class="form-group">
      <label>3. Key Words</label>
      <small>(Maximum 5 key words describing the project)</small>
      <input type="text" name="keywords" value="<?= $editing ? htmlspecialchars($editData['keywords']) : '' ?>">
    </div>

    <!-- Specific Objectives -->
    <div class="form-group">
      <label>4. Specific Objectives of the Project</label>
      <small>(Describe measurable objectives and expected results)</small>
      <textarea name="specific_objectives" rows="4"><?= $editing ? htmlspecialchars($editData['specific_objectives']) : '' ?></textarea>
    </div>

    <!-- Research Background -->
    <div class="form-group">
      <label>5a) Project Status</label>
      <select name="project_status">
        <option value="New" <?= $editing && $editData['project_status']=='New' ? 'selected' : '' ?>>New</option>
        <option value="Modification" <?= $editing && $editData['project_status']=='Modification' ? 'selected' : '' ?>>Modification</option>
        <option value="Extension" <?= $editing && $editData['project_status']=='Extension' ? 'selected' : '' ?>>Extension</option>
      </select>
    </div>
    <div class="form-group">
      <label>5b) Literature Review Summary</label>
      <input type="file" name="literature_review_file">
    </div>
    <div class="form-group">
      <label>5c) Related Research</label>
      <textarea name="related_research" rows="4"><?= $editing ? htmlspecialchars($editData['related_research']) : '' ?></textarea>
    </div>

    <!-- Type of Research -->
    <div class="form-group">
      <label>6. Type of Research</label>
      <select name="research_type">
        <option value="Scientific" <?= $editing && $editData['research_type']=='Scientific' ? 'selected':'' ?>>Scientific Research (Fundamental)</option>
        <option value="Technology" <?= $editing && $editData['research_type']=='Technology' ? 'selected':'' ?>>Technology Development (Applied)</option>
        <option value="Product" <?= $editing && $editData['research_type']=='Product' ? 'selected':'' ?>>Product/Process Development</option>
      </select>
    </div>

    <?php
    $areas = [
      'beneficiaries'=>'7. Direct Customers/Beneficiaries of the Project<small>(Identify potential customers and their relevance)</small>',
      'outputs'=>'8. Outputs Expected from the Project<small>(Provide details)</small>',
      'transfer'=>'9. Technology Transfer/Diffusion Approach<small>(Describe transfer method and sustainability)</small>',
      'organizational_outcomes'=>'10. Organizational Outcomes Expected<small>(Provide details)</small>',
      'national_impacts'=>'11. National Impacts Expected<small>(Provide details)</small>',
      'external_org'=>'12. Outside Research Organizations/Industries Involved<small>(Identify and describe their role)</small>',
    ];
    foreach($areas as $k=>$label):
    ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <textarea name="<?= $k ?>" rows="3"><?= $editing ? htmlspecialchars($editData[$k]) : '' ?></textarea>
      </div>
    <?php endforeach; ?>

    <!-- Project Team -->
    <div class="form-group">
      <label>13. Project Team</label>
      <small>(Refer to Staff Cost Estimation Form in Appendix-A)</small>
      <div id="teamRows"></div>
      <button type="button" class="btn-add" id="addTeamBtn">Add Team Member</button>
      <input type="hidden" name="project_team" id="project_team" value='<?= $editing ? htmlspecialchars($editData['project_team']) : '[]' ?>'>
    </div>

    <!-- Research Methodology, Activities, Milestones -->
    <?php
    $areas2 = [
      'methodology'=>'14. Research Methodology<small>(Describe methodology, facilities, equipment)</small>',
      'activities'=>'15. Project Activities<small>(List main activities and timeline)</small>',
      'milestones'=>'16. Key Milestones<small>(List principal milestones with timeline)</small>'
    ];
    foreach($areas2 as $k=>$label):
    ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <textarea name="<?= $k ?>" rows="3"><?= $editing ? htmlspecialchars($editData[$k]) : '' ?></textarea>
      </div>
    <?php endforeach; ?>

    <!-- Duration -->
    <div class="flex-row">
      <div class="form-group">
        <label>17. Start Date</label>
        <input type="date" name="start_date" value="<?= $editing ? $editData['start_date'] : '' ?>">
      </div>
      <div class="form-group">
        <label>Duration (months)</label>
        <input type="number" name="duration_months" value="<?= $editing ? $editData['duration_months'] : '' ?>">
      </div>
    </div>

    <!-- Staff Costs -->
    <div class="form-group">
      <label>18. Additional Staff Costs</label>
      <div id="staffCosts"></div>
      <button type="button" class="btn-add" id="addStaffBtn">Add Staff Cost</button>
      <input type="hidden" name="staff_costs" id="staff_costs" value='<?= $editing ? htmlspecialchars($editData['staff_costs']) : '[]' ?>'>
    </div>

    <!-- Direct Expenses -->
    <div class="form-group">
      <label>19. Direct Project Expenses</label>
      <div id="directExpenses"></div>
      <button type="button" class="btn-add" id="addExpenseBtn">Add Expense</button>
      <input type="hidden" name="direct_expenses" id="direct_expenses" value='<?= $editing ? htmlspecialchars($editData['direct_expenses']) : '[]' ?>'>
    </div>

    <!-- Other Grants, Contractual Obligations, IP Ownership -->
    <?php
    $areas3 = [
      'other_grants'=>'20. Any Other Research Grant',
      'contractual_obligations'=>'21. Contractual Obligations under this Project',
      'ip_ownership'=>'Ownership of Intellectual Property Rights'
    ];
    foreach($areas3 as $k=>$label):
    ?>
      <div class="form-group">
        <label><?= $label ?></label>
        <textarea name="<?= $k ?>" rows="3"><?= $editing ? htmlspecialchars($editData[$k]) : '' ?></textarea>
      </div>
    <?php endforeach; ?>

    <!-- Appendices -->
     <div class="form-group">
  <!-- Appendix-B -->
  <a href="/files/Appendix-A-Sample.docx" 
     class="btn btn-sm btn-info mb-2" 
     download>
     Download Sample Appendix-A
  </a>
  <label>Upload Appendix-A</label>
  <input type="file" 
         name="appendix_a_file" 
         id="appendix_a_file" 
         class="form-control-file" 
         accept=".doc,.docx,.pdf">
</div>
<div class="form-group">
  <!-- Appendix-B -->
  <a href="/files/Appendix-B-Sample.docx" 
     class="btn btn-sm btn-info mb-2" 
     download>
     Download Sample Appendix-B
  </a>
  <label>Upload Appendix-B</label>
  <input type="file" 
         name="appendix_b_file" 
         id="appendix_b_file" 
         class="form-control-file" 
         accept=".doc,.docx,.pdf">
</div>

<div class="form-group">
  <!-- Appendix-C -->
  <a href="/files/Appendix-C-Sample.docx" 
     class="btn btn-sm btn-info mb-2" 
     download>
     Download Sample Appendix-C
  </a>
  <label>Upload Appendix-C</label>
  <input type="file" 
         name="appendix_c_file" 
         id="appendix_c_file" 
         class="form-control-file" 
         accept=".doc,.docx,.pdf">
</div>

    <!-- Acknowledgement -->
<div class="form-group">
  <label>
    <input type="checkbox" name="acknowledge" value="1" 
      <?= $editing && $editData['acknowledgement'] ? 'checked':'' ?>>
    I hereby acknowledge that all information is true and correct.
  </label>
</div>
    <div>
      <button type="submit" class="btn-submit"><?= $editing ? 'Update' : 'Submit' ?></button>
      <a href="/teacher/dashboard.php" class="btn-cancel">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  // --- Project Team ---
  const teamInput = $('#project_team');
  let team = JSON.parse(teamInput.val() || '[]');
  function renderTeam(){
    $('#teamRows').html('');
    team.forEach((t,i)=>{
      $('#teamRows').append(`<div style="display:flex;gap:8px;margin-bottom:8px;">
        <input style="flex:1;padding:6px;border-radius:4px;" placeholder="Name" value="${t.name||''}" data-i="${i}" data-key="name">
        <input style="flex:1;padding:6px;border-radius:4px;" placeholder="Organization" value="${t.org||''}" data-i="${i}" data-key="org">
        <input style="flex:0 0 80px;padding:6px;border-radius:4px;" type="number" placeholder="Man month" value="${t.mm||0}" data-i="${i}" data-key="mm">
        <button type="button" class="btn-remove" data-i="${i}">Remove</button>
      </div>`);
    });
    teamInput.val(JSON.stringify(team));
  }
  renderTeam();
  $('#addTeamBtn').click(()=>{ team.push({name:'',org:'',mm:0}); renderTeam(); });
  $(document).on('input','#teamRows [data-key]',function(){ const i=$(this).data('i'),k=$(this).data('key'); team[i][k]=$(this).val(); teamInput.val(JSON.stringify(team)); });
  $(document).on('click','.btn-remove',function(){ team.splice($(this).data('i'),1); renderTeam(); });

  // --- Staff Costs ---
  // --- Staff Costs ---
const staffInput = $('#staff_costs');
let staffCosts = JSON.parse(staffInput.val() || '[]');

function renderStaff() {
  $('#staffCosts').html('');
  staffCosts.forEach((s, i) => {
    $('#staffCosts').append(`
      <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center;">
        <select style="flex:1;padding:6px;border-radius:4px;" data-i="${i}" data-key="category">
          <option value="">Select Category</option>
          <option value="Salaried Paid" ${s.category === 'Salaried Paid' ? 'selected' : ''}>Salaried Paid</option>
          <option value="Temporary and Contract personnel" ${s.category === 'Temporary and Contract personnel' ? 'selected' : ''}>Temporary and Contract personnel</option>
        </select>
        <input style="flex:2;padding:6px;border-radius:4px;" placeholder="Year" value="${s.year || ''}" data-i="${i}" data-key="year">

        <input style="flex:1;padding:6px;border-radius:4px;" type="number" placeholder="Amount (৳)" value="${s.amount || 0}" data-i="${i}" data-key="amount">

        <button type="button" class="btn-remove" data-i="${i}">Remove</button>
      </div>
    `);
  });
  staffInput.val(JSON.stringify(staffCosts));
}

renderStaff();

$('#addStaffBtn').click(() => {
  staffCosts.push({
    category: '',
    year: '',
    amount: 0,
  });
  renderStaff();
});

$(document).on('input change', '#staffCosts [data-key]', function () {
  const i = $(this).data('i'),
        k = $(this).data('key');
  staffCosts[i][k] = $(this).val();
  staffInput.val(JSON.stringify(staffCosts));
});

$(document).on('click', '#staffCosts .btn-remove', function () {
  staffCosts.splice($(this).data('i'), 1);
  renderStaff();
});

  // --- Direct Expenses ---
const expenseInput = $('#direct_expenses');
let directExpenses = JSON.parse(expenseInput.val() || '[]');

function renderExpenses() {
  $('#directExpenses').html('');
  directExpenses.forEach((d, i) => {
    $('#directExpenses').append(`
      <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center;">
        <select style="flex:2;padding:6px;border-radius:4px;" data-i="${i}" data-key="category">
          <option value="">Select Expense Category</option>
          <option value="Travel and Transportation" ${d.category === 'Travel and Transportation' ? 'selected' : ''}>Travel and Transportation</option>
          <option value="Rentals" ${d.category === 'Rentals' ? 'selected' : ''}>Rentals</option>
          <option value="Research Materials and Supplies" ${d.category === 'Research Materials and Supplies' ? 'selected' : ''}>Research Materials and Supplies</option>
          <option value="Minor Modifications and Repairs" ${d.category === 'Minor Modifications and Repairs' ? 'selected' : ''}>Minor Modifications and Repairs</option>
          <option value="Special Services" ${d.category === 'Special Services' ? 'selected' : ''}>Special Services</option>
          <option value="Special Equipment and Accessories" ${d.category === 'Special Equipment and Accessories' ? 'selected' : ''}>Special Equipment and Accessories</option>
        </select>

        <input style="flex:2;padding:6px;border-radius:4px;" placeholder="year" value="${d.year || ''}" data-i="${i}" data-key="year">

        <input style="flex:1;padding:6px;border-radius:4px;" type="number" placeholder="Amount (৳)" value="${d.amount || 0}" data-i="${i}" data-key="amount">

        <button type="button" class="btn-remove" data-i="${i}">Remove</button>
      </div>
    `);
  });
  expenseInput.val(JSON.stringify(directExpenses));
}

renderExpenses();

$('#addExpenseBtn').click(() => {
  directExpenses.push({
    category: '',
    year: '',
    amount: 0
  });
  renderExpenses();
});

$(document).on('input change', '#directExpenses [data-key]', function () {
  const i = $(this).data('i'),
        k = $(this).data('key');
  directExpenses[i][k] = $(this).val();
  expenseInput.val(JSON.stringify(directExpenses));
});

$(document).on('click', '#directExpenses .btn-remove', function () {
  directExpenses.splice($(this).data('i'), 1);
  renderExpenses();
});

});
</script>
