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
$left_with = isset($_POST['left-with']) ? trim($_POST['left-with']) : null;

$regex = "/^[a-zA-Z0-9\s.,'\"()\-!?:;]+$/";
if (!preg_match($regex, $name)) respond(false, 'Invalid characters in item name.');
if (!preg_match($regex, $desc)) respond(false, 'Invalid characters in description.');
if (contains_bad_words($name, $BAD_WORDS)) respond(false, 'Inappropriate language in item name.');
if (contains_bad_words($desc, $BAD_WORDS)) respond(false, 'Inappropriate language in description.');

// --- ENABLE IMAGE UPLOAD LOGIC ---
$image_path = null;
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
$upload_dir = __DIR__ . '/../database/image_uploaded/';
if (!is_dir($upload_dir)) {
  mkdir($upload_dir, 0777, true);
}
// Check if a file was actually uploaded
if (
    isset($_FILES['images']) &&
    isset($_FILES['images']['name']) &&
    $_FILES['images']['name'] !== '' &&
    $_FILES['images']['error'] != 4
) {
  $original_name = $_FILES['images']['name'];
  $tmp_name = $_FILES['images']['tmp_name'];
  $size = $_FILES['images']['size'];
  $error = $_FILES['images']['error'];
  if ($error === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed_exts)) {
      $mime = mime_content_type($tmp_name);
      if (in_array($mime, $allowed_mimes) && $size <= 5 * 1024 * 1024) {
        // Insert item first to get the item_id
        $stmt = $conn->prepare("INSERT INTO items (user_id, name, category, description, date_time, location, specific_location, left_with, contact_method, type, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', 1, NOW())");
        $date_time = $date . ($time ? (' ' . $time) : '');
        $stmt->bind_param('isssssssss', $user_id, $name, $category, $desc, $date_time, $location, $specific_location, $left_with, $contact, $type);
        if (!$stmt->execute()) respond(false, 'Database error: ' . $stmt->error);
        $item_id = $stmt->insert_id;
        $stmt->close();
        $unique_name = $user_id . '_' . $item_id . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original_name);
        $dest_path = $upload_dir . $unique_name;
        if (move_uploaded_file($tmp_name, $dest_path)) {
          $relative_path = 'database/image_uploaded/' . $unique_name;
          // Update the item with the image path
          $update_stmt = $conn->prepare("UPDATE items SET image = ? WHERE id = ?");
          $update_stmt->bind_param('si', $relative_path, $item_id);
          $update_stmt->execute();
          $update_stmt->close();
        }
      }
    }
  }
} else {
  // Insert item without image
  $stmt = $conn->prepare("INSERT INTO items (user_id, name, category, description, date_time, location, specific_location, left_with, contact_method, type, image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', 1, NOW())");
  $date_time = $date . ($time ? (' ' . $time) : '');
  $stmt->bind_param('isssssssss', $user_id, $name, $category, $desc, $date_time, $location, $specific_location, $left_with, $contact, $type);
  if (!$stmt->execute()) respond(false, 'Database error: ' . $stmt->error);
  $item_id = $stmt->insert_id;
  $stmt->close();
}

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
    $link = '/FoundIT/pages/view-item.html?id=' . $match_id;
    $msg_other = $type === 'lost'
      ? "Good news! Someone found an item matching your lost item: $name. <a href='$link'>View Item</a>"
      : "Someone reported losing an item matching what you found: $name. <a href='$link'>View Item</a>";
    $inbox_stmt = $conn->prepare("INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $subject_other = 'Item Match Notification';
    $inbox_stmt->bind_param('iss', $match_user_id, $subject_other, $msg_other);
    $inbox_stmt->execute();
    $inbox_stmt->close();
    // Notify the submitter as well
    $msg_submitter = $type === 'lost'
      ? "A matching found item was detected for your lost item: $name. <a href='$link'>View Item</a>"
      : "A matching lost item was detected for your found item: $name. <a href='$link'>View Item</a>";
    $inbox_stmt2 = $conn->prepare("INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $subject_submitter = 'Item Match Notification';
    $inbox_stmt2->bind_param('iss', $user_id, $subject_submitter, $msg_submitter);
    $inbox_stmt2->execute();
    $inbox_stmt2->close();
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

// 7. Inbox notification for submitter
$inbox_stmt = $conn->prepare("INSERT INTO inbox (user_id, subject, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
$subject = 'Item Submission Confirmation';
$msg = "Your item '$name' has been submitted successfully.";
$inbox_stmt->bind_param('iss', $user_id, $subject, $msg);
$inbox_stmt->execute();
$inbox_stmt->close();

respond(true, 'Item submitted successfully!'); 