<?php
require __DIR__ . '/includes/functions.php';

$facilities = db()->query(
    'SELECT f.id, f.name, f.place, f.phone,
            c.status, c.created_at,
            TIMESTAMPDIFF(MINUTE, c.created_at, NOW()) AS age_min
     FROM facilities f
     LEFT JOIN confirmations c ON c.id = (
         SELECT id FROM confirmations
         WHERE facility_id = f.id
         ORDER BY created_at DESC, id DESC
         LIMIT 1
     )
     WHERE f.active = 1
     ORDER BY f.name'
)->fetchAll();

$v = [
    'facility_id' => (int)($_GET['facility'] ?? 0),
    'caller_name' => '',
    'bite_time' => '',
    'snake_description' => '',
    'patient_condition' => '',
    'actions_taken' => '',
    'eta' => '',
];
$errors = [];
$summary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = array_merge($v, array_filter($_POST, 'is_string'));
    $v['facility_id'] = (int)($v['facility_id'] ?? 0);

    $facility = null;
    foreach ($facilities as $row) {
        if ((int)$row['id'] === $v['facility_id']) {
            $facility = $row;
            break;
        }
    }
    if (!$facility) $errors[] = 'Choose a facility.';

    foreach (['caller_name', 'bite_time', 'snake_description', 'patient_condition', 'actions_taken', 'eta'] as $field) {
        $v[$field] = trim((string)($v[$field] ?? ''));
        if ($v[$field] === '') $errors[] = 'Complete all handoff fields.';
    }

    $biteTimeFormatted = null;
    if ($v['bite_time'] !== '') {
        $biteTime = DateTime::createFromFormat('Y-m-d\TH:i', $v['bite_time']);
        if (!$biteTime || $biteTime->format('Y-m-d\TH:i') !== $v['bite_time']) {
            $errors[] = 'Enter a valid bite date and time.';
        } elseif ($biteTime > new DateTime('now')) {
            $errors[] = 'The bite date and time cannot be in the future.';
        } else {
            $biteTimeFormatted = $biteTime->format('M j, Y g:i A');
        }
    }

    $etaFormatted = null;
    if ($v['eta'] !== '') {
        $eta = DateTime::createFromFormat('Y-m-d\TH:i', $v['eta']);
        if (!$eta || $eta->format('Y-m-d\TH:i') !== $v['eta']) {
            $errors[] = 'Enter a valid arrival date and time.';
        } elseif ($eta < new DateTime('now')) {
            $errors[] = 'The estimated arrival cannot be in the past.';
        } else {
            $etaFormatted = $eta->format('M j, Y g:i A');
        }
    }

    if (!$errors && $facility) {
        $stockStatus = 'Not yet confirmed';
        $lastConfirmed = 'No confirmation recorded';
        if (!empty($facility['status'])) {
            $stockStatus = STATUS[$facility['status']][1];
            $lastConfirmed = date('M j, Y g:i A', strtotime($facility['created_at']));
            $lastConfirmed .= ' (' . ago((int)$facility['age_min']) . ')';
        }

        $summary = "FACILITY: {$facility['name']}\n"
            . "Facility location: " . ($facility['place'] ?: 'Not recorded') . "\n"
            . "Facility phone: " . ($facility['phone'] ?: 'Not recorded') . "\n"
            . "Antivenom status: {$stockStatus}\n"
            . "Last confirmed: {$lastConfirmed}\n\n"
            . "HANDOFF FROM: {$v['caller_name']}\n"
            . "Time of bite: {$biteTimeFormatted}\n"
            . "Snake description: {$v['snake_description']}\n"
            . "Patient condition: {$v['patient_condition']}\n"
            . "Actions taken: {$v['actions_taken']}\n"
            . "Estimated time of arrival: {$etaFormatted}";
    }
}

page_head('Handoff summary', 'handoff');
?>
<p class="note" role="note">Use this to prepare a clear handoff before transfer. Confirm antivenom stock by phone before sending a patient.</p>

<?php if ($errors): ?>
  <div class="err" role="alert"><p>Fix these before building the summary:</p><ul>
    <?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach ?>
  </ul></div>
<?php endif ?>

<form method="post" class="card handoff-form">
  <label for="facility_id">Facility</label>
  <select id="facility_id" name="facility_id" required>
    <option value="">Choose a facility</option>
    <?php foreach ($facilities as $facility): ?>
      <option value="<?= (int)$facility['id'] ?>"<?= $v['facility_id'] === (int)$facility['id'] ? ' selected' : '' ?>>
        <?= h($facility['name']) ?>
      </option>
    <?php endforeach ?>
  </select>

  <label for="caller_name">Your name</label>
  <input id="caller_name" name="caller_name" maxlength="100" required value="<?= h($v['caller_name']) ?>">

  <label for="bite_time">Time of bite</label>
  <input id="bite_time" name="bite_time" type="datetime-local" required value="<?= h($v['bite_time']) ?>">

  <label for="snake_description">Snake description</label>
  <textarea id="snake_description" name="snake_description" rows="3" maxlength="1000" required><?= h($v['snake_description']) ?></textarea>

  <label for="patient_condition">Patient condition</label>
  <textarea id="patient_condition" name="patient_condition" rows="3" maxlength="1000" required><?= h($v['patient_condition']) ?></textarea>

  <label for="actions_taken">Actions taken</label>
  <textarea id="actions_taken" name="actions_taken" rows="4" maxlength="1500" required><?= h($v['actions_taken']) ?></textarea>

  <label for="eta">Estimated time of arrival</label>
  <input id="eta" name="eta" type="datetime-local" required value="<?= h($v['eta']) ?>">

  <div class="row"><button class="pri" type="submit">Build summary</button></div>
</form>

<?php if ($summary): ?>
  <section class="card summary-output" aria-labelledby="summary-heading">
    <h2 id="summary-heading">Copy-ready handoff</h2>
    <textarea id="handoff-summary" rows="16" readonly><?= h($summary) ?></textarea>
    <div class="row">
      <button type="button" class="pri" id="copy-summary">Copy summary</button>
      <button type="button" id="print-summary">Print summary</button>
    </div>
    <p class="mute" id="copy-status" role="status"></p>
  </section>
  <script>
    const summary = document.getElementById('handoff-summary');
    document.getElementById('copy-summary').addEventListener('click', async () => {
      const status = document.getElementById('copy-status');
      try {
        await navigator.clipboard.writeText(summary.value);
        status.textContent = 'Summary copied.';
      } catch (_) {
        summary.select();
        status.textContent = 'Select the text and use your device\u2019s copy command.';
      }
    });
    document.getElementById('print-summary').addEventListener('click', () => window.print());
  </script>
<?php endif ?>

<?php page_foot();
