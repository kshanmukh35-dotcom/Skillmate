<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$senderId = (int)$_SESSION['user_id'];
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = trim($_POST['message'] ?? '');
$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : null;

if ($receiverId <= 0 || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit();
}

if ($receiverId === $senderId) {
    echo json_encode(['success' => false, 'error' => 'Cannot send message to yourself.']);
    exit();
}

// Validate receiver exists
$stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE user_id = ?');
mysqli_stmt_bind_param($stmt, 'i', $receiverId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'error' => 'Receiver not found.']);
    exit();
}
mysqli_stmt_close($stmt);

// Ensure there's an exchange request between these users
$reqStmt = mysqli_prepare($conn, 'SELECT id, sender_id, receiver_id, status FROM exchange_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1');
mysqli_stmt_bind_param($reqStmt, 'iiii', $senderId, $receiverId, $receiverId, $senderId);
mysqli_stmt_execute($reqStmt);
mysqli_stmt_store_result($reqStmt);
if (mysqli_stmt_num_rows($reqStmt) === 0) {
    mysqli_stmt_close($reqStmt);
    echo json_encode(['success' => false, 'error' => 'No exchange request exists between these users.']);
    exit();
}
mysqli_stmt_bind_result($reqStmt, $reqIdDb, $reqSender, $reqReceiver, $reqStatus);
mysqli_stmt_fetch($reqStmt);
mysqli_stmt_close($reqStmt);

// Allow if accepted, or if pending and the current user is the original sender
if ($reqStatus !== 'Accepted') {
    if ($reqStatus === 'Pending' && $reqSender === $senderId) {
        // allow sender to send initial/pending message
        $allowed = true;
    } else {
        echo json_encode(['success' => false, 'error' => 'Exchange request not accepted yet.']);
        exit();
    }
}

// Use the found request id if not provided
if (empty($requestId)) { $requestId = (int)$reqIdDb; }

$insert = mysqli_prepare($conn, 'INSERT INTO messages (request_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())');
if (!$insert) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed.']);
    exit();
}
mysqli_stmt_bind_param($insert, 'iiis', $requestId, $senderId, $receiverId, $message);
if (!mysqli_stmt_execute($insert)) {
    mysqli_stmt_close($insert);
    echo json_encode(['success' => false, 'error' => 'Unable to save message.']);
    exit();
}
mysqli_stmt_close($insert);

echo json_encode(['success' => true]);
exit();

