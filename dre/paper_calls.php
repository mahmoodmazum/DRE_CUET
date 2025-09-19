<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$calls = $pdo->query("
    SELECT pc.*, u.name AS creator
    FROM paper_calls pc
    LEFT JOIN users u ON pc.created_by = u.id
    ORDER BY pc.created_at DESC
")->fetchAll();

$i=1;

?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><h1>Paper Calls</h1>
    <button class="btn btn-success" data-toggle="modal" data-target="#createCallModal"><i class="fas fa-plus"></i> New Call</button>
  </div></section>

  <section class="content">
    <div class="card"><div class="card-body">
      <table id="callsTable" class="table table-bordered table-striped">
        <thead><tr><th>Sl No</th><th>Issue</th><th>Submission Deadline</th><th>Review Deadline</th><th>Message</th><th>Attachments</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($calls as $c): ?>
          <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($c['issue_date']) ?></td>
            <td><?= htmlspecialchars($c['deadline_date']) ?></td>
            <td><?= htmlspecialchars($c['review_deadline']) ?></td>
            <td><?= nl2br(htmlspecialchars(substr($c['message'],0,200))) ?></td>
            <td>
              <?php
                $stmt = $pdo->prepare("SELECT * FROM paper_call_attachments WHERE paper_call_id = ?");
                $stmt->execute([$c['id']]);
                $atts = $stmt->fetchAll();
                foreach($atts as $a) {
                  echo '<a href="/DRE/'.$a['file_path'].'" target="_blank">'.htmlspecialchars($a['original_name']).'</a><br>';
                }
              ?>
            </td>
            <td>
    <form style="display:inline" method="get" action="view_active_paper_call.php">
        <input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="btn btn-sm btn-info">View All</button>
    </form>
    <form style="display:inline" method="post" action="edit_deadline.php">
    <input type="hidden" name="id" value="<?= $c['id'] ?>">

    <div class="mb-2">
        <label for="submission_deadline_<?= $c['id'] ?>" class="form-label">Submission Deadline</label>
        <input 
            type="date" 
            id="submission_deadline_<?= $c['id'] ?>" 
            name="deadline_date" 
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($c['deadline_date']) ?>"
        >
    </div>

    <div class="mb-2">
        <label for="review_deadline_<?= $c['id'] ?>" class="form-label">Review Deadline</label>
        <input 
            type="date" 
            id="review_deadline_<?= $c['id'] ?>" 
            name="review_deadline" 
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($c['review_deadline']) ?>"
        >
    </div>

    <button class="btn btn-sm btn-primary">Update</button>
</form>


    <?php if (strtotime($c['deadline_date']) >= strtotime(date('Y-m-d'))): ?>
        <form style="display:inline" method="post" action="delete_call.php" onsubmit="return confirm('Delete this call?');">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="btn btn-sm btn-danger">Delete</button>
        </form>
    <?php else: ?>
        <button class="btn btn-sm btn-secondary" disabled>Deadline Passed</button>
    <?php endif; ?>
</td>

          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  </section>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createCallModal">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="post" action="add_paper_call.php" enctype="multipart/form-data">
      <div class="modal-header"><h5 class="modal-title">New Paper Call</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" class="form-control" required></div>
        <div class="form-group"><label>Deadline Date</label><input type="date" name="deadline_date" class="form-control" required></div>
        <div class="form-group"><label>Review Deadline</label><input type="date" name="review_deadline_date" class="form-control" required></div>
        <div class="form-group"><label>Attachments (multiple allowed)</label><input type="file" name="attachments[]" multiple class="form-control-file"></div>
        <div class="form-group"><label>Message Body</label><textarea name="message" rows="6" class="form-control" required></textarea></div>
        <div class="form-group"><label>Signature Body</label><textarea name="signature" rows="3" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Create & Email</button></div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>

<script>
$(function(){ $('#callsTable').DataTable({responsive:true}); });
</script>
