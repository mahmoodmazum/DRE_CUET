<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
$call = $pdo->query("SELECT * FROM paper_calls WHERE deadline_date >= CURDATE() ORDER BY issue_date DESC LIMIT 1")->fetch();

if ($user['role'] !== 'teacher' || !$call) { http_response_code(403); exit('Access denied'); }

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

$editing  = false;
$editData = null;
if (!empty($_GET['edit'])) {
    $editing = true;
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user['sub'] ?? $user['id']]);
    $editData = $stmt->fetch();
    if (!$editData) { exit('Not found or access denied'); }

    // Fetch existing attachments
    $attStmt = $pdo->prepare("SELECT * FROM submission_attachments WHERE submission_id = ?");
    $attStmt->execute([$id]);
    $existingAtts = [];
    foreach ($attStmt->fetchAll() as $att) {
        $existingAtts[$att['type']] = $att;
    }
}

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_teacher.php';

function ev($v){ return htmlspecialchars($v ?? ''); }
function sel($a,$b){ return ($a==$b)?'selected':''; }
?>

<style>
.main-content{display:flex;flex-direction:column;}
.prop-form{max-width:860px;width:100%;}
.numbered-section{
  background:var(--c-surface);border-radius:var(--r-lg);
  box-shadow:var(--sh);padding:22px 24px;margin-bottom:16px;
  border-left:4px solid var(--c-primary);
}
.sec-head{display:flex;align-items:baseline;gap:10px;margin-bottom:14px;}
.sec-num{
  background:var(--c-primary);color:#fff;
  min-width:30px;height:30px;border-radius:50%;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:0.8rem;font-weight:700;flex-shrink:0;
}
.sec-title{font-size:1rem;font-weight:600;color:var(--c-text);}
.sec-sub{font-size:0.82rem;color:var(--c-text-muted);margin-top:2px;}

/* Team builder */
#teamInputRow{background:var(--c-primary-bg);border-radius:var(--r);padding:14px;margin-bottom:12px;display:none;}
.team-input-grid{display:grid;grid-template-columns:1fr 80px 1fr 100px;gap:10px;align-items:end;}
@media(max-width:600px){.team-input-grid{grid-template-columns:1fr;}}

/* Cost table rows */
.cost-entry{background:var(--c-bg);border-radius:var(--r);padding:10px 14px;margin-bottom:8px;}
.cost-entry-grid{display:grid;gap:8px;align-items:end;}
.staff-grid{grid-template-columns:2fr 1fr 1fr 1fr 32px;}
.expense-grid{grid-template-columns:2fr 1fr 1fr 1fr 32px;}
@media(max-width:640px){
  .staff-grid,.expense-grid{grid-template-columns:1fr;}
}
.yr-label{font-size:0.72rem;font-weight:600;color:var(--c-text-muted);text-align:center;margin-bottom:2px;}
.rm-btn{background:none;border:none;color:var(--c-danger);cursor:pointer;padding:4px;border-radius:4px;display:flex;align-items:center;justify-content:center;align-self:flex-end;height:36px;}
.rm-btn:hover{background:var(--c-danger-bg);}
.rm-btn .material-icons-round{font-size:18px;}

/* Totals bar */
.subtotal-bar{background:var(--c-primary-bg);border-radius:var(--r);padding:10px 14px;display:flex;justify-content:space-between;align-items:center;font-weight:600;color:var(--c-primary-dark);margin-top:8px;font-size:0.9rem;}

/* Section 20 grand total */
.grand-total-card{background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;border-radius:var(--r-lg);padding:22px 24px;margin-bottom:16px;}
.grand-total-card .gt-row{display:flex;justify-content:space-between;padding:5px 0;font-size:0.9rem;opacity:.9;}
.grand-total-card .gt-total{display:flex;justify-content:space-between;padding:12px 0 0;margin-top:8px;border-top:1px solid rgba(255,255,255,.3);font-size:1.2rem;font-weight:700;}

