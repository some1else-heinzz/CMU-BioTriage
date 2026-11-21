<?php
require __DIR__ . '/includes/functions.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (attempt_login(trim((string)($_POST['email'] ?? '')), (string)($_POST['password'] ?? ''))) redirect('index.php');
    $err = 'Email or password is incorrect.';
}
page_head('Staff log in');
?>
<p>Only UH staff who log confirmations need an account. Anyone can view the board without logging in.</p>
<?php if ($err): ?><p class="err" role="alert"><?= h($err) ?></p><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="username">
  <label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password">
  <div class="row"><button class="pri">Log in</button></div>
</form>
<?php page_foot();
