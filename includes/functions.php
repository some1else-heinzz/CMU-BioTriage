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
function icon(string $name): string {
    $icons = [
        'pulse' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l2-5 4 10 2-5h6"/></svg>',
        'clipboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 9h6M9 13h6M9 17h4"/></svg>',
        'handoff' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h8M8 11h5M16 15l3 3-3 3M8 18h11"/></svg>',
        'medical' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 2.75A2.75 2.75 0 0 0 6.25 5.5V6H5A3 3 0 0 0 2 9v9a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V9a3 3 0 0 0-3-3h-1.25v-.5A2.75 2.75 0 0 0 15 2.75H9zm.25 2.5A.25.25 0 0 1 9.5 5h5a.25.25 0 0 1 .25.25V6H9.25v-.75zM12 9.5a1 1 0 0 1 1 1v2.25h2.25a1 1 0 1 1 0 2H13v2.25a1 1 0 1 1-2 0v-2.25H8.75a1 1 0 1 1 0-2H11v-2.25a1 1 0 0 1 1-1z"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16.5 9"/></svg>',
        'admin' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V20h-2.6v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1L7.6 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H6v-2.6h.5a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1L9.4 6l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V5H15v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 8l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.1v2.6h-.1a1.7 1.7 0 0 0-1.5 1.4z"/></svg>',
        'testing' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6M10 3v5l-5 9a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-9V3"/><path d="M7.5 15h9"/></svg>',
        'building' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V5h14v16M9 9h2M13 9h2M9 13h2M13 13h2M9 17h2M13 17h2"/></svg>',
        'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a2 2 0 0 0 2 2h2l10 4V5L7 9H5a2 2 0 0 0-2 2zM7 19l1-4M18 8a4 4 0 0 1 0 8"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1.15 9.72V6.5a1.15 1.15 0 1 0-2.3 0v5.8c0 .31.13.61.35.82l3.6 3.4a1.15 1.15 0 1 0 1.58-1.67l-3.23-3.13z"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6"/><path d="m16 16 5 5"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.6 1.9z"/></svg>',
    ];
    return $icons[$name] ?? $icons['pulse'];
}
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
       . '<title>' . h($title) . ' | CMU Snakebite Response Support</title><link rel="stylesheet" href="' . h(url('assets/style.css')) . '?v=20261122"></head><body>'
       . '<header><div class="brand-row"><div class="brand-lockup"><div class="brand-mark" aria-hidden="true">' . icon('medical') . '</div>'
       . '<div><p class="site">CMU Snakebite Response Support</p><h1>' . h($title) . '</h1>'
       . '<p class="page-subtitle">' . ($active === 'board' ? 'Real-time availability and facility information' : 'Snakebite response support') . '</p></div></div>'
       . '<span class="system-status" id="system-status"><span></span><b>System Online</b></span></div>'
       . '<nav aria-label="Main"><a href="' . h(url('index.php')) . '"' . ($active === 'board' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('pulse') . '</span><span>Antivenom status</span></a>';
    if (is_staff()) echo '<a href="' . h(url('staff/confirm.php')) . '"' . ($active === 'confirm' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('clipboard') . '</span><span>Log confirmation</span></a>';
    if (is_staff()) echo '<a href="' . h(url('staff/testing.php')) . '"' . ($active === 'testing' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('testing') . '</span><span>Testing and changes</span></a>';
    if (is_admin()) echo '<a href="' . h(url('staff/admin.php')) . '"' . ($active === 'admin' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('admin') . '</span><span>Admin page</span></a>';
    echo $u
        ? '<form method="post" action="' . h(url('logout.php')) . '"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><button>Log out (' . h($u['name']) . ')</button></form>'
        : '<a href="' . h(url('login.php')) . '">Staff log in</a>';
    echo '<a href="' . h(url('handoff.php')) . '"' . ($active === 'handoff' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('handoff') . '</span><span>Handoff summary</span></a>';
    echo '<a href="' . h(url('first_aid.php')) . '"' . ($active === 'first-aid' ? ' aria-current="page"' : '') . '><span class="nav-icon" aria-hidden="true">' . icon('medical') . '</span><span>First aid and contacts</span></a>';
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
       . 'const systemStatus = document.getElementById("system-status");'
       . 'function updateSystemStatus() {'
       . 'if (!systemStatus) return;'
       . 'systemStatus.classList.toggle("offline", !navigator.onLine);'
       . 'const label = systemStatus.querySelector("b");'
       . 'if (label) label.textContent = navigator.onLine ? "System Online" : "Offline Mode";'
       . '}'
       . 'window.addEventListener("offline", updateSystemStatus);'
       . 'window.addEventListener("online", updateSystemStatus);'
       . 'updateSystemStatus();'
       . '</script>'
       . '</body></html>';
}
