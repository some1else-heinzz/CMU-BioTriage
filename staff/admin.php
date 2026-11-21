<?php
require __DIR__ . '/../includes/functions.php';
require_admin();

$db = db();
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$form = [
    'name' => '',
    'place' => '',
    'phone' => '',
];

if ($editId) {
    $statement = $db->prepare('SELECT id, name, place, phone FROM facilities WHERE id = ?');
    $statement->execute([$editId]);
    $existing = $statement->fetch();
    if ($existing) {
        $form = [
            'name' => $existing['name'],
            'place' => $existing['place'],
            'phone' => $existing['phone'],
        ];
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'toggle') {
        $id = (int)($_POST['facility_id'] ?? 0);
        $statement = $db->prepare('UPDATE facilities SET active = IF(active = 1, 0, 1) WHERE id = ?');
        $statement->execute([$id]);
        flash('Facility status updated.');
        redirect('staff/admin.php');
    }

    $form = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'place' => trim((string)($_POST['place'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? '')),
    ];
    $editId = (int)($_POST['facility_id'] ?? 0);

    if ($form['name'] === '') $errors[] = 'Facility name is required.';
    if (mb_strlen($form['name']) > 150) $errors[] = 'Facility name is too long.';
    if (mb_strlen($form['place']) > 255) $errors[] = 'Location is too long.';
    if ($form['phone'] !== '' && !preg_match('/^[0-9+()\-\s]{5,30}$/', $form['phone']))
        $errors[] = 'Enter a valid phone number.';

    if (!$errors) {
        if ($action === 'add') {
            $db->prepare('INSERT INTO facilities (name, place, phone, active) VALUES (?, ?, ?, 1)')
                ->execute([$form['name'], $form['place'], $form['phone']]);
            flash('Facility added.');
        } elseif ($action === 'edit' && $editId) {
            $db->prepare('UPDATE facilities SET name = ?, place = ?, phone = ? WHERE id = ?')
                ->execute([$form['name'], $form['place'], $form['phone'], $editId]);
            flash('Facility updated.');
        } else {
            $errors[] = 'Could not identify the facility action.';
        }
        if (!$errors) redirect('staff/admin.php');
    }
}

$facilities = $db->query('SELECT id, name, place, phone, active FROM facilities ORDER BY active DESC, name')->fetchAll();

page_head('Admin page', 'admin');
?>
<p class="note" role="note">Manage the facilities shown on the public status board and first-aid page. Deactivate a facility instead of deleting its confirmation history.</p>

<?php if ($errors): ?>
  <div class="err" role="alert"><p>Fix these before saving:</p><ul>
    <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach ?>
  </ul></div>
<?php endif ?>

<form method="post" class="card admin-form">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="action" value="<?= $editId ? 'edit' : 'add' ?>">
  <?php if ($editId): ?><input type="hidden" name="facility_id" value="<?= $editId ?>"><?php endif ?>

  <h2><?= $editId ? 'Edit facility' : 'Add a facility' ?></h2>
  <label for="name">Facility name</label>
  <input id="name" name="name" maxlength="150" required value="<?= h($form['name']) ?>">

  <label for="place">Location or description</label>
  <input id="place" name="place" maxlength="255" value="<?= h($form['place']) ?>">

  <label for="phone">Phone number</label>
  <input id="phone" name="phone" maxlength="30" type="tel" value="<?= h($form['phone']) ?>">

  <div class="row">
    <button class="pri" type="submit"><?= $editId ? 'Save changes' : 'Add facility' ?></button>
    <?php if ($editId): ?><a class="btn" href="<?= h(url('staff/admin.php')) ?>">Cancel</a><?php endif ?>
  </div>
</form>

<h2>Facilities</h2>
<div class="scroll">
  <table>
    <thead><tr><th>Name</th><th>Location</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($facilities as $facility): ?>
        <tr>
          <td><?= h($facility['name']) ?></td>
          <td><?= h($facility['place'] ?: 'Not recorded') ?></td>
          <td><?= h($facility['phone'] ?: 'Not recorded') ?></td>
          <td><?= $facility['active'] ? 'Active' : 'Inactive' ?></td>
          <td>
            <div class="table-actions">
              <a class="btn" href="<?= h(url('staff/admin.php?edit=' . (int)$facility['id'])) ?>">Edit</a>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="facility_id" value="<?= (int)$facility['id'] ?>">
                <button type="submit"><?= $facility['active'] ? 'Deactivate' : 'Activate' ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
<?php page_foot();
