<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connection.php';

// Helper: bad words list
$BAD_WORDS = [
  'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'crap', 'dick', 'piss', 'cunt', 'fag', 'slut', 'whore', 'nigger', 'fucker', 'motherfucker', 'cock', 'pussy', 'ass', 'retard', 'douche', 'bollocks', 'bugger', 'bloody', 'arse', 'wank', 'twat', 'prick', 'tit', 'tosser', 'wanker', 'jerk', 'idiot', 'moron', 'stupid'
];
function contains_bad_words($str, $bad_words) {
  $str = strtolower($str);
  foreach ($bad_words as $word) {
    if (strpos($str, $word) !== false) return true;
  }
  return false;
}

// Helper: respond JSON and exit
function respond($success, $message) {
  echo json_encode(['success' => $success, 'message' => $message]);
  exit;
}

// 1. Check session
if (!isset($_SESSION['user_id'])) {
  respond(false, 'You must be logged in to submit an item.');
}
$user_id = $_SESSION['user_id'];

// 2. Validate fields
$required = ['item-name', 'category', 'description', 'date', 'location', 'contact-preference', 'type'];
foreach ($required as $field) {
  if (empty($_POST[$field])) {
    respond(false, 'All required fields must be filled.');
  }
}
$name = trim($_POST['item-name']);
$category = trim($_POST['category']);
$desc = trim($_POST['description']);
$date = trim($_POST['date']);
$time = isset($_POST['time']) ? trim($_POST['time']) : null;
$location = trim($_POST['location']);
$specific_location = isset($_POST['specific-location']) ? trim($_POST['specific-location']) : null;
$contact = trim($_POST['contact-preference']);
$type = trim($_POST['type']);

$regex = "/^[a-zA-Z0-9\s.,'\"()\-!?:;]+$/";
if (!preg_match($regex, $name)) respond(false, 'Invalid characters in item name.');
if (!preg_match($regex, $desc)) respond(false, 'Invalid characters in description.');
if (contains_bad_words($name, $BAD_WORDS)) respond(false, 'Inappropriate language in item name.');
if (contains_bad_words($desc, $BAD_WORDS)) respond(false, 'Inappropriate language in description.');

// --- ENABLE IMAGE UPLOAD LOGIC ---
$uploaded_files = [];
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
$upload_dir = __DIR__ . '/../web/uploads/';
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0777, true);
}
if (isset($_FILES['images']) && $_FILES['images']['error'][0] != 4) {
  if (count($_FILES['images']['name']) > 5) respond(false, 'You can upload up to 5 images only.');
  foreach ($_FILES['images']['name'] as $i => $original_name) {
    $tmp_name = $_FILES['images']['tmp_name'][$i];
    $size = $_FILES['images']['size'][$i];
    $error = $_FILES['images']['error'][$i];
    if ($error !== UPLOAD_ERR_OK) continue;
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts)) continue;
    $mime = mime_content_type($tmp_name);
    if (!in_array($mime, $allowed_mimes)) continue;
    if ($size > 5 * 1024 * 1024) continue;
    $unique_name = uniqid('img_', true) . '.' . $ext;
    $dest_path = $upload_dir . $unique_name;
    if (move_uploaded_file($tmp_name, $dest_path)) {
      $relative_path = 'web/uploads/' . $unique_name;
      $uploaded_files[] = $relative_path;
    }
  }
}
$images_json = json_encode($uploaded_files);

// 4. Insert into items table
$stmt = $conn->prepare("INSERT INTO items (user_id, name, category, description, date_time, location, specific_location, contact_method, type, images, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
$date_time = $date . ($time ? (' ' . $time) : '');
$stmt->bind_param('isssssssss', $user_id, $name, $category, $desc, $date_time, $location, $specific_location, $contact, $type, $images_json);
if (!$stmt->execute()) respond(false, 'Database error: ' . $stmt->error);
$item_id = $stmt->insert_id;

// 5. Check for matches (simple: same name, category, location, and opposite type)
$match_type = $type === 'lost' ? 'found' : 'lost';
$match_stmt = $conn->prepare("SELECT id, user_id FROM items WHERE name = ? AND category = ? AND location = ? AND type = ? AND status = 1");
$match_stmt->bind_param('ssss', $name, $category, $location, $match_type);
$match_stmt->execute();
$match_stmt->store_result();
if ($match_stmt->num_rows > 0) {
  $match_stmt->bind_result($match_id, $match_user_id);
  while ($match_stmt->fetch()) {
    // Notify the other user
    $msg = $type === 'lost'
      ? "Good news! Someone found an item matching your lost item: $name. Please check the details."
      : "Someone reported losing an item matching what you found: $name. Please check the details.";
    $inbox_stmt = $conn->prepare("INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $subject = 'Item Match Notification';
    $inbox_stmt->bind_param('iss', $match_user_id, $subject, $msg);
    $inbox_stmt->execute();
    $inbox_stmt->close();
  }
}
$match_stmt->close();

// 6. Log to activity_log
$action = 'submit_item';
$details = "Submitted $type item: $name";
$log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, item_id, details, created_at) VALUES (?, ?, ?, ?, NOW())");
$log_stmt->bind_param('isis', $user_id, $action, $item_id, $details);
$log_stmt->execute();
$log_stmt->close();

respond(true, 'Item submitted successfully!'); 