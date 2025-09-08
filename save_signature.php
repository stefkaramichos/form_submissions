<?php
require __DIR__ . '/db.php'; // brings in $pdo

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
$terms = field('terms') ? 1 : 0;
$interests = isset($_POST['interests']) && is_array($_POST['interests']) ? implode('|', $_POST['interests']) : '';
$sigDataUrl = field('signature_data');

// Validate requireds (basic)
$errors = [];
foreach (['first_name','last_name','email','phone','topic','contact_pref','signature_data'] as $req) {
  if (empty($_POST[$req])) $errors[] = "Missing: $req";
}
if ($terms !== 1) $errors[] = "You must agree to the terms.";

if (!empty($errors)) {
  http_response_code(400);
  echo "<p style='color:red;'>Form errors:<br>" . implode('<br>', array_map('htmlspecialchars',$errors)) . "</p>";
  echo "<p><a href='index.html'>&laquo; Back</a></p>";
  exit;
}

// Save signature image to disk
$sigPngPath = '';
if (strpos($sigDataUrl, 'data:image/png;base64,') === 0) {
  $base64 = substr($sigDataUrl, strlen('data:image/png;base64,'));
  $base64 = str_replace(' ', '+', $base64);
  $binary = base64_decode($base64);

  if ($binary === false) {
    echo "<p style='color:red;'>Failed to decode signature image.</p>";
    echo "<p><a href='index.html'>&laquo; Back</a></p>";
    exit;
  }

  // Ensure the folder is writable (C:\xampp\htdocs\sign)
  $fileName = 'signature_' . time() . '_' . bin2hex(random_bytes(3)) . '.png';
  $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $fileName;

  if (!file_put_contents($fullPath, $binary)) {
    echo "<p style='color:red;'>Failed to save signature file.</p>";
    echo "<p><a href='index.html'>&laquo; Back</a></p>";
    exit;
  }
  $sigPngPath = $fileName; // store relative name (easier for <img src>)
} else {
  echo "<p style='color:red;'>Signature data missing or invalid.</p>";
  echo "<p><a href='index.html'>&laquo; Back</a></p>";
  exit;
}

// Insert into DB
try {
  $sql = "INSERT INTO submissions
          (first_name, last_name, email, phone, topic, contact_pref, interests, terms, message, signature_file)
          VALUES
          (:first_name, :last_name, :email, :phone, :topic, :contact_pref, :interests, :terms, :message, :signature_file)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':first_name'     => $first,
    ':last_name'      => $last,
    ':email'          => $email,
    ':phone'          => $phone,
    ':topic'          => $topic,
    ':contact_pref'   => $contact_pref,
    ':interests'      => $interests,
    ':terms'          => $terms,
    ':message'        => $message,
    ':signature_file' => $sigPngPath,
  ]);

  $newId = $pdo->lastInsertId();
} catch (Throwable $e) {
  http_response_code(500);
  echo "<p style='color:red;'>Failed to save to the database.</p>";
  // For debugging you could echo $e->getMessage() locally, but avoid in production
  echo "<p><a href='index.html'>&laquo; Back</a></p>";
  exit;
}

// Simple response page
echo "<h2>Thanks, " . htmlspecialchars($first) . "!</h2>";
echo "<p>Your submission has been saved (ID #" . htmlspecialchars($newId) . ").</p>";

echo "<h4>Summary</h4>";
echo "<ul>";
echo "<li>Name: " . htmlspecialchars($first . ' ' . $last) . "</li>";
echo "<li>Email: " . htmlspecialchars($email) . "</li>";
echo "<li>Phone: " . htmlspecialchars($phone) . "</li>";
echo "<li>Topic: " . htmlspecialchars($topic) . "</li>";
echo "<li>Preferred Contact: " . htmlspecialchars($contact_pref) . "</li>";
echo "<li>Interests: " . htmlspecialchars($interests ?: 'None') . "</li>";
echo "<li>Agreed to Terms: " . ($terms ? 'Yes' : 'No') . "</li>";
echo "</ul>";

echo "<h4>Signature</h4>";
echo "<img src='" . htmlspecialchars($sigPngPath) . "' style='max-width:400px;border:1px solid #ccc;border-radius:6px;'>";

echo "<p><a href='index.html'>&laquo; Back to form</a></p>";
