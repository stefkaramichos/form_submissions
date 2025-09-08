<?php require __DIR__ . '/auth_guard.php'; ?>
<?php
// C:\xampp\htdocs\sign\admin.php
require __DIR__ . '/db.php';

// Pull everything (newest first)
$stmt = $pdo->query("SELECT * FROM submissions ORDER BY created_at DESC, id DESC");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Submissions Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .sig-thumb { height: 40px; border:1px solid #ddd; border-radius:4px; background:#fff; }
    .table-wrap { overflow:auto; }
    .search-wrap { display:flex; gap:.5rem; width:100%; max-width:520px; }
    .search-input { flex:1; }
    .badge-muted { background:#f1f3f5; color:#495057; }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <h1 class="h4 mb-0">Submissions</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="index.html">+ New Submission</a>
      <a class="btn btn-primary" href="print_view.php?all=1" target="_blank">Print PDF (All)</a>
    </div>
  </div>

  <!-- Live Search Bar -->
  <div class="card shadow-sm mb-3">
    <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
      <div class="search-wrap">
        <input id="liveSearch" class="form-control search-input" type="search" placeholder="Search by name, email, phone, topic, ID, interests, message..." autocomplete="off">
        <button id="clearSearch" class="btn btn-outline-secondary" type="button">Clear</button>
      </div>
      <div class="text-muted">
        <span class="me-2">Showing <span id="visibleCount">0</span> of <span id="totalCount"><?= count($rows) ?></span></span>
        <span class="badge badge-muted rounded-pill">Live</span>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <p class="text-muted mb-0">No submissions yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table table-striped align-middle" id="submissionsTable">
            <thead>
              <tr>
                <th style="min-width:70px;">ID</th>
                <th style="min-width:150px;">Created</th>
                <th style="min-width:180px;">Name</th>
                <th style="min-width:200px;">Email</th>
                <th style="min-width:140px;">Phone</th>
                <th style="min-width:140px;">Topic</th>
                <th style="min-width:120px;">Signature</th>
                <th class="text-end" style="min-width:150px;">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
              <?php
                // Build a search “haystack” for quick client-side filtering
                $hay = strtolower(
                  trim($r['id'] . ' ' .
                       $r['created_at'] . ' ' .
                       $r['first_name'] . ' ' .
                       $r['last_name'] . ' ' .
                       $r['email'] . ' ' .
                       $r['phone'] . ' ' .
                       $r['topic'] . ' ' .
                       $r['contact_pref'] . ' ' .
                       ($r['interests'] ?? '') . ' ' .
                       ($r['message'] ?? ''))
                );
              ?>
              <tr data-search="<?= htmlspecialchars($hay, ENT_QUOTES) ?>">
                <td><?= htmlspecialchars($r['id']) ?></td>
                <td><small><?= htmlspecialchars($r['created_at']) ?></small></td>
                <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['phone']) ?></td>
                <td><?= htmlspecialchars($r['topic']) ?></td>
                <td>
                  <?php if (!empty($r['signature_file']) && file_exists(__DIR__ . '/' . $r['signature_file'])): ?>
                    <img class="sig-thumb" src="<?= htmlspecialchars($r['signature_file']) ?>" alt="signature">
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="print_view.php?id=<?= urlencode($r['id']) ?>" target="_blank">Print PDF</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
            <tbody id="noResultsRow" style="display:none;">
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No results match your search.</td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  const input = document.getElementById('liveSearch');
  const clearBtn = document.getElementById('clearSearch');
  const table = document.getElementById('submissionsTable');
  const rows = table ? Array.from(table.querySelectorAll('tbody:first-of-type tr')) : [];
  const noResults = document.getElementById('noResultsRow');
  const visibleCountEl = document.getElementById('visibleCount');
  const totalCountEl = document.getElementById('totalCount');

  function updateVisibleCount(count) {
    if (visibleCountEl) visibleCountEl.textContent = count;
  }
  function filter(q) {
    const query = q.trim().toLowerCase();
    let visible = 0;
    rows.forEach(tr => {
      const hay = tr.getAttribute('data-search') || '';
      const show = !query || hay.includes(query);
      tr.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (noResults) noResults.style.display = visible === 0 ? '' : 'none';
    updateVisibleCount(visible);
  }

  // Initialize counts
  if (totalCountEl) {
    updateVisibleCount(rows.length);
  }

  // Debounce input for smoother typing
  let t = null;
  input && input.addEventListener('input', (e) => {
    const val = e.target.value;
    clearTimeout(t);
    t = setTimeout(() => filter(val), 80);
  });

  clearBtn && clearBtn.addEventListener('click', () => {
    if (!input) return;
    input.value = '';
    filter('');
    input.focus();
  });
})();
</script>
</body>
</html>
