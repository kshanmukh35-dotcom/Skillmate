<?php

session_start();
require_once __DIR__ . '/../db_connect.php';

/* -------------------------------------------------
   1. Check request method
------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../learn.php?error=' . rawurlencode('Invalid request method.'));
    exit();
}

/* -------------------------------------------------
   2. Check login
------------------------------------------------- */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$senderId = (int) $_SESSION['user_id'];

/* -------------------------------------------------
   3. Get form data
------------------------------------------------- */
$receiverId      = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
$teacherName     = trim($_POST['teacher_name'] ?? '');
$skillToLearn    = trim($_POST['skill_to_learn'] ?? '');
$skillICanTeach  = trim($_POST['skill_i_can_teach'] ?? '');
$preferredDate   = trim($_POST['preferred_date'] ?? '');
$preferredTime   = trim($_POST['preferred_time'] ?? '');
$message         = trim($_POST['message'] ?? '');

/* -------------------------------------------------
   4. Validate required fields
------------------------------------------------- */
if (
    $receiverId <= 0 ||
    $teacherName === '' ||
    $skillToLearn === '' ||
    $skillICanTeach === '' ||
    $preferredDate === '' ||
    $preferredTime === ''
) {
    header(
        'Location: ../learn.php?error=' .
        rawurlencode('Please complete all required fields before sending your request.')
    );
    exit();
}

/* -------------------------------------------------
   5. Prevent sending request to yourself
------------------------------------------------- */
if ($receiverId === $senderId) {
    header(
        'Location: ../learn.php?error=' .
        rawurlencode('You cannot send a request to yourself.')
    );
    exit();
}

/* -------------------------------------------------
   6. Verify receiver exists
------------------------------------------------- */
$stmt = mysqli_prepare(
    $conn,
    'SELECT user_id, full_name FROM users WHERE user_id = ?'
);

if (!$stmt) {
    header(
        'Location: ../learn.php?error=' .
        rawurlencode('Database error while checking the selected user.')
    );
    exit();
}

mysqli_stmt_bind_param($stmt, 'i', $receiverId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    header(
        'Location: ../learn.php?error=' .
        rawurlencode('Selected teacher was not found.')
    );
    exit();
}

/* Use the real name from users table */
$teacherName = $user['full_name'];

/* -------------------------------------------------
   7. Start transaction
------------------------------------------------- */
mysqli_begin_transaction($conn);

try {

    /* -------------------------------------------------
       8. Insert exchange request
    ------------------------------------------------- */
    $insertRequest = mysqli_prepare(
        $conn,
        "INSERT INTO exchange_requests
        (
            sender_id,
            receiver_id,
            teacher_name,
            skill_to_learn,
            skill_i_can_teach,
            preferred_date,
            preferred_time,
            message,
            status,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
    );

    if (!$insertRequest) {
        throw new Exception('Could not prepare exchange request.');
    }

    mysqli_stmt_bind_param(
        $insertRequest,
        'iissssss',
        $senderId,
        $receiverId,
        $teacherName,
        $skillToLearn,
        $skillICanTeach,
        $preferredDate,
        $preferredTime,
        $message
    );

    if (!mysqli_stmt_execute($insertRequest)) {
        throw new Exception('Could not save exchange request.');
    }

    /* Get the newly created request ID */
    $requestId = mysqli_insert_id($conn);

    mysqli_stmt_close($insertRequest);


    /* -------------------------------------------------
       9. Create initial message
    ------------------------------------------------- */
    $initialMessage =
        "Hi " . $teacherName .
        ", I would like to exchange skills with you. " .
        "I want to learn " . $skillToLearn .
        " and I can teach " . $skillICanTeach .
        ". Preferred date: " . $preferredDate .
        ", Preferred time: " . $preferredTime .
        ". Message: " . $message;

    $insertMessage = mysqli_prepare(
        $conn,
        "INSERT INTO messages
        (
            request_id,
            sender_id,
            receiver_id,
            message,
            created_at
        )
        VALUES (?, ?, ?, ?, NOW())"
    );

    if (!$insertMessage) {
        throw new Exception('Could not prepare message.');
    }

    mysqli_stmt_bind_param(
        $insertMessage,
        'iiis',
        $requestId,
        $senderId,
        $receiverId,
        $initialMessage
    );

    if (!mysqli_stmt_execute($insertMessage)) {
        throw new Exception('Could not save message.');
    }

    mysqli_stmt_close($insertMessage);


    /* -------------------------------------------------
       10. Everything succeeded
    ------------------------------------------------- */
    mysqli_commit($conn);

    header('Location: ../learn.php?success=1');
    exit();

} catch (Exception $e) {

    /* Something failed → undo everything */
    mysqli_rollback($conn);

    header(
        'Location: ../learn.php?error=' .
        rawurlencode('Unable to send the exchange request. Please try again.')
    );

    exit();
}
?>