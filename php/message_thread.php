<?php
session_start();
require_once __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}
$userId = (int)$_SESSION['user_id'];
$otherId = isset($_GET['other_id']) ? (int)$_GET['other_id'] : 0;

if ($otherId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing other_id']);
    exit();
}

// Validate other user exists
$stmt = mysqli_prepare($conn, 'SELECT user_id, full_name FROM users WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $otherId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}
mysqli_stmt_bind_result($stmt, $dbUserId, $dbFullName);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Fetch messages between the two users
$query = "SELECT id, request_id, sender_id, receiver_id, message, created_at FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'iiii', $userId, $otherId, $otherId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        'id' => (int)$row['id'],
        'request_id' => isset($row['request_id']) ? (int)$row['request_id'] : null,
        'sender_id' => (int)$row['sender_id'],
        'receiver_id' => (int)$row['receiver_id'],
        'message' => $row['message'],
        'created_at' => $row['created_at']
    ];
}
mysqli_stmt_close($stmt);

echo json_encode(['success' => true, 'messages' => $messages, 'other' => ['user_id' => $dbUserId, 'full_name' => $dbFullName]]);
exit();
