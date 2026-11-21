<?php
require_once __DIR__ . '/auth.php';

const STATUS = [
    'available' => ['✓', 'Confirmed available', 'ok'],
    'restock'   => ['◐', 'Restock announced, not confirmed', 'info'],
    'out'       => ['✕', 'Reported out of stock', 'bad'],
    'unknown'   => ['?', 'Not yet confirmed', 'warn'],
];
const METHOD = ['phone' => 'Phone call', 'pgb_post' => 'PGB Facebook post', 'in_person' => 'In person', 'other' => 'Other'];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $p = ''): string { return BASE . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }
function flash(?string $m = null): ?string {
    if ($m !== null) { $_SESSION['flash'] = $m; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function is_stale(?int $min): bool { return $min === null || $min > STALE_HOURS * 60; }
function ago(int $min): string {
    if ($min < 1) return 'just now';
    if ($min < 60) return $min . ' min ago';
    $hr = intdiv($min, 60);
    return $hr < 48 ? $hr . ' h ago' : intdiv($hr, 24) . ' days ago';
}

// Latest confirmation per facility. Fresh entries first, stale or never-confirmed after.
function board(?string $query = null, ?string $status = null): array {
    $rows = db()->query(
        'SELECT f.id, f.name, f.place, f.phone, c.status, c.method, c.restock_date, c.restock_source,
                c.source_person, c.note, c.created_at, u.name AS logged_by,
                TIMESTAMPDIFF(MINUTE, c.created_at, NOW()) AS age_min
         FROM facilities f
         LEFT JOIN confirmations c ON c.id = (
             SELECT id FROM confirmations WHERE facility_id = f.id ORDER BY created_at DESC, id DESC LIMIT 1)
         LEFT JOIN users u ON u.id = c.user_id
         WHERE f.active = 1'
    )->fetchAll();
    $query = $query !== null ? mb_strtolower(trim($query), 'UTF-8') : '';
    if ($query !== '' || ($status !== null && $status !== '')) {
        $rows = array_values(array_filter($rows, function ($row) use ($query, $status) {
            $matchesText = $query === '' || str_contains(mb_strtolower($row['name'], 'UTF-8'), $query)
                || str_contains(mb_strtolower($row['place'], 'UTF-8'), $query);
            $rowStatus = $row['status'] ?? 'unknown';
            $matchesStatus = $status === null || $status === '' || $rowStatus === $status;
            return $matchesText && $matchesStatus;
        }));
    }
    usort($rows, function ($a, $b) {
        $sa = is_stale($a['age_min'] === null ? null : (int)$a['age_min']);
        $sb = is_stale($b['age_min'] === null ? null : (int)$b['age_min']);
        if ($sa !== $sb) return $sa <=> $sb;
        if (!$sa) return (int)$a['age_min'] <=> (int)$b['age_min'];
        return strcmp($a['name'], $b['name']);
    });
    return $rows;
}

// Server-side rules. Returns [errors, cleaned values].
function validate_confirmation(array $in): array {
    $e = []; $c = [];
    $c['facility_id'] = (int)($in['facility_id'] ?? 0);
    $q = db()->prepare('SELECT COUNT(*) FROM facilities WHERE id = ? AND active = 1');
    $q->execute([$c['facility_id']]);
    if (!$q->fetchColumn()) $e[] = 'Choose a facility from the list.';

    $c['status'] = (string)($in['status'] ?? '');
    if (!in_array($c['status'], ['available', 'restock', 'out'], true)) $e[] = 'Choose the stock situation.';
    $c['method'] = (string)($in['method'] ?? '');
    if (!isset(METHOD[$c['method']])) $e[] = 'Choose how you confirmed it.';

    if ($c['status'] === 'available' && $c['method'] === 'pgb_post')
        $e[] = 'A PGB post shows a restock, not current stock. Choose “Restock announced”, or confirm by phone or in person.';

    $c['restock_date'] = trim((string)($in['restock_date'] ?? ''));
    if ($c['restock_date'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $c['restock_date']);
        if (!$d || $d->format('Y-m-d') !== $c['restock_date']) $e[] = 'Enter a valid restock date.';
        elseif ($d > new DateTime('today')) $e[] = 'The restock date cannot be in the future.';
    } else {
        $c['restock_date'] = null;
        if ($c['status'] === 'restock') $e[] = 'Enter the date the restock was announced.';
    }
    $c['restock_source'] = trim((string)($in['restock_source'] ?? ''));
    if ($c['method'] === 'pgb_post' && $c['restock_source'] === '') $e[] = 'Add the link or a description of the PGB post.';
    $c['source_person'] = trim((string)($in['source_person'] ?? ''));
    $c['note'] = trim((string)($in['note'] ?? ''));
    $c['phone'] = trim((string)($in['phone'] ?? ''));
    if ($c['phone'] !== '' && !preg_match('/^[0-9+()\-\s]{5,30}$/', $c['phone'])) $e[] = 'Enter a valid phone number.';
    if (mb_strlen($c['restock_source']) > 500 || mb_strlen($c['note']) > 500 || mb_strlen($c['source_person']) > 100)
        $e[] = 'One of the text fields is too long.';
    return [$e, $c];
}

function page_head(string $title, string $active = ''): void {
    $u = user();
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="theme-color" content="#146b57">'
       . '<title>' . h($title) . ' | CMU Snakebite Response Support</title><link rel="stylesheet" href="' . h(url('assets/style.css')) . '"></head><body>'
       . '<header><p class="site">CMU Snakebite Response Support</p><h1>' . h($title) . '</h1>'
       . '<nav aria-label="Main"><a href="' . h(url('index.php')) . '"' . ($active === 'board' ? ' aria-current="page"' : '') . '>Antivenom status</a>';
    if (is_staff()) echo '<a href="' . h(url('staff/confirm.php')) . '"' . ($active === 'confirm' ? ' aria-current="page"' : '') . '>Log a confirmation</a>';
    if (is_staff()) echo '<a href="' . h(url('staff/testing.php')) . '"' . ($active === 'testing' ? ' aria-current="page"' : '') . '>Testing and changes</a>';
    if (is_admin()) echo '<a href="' . h(url('staff/admin.php')) . '"' . ($active === 'admin' ? ' aria-current="page"' : '') . '>Admin page</a>';
    echo $u
        ? '<form method="post" action="' . h(url('logout.php')) . '"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><button>Log out (' . h($u['name']) . ')</button></form>'
        : '<a href="' . h(url('login.php')) . '">Staff log in</a>';
    echo '<a href="' . h(url('handoff.php')) . '"' . ($active === 'handoff' ? ' aria-current="page"' : '') . '>Handoff summary</a>';
    echo '<a href="' . h(url('first_aid.php')) . '"' . ($active === 'first-aid' ? ' aria-current="page"' : '') . '>First aid and contacts</a>';
    echo '</nav></header><main>';
    if ($m = flash()) echo '<p class="toast" role="status">' . h($m) . '</p>';
}
function page_foot(): void {
    echo '</main>'
       . '<script>'
       . 'if ("serviceWorker" in navigator) { window.addEventListener("load", function () { navigator.serviceWorker.register(' . json_encode(url('sw.js')) . '); }); }'
       . 'const offlineBanner = document.createElement("div");'
       . 'offlineBanner.className = "offline-banner";'
       . 'offlineBanner.setAttribute("role", "status");'
       . 'function updateConnectionStatus() {'
       . 'if (!navigator.onLine) {'
       . 'offlineBanner.textContent = "Offline mode: showing the most recently cached page.";'
       . 'offlineBanner.hidden = false;'
       . 'document.body.prepend(offlineBanner);'
       . '} else { offlineBanner.hidden = true; }'
       . '}'
       . 'window.addEventListener("offline", updateConnectionStatus);'
       . 'window.addEventListener("online", updateConnectionStatus);'
       . 'updateConnectionStatus();'
       . '</script>'
       . '</body></html>';
}
