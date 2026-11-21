<?php
require __DIR__ . '/../includes/functions.php';
require_staff();
$db = db();
$fac = $db->query('SELECT id, name, phone FROM facilities WHERE active = 1 ORDER BY name')->fetchAll();
$errors = [];
$v = ['facility_id' => (int)($_GET['facility'] ?? 0), 'status' => '', 'method' => 'phone', 'restock_date' => '',
      'restock_source' => '', 'source_person' => '', 'note' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    [$errors, $c] = validate_confirmation($_POST);
    $v = array_merge($v, array_filter($_POST, 'is_string'));
    if (!$errors) {
        $db->beginTransaction();
        try {
            $db->prepare('INSERT INTO confirmations (facility_id, user_id, status, method, restock_date, restock_source, source_person, note)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
               ->execute([$c['facility_id'], user()['id'], $c['status'], $c['method'], $c['restock_date'], $c['restock_source'], $c['source_person'], $c['note']]);
            if ($c['phone'] !== '') $db->prepare('UPDATE facilities SET phone = ? WHERE id = ?')->execute([$c['phone'], $c['facility_id']]);
            $db->commit();
        } catch (Throwable $t) { $db->rollBack(); throw $t; }
        flash('Confirmation saved.');
        redirect('index.php');
    }
}
$sel = (int)$v['facility_id'];
if ($v['phone'] === '') foreach ($fac as $f) if ((int)$f['id'] === $sel) $v['phone'] = $f['phone'];

page_head('Log a confirmation', 'confirm');
?>
<p class="mute">Saving stamps the current time. Only record what someone told you or what you saw. Earlier entries are kept in the log below.</p>
<?php if ($errors): ?><div class="err" role="alert"><p>Fix these before saving:</p><ul><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <label for="facility_id">Facility</label>
  <select id="facility_id" name="facility_id" required><option value="">Choose a facility</option>
    <?php foreach ($fac as $f): ?><option value="<?= (int)$f['id'] ?>"<?= $sel === (int)$f['id'] ? ' selected' : '' ?>><?= h($f['name']) ?></option><?php endforeach; ?>
  </select>
  <label for="status">What is the stock situation?</label>
  <select id="status" name="status" required><option value="">Choose one</option>
    <?php foreach (['available', 'restock', 'out'] as $k): ?><option value="<?= $k ?>"<?= $v['status'] === $k ? ' selected' : '' ?>><?= h(STATUS[$k][1]) ?></option><?php endforeach; ?>
  </select>
  <label for="method">How did you confirm it?</label>
  <select id="method" name="method"><?php foreach (METHOD as $k => $l): ?><option value="<?= $k ?>"<?= $v['method'] === $k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach; ?></select>
  <label for="source_person">Who at the facility told you? (optional)</label>
  <input id="source_person" name="source_person" maxlength="100" value="<?= h($v['source_person']) ?>">
  <label for="restock_date">Restock announcement date (needed for “Restock announced”)</label>
  <input id="restock_date" name="restock_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= h($v['restock_date']) ?>">
  <label for="restock_source">Announcement source (needed for PGB posts)</label>
  <input id="restock_source" name="restock_source" maxlength="500" value="<?= h($v['restock_source']) ?>" placeholder="For example, PGB Facebook post, with link">
  <label for="phone">Facility phone number (optional)</label>
  <input id="phone" name="phone" type="tel" value="<?= h($v['phone']) ?>">
  <label for="note">Note (optional)</label>
  <input id="note" name="note" maxlength="500" value="<?= h($v['note']) ?>">
  <div class="row"><button class="pri">Save confirmation</button><a class="btn" href="<?= h(url('index.php')) ?>">Cancel</a></div>
</form>

<h2>Recent confirmations</h2>
<?php $log = $db->query('SELECT c.*, f.name AS fname, u.name AS uname FROM confirmations c
        JOIN facilities f ON f.id = c.facility_id JOIN users u ON u.id = c.user_id
        ORDER BY c.created_at DESC, c.id DESC LIMIT 20')->fetchAll();
if (!$log): ?><p class="mute">No confirmations logged yet.</p><?php else: ?>
<div class="scroll"><table><thead><tr><th>When</th><th>Facility</th><th>Status</th><th>Method</th><th>Logged by</th></tr></thead><tbody>
<?php foreach ($log as $r): ?><tr><td><?= h(date('M j, g:i A', strtotime($r['created_at']))) ?></td><td><?= h($r['fname']) ?></td><td><?= h(STATUS[$r['status']][1]) ?></td><td><?= h(METHOD[$r['method']]) ?></td><td><?= h($r['uname']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; page_foot();
