<?php
// C:\xampp\htdocs\sign\print_view.php
require __DIR__ . '/db.php';

// Fetch target: single by id, or all
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$all = isset($_GET['all']) ? (int)$_GET['all'] : 0;

if ($id) {
  $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $rows = $stmt->fetchAll();
} elseif ($all) {
  $stmt = $pdo->query("SELECT * FROM submissions ORDER BY created_at DESC, id DESC");
  $rows = $stmt->fetchAll();
} else {
  $rows = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Printable Submission<?= $all ? 's' : '' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root {
      --text:#111; --muted:#666; --border:#ccc;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: var(--text);
      margin: 0;
      background: #fff;
    }
    .sheet {
      width: 210mm;  /* A4 width */
      min-height: 297mm; /* A4 height */
      padding: 18mm;
      box-sizing: border-box;
      margin: 0 auto;
      page-break-after: always;
    }
    .head {
      display:flex; justify-content:space-between; align-items:baseline; margin-bottom:12px;
      border-bottom:1px solid var(--border); padding-bottom:8px;
    }
    .head h1 { font-size:18px; margin:0; }
    .meta { color:var(--muted); font-size:12px; }
    .section { margin-top:16px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
    .label { color:var(--muted); font-size:12px; margin-bottom:2px; }
    .value { font-size:14px; }
    .sig-box { margin-top:14px; border:1px solid var(--border); border-radius:6px; padding:10px; }
    .sig-img { max-width:280px; border:1px solid var(--border); border-radius:6px; background:#fff; }
    .msg { white-space:pre-wrap; border:1px solid var(--border); border-radius:6px; padding:10px; background:#fafafa; }
    .footer { margin-top:18px; color:var(--muted); font-size:11px; text-align:center; }
    @media print {
      .no-print { display:none !important; }
      .sheet { box-shadow:none; margin:0; }
    }
    @page { size: A4; margin: 12mm; }
    .toolbar {
      position: sticky; top: 0; background: #fff; border-bottom:1px solid var(--border);
      padding: 8px 12px; display:flex; gap:8px; justify-content:flex-end;
    }
    .btn {
      border:1px solid #888; background:#f8f8f8; border-radius:6px; padding:6px 10px; cursor:pointer;
    }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button class="btn" onclick="window.print()">Print / Save as PDF</button>
    <a class="btn" href="admin.php">Back to Admin</a>
  </div>

  <?php if (empty($rows)): ?>
    <div class="sheet">
      <p>No records found.</p>
    </div>
    <script>/* no auto-print if nothing found */</script>
  <?php else: ?>
    <?php foreach ($rows as $r): ?>
      <div class="sheet">
        <div class="head">
          <h1>Submission #<?= htmlspecialchars($r['id']) ?></h1>
          <div class="meta">Created: <?= htmlspecialchars($r['created_at']) ?></div>
        </div>

        <div class="section grid">
          <div>
            <div class="label">First Name</div>
            <div class="value"><?= htmlspecialchars($r['first_name']) ?></div>
          </div>
          <div>
            <div class="label">Last Name</div>
            <div class="value"><?= htmlspecialchars($r['last_name']) ?></div>
          </div>
          <div>
            <div class="label">Email</div>
            <div class="value"><?= htmlspecialchars($r['email']) ?></div>
          </div>
          <div>
            <div class="label">Phone</div>
            <div class="value"><?= htmlspecialchars($r['phone']) ?></div>
          </div>
          <div>
            <div class="label">Topic</div>
            <div class="value"><?= htmlspecialchars($r['topic']) ?></div>
          </div>
          <div>
            <div class="label">Preferred Contact</div>
            <div class="value"><?= htmlspecialchars($r['contact_pref']) ?></div>
          </div>
          <div>
            <div class="label">Interests</div>
            <div class="value"><?= htmlspecialchars($r['interests'] ?: '—') ?></div>
          </div>
          <div>
            <div class="label">Agreed to Terms</div>
            <div class="value"><?= (!empty($r['terms']) ? 'Yes' : 'No') ?></div>
          </div>
        </div>

        <div class="section">
          <div class="label">Message</div>
          <div class="msg"><?= htmlspecialchars($r['message'] ?: '') ?></div>
        </div>

        <div class="section">
          <div class="label">Signature</div>
          <div class="sig-box">
            <?php
              $sig = $r['signature_file'];
              $sigPath = __DIR__ . '/' . $sig;
            ?>
            <?php if (!empty($sig) && file_exists($sigPath)): ?>
              <img class="sig-img" src="<?= htmlspecialchars($sig) ?>" alt="signature image">
            <?php else: ?>
              <div class="value">No signature file found.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="footer">
          Generated on <?= date('Y-m-d H:i') ?> • signapp
        </div>
      </div>
    <?php endforeach; ?>

    <script>
      // Auto-open print dialog when page loads (only when we have data)
      window.addEventListener('load', () => {
        // small delay for images to load
        setTimeout(() => window.print(), 200);
      });
    </script>
  <?php endif; ?>
</body>
</html>
