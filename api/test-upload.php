<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'PHP is working!']);

file_put_contents(__DIR__ . '/../debug.log', "Reached point X\n", FILE_APPEND); 

file_put_contents(__DIR__ . '/../debug.log', "Before move_uploaded_file\n", FILE_APPEND);
if (!move_uploaded_file($tmp, $dest)) {
    file_put_contents(__DIR__ . '/../debug.log', "Failed to move file\n", FILE_APPEND);
    respond(false, 'Failed to upload image.');
}
file_put_contents(__DIR__ . '/../debug.log', "After move_uploaded_file\n", FILE_APPEND); 