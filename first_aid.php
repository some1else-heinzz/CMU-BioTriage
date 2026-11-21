<?php
require __DIR__ . '/includes/functions.php';

$facilities = db()->query(
    'SELECT id, name, place, phone
     FROM facilities
     WHERE active = 1
     ORDER BY name'
)->fetchAll();

page_head('First aid and contacts', 'first-aid');
?>
<section class="review-banner" role="alert" aria-label="Review status">
  <strong>DRAFT - Medical review required</strong>
  <span>Reviewer: Not assigned</span>
  <span>Review date: Pending</span>
  <p>This guidance and the listed numbers must be approved by University Health before use as official instructions.</p>
</section>

<p class="cache-label" id="first-aid-cache-label" hidden>Offline or cached data: verify current instructions and phone numbers before use.</p>

<p class="note" role="note">A snakebite is an emergency. Call for help immediately and arrange transport to an appropriate hospital. Antivenom and further medical treatment must be provided by trained health professionals.</p>

<section class="first-aid-grid" aria-label="Snakebite first aid guidance">
  <article class="card guidance-card">
    <h2>Do</h2>
    <ul>
      <li>Stay calm and keep the patient still and as close to resting position as possible.</li>
      <li>Move away from the snake and prevent further bites. Do not try to catch or kill it.</li>
      <li>Remove rings, watches, tight clothing, and footwear from the bitten limb before swelling begins.</li>
      <li>Immobilize the bitten limb and keep it at or below heart level if it is safe and comfortable to do so.</li>
      <li>Note the time of the bite, describe the snake if safely observed, and bring that information to the hospital.</li>
      <li>Call emergency or facility personnel before transport and confirm whether antivenom is available.</li>
      <li>Monitor breathing and consciousness. Follow the dispatcher or emergency clinician's instructions.</li>
    </ul>
  </article>

  <article class="card guidance-card danger-card">
    <h2>Do not</h2>
    <ul>
      <li>Do not cut or incise the bite, suck out venom, or use a tourniquet or tight bandage.</li>
      <li>Do not apply ice, electric shock, heat, chemicals, or any traditional product to the wound.</li>
      <li>Do not give the patient alcohol, sedatives, food, or unprescribed medicines.</li>
      <li>Do not wait for symptoms to appear before seeking emergency medical care.</li>
      <li>Do not delay transport, attempt to identify the snake at close range, or leave the patient alone.</li>
    </ul>
  </article>
</section>

<section aria-labelledby="contacts-heading">
  <h2 id="contacts-heading">Emergency contacts</h2>
  <div class="contacts-list">
    <article class="card contact-card">
      <h3>National emergency service</h3>
      <p>For immediate danger or a medical emergency:</p>
      <a class="btn pri" href="tel:911">Call 911</a>
    </article>

    <?php foreach ($facilities as $facility): ?>
      <article class="card contact-card">
        <h3><?= h($facility['name']) ?></h3>
        <?php if ($facility['place'] !== ''): ?><p class="mute"><?= h($facility['place']) ?></p><?php endif ?>
        <?php if ($facility['phone'] !== ''): ?>
          <a class="btn pri" href="tel:<?= h(preg_replace('/[^\d+]/', '', $facility['phone'])) ?>">Call <?= h($facility['phone']) ?></a>
        <?php else: ?>
          <p class="unconfirmed">Number not confirmed by University Health</p>
          <span class="btn dis" aria-disabled="true">Phone number pending</span>
        <?php endif ?>
      </article>
    <?php endforeach ?>
  </div>
</section>

<section class="card escalation-card" aria-labelledby="escalation-heading">
  <h2 id="escalation-heading">When calling</h2>
  <p>Report the patient's condition, exact bite location and time, current first aid, and estimated arrival time. Ask the facility to confirm antivenom stock before departure and state that the case involves a possible snakebite.</p>
</section>
<script>
  if (!navigator.onLine) {
    document.getElementById('first-aid-cache-label').hidden = false;
  }
</script>
<?php page_foot();
