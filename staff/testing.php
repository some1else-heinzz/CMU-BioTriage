<?php
require __DIR__ . '/../includes/functions.php';
require_staff();

$db = db();
$db->exec(
    'CREATE TABLE IF NOT EXISTS validation_records (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      tester_type ENUM(\'uh_staff\',\'student\',\'other\') NOT NULL,
      task_name VARCHAR(150) NOT NULL,
      result ENUM(\'pass\',\'issue\',\'blocked\') NOT NULL,
      accessibility_notes VARCHAR(1500) NOT NULL DEFAULT \'\',
      problem_notes VARCHAR(1500) NOT NULL DEFAULT \'\',
      change_made VARCHAR(1500) NOT NULL DEFAULT \'\',
      resolved TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id)
    )'
);

$errors = [];
$form = [
    'tester_type' => 'uh_staff',
    'task_name' => '',
    'result' => 'pass',
    'accessibility_notes' => '',
    'problem_notes' => '',
    'change_made' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'resolve') {
        $id = (int)($_POST['record_id'] ?? 0);
        $db->prepare('UPDATE validation_records SET resolved = 1 WHERE id = ?')->execute([$id]);
        flash('Validation issue marked resolved.');
        redirect('staff/testing.php');
    }

    foreach (array_keys($form) as $key) {
        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if (!in_array($form['tester_type'], ['uh_staff', 'student', 'other'], true))
        $errors[] = 'Choose the tester type.';
    if ($form['task_name'] === '' || mb_strlen($form['task_name']) > 150)
        $errors[] = 'Enter a validation task name.';
    if (!in_array($form['result'], ['pass', 'issue', 'blocked'], true))
        $errors[] = 'Choose a validation result.';
    foreach (['accessibility_notes', 'problem_notes', 'change_made'] as $field) {
        if (mb_strlen($form[$field]) > 1500) $errors[] = 'A text field is too long.';
    }

    if (!$errors) {
        $db->prepare(
            'INSERT INTO validation_records
             (user_id, tester_type, task_name, result, accessibility_notes, problem_notes, change_made, resolved)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)'
        )->execute([
            user()['id'],
            $form['tester_type'],
            $form['task_name'],
            $form['result'],
            $form['accessibility_notes'],
            $form['problem_notes'],
            $form['change_made'],
        ]);
        flash('Validation result recorded.');
        redirect('staff/testing.php');
    }
}

$records = $db->query(
    'SELECT r.*, u.name AS tester_name
     FROM validation_records r
     JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 100'
)->fetchAll();

page_head('Testing and change log', 'testing');
?>
<p class="note" role="note">Use this page to record each validation session with UH staff or students, including accessibility results and fixes made.</p>

<section class="card validation-tasks" aria-labelledby="tasks-heading">
  <h2 id="tasks-heading">Minimum validation tasks</h2>
  <ol>
    <li>Find a facility and confirm stock using search and status filters.</li>
    <li>Log a confirmation, including the server-side validation and append-only log.</li>
    <li>Build and copy a handoff summary.</li>
    <li>Open first aid and calling links on a phone.</li>
    <li>Add, edit, activate, or deactivate a facility as an admin.</li>
    <li>Repeat the board and first-aid tasks with the network disconnected.</li>
    <li>Complete the tasks using keyboard-only navigation and a screen reader where available.</li>
  </ol>
</section>

<?php if ($errors): ?>
  <div class="err" role="alert"><p>Fix these before saving:</p><ul>
    <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach ?>
  </ul></div>
<?php endif ?>

<form method="post" class="card testing-form">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <h2>Record validation</h2>

  <label for="tester_type">Tester</label>
  <select id="tester_type" name="tester_type" required>
    <option value="uh_staff"<?= $form['tester_type'] === 'uh_staff' ? ' selected' : '' ?>>University Health staff</option>
    <option value="student"<?= $form['tester_type'] === 'student' ? ' selected' : '' ?>>Student</option>
    <option value="other"<?= $form['tester_type'] === 'other' ? ' selected' : '' ?>>Other reviewer</option>
  </select>

  <label for="task_name">Task tested</label>
  <input id="task_name" name="task_name" maxlength="150" required value="<?= h($form['task_name']) ?>">

  <label for="result">Result</label>
  <select id="result" name="result" required>
    <option value="pass"<?= $form['result'] === 'pass' ? ' selected' : '' ?>>Pass</option>
    <option value="issue"<?= $form['result'] === 'issue' ? ' selected' : '' ?>>Issue found</option>
    <option value="blocked"<?= $form['result'] === 'blocked' ? ' selected' : '' ?>>Blocked</option>
  </select>

  <label for="accessibility_notes">Accessibility findings</label>
  <textarea id="accessibility_notes" name="accessibility_notes" rows="3" maxlength="1500"><?= h($form['accessibility_notes']) ?></textarea>

  <label for="problem_notes">Problem or test notes</label>
  <textarea id="problem_notes" name="problem_notes" rows="3" maxlength="1500"><?= h($form['problem_notes']) ?></textarea>

  <label for="change_made">Change made</label>
  <textarea id="change_made" name="change_made" rows="3" maxlength="1500" placeholder="Describe the fix, or leave blank if no change is needed"><?= h($form['change_made']) ?></textarea>

  <div class="row"><button class="pri" type="submit">Save validation result</button></div>
</form>

<h2>Problems and change log</h2>
<?php if (!$records): ?>
  <p class="card empty-state">No validation records yet.</p>
<?php else: ?>
  <div class="testing-list">
    <?php foreach ($records as $record): ?>
      <article class="card validation-record">
        <div class="record-heading">
          <h3><?= h($record['task_name']) ?></h3>
          <span class="result-badge result-<?= h($record['result']) ?>">
            <?= h(ucfirst($record['result'])) ?>
          </span>
        </div>
        <p class="mute">
          <?= h(str_replace('_', ' ', ucfirst($record['tester_type']))) ?> |
          <?= h($record['tester_name']) ?> |
          <?= h(date('M j, Y g:i A', strtotime($record['created_at']))) ?>
        </p>
        <?php if ($record['accessibility_notes'] !== ''): ?>
          <p><strong>Accessibility:</strong> <?= h($record['accessibility_notes']) ?></p>
        <?php endif ?>
        <?php if ($record['problem_notes'] !== ''): ?>
          <p><strong>Problem:</strong> <?= h($record['problem_notes']) ?></p>
        <?php endif ?>
        <?php if ($record['change_made'] !== ''): ?>
          <p><strong>Change made:</strong> <?= h($record['change_made']) ?></p>
        <?php endif ?>
        <?php if (!$record['resolved'] && $record['result'] !== 'pass'): ?>
          <form method="post" class="resolve-form">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="resolve">
            <input type="hidden" name="record_id" value="<?= (int)$record['id'] ?>">
            <button type="submit">Mark resolved</button>
          </form>
        <?php elseif ($record['resolved']): ?>
          <p class="resolved-label">Resolved</p>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </div>
<?php endif ?>
<?php page_foot();