/* Appendix block */
.appendix-block{background:var(--c-primary-bg);border:1px dashed var(--c-primary);border-radius:var(--r-lg);padding:16px 20px;margin-bottom:16px;}
.appendix-block .app-title{font-weight:600;font-size:0.9rem;color:var(--c-primary-dark);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.appendix-block .app-title .material-icons-round{font-size:18px;}
.appendix-block .existing-file{font-size:0.82rem;color:var(--c-text-muted);margin-top:6px;}
.appendix-block .existing-file a{color:var(--c-primary);font-weight:500;}

/* Ensure staff cost category native select is always visible */
.cost-entry select{display:block;width:100%;padding:9px 12px;border:1.5px solid var(--c-border);border-radius:var(--r);font-family:'Inter',sans-serif;font-size:0.875rem;color:var(--c-text);background:var(--c-surface);}
.cost-entry select:focus{outline:none;border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(21,101,192,.1);}

/* Confirm name */
.confirm-block{background:var(--c-primary-bg);border-radius:var(--r-lg);padding:20px 24px;margin-bottom:16px;border:1px solid var(--c-primary);}
.confirm-block p{font-size:0.88rem;color:var(--c-text-muted);margin-bottom:10px;}

/* Sticky action bar */
.action-bar{background:var(--c-surface);border-top:1px solid var(--c-border);padding:14px 28px;display:flex;align-items:center;gap:12px;position:sticky;bottom:0;z-index:10;box-shadow:0 -2px 8px rgba(0,0,0,0.06);}
/* LR toggle */
.lr-toggle label{display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-size:0.875rem;font-weight:500;padding:6px 14px;border:1.5px solid var(--c-border);border-radius:20px;transition:all .15s;}
.lr-toggle label:has(input:checked){border-color:var(--c-primary);background:var(--c-primary-bg);color:var(--c-primary-dark);}
</style>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<div class="main-content">
  <div class="page-header">
    <h1><?= $editing ? 'Edit' : 'Submit' ?> Proposal</h1>
    <div class="breadcrumb">Dashboard / <?= $editing ? 'Edit' : 'Submit' ?> Proposal</div>
  </div>

  <div class="page-body">
  <form id="submissionForm" method="post" action="add_submission.php" enctype="multipart/form-data" class="prop-form">
    <input type="hidden" name="id" value="<?= $editing ? $editData['id'] : '' ?>">

    <!-- ── Header Info ── -->
    <div class="numbered-section">
      <div class="sec-head"><span class="sec-title">Proposal Information</span></div>
      <div class="form-row">
        <div class="form-group">
          <label class="field-label">Department <span style="color:var(--c-danger)">*</span></label>
          <select name="department_id" required>
            <option value="">Select Department</option>
            <?php foreach($departments as $d): ?>
              <option value="<?= $d['id'] ?>" <?= $editing && $editData['department_id']==$d['id'] ? 'selected' : '' ?>>
                <?= ev($d['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="flex:0 0 120px;">
          <label class="field-label">Year</label>
          <input type="number" name="year" value="<?= $editing ? ev($editData['year']) : '' ?>" placeholder="<?= date('Y') ?>">
        </div>
        <div class="form-group" style="flex:0 0 140px;">
          <label class="field-label">Phase</label>
          <input type="text" name="phase" value="<?= $editing ? ev($editData['phase']) : '' ?>" placeholder="e.g. Phase 1">
        </div>
      </div>
    </div>

    <!-- ── 1. Project Title ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">1</span>
        <div><div class="sec-title">Project Title</div>
        <div class="sec-sub">(Please indicate the title of project; the title should be short and concise)</div></div>
      </div>
      <input type="text" name="project_title" value="<?= $editing ? ev($editData['project_title']) : '' ?>" required placeholder="Enter project title">
    </div>

    <!-- ── 2a. PI ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">2a</span>
        <div><div class="sec-title">Principal Investigator (PI)</div>
        <div class="sec-sub">Please indicate the name and the department/organization</div></div>
      </div>
      <input type="text" name="pi" value="<?= $editing ? ev($editData['pi']) : '' ?>" placeholder="Full name, Department/Organization, Designation">
    </div>

    <!-- ── 2b. Co-PI ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">2b</span>
        <div><div class="sec-title">Co-Principal Investigator (Co-PI)</div>
        <div class="sec-sub">Please indicate the name and the department/organization</div></div>
      </div>
      <input type="text" name="co_pi" value="<?= $editing ? ev($editData['co_pi']) : '' ?>" placeholder="Full name, Department/Organization, Designation">
    </div>

    <!-- ── 3. Keywords ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">3</span>
        <div><div class="sec-title">Key Words</div>
        <div class="sec-sub">Please provide a maximum of 5 key words that describe the research of the project</div></div>
      </div>
      <input type="text" name="keywords" value="<?= $editing ? ev($editData['keywords']) : '' ?>" placeholder="keyword1, keyword2, keyword3...">
    </div>

    <!-- ── 4a. Specific Objectives ── -->
<div class="numbered-section">
  <div class="sec-head">
    <span class="sec-num">4a</span>
    <div>
      <div class="sec-title">Specific Objectives of the Project</div>
      <div class="sec-sub">
        Please describe the measurable objectives of the project and define the expected results.
        Use results-oriented wording with verbs such as “to define ...”, “to determine…”, “to identify …”
      </div>
    </div>
  </div>
  <textarea name="specific_objectives" id="sn_specific_objectives" rows="4"
    placeholder="List specific, measurable objectives..."><?= $editing ? ($editData['specific_objectives'] ?? '') : '' ?></textarea>
</div>

<!-- ── 4b. General Objective ── -->
<div class="numbered-section">
  <div class="sec-head">
    <span class="sec-num">4b</span>
    <div>
      <div class="sec-title">General Objective of the Project</div>
      <div class="sec-sub">
        Please describe the general/overall objective of the project
      </div>
    </div>
  </div>
  <textarea name="general_objectives" id="sn_general_objectives" rows="4"
    placeholder="Describe the general objective..."><?= $editing ? ($editData['general_objectives'] ?? '') : '' ?></textarea>
</div>
    <!-- ── 5a. Project Status ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">5a</span>
        <div><div class="sec-title">Project Status</div>
        <div class="sec-sub">Please indicate if the project is new, modified or extended.</div></div>
      </div>
      <select name="project_status">
        <option value="New" <?= $editing ? sel($editData['project_status'],'New') : 'selected' ?>>New</option>
        <option value="Modification" <?= $editing ? sel($editData['project_status'],'Modification') : '' ?>>Modification</option>
        <option value="Extension" <?= $editing ? sel($editData['project_status'],'Extension') : '' ?>>Extension</option>
      </select>
    </div>

    <!-- ── 5b. Project Summary ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">5b</span>
        <div><div class="sec-title">Project Summary</div>

        <div class="sec-sub">Give a summary of your literature review to indicate the originality of the proposed research-Maximum 300 words</div></div>
      </div>
      <textarea name="literature_review_text" rows="6" placeholder="Write your project summary here (max 300 words)..."><?= $editing ? ev($editData['literature_review_text']) : '' ?></textarea>
    </div>

    <!-- ── 5c. Literature Review and Related Research ── -->
    <?php
      $lr_has_file = $editing && isset($existingAtts['l_rev']);
      $lr_has_text = $editing && !empty($editData['literature_review']);
      $lr_initial  = ($lr_has_text && !$lr_has_file) ? 'text' : 'file';
    ?>
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">5c</span>
        <div><div class="sec-title">Literature Review and Related Research</div>
        <div class="sec-sub">Provide either a file upload or text input — not both</div></div>
      </div>
      <!-- Toggle -->
      <div class="lr-toggle" style="display:flex;gap:10px;margin-bottom:14px;">
        <label>
          <input type="radio" name="lr_input_type" value="file" id="lr_type_file" <?= $lr_initial==='file'?'checked':'' ?>>
          <span class="material-icons-round" style="font-size:16px;">upload_file</span> Upload File
        </label>
        <label>
          <input type="radio" name="lr_input_type" value="text" id="lr_type_text" <?= $lr_initial==='text'?'checked':'' ?>>
          <span class="material-icons-round" style="font-size:16px;">edit_note</span> Text Input
        </label>
      </div>
      <!-- File zone -->
      <div id="lr_file_zone" <?= $lr_initial==='text'?'style="display:none"':'' ?>>
        <div class="file-zone">
          <span class="material-icons-round" style="font-size:32px;color:var(--c-primary);opacity:.6;">upload_file</span>
          <div style="font-size:0.82rem;color:var(--c-text-muted);margin:6px 0;">Choose file to upload (PDF, DOC, DOCX — max 10MB)</div>
          <input type="file" name="related_research_file" id="lr_file_input" accept=".pdf,.doc,.docx">
        </div>
        <?php if ($lr_has_file): ?>
          <div class="appendix-block existing-file" style="margin-top:8px;">
            <strong>Current file:</strong>
            <a href="/DRE/<?= ev($existingAtts['l_rev']['file_path']) ?>" target="_blank">
              <?= ev($existingAtts['l_rev']['original_name']) ?>
            </a> — upload new to replace
          </div>
        <?php endif; ?>
      </div>
      <!-- Text zone -->
      <div id="lr_text_zone" <?= $lr_initial==='file'?'style="display:none"':'' ?>>
        <textarea name="literature_review" id="literature_review" rows="6"
          placeholder="Write your literature review and related research here..."><?= $lr_has_text ? ($editData['literature_review'] ?? '') : '' ?></textarea>
      </div>
    </div>

    <!-- ── 6. Type of Research ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">6</span>
        <span class="sec-title">Type of Research</span>
      </div>
      <select name="research_type">
        <option value="Scientific" <?= $editing ? sel($editData['research_type'],'Scientific') : '' ?>>Scientific Research (Fundamental)</option>
        <option value="Technology" <?= $editing ? sel($editData['research_type'],'Technology') : '' ?>>Technology Development (Applied)</option>
        <option value="Product"    <?= $editing ? sel($editData['research_type'],'Product')    : '' ?>>Product / Process Development</option>
      </select>
    </div>

    <!-- ── 7–12 textarea fields ── -->
    <?php
    $areas = [
      7  => ['beneficiaries',        'Direct Customers / Beneficiaries of the Project',        'please identify clearly the potential customers/beneficiaries of the research results and provide details of their relevance, e.g. size economic contribution, etc.'],
      8  => ['outputs',              'Expected Outcomes from the Project',                      'Provide details on expected outputs and results'],
      9  => ['transfer',             'Technology Transfer / Diffusion Approach',                'please describe how the outputs of the project will be transferred to the direct beneficiaries/customers. Please also state if the project outputs are sustainable, i.e. if they can be utilized without further external assistance)'],
      10 => ['organizational_outcomes','Organizational Expected Outcomes',                       'Provide details'],
      11 => ['national_impacts',     'National Impacts Expected',                               'Provide details'],
      12 => ['external_org',         'Outside Research Organizations / Industries Involved',    'please identify all research organizations collaborating in the project and describe their role/contribution to the project'],
    ];
    $sn_sections = [7, 8]; // use Summernote for these
    foreach($areas as $num => [$key, $title, $hint]): ?>
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num"><?= $num ?></span>
        <div><div class="sec-title"><?= $title ?></div>
        <div class="sec-sub"><?= $hint ?></div></div>
      </div>
      <?php if (in_array($num, $sn_sections)): ?>
      <textarea name="<?= $key ?>" id="sn_<?= $key ?>" rows="4"
        placeholder="<?= $title ?>..."><?= $editing ? ($editData[$key] ?? '') : '' ?></textarea>
      <?php else: ?>
      <textarea name="<?= $key ?>" rows="3"
        placeholder="<?= $title ?>..."><?= $editing ? ev($editData[$key]) : '' ?></textarea>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- ── 13. Project Team ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">13</span>
        <div><div class="sec-title">Project Team</div>
        <div class="sec-sub">Add all team members. Man-Month totals will be calculated automatically.</div></div>
      </div>

      <!-- Add member form -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap;">
        <select id="teamTypeSelect" style="flex:1;min-width:220px;">
          <option value="">-- Select Member Type to Add --</option>
          <option value="Principal Investigator (PI)">Principal Investigator (PI)</option>
          <option value="Co-Principal Investigator (Co-PI)">Co-Principal Investigator (Co-PI)</option>
          <option value="Researchers">Researchers</option>
          <option value="Support Staff">Support Staff</option>
          <option value="Contract Staff">Contract Staff</option>
        </select>
        <button type="button" class="btn btn-outline btn-sm" id="showTeamInputBtn">
          <span class="material-icons-round">add</span> Add Member
        </button>
      </div>

      <div id="teamInputRow">
        <div class="team-input-grid">
          <div class="form-group mb-0">
            <label class="field-label">Name</label>
            <input type="text" id="ti_name" placeholder="Full name">
          </div>
          <div class="form-group mb-0">
            <label class="field-label">Count</label>
            <input type="number" id="ti_count" value="1" min="1">
          </div>
          <div class="form-group mb-0">
            <label class="field-label">Organization</label>
            <input type="text" id="ti_org" placeholder="Department / Institution">
          </div>
          <div class="form-group mb-0">
            <label class="field-label">Man-Month</label>
            <input type="number" id="ti_mm" placeholder="0" min="0" step="0.5">
          </div>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;">
          <button type="button" class="btn btn-primary btn-sm" id="addTeamMemberBtn">
            <span class="material-icons-round">check</span> Add to Team
          </button>
          <button type="button" class="btn btn-secondary btn-sm" id="cancelTeamBtn">Cancel</button>
        </div>
      </div>

      <!-- Team table -->
      <div id="teamTableWrap" style="display:none;">
        <table class="team-table" id="teamTable">
          <thead>
            <tr>
              <th>Type</th><th>Name</th><th>Count</th><th>Organization</th><th>Man-Month</th><th></th>
            </tr>
          </thead>
          <tbody id="teamTbody"></tbody>
          <tfoot>
            <tr>
              <td colspan="4" style="font-weight:600;">Total Man-Month</td>
              <td id="teamTotalMM" style="font-weight:700;color:var(--c-primary-dark);">0</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <input type="hidden" name="project_team" id="project_team" value='<?= $editing ? ev($editData['project_team']) : '[]' ?>'>
    </div>

    <!-- ── Appendix-A ── -->
    <div class="appendix-block">
      <div class="app-title">
        <span class="material-icons-round">attach_file</span>
        Appendix-A — Staff Cost Estimation Form
      </div>
      <a href="/DRE/files/Appendix-A-Sample.docx" download class="file-download-link">
        <span class="material-icons-round">download</span> Download Sample Appendix-A
      </a>
      <div class="file-zone">
        <input type="file" name="appendix_a_file" accept=".pdf,.doc,.docx">
      </div>
      <?php if ($editing && isset($existingAtts['appendA'])): ?>
        <div class="existing-file mt-2">
          Current: <a href="/DRE/<?= ev($existingAtts['appendA']['file_path']) ?>" target="_blank"><?= ev($existingAtts['appendA']['original_name']) ?></a>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── 14. Research Methodology ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">14</span>
        <div><div class="sec-title">Research Methodology</div>
        <div class="sec-sub">please describe the research methodology to be followed. Identify specialized equipment, facilities and infrastructure which are required for the project and indicate which are new. Use separate sheets if necessary (PDF, DOC, DOCX)</div></div>
      </div>
      <div class="file-zone">
        <span class="material-icons-round" style="font-size:32px;color:var(--c-primary);opacity:.6;">description</span>
        <div style="font-size:0.82rem;color:var(--c-text-muted);margin:6px 0;">Choose methodology document</div>
        <input type="file" name="methodology_file" accept=".pdf,.doc,.docx">
      </div>
      <?php if ($editing && isset($existingAtts['methodology'])): ?>
        <div class="appendix-block existing-file" style="margin-top:8px;">
          Current: <a href="/DRE/<?= ev($existingAtts['methodology']['file_path']) ?>" target="_blank"><?= ev($existingAtts['methodology']['original_name']) ?></a>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── 15. Project Activities ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">15</span>
        <div><div class="sec-title">Project Activities</div>
        <div class="sec-sub">please list and describe the main project activities, including those associated with the transfer of the research results to customers/beneficiaries. The timing and duration of these activities are to be shown in the Gantt chart as attached in Appendix-B</div></div>
      </div>
      <textarea name="activities" rows="4" placeholder="List the main project activities..."><?= $editing ? ev($editData['activities']) : '' ?></textarea>
    </div>

    <!-- ── Appendix-B (after Section 15) ── -->
    <div class="appendix-block">
      <div class="app-title">
        <span class="material-icons-round">attach_file</span>
        Appendix-B — Milestones / Work Plan
      </div>
      <a href="/DRE/files/Appendix-B-Sample.docx" download class="file-download-link">
        <span class="material-icons-round">download</span> Download Sample Appendix-B
      </a>
      <div class="file-zone">
        <input type="file" name="appendix_b_file" accept=".pdf,.doc,.docx">
      </div>
      <?php if ($editing && isset($existingAtts['appendB'])): ?>
        <div class="existing-file mt-2">
          Current: <a href="/DRE/<?= ev($existingAtts['appendB']['file_path']) ?>" target="_blank"><?= ev($existingAtts['appendB']['original_name']) ?></a>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── 16. Key Milestones ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">16</span>
        <div><div class="sec-title">Key Milestones</div>
        <div class="sec-sub">please list and describe the principle milestones of the project. Timing of milestones is to be shown in the Gantt chart as attached in Appendix B. A key milestone is reached when a significant phase in the project is concluded. E.g. completion of test, review, commissioning of equipment, etc.</div></div>
      </div>
      <textarea name="milestones" rows="4" placeholder="Milestone 1: Month X — Description..."><?= $editing ? ev($editData['milestones']) : '' ?></textarea>
    </div>

    <!-- ── 17. Duration ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">17</span>
        <span class="sec-title">Project Duration</span>
        <div><div class="sec-sub">State the elapsed time, in months, to complete this project</div></div>
      </div>
      <div class="form-group" style="max-width:300px;">
        <label class="field-label">Duration (Months)</label>
        <select name="duration_months" id="durationSelect" class="no-select2">
          <option value="">-- Select Duration --</option>
          <option value="12" <?= $editing && ($editData['duration_months']??0)==12 ? 'selected' : '' ?>>12 Months (Year 1 only)</option>
          <option value="24" <?= $editing && ($editData['duration_months']??0)==24 ? 'selected' : '' ?>>24 Months (Year 1 &amp; Year 2)</option>
          <option value="36" <?= $editing && ($editData['duration_months']??0)==36 ? 'selected' : '' ?>>36 Months (Year 1, Year 2 &amp; Year 3)</option>
        </select>
      </div>
    </div>

    <!-- ── 18. Additional Staff Costs ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">18</span>
        <div><div class="sec-title">Additional Staff Costs</div>
        <div class="sec-sub">please include the yearly staff costs of the project. Present staff costs are not included. Give details of calculation based on Appendix-A</div></div>
      </div>

      <div id="staffHeader" style="display:grid;gap:8px;padding:0 0 6px;font-size:0.75rem;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.05em;"></div>

      <div id="staffCostRows"></div>

      <button type="button" class="btn btn-outline btn-sm mt-2" id="addStaffBtn">
        <span class="material-icons-round">add</span> Add Staff Category
      </button>

      <div class="subtotal-bar mt-3">
        <span>Sub-Total Staff Costs</span>
        <span id="staffSubtotal">৳ 0.00</span>
      </div>
      <input type="hidden" name="staff_costs" id="staff_costs"
             value='<?= $editing ? ev($editData['staff_costs']) : '[]' ?>'>
    </div>

    <!-- ── 19. Direct Project Expenses ── -->
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num">19</span>
        <div><div class="sec-title">Direct Project Expenses</div>
        <div class="sec-sub">please include the yearly direct expenses of the project. For computation. Use the Direct Expenses Estimation from as attached in Appendix-C</div></div>
      </div>

      <div id="expenseHeader" style="display:grid;gap:8px;padding:0 0 6px;font-size:0.75rem;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.05em;"></div>

      <div id="directExpenseRows"></div>

      <button type="button" class="btn btn-outline btn-sm mt-2" id="addExpenseBtn">
        <span class="material-icons-round">add</span> Add Expense
      </button>

      <div class="subtotal-bar mt-3">
        <span>Sub-Total Direct Expenses</span>
        <span id="expenseSubtotal">৳ 0.00</span>
      </div>
      <input type="hidden" name="direct_expenses" id="direct_expenses"
             value='<?= $editing ? ev($editData['direct_expenses']) : '[]' ?>'>
    </div>

    <!-- ── 20. Total Cost ── -->
    <div class="grand-total-card">
      <div style="font-size:0.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.75;margin-bottom:12px;">20. Total Cost</div>
      <div id="gt_year_rows"></div>
      <div class="gt-row" style="border-top:1px solid rgba(255,255,255,.2);padding-top:8px;margin-top:4px;">
        <span>Sub-Total Staff Costs (Section 18)</span>
        <span id="gt_staff">৳ 0.00</span>
      </div>
      <div class="gt-row">
        <span>Sub-Total Direct Expenses (Section 19)</span>
        <span id="gt_expense">৳ 0.00</span>
      </div>
      <div class="gt-total">
        <span>Grand Total</span>
        <span id="gt_total">৳ 0.00</span>
      </div>
    </div>
<!-- ── Appendix-C ── -->
    <div class="appendix-block">
      <div class="app-title">
        <span class="material-icons-round">attach_file</span>
        Appendix-C — Supporting Documents
      </div>
      <a href="/DRE/files/Appendix-C-Sample.docx" download class="file-download-link">
        <span class="material-icons-round">download</span> Download Sample Appendix-C
      </a>
      <div class="file-zone">
        <input type="file" name="appendix_c_file" accept=".pdf,.doc,.docx">
      </div>
      <?php if ($editing && isset($existingAtts['appendC'])): ?>
        <div class="existing-file mt-2">
          Current: <a href="/DRE/<?= ev($existingAtts['appendC']['file_path']) ?>" target="_blank"><?= ev($existingAtts['appendC']['original_name']) ?></a>
        </div>
      <?php endif; ?>
    </div>
    <!-- ── 21–23 other text fields ── -->
    <?php
    $areas3 = [
      21 => ['other_grants',            'Any Other Research Grant','Please mention if so'],
      22 => ['contractual_obligations', 'Contractual Obligations under this Project','please indicate any contractual obligation with third parties that will be entered in for this project'],
      23 => ['ip_ownership',            'Ownership of Intellectual Property Rights','Please indicate the organization(s) that will own the intellectual property rights that may from this project'],
    ];
    foreach($areas3 as $num => [$key, $title, $subTitle]): ?>
    <div class="numbered-section">
      <div class="sec-head">
        <span class="sec-num"><?= $num ?></span>
        <span class="sec-title"><?= $title ?></span>
      </div>
      <div class="sec-sub"><?= $subTitle ?></div>
      <textarea name="<?= $key ?>" rows="3" placeholder="<?= $title ?>..."><?= $editing ? ev($editData[$key]) : '' ?></textarea>
      
    </div>
    <?php endforeach; ?>

    

    <!-- ── Acknowledgement / Full Name Confirm ── -->
    <div class="confirm-block">
      <div style="font-weight:600;font-size:1rem;color:var(--c-primary-dark);margin-bottom:8px;display:flex;align-items:center;gap:8px;">
        <span class="material-icons-round">verified_user</span>
        Declaration &amp; Confirmation
      </div>
      <p>I hereby declare that all the information provided in this proposal is true, correct, and complete to the best of my knowledge. I understand that any false or misleading information may result in disqualification.</p>
      <label class="field-label">Enter your full name to confirm <span style="color:var(--c-danger)">*</span></label>
      <input type="text" name="confirm_name" id="confirm_name"
             value="<?= $editing ? ev($editData['confirm_name'] ?? '') : '' ?>"
             placeholder="Type your full name exactly" required
             style="max-width:400px;">
      <input type="hidden" name="acknowledge" value="1">
    </div>

    <!-- ── Action Bar ── -->
    <div class="action-bar">
      <button type="submit" class="btn btn-primary btn-lg">
        <span class="material-icons-round">send</span>
        <?= $editing ? 'Update Proposal' : 'Submit Proposal' ?>
      </button>
      <a href="/DRE/teacher/dashboard.php" class="btn btn-secondary">Cancel</a>
    </div>

  </form>
  </div><!-- .page-body -->
</div><!-- .main-content -->

<script>
$(function(){

  // ── Duration & dynamic year columns ───────────────────────────
  let selectedDuration = parseInt($('#durationSelect').val()) || 0;

  function getYearCols(){
    if(selectedDuration===12) return 1;
    if(selectedDuration===24) return 2;
    return 3; // 36 or unset => show all
  }

  function updateBudgetHeaders(){
    const yr = getYearCols();
    const colBase = '2fr';
    let sCols = colBase, sHdr = '<span>Staff Category</span>';
    let eCols = colBase, eHdr = '<span>Expense Description</span>';
    for(let y=1; y<=yr; y++){
      sCols += ' 1fr'; sHdr += `<span style="text-align:center;">Year ${y} (৳)</span>`;
      eCols += ' 1fr'; eHdr += `<span style="text-align:center;">Year ${y} (৳)</span>`;
    }
    sCols += ' 32px'; sHdr += '<span></span>';
    eCols += ' 32px'; eHdr += '<span></span>';
    $('#staffHeader').css('grid-template-columns', sCols).html(sHdr);
    $('#expenseHeader').css('grid-template-columns', eCols).html(eHdr);
  }

  $('#durationSelect').on('change', function(){
    selectedDuration = parseInt($(this).val()) || 0;
    updateBudgetHeaders();
    renderStaff();
    renderExpenses();
    updateGrandTotal();
  });

  // ── LR toggle (5c) ────────────────────────────────────────────
  $('input[name="lr_input_type"]').on('change', function(){
    if($(this).val()==='file'){
      $('#lr_file_zone').show(); $('#lr_text_zone').hide();
    } else {
      $('#lr_file_zone').hide(); $('#lr_text_zone').show();
    }
  });

  // ── Project Team ──────────────────────────────────────────────
  let team = [];
  try { team = JSON.parse($('#project_team').val()) || []; } catch(e){ team = []; }

  const piTypes = ['Principal Investigator (PI)', 'Co-Principal Investigator (Co-PI)'];

  $('#teamTypeSelect').on('change', function(){
    const type = $(this).val();
    if (!type) return;
    const isPI = piTypes.includes(type);
    $('#ti_count').val(1).prop('readonly', isPI);
    $('#teamInputRow').slideDown(180);
    $('#ti_name').focus();
  });

  $('#showTeamInputBtn').on('click', function(){
    const type = $('#teamTypeSelect').val();
    if (!type){ alert('Please select a member type first.'); return; }
    $('#teamInputRow').slideDown(180);
    $('#ti_name').focus();
  });

  $('#cancelTeamBtn').on('click', function(){
    $('#teamInputRow').slideUp(180);
    clearTeamInput();
  });

  $('#addTeamMemberBtn').on('click', function(){
    const type  = $('#teamTypeSelect').val();
    const name  = $('#ti_name').val().trim();
    const count = parseInt($('#ti_count').val()) || 1;
    const org   = $('#ti_org').val().trim();
    const mm    = parseFloat($('#ti_mm').val()) || 0;
    if (!type){ alert('Select a member type.'); return; }
    if (!name){ alert('Enter the name.'); return; }
    team.push({ type, name, count, org, mm });
    renderTeam();
    clearTeamInput();
    $('#teamInputRow').slideUp(180);
    $('#teamTypeSelect').val('').trigger('change');
  });

  function clearTeamInput(){
    $('#ti_name,#ti_org,#ti_mm').val('');
    $('#ti_count').val(1).prop('readonly',false);
  }

  function renderTeam(){
    const tbody = $('#teamTbody').empty();
    let totalMM = 0;
    team.forEach((m, i) => {
      totalMM += parseFloat(m.mm) || 0;
      tbody.append(`<tr>
        <td>${escHtml(m.type)}</td>
        <td>${escHtml(m.name)}</td>
        <td>${m.count}</td>
        <td>${escHtml(m.org)}</td>
        <td>${m.mm}</td>
        <td>
          <button type="button" class="rm-btn" data-i="${i}" title="Remove">
            <span class="material-icons-round">delete_outline</span>
          </button>
        </td>
      </tr>`);
    });
    $('#teamTotalMM').text(totalMM.toFixed(1));
    $('#teamTableWrap').toggle(team.length > 0);
    $('#project_team').val(JSON.stringify(team));
  }

  $(document).on('click','#teamTbody .rm-btn', function(){
    team.splice($(this).data('i'), 1);
    renderTeam();
  });

  renderTeam();
// ── Direct Expenses ────────────────────────────────────────────
  let directExpenses = [];
  try { directExpenses = JSON.parse($('#direct_expenses').val()) || []; } catch(e){ directExpenses = []; }
  // ── Staff Costs ────────────────────────────────────────────────
  let staffCosts = [];
  try { staffCosts = JSON.parse($('#staff_costs').val()) || []; } catch(e){ staffCosts = []; }

  const staffCategories = [
    'Salaried Paid',
    'Temporary and Contract Personnel'
  ];

  function renderStaff(){
    const yr = getYearCols();
    const colTpl = yr===1 ? '2fr 1fr 32px' : yr===2 ? '2fr 1fr 1fr 32px' : '2fr 1fr 1fr 1fr 32px';
    const wrap = $('#staffCostRows').empty();
    staffCosts.forEach((s, i) => {
      const opts = staffCategories.map(c =>
        `<option value="${c}" ${s.category===c?'selected':''}>${c}</option>`
      ).join('');
      let rowHtml = `
        <div class="cost-entry">
          <div class="cost-entry-grid" style="grid-template-columns:${colTpl};">
            <div>
              <select class="no-select2" data-i="${i}" data-k="category" style="display:block;">
                <option value="">Select Category</option>${opts}
              </select>
            </div>
            <div>
              <div class="yr-label">Year 1</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${s.year1||0}" data-i="${i}" data-k="year1">
            </div>`;
      if(yr>=2) rowHtml += `
            <div>
              <div class="yr-label">Year 2</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${s.year2||0}" data-i="${i}" data-k="year2">
            </div>`;
      if(yr>=3) rowHtml += `
            <div>
              <div class="yr-label">Year 3</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${s.year3||0}" data-i="${i}" data-k="year3">
            </div>`;
      const rt = (parseFloat(s.year1)||0)+(yr>=2?parseFloat(s.year2)||0:0)+(yr>=3?parseFloat(s.year3)||0:0);
      rowHtml += `
            <button type="button" class="rm-btn staff-rm" data-i="${i}" title="Remove">
              <span class="material-icons-round">delete_outline</span>
            </button>
          </div>
          <div style="text-align:right;font-size:0.78rem;color:var(--c-text-muted);margin-top:4px;">
            Row total: ৳ <span class="row-total">${rt.toLocaleString()}</span>
          </div>
        </div>`;
      wrap.append(rowHtml);
    });
    updateStaffTotals();
  }

  function updateStaffTotals(){
    const yr = getYearCols();
    let total = 0;
    staffCosts.forEach(s => {
      total += (parseFloat(s.year1)||0)
             + (yr>=2 ? parseFloat(s.year2)||0 : 0)
             + (yr>=3 ? parseFloat(s.year3)||0 : 0);
    });
    const fmt = '৳ ' + total.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
    $('#staffSubtotal').text(fmt);
    $('#gt_staff').text(fmt);
    $('#staff_costs').val(JSON.stringify(staffCosts));
    updateGrandTotal();
  }

  $(document).on('input change','#staffCostRows [data-i]', function(){
    const i = $(this).data('i'), k = $(this).data('k');
    staffCosts[i][k] = $(this).val();
    const r = staffCosts[i]; const yr = getYearCols();
    const rt = (parseFloat(r.year1)||0)+(yr>=2?parseFloat(r.year2)||0:0)+(yr>=3?parseFloat(r.year3)||0:0);
    $(this).closest('.cost-entry').find('.row-total').text(rt.toLocaleString());
    updateStaffTotals();
  });

  $(document).on('click','.staff-rm', function(){
    staffCosts.splice($(this).data('i'),1);
    renderStaff();
  });

  $('#addStaffBtn').on('click', function(){
    staffCosts.push({category:'',year1:0,year2:0,year3:0});
    renderStaff();
  });

  renderStaff();

  

  function renderExpenses(){
    const yr = getYearCols();
    const colTpl = yr===1 ? '2fr 1fr 32px' : yr===2 ? '2fr 1fr 1fr 32px' : '2fr 1fr 1fr 1fr 32px';
    const wrap = $('#directExpenseRows').empty();
    directExpenses.forEach((d, i) => {
      let rowHtml = `
        <div class="cost-entry">
          <div class="cost-entry-grid" style="grid-template-columns:${colTpl};">
            <div>
              <input type="text" placeholder="Expense description (e.g. Travel, Equipment...)"
                     value="${escHtml(d.description||d.category||'')}" data-i="${i}" data-k="description">
            </div>
            <div>
              <div class="yr-label">Year 1</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${d.year1||0}" data-i="${i}" data-k="year1">
            </div>`;
      if(yr>=2) rowHtml += `
            <div>
              <div class="yr-label">Year 2</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${d.year2||0}" data-i="${i}" data-k="year2">
            </div>`;
      if(yr>=3) rowHtml += `
            <div>
              <div class="yr-label">Year 3</div>
              <input type="number" min="0" step="1" placeholder="0"
                     value="${d.year3||0}" data-i="${i}" data-k="year3">
            </div>`;
      const rt = (parseFloat(d.year1)||0)+(yr>=2?parseFloat(d.year2)||0:0)+(yr>=3?parseFloat(d.year3)||0:0);
      rowHtml += `
            <button type="button" class="rm-btn expense-rm" data-i="${i}" title="Remove">
              <span class="material-icons-round">delete_outline</span>
            </button>
          </div>
          <div style="text-align:right;font-size:0.78rem;color:var(--c-text-muted);margin-top:4px;">
            Row total: ৳ <span class="row-total">${rt.toLocaleString()}</span>
          </div>
        </div>`;
      wrap.append(rowHtml);
    });
    updateExpenseTotals();
  }

  function updateExpenseTotals(){
    const yr = getYearCols();
    let total = 0;
    directExpenses.forEach(d => {
      total += (parseFloat(d.year1)||0)
             + (yr>=2 ? parseFloat(d.year2)||0 : 0)
             + (yr>=3 ? parseFloat(d.year3)||0 : 0);
    });
    const fmt = '৳ ' + total.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
    $('#expenseSubtotal').text(fmt);
    $('#gt_expense').text(fmt);
    $('#direct_expenses').val(JSON.stringify(directExpenses));
    updateGrandTotal();
  }

  $(document).on('input change','#directExpenseRows [data-i]', function(){
    const i = $(this).data('i'), k = $(this).data('k');
    directExpenses[i][k] = $(this).val();
    const r = directExpenses[i]; const yr = getYearCols();
    const rt = (parseFloat(r.year1)||0)+(yr>=2?parseFloat(r.year2)||0:0)+(yr>=3?parseFloat(r.year3)||0:0);
    $(this).closest('.cost-entry').find('.row-total').text(rt.toLocaleString());
    updateExpenseTotals();
  });

  $(document).on('click','.expense-rm', function(){
    directExpenses.splice($(this).data('i'),1);
    renderExpenses();
  });

  $('#addExpenseBtn').on('click', function(){
    directExpenses.push({description:'',year1:0,year2:0,year3:0});
    renderExpenses();
  });

  renderExpenses();

  // ── Grand Total ────────────────────────────────────────────────
  function fmt2(v){ return '৳ '+v.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2}); }

  function updateGrandTotal(){
    const yr    = getYearCols();
    const staff = parseStaffTotal();
    const exp   = parseExpTotal();
    const total = staff + exp;

    // Year-wise breakdown rows
    let yHtml = '';
    for(let y=1; y<=yr; y++){
      const yrS = staffCosts.reduce((s,c)=>(s+(parseFloat(c['year'+y])||0)),0);
      const yrE = directExpenses.reduce((s,d)=>(s+(parseFloat(d['year'+y])||0)),0);
      yHtml += `<div class="gt-row" style="border-bottom:1px solid rgba(255,255,255,.12);padding-bottom:4px;margin-bottom:4px;">
        <span>Year ${y} Total</span><span>${fmt2(yrS+yrE)}</span>
      </div>`;
    }
    $('#gt_year_rows').html(yHtml);
    $('#gt_staff').text(fmt2(staff));
    $('#gt_expense').text(fmt2(exp));
    $('#gt_total').text(fmt2(total));
  }
  function parseStaffTotal(){
    const yr = getYearCols();
    return staffCosts.reduce((s,c)=>(s+(parseFloat(c.year1)||0)+(yr>=2?parseFloat(c.year2)||0:0)+(yr>=3?parseFloat(c.year3)||0:0)),0);
  }
  function parseExpTotal(){
    const yr = getYearCols();
    return directExpenses.reduce((s,d)=>(s+(parseFloat(d.year1)||0)+(yr>=2?parseFloat(d.year2)||0:0)+(yr>=3?parseFloat(d.year3)||0:0)),0);
  }
  updateGrandTotal();

  // ── Utility ────────────────────────────────────────────────────
  function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  // ── Form validation + Summernote sync ─────────────────────────
  $('#submissionForm').on('submit', function(e){
    // Sync all Summernote fields to their textareas
    ['sn_specific_objectives','sn_general_objectives','sn_beneficiaries','sn_outputs','literature_review'].forEach(function(id){
      const $el = $('#'+id);
      if($el.length && $el.data('summernote')) $el.val($el.summernote('code'));
    });

    const name = $('#confirm_name').val().trim();
    if (!name){ e.preventDefault(); alert('Please enter your full name to confirm the declaration.'); $('#confirm_name').focus(); return false; }

    // Validate LR: if text mode selected, must not be empty
    if($('#lr_type_text').is(':checked')){
      const lrVal = ($('#literature_review').data('summernote')
        ? $('#literature_review').summernote('isEmpty')
        : $('#literature_review').val().trim()==='');
      if(lrVal){ e.preventDefault(); alert('Please enter the literature review text or switch to file upload.'); return false; }
    }
  });

  // ── Summernote initialisation ──────────────────────────────────
  const snToolbar = [['style',['bold','italic','underline']],['para',['ul','ol']],['misc',['fullscreen']]];

  $('#sn_specific_objectives').summernote({ height:150, toolbar: snToolbar, disableDragAndDrop:true });
  $('#sn_general_objectives').summernote({ height:150, toolbar: snToolbar, disableDragAndDrop:true });
  $('#sn_beneficiaries').summernote({ height:150, toolbar: snToolbar, disableDragAndDrop:true });
  $('#sn_outputs').summernote({ height:150, toolbar: snToolbar, disableDragAndDrop:true });
  $('#literature_review').summernote({ height:180, toolbar: snToolbar, disableDragAndDrop:true });

  // ── Init budget headers ────────────────────────────────────────
  updateBudgetHeaders();

  // ── Select2 for static selects (skip summernote wrappers) ─────
  $('select.no-select2').each(function(){
    var placeholder = $(this).find('option[value=""]').first().text() || 'Select...';
    $(this).select2({ width: '100%', placeholder: placeholder, allowClear: true });
  });
  $('select:not(.no-select2):not([id^="note"])').each(function(){
    var placeholder = $(this).find('option[value=""]').first().text() || 'Select...';
    $(this).select2({ width: '100%', placeholder: placeholder, allowClear: true });
  });

});
</script>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
