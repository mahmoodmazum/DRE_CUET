<?php
require __DIR__ . '/../src/lib/Auth.php';
require __DIR__ . '/../src/db.php';
Auth::requireLogin();
$user = $_SESSION['user'];
if ($user['role'] !== 'dre_admin') { http_response_code(403); exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['add_user_id'])) {
        $uid = intval($_POST['add_user_id']);
        $pdo->prepare("INSERT IGNORE INTO reviewer_pool (user_id, added_by) VALUES (?, ?)")->execute([$uid, $user['sub'] ?? $user['id']]);
    } elseif (!empty($_POST['external_name']) && !empty($_POST['external_email'])) {
        $ename = trim($_POST['external_name']);
        $eemail = trim($_POST['external_email']);
        $pdo->prepare("INSERT INTO reviewer_pool (external_name, external_email, added_by) VALUES (?, ?, ?)")->execute([$ename, $eemail, $user['sub'] ?? $user['id']]);
    } elseif (!empty($_POST['remove_id'])) {
        $id = intval($_POST['remove_id']);
        $pdo->prepare("DELETE FROM reviewer_pool WHERE id = ?")->execute([$id]);
    }
    header('Location: reviewer.php');
    exit;
}

include __DIR__ . '/../src/includes/header.php';
include __DIR__ . '/../src/includes/sidebar_dre.php';

$teachers = $pdo->query("SELECT id, name, email FROM users WHERE role='teacher'")->fetchAll();
$reviewers = $pdo->query("SELECT rp.id, rp.user_id, rp.external_name, rp.external_email, u.name AS user_name, u.email AS user_email FROM reviewer_pool rp LEFT JOIN users u ON rp.user_id = u.id ORDER BY rp.added_at DESC")->fetchAll();
?>

<div class="content-wrapper">
  <section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><h1>Reviewer Pool</h1></div></section>
  <section class="content">
    <div class="card"><div class="card-body">
      <h5>Add internal reviewer</h5>
      <form method="post" class="form-inline mb-3">
        <select name="add_user_id" class="form-control mr-2">
          <option value="">Select teacher</option>
          <?php foreach($teachers as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary">Add</button>
      </form>

      <h5>Add external reviewer</h5>
      <form method="post" class="form-inline mb-3">
        <input type="text" name="external_name" placeholder="Name" class="form-control mr-2" required>
        <input type="email" name="external_email" placeholder="Email" class="form-control mr-2" required>
        <button class="btn btn-secondary">Add External</button>
      </form>

      <h5>Current Reviewers</h5>
      <table class="table table-sm">
        <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($reviewers as $r): 
            $name = $r['user_id'] ? $r['user_name'] : $r['external_name'];
            $email = $r['user_id'] ? $r['user_email'] : $r['external_email'];
            $type = $r['user_id'] ? 'Internal' : 'External';
          ?>
            <tr>
              <td><?= htmlspecialchars($name) ?></td>
              <td><?= htmlspecialchars($email) ?></td>
              <td><?= $type ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Remove?')">
                  <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                  <button class="btn btn-sm btn-danger">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div></div>
  </section>
</div>

<?php include __DIR__ . '/../src/includes/footer.php'; ?>
