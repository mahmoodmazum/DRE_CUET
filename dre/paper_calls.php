<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/custom_header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$calls = $pdo->query("
    SELECT pc.*, u.name AS creator
    FROM paper_calls pc
    LEFT JOIN users u ON pc.created_by = u.id
    ORDER BY pc.created_at DESC
")->fetchAll();

$i = 1;
?>

<div class="main-content">
  <div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <h1>Proposal Calls</h1>
      <div class="breadcrumb">Dashboard / Proposal Calls</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('createCallModal').classList.add('show')">
      <span class="material-icons-round">add</span> New Proposal Call
    </button>
  </div>

  <?php if (!empty($_GET['created'])): ?>
    <div class="alert alert-success">
      <span class="material-icons-round">check_circle</span>
      Proposal Call created and emails sent to all active teachers.
    </div>
  <?php elseif (!empty($_GET['updated'])): ?>
    <div class="alert alert-info">
      <span class="material-icons-round">info</span>
      Deadline updated and teachers notified.
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3>All Proposal Calls</h3></div>
    <div class="card-body" style="padding:0;">
      <table id="callsTable" class="data-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Issue Date</th>
            <th>Submission Deadline</th>
            <th>Review Deadline</th>
            <th>Message</th>
            <th>Attachments</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($calls as $c): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($c['issue_date']) ?></td>
            <td>
              <?php $passed = strtotime($c['deadline_date']) < strtotime(date('Y-m-d')); ?>
              <span class="badge <?= $passed ? 'badge-secondary' : 'badge-success' ?>">
                <?= htmlspecialchars($c['deadline_date']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($c['review_deadline'] ?? '—') ?></td>
            <td style="max-width:180px;font-size:0.8rem;color:var(--c-text-muted);">
              <?= nl2br(htmlspecialchars(substr($c['message'], 0, 120))) ?>
              <?= strlen($c['message']) > 120 ? '...' : '' ?>
            </td>
            <td>
              <?php
                $stmt = $pdo->prepare("SELECT * FROM paper_call_attachments WHERE paper_call_id = ?");
                $stmt->execute([$c['id']]);
                foreach($stmt->fetchAll() as $a):
              ?>
                <a href="/DRE/<?= $a['file_path'] ?>" target="_blank" class="file-download-link" style="margin-bottom:4px;font-size:0.75rem;">
                  <span class="material-icons-round">attach_file</span>
                  <?= htmlspecialchars($a['original_name']) ?>
                </a>
              <?php endforeach; ?>
            </td>
            <td>
              <div style="display:flex;flex-wrap:wrap;gap:4px;">
                <a href="view_active_paper_call.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">
                  <span class="material-icons-round">visibility</span> View
                </a>
                <a href="paper_call_email_log.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">
                  <span class="material-icons-round">email</span> Email Log
                </a>
                <button type="button" class="btn btn-primary btn-sm btn-edit-deadline"
                  data-id="<?= $c['id'] ?>"
                  data-deadline="<?= htmlspecialchars($c['deadline_date']) ?>"
                  data-review="<?= htmlspecialchars($c['review_deadline'] ?? '') ?>">
                  <span class="material-icons-round">edit_calendar</span> Edit Deadline
                </button>
                <?php if (!$passed): ?>
                <form method="post" action="delete_call.php" style="display:inline;"
                      onsubmit="return confirm('Delete this Proposal Call? This cannot be undone.');">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button class="btn btn-danger btn-sm">
                    <span class="material-icons-round">delete_outline</span> Delete
                  </button>
                </form>
                <?php else: ?>
                  <span class="badge badge-secondary">Deadline Passed</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Deadline Modal -->
<div class="modal-backdrop" id="editDeadlineModal">
  <div class="modal-box" style="max-width:420px;">
    <form method="post" action="edit_deadline.php">
      <input type="hidden" name="id" id="edit_pc_id">
      <div class="modal-header">
        <h3><span class="material-icons-round" style="vertical-align:middle;font-size:18px;margin-right:6px;">edit_calendar</span> Edit Deadline</h3>
        <button type="button" class="modal-close" onclick="document.getElementById('editDeadlineModal').classList.remove('show')">
          <span class="material-icons-round">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label class="field-label">Submission Deadline <span style="color:var(--c-danger)">*</span></label>
          <input type="date" name="deadline_date" id="edit_deadline_date" class="no-select2" required>
        </div>
        <div class="form-group">
          <label class="field-label">Review Deadline</label>
          <input type="date" name="review_deadline" id="edit_review_deadline" class="no-select2">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editDeadlineModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <span class="material-icons-round">save</span> Update &amp; Notify Teachers
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Create Proposal Call Modal -->
<div class="modal-backdrop" id="createCallModal">
  <div class="modal-box">
    <form method="post" action="add_paper_call.php" enctype="multipart/form-data">
      <div class="modal-header">
        <h3><span class="material-icons-round" style="vertical-align:middle;font-size:18px;margin-right:6px;">add_circle</span> New Proposal Call</h3>
        <button type="button" class="modal-close" onclick="document.getElementById('createCallModal').classList.remove('show')">
          <span class="material-icons-round">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="field-label">Issue Date <span style="color:var(--c-danger)">*</span></label>
            <input type="date" name="issue_date" class="no-select2" required>
          </div>
          <div class="form-group">
            <label class="field-label">Submission Deadline <span style="color:var(--c-danger)">*</span></label>
            <input type="date" name="deadline_date" class="no-select2" required>
          </div>
        </div>
        <div class="form-group">
          <label class="field-label">Review Deadline</label>
          <input type="date" name="review_deadline_date" class="no-select2">
        </div>
        <div class="form-group">
          <label class="field-label">Attachments <span class="text-muted fs-sm">(multiple allowed)</span></label>
          <input type="file" name="attachments[]" multiple style="display:block;padding:8px 0;">
        </div>
        <div class="form-group">
          <label class="field-label">Message Body <span style="color:var(--c-danger)">*</span></label>
          <textarea name="message" rows="6" required placeholder="Body of the proposal call email..."></textarea>
        </div>
        <div class="form-group">
          <label class="field-label">Signature</label>
          <textarea name="signature" rows="3" placeholder="e.g. Director, DRE — CUET"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('createCallModal').classList.remove('show')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <span class="material-icons-round">send</span> Create &amp; Send Emails
        </button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/custom_footer.php'; ?>
<script>
$(function(){
  $('#callsTable').DataTable({responsive:true, order:[[0,'desc']], columnDefs:[{orderable:false, targets:-1}]});

  // Edit Deadline modal
  $(document).on('click', '.btn-edit-deadline', function(){
    const $btn = $(this);
    $('#edit_pc_id').val($btn.data('id'));
    $('#edit_deadline_date').val($btn.data('deadline'));
    $('#edit_review_deadline').val($btn.data('review'));
    document.getElementById('editDeadlineModal').classList.add('show');
  });

  // Close modals on backdrop click
  ['createCallModal','editDeadlineModal'].forEach(function(id){
    document.getElementById(id).addEventListener('click', function(e){
      if(e.target === this) this.classList.remove('show');
    });
  });

  $('select:not(.no-select2)').select2({theme:'default',width:'100%',allowClear:true});
});
</script>
