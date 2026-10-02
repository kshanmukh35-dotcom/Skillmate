<?php

session_start();

header('Content-Type: application/json');


/* =========================================================
   1. CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'Please log in first.'
    ]);

    exit;
}


/* =========================================================
   2. DATABASE CONNECTION
   ========================================================= */

require_once __DIR__ . '/../db_connect.php';


/* =========================================================
   3. GET USER IDS
   ========================================================= */

$userId = (int)$_SESSION['user_id'];

$otherId = isset($_GET['other_id'])
    ? (int)$_GET['other_id']
    : 0;


if ($otherId <= 0 || $otherId === $userId) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'error' => 'Invalid conversation.'
    ]);

    exit;
}


/* =========================================================
   4. FIND LATEST EXCHANGE REQUEST
   ========================================================= */

$requestId = 0;

$requestStatus = null;


$statusStmt = mysqli_prepare(
    $conn,

    "SELECT id, status
     FROM exchange_requests
     WHERE
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)
     ORDER BY id DESC
     LIMIT 1"
);


if (!$statusStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to check connection status.'
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $statusStmt,
    'iiii',
    $userId,
    $otherId,
    $otherId,
    $userId
);


mysqli_stmt_execute($statusStmt);


mysqli_stmt_bind_result(
    $statusStmt,
    $requestIdResult,
    $statusResult
);


if (mysqli_stmt_fetch($statusStmt)) {

    $requestId = (int)$requestIdResult;

    $requestStatus = $statusResult;
}


mysqli_stmt_close($statusStmt);


/* =========================================================
   5. LOAD COMPLETE CHAT HISTORY
   ========================================================= */

$messageStmt = mysqli_prepare(
    $conn,

    "SELECT
        id,
        request_id,
        sender_id,
        receiver_id,
        message,
        created_at

     FROM messages

     WHERE
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)

     ORDER BY
        created_at ASC,
        id ASC"
);


if (!$messageStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to load messages.'
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $messageStmt,
    'iiii',
    $userId,
    $otherId,
    $otherId,
    $userId
);


mysqli_stmt_execute($messageStmt);


$result = mysqli_stmt_get_result($messageStmt);


$messages = [];


while ($row = mysqli_fetch_assoc($result)) {

    $messages[] = [

        'id' => (int)$row['id'],

        'request_id' => (int)$row['request_id'],

        'sender_id' => (int)$row['sender_id'],

        'receiver_id' => (int)$row['receiver_id'],

        'message' => $row['message'],

        'created_at' => $row['created_at']

    ];
}


mysqli_stmt_close($messageStmt);


/* =========================================================
   6. NORMALIZE STATUS
   ========================================================= */

if ($requestStatus !== null) {

    $requestStatus = trim(
        (string)$requestStatus
    );

}


/* =========================================================
   7. CHECK WHETHER MESSAGING IS ALLOWED
   ========================================================= */

$canSend = (

    $requestStatus !== null

    &&

    strcasecmp(
        $requestStatus,
        'Accepted'
    ) === 0

);


/* =========================================================
   8. RETURN JSON
   ========================================================= */

echo json_encode([

    'success' => true,

    'messages' => $messages,

    'request_id' => $requestId,

    'request_status' => $requestStatus,

    'can_send' => $canSend

]);


exit;

?>