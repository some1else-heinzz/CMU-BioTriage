<?php
require __DIR__ . '/includes/functions.php';
$query = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
if (!in_array($statusFilter, ['', 'available', 'restock', 'out', 'unknown'], true)) $statusFilter = '';
$rows = board($query, $statusFilter);
$counts = ['all' => 0, 'available' => 0, 'restock' => 0, 'out' => 0, 'unknown' => 0];
foreach (board() as $facility) {
    $counts['all']++;
    $key = $facility['status'] ?? 'unknown';
    $counts[$key]++;
}
page_head('Antivenom status', 'board');
?>
<p class="board-load-note" id="board-load-note">Last loaded: checking...</p>
<section class="summary" aria-label="Stock status summary">
  <div class="summary-item"><span>All facilities</span><strong><?= $counts['all'] ?></strong></div>
  <div class="summary-item"><span>Confirmed available</span><strong><?= $counts['available'] ?></strong></div>
  <div class="summary-item"><span>Restock announced</span><strong><?= $counts['restock'] ?></strong></div>
  <div class="summary-item"><span>Needs confirmation</span><strong><?= $counts['unknown'] ?></strong></div>
</section>
<p class="note" role="note">This board can be out of date. A restock announcement is not a current stock confirmation. Always call before sending a patient.</p>
<form method="get" class="filters" role="search" aria-label="Filter antivenom facilities">
  <label for="q">Search facility or location</label>
  <input id="q" name="q" type="search" value="<?= h($query) ?>" placeholder="Hospital or town">
  <label for="status">Status</label>
  <select id="status" name="status">
    <option value="">All (<?= $counts['all'] ?>)</option>
    <?php foreach (['available', 'restock', 'out', 'unknown'] as $key): ?>
      <option value="<?= $key ?>"<?= $statusFilter === $key ? ' selected' : '' ?>><?= h(STATUS[$key][1]) ?> (<?= $counts[$key] ?>)</option>
    <?php endforeach ?>
  </select>
  <button class="pri" type="submit">Filter</button>
  <?php if ($query !== '' || $statusFilter !== ''): ?><a class="btn" href="<?= h(url('index.php')) ?>">Clear</a><?php endif ?>
</form>
<p class="board-count"><?= count($rows) ?> <?= count($rows) === 1 ? 'facility' : 'facilities' ?> shown</p>
<?php if (!$rows): ?>
  <p class="card warn">No facilities match these filters. <a href="<?= h(url('index.php')) ?>">View the full board</a>.</p>
<?php endif ?>
<section class="facility-grid" aria-label="Antivenom facility status">
<?php foreach ($rows as $f):
    $key = $f['status'] ?? 'unknown'; [$icon, $label, $cls] = STATUS[$key];
    $min = $f['age_min'] === null ? null : (int)$f['age_min']; $old = is_stale($min); ?>
<article class="card <?= $cls ?><?= $old ? ' old' : '' ?>">
  <h2><?= h($f['name']) ?></h2>
  <p class="mute"><?= h($f['place']) ?></p>
  <p class="badge <?= $cls ?>"><span aria-hidden="true"><?= $icon ?></span> <?= h($label) ?></p>
  <?php if ($min !== null): ?>
    <p>Last confirmed <?= h(ago($min)) ?> (<?= h(date('M j, Y g:i A', strtotime($f['created_at']))) ?>) by <?= h($f['logged_by']) ?>, by <?= h(strtolower(METHOD[$f['method']])) ?><?= $f['source_person'] !== '' ? ', speaking with ' . h($f['source_person']) : '' ?>.</p>
    <?php if ($f['restock_date'] || $f['restock_source'] !== ''): ?>
      <p>Restock announced <?= h($f['restock_date'] ?: '(date not recorded)') ?>. Source: <?= h($f['restock_source'] ?: 'not recorded') ?>.</p>
    <?php endif; ?>
    <?php if ($f['note'] !== ''): ?><p>Note: <?= h($f['note']) ?></p><?php endif; ?>
  <?php endif; ?>
  <?php if ($old): ?><p class="flag"><?= $min === null ? 'Nobody has confirmed this yet.' : 'This information is more than ' . STALE_HOURS . ' hours old.' ?> Call to confirm before sending a patient.</p><?php endif; ?>
  <div class="row">
    <?php if ($f['phone'] !== ''): ?><a class="btn pri" href="tel:<?= h(preg_replace('/[^\d+]/', '', $f['phone'])) ?>">Call to confirm</a>
    <?php else: ?><span class="btn dis">Number not set</span><?php endif; ?>
    <?php if (is_staff()): ?><a class="btn" href="<?= h(url('staff/confirm.php?facility=' . (int)$f['id'])) ?>">Log a confirmation</a><?php endif; ?>
  </div>
</article>
<?php endforeach; ?>
</section>
<script>
  if (navigator.onLine) {
    localStorage.setItem('cmu-biotriage-board-loaded', new Date().toISOString());
  }
  const loadedAt = localStorage.getItem('cmu-biotriage-board-loaded');
  if (loadedAt) {
    document.getElementById('board-load-note').textContent =
      'Last loaded: ' + new Date(loadedAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
  } else {
    document.getElementById('board-load-note').textContent = 'Last loaded: not yet recorded';
  }
</script>
<?php page_foot();
