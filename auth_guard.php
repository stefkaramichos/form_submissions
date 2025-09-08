<?php
// C:\xampp\htdocs\sign\auth_guard.php
// Place `require __DIR__.'/auth_guard.php';` as the FIRST line in protected files (before any output).

session_start();

const ADMIN_PASSWORD = 'bloodycherry';

// Handle logout
if (isset($_GET['logout'])) {
  $_SESSION['is_admin'] = false;
  unset($_SESSION['is_admin']);
  // redirect to login screen (same page without query)
  $base = strtok($_SERVER['REQUEST_URI'], '?');
  header("Location: $base");
  exit;
}

// If already logged in, allow through
if (!empty($_SESSION['is_admin'])) {
  return; // let the rest of the page run
}

// Handle login POST
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
  if (hash_equals(ADMIN_PASSWORD, (string)$_POST['admin_password'])) {
    $_SESSION['is_admin'] = true;
    // Redirect back to the same URL (clears POST/avoids resubmission)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
  } else {
    $loginError = 'Invalid password.';
  }
}

// If not logged in, render a minimal login page and exit
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h1 class="h4 mb-3">Admin Login</h1>
            <?php if ($loginError): ?>
              <div class="alert alert-danger py-2"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <form method="post">
              <div class="mb-3">
                <label for="admin_password" class="form-label">Password</label>
                <input type="password" class="form-control" id="admin_password" name="admin_password" required autofocus>
              </div>
              <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </form>
            <div class="text-muted small mt-3">Protected area • Access required</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
<?php
exit;
