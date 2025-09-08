<?php
// Helper to sanitize text fields
function field($key) {
  return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$first = field('first_name');
$last = field('last_name');
$email = field('email');
$phone = field('phone');
$topic = field('topic');
$contact_pref = field('contact_pref');
$message = field('message');
$terms = field('terms') ? 'Yes' : 'No';
$interests = isset($_POST['interests']) && is_array($_POST['interests']) ? implode('|', $_POST['interests']) : '';
$sigDataUrl = field('signature_data');

// Validate requireds (basic)
$errors = [];
foreach (['first_name','last_name','email','phone','topic','contact_pref','terms','signature_data'] as $req) {
  if (empty($_POST[$req])) $errors[] = "Missing: $req";
}

if (!empty($errors)) {
  http_response_code(400);
  echo "<p style='color:red;'>Form errors:<br>" . implode('<br>', array_map('htmlspecialchars',$errors)) . "</p>";
  exit;
}

// Save signature image
$sigPngPath = '';
if (strpos($sigDataUrl, 'data:image/png;base64,') === 0) {
  $base64 = substr($sigDataUrl, strlen('data:image/png;base64,'));
  $base64 = str_replace(' ', '+', $base64);
  $binary = base64_decode($base64);

  if ($binary === false) {
    echo "<p style='color:red;'>Failed to decode signature image.</p>";
    exit;
  }

  $fileName = 'signature_' . time() . '_' . bin2hex(random_bytes(3)) . '.png';
  $sigPngPath = __DIR__ . DIRECTORY_SEPARATOR . $fileName;

  if (!file_put_contents($sigPngPath, $binary)) {
    echo "<p style='color:red;'>Failed to save signature file.</p>";
    exit;
  }
} else {
  echo "<p style='color:red;'>Signature data missing or invalid.</p>";
  exit;
}

// OPTIONAL: Save form data to CSV for quick logging
$csvPath = __DIR__ . DIRECTORY_SEPARATOR . 'submissions.csv';
$headerNeeded = !file_exists($csvPath);
$fp = fopen($csvPath, 'a');
if ($fp) {
  if ($headerNeeded) {
    fputcsv($fp, ['timestamp','first_name','last_name','email','phone','topic','contact_pref','interests','terms','message','signature_file']);
  }
  fputcsv($fp, [
    date('Y-m-d H:i:s'),
    $first, $last, $email, $phone, $topic, $contact_pref, $interests, $terms, $message,
    basename($sigPngPath)
  ]);
  fclose($fp);
}

// Response page
echo "<h2>Thanks, " . htmlspecialchars($first) . "!</h2>";
echo "<p>Your submission has been saved.</p>";

echo "<h4>Summary</h4>";
echo "<ul>";
echo "<li>Name: " . htmlspecialchars($first . ' ' . $last) . "</li>";
echo "<li>Email: " . htmlspecialchars($email) . "</li>";
echo "<li>Phone: " . htmlspecialchars($phone) . "</li>";
echo "<li>Topic: " . htmlspecialchars($topic) . "</li>";
echo "<li>Preferred Contact: " . htmlspecialchars($contact_pref) . "</li>";
echo "<li>Interests: " . htmlspecialchars($interests ?: 'None') . "</li>";
echo "<li>Agreed to Terms: " . htmlspecialchars($terms) . "</li>";
echo "</ul>";

echo "<h4>Signature</h4>";
echo "<img src='" . htmlspecialchars(basename($sigPngPath)) . "' style='max-width:400px;border:1px solid #ccc;border-radius:6px;'>";

echo "<p><a href='index.html'>&laquo; Back to form</a></p>";
