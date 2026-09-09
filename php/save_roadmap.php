<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

register_shutdown_function(function () {

    $error = error_get_last();

    if ($error && in_array($error['type'], [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR
    ])) {

        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code(500);

        header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'PHP Fatal Error',
    'error' => isset($error['message']) ? $error['message'] : 'Unknown fatal error',
    'file' => isset($error['file']) ? $error['file'] : 'Unknown file',
    'line' => isset($error['line']) ? $error['line'] : 'Unknown line'
]);
        
    }
});

session_start();
/*
|--------------------------------------------------------------------------
| Database connection
|--------------------------------------------------------------------------
| save_roadmap.php:
| C:/xampp/htdocs/skillmate7/php/save_roadmap.php
|
| db_connect.php:
| C:/xampp/htdocs/skillmate7/db_connect.php
|--------------------------------------------------------------------------
*/

include __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| Check database connection
|--------------------------------------------------------------------------
*/

if (!$conn) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => mysqli_connect_error()
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check POST request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'User is not logged in'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get JSON data
|--------------------------------------------------------------------------
*/

$rawData = file_get_contents('php://input');

$payload = json_decode($rawData, true);

if (!is_array($payload)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data',
        'received' => $rawData
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Current logged-in user
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get roadmap ID
|--------------------------------------------------------------------------
*/

$roadmap_id = (int) ($payload['roadmap_id'] ?? 0);

if ($roadmap_id <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid roadmap ID'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get completed steps from JavaScript
|--------------------------------------------------------------------------
*/

$steps = $payload['steps'] ?? [];

if (!is_array($steps)) {

    $steps = [];
}


/*
|--------------------------------------------------------------------------
| Convert step IDs to integers
|--------------------------------------------------------------------------
*/

$completedSteps = [];

foreach ($steps as $step_id) {

    $step_id = (int) $step_id;

    if ($step_id > 0) {

        $completedSteps[] = $step_id;
    }
}


/*
|--------------------------------------------------------------------------
| Remove duplicate step IDs
|--------------------------------------------------------------------------
*/

$completedSteps = array_values(
    array_unique($completedSteps)
);


/*
|--------------------------------------------------------------------------
| Verify that roadmap exists
|--------------------------------------------------------------------------
*/

$roadmapCheck = mysqli_prepare(
    $conn,
    "SELECT roadmap_id
     FROM roadmaps
     WHERE roadmap_id = ?"
);

if (!$roadmapCheck) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare roadmap check query',
        'error' => mysqli_error($conn)
    ]);

    exit;
}

mysqli_stmt_bind_param(
    $roadmapCheck,
    "i",
    $roadmap_id
);

if (!mysqli_stmt_execute($roadmapCheck)) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not execute roadmap check query',
        'error' => mysqli_stmt_error($roadmapCheck)
    ]);

    exit;
}

$roadmapResult = mysqli_stmt_get_result($roadmapCheck);

if (!$roadmapResult || mysqli_num_rows($roadmapResult) === 0) {

    mysqli_stmt_close($roadmapCheck);

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Roadmap does not exist'
    ]);

    exit;
}

mysqli_stmt_close($roadmapCheck);


/*
|--------------------------------------------------------------------------
| Get steps belonging ONLY to this roadmap
|--------------------------------------------------------------------------
*/

$getSteps = mysqli_prepare(
    $conn,
    "SELECT
        step_id,
        roadmap_id,
        step_name
     FROM roadmap_steps
     WHERE roadmap_id = ?
     ORDER BY step_number ASC"
);

if (!$getSteps) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare roadmap steps query',
        'error' => mysqli_error($conn)
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $getSteps,
    "i",
    $roadmap_id
);


if (!mysqli_stmt_execute($getSteps)) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not execute roadmap steps query',
        'error' => mysqli_stmt_error($getSteps)
    ]);

    exit;
}


$result = mysqli_stmt_get_result($getSteps);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not load roadmap steps',
        'error' => mysqli_stmt_error($getSteps)
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check existing progress
|--------------------------------------------------------------------------
*/

$check = mysqli_prepare(
    $conn,
    "SELECT id
     FROM roadmap_progress
     WHERE user_id = ?
     AND roadmap_id = ?
     AND step_id = ?"
);

if (!$check) {

    mysqli_stmt_close($getSteps);

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare check query',
        'error' => mysqli_error($conn)
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Update existing progress
|--------------------------------------------------------------------------
*/

$update = mysqli_prepare(
    $conn,
    "UPDATE roadmap_progress
     SET
        step_name = ?,
        completed = ?,
        completed_at = CASE
            WHEN ? = 1 THEN NOW()
            ELSE NULL
        END
     WHERE id = ?"
);

if (!$update) {

    mysqli_stmt_close($check);
    mysqli_stmt_close($getSteps);

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare update query',
        'error' => mysqli_error($conn)
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Insert new progress
|--------------------------------------------------------------------------
*/

$insert = mysqli_prepare(
    $conn,
    "INSERT INTO roadmap_progress
    (
        user_id,
        roadmap_id,
        step_id,
        step_name,
        completed,
        completed_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        CASE
            WHEN ? = 1 THEN NOW()
            ELSE NULL
        END
    )"
);

if (!$insert) {

    mysqli_stmt_close($update);
    mysqli_stmt_close($check);
    mysqli_stmt_close($getSteps);

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Could not prepare insert query',
        'error' => mysqli_error($conn)
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Process every roadmap step
|--------------------------------------------------------------------------
*/

while ($row = mysqli_fetch_assoc($result)) {

    $step_id = (int) $row['step_id'];

    $step_name = $row['step_name'];

    /*
    |--------------------------------------------------------------------------
    | Determine completed status
    |--------------------------------------------------------------------------
    */

    $completed = in_array(
        $step_id,
        $completedSteps,
        true
    ) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Check whether progress already exists
    |--------------------------------------------------------------------------
    */

    mysqli_stmt_bind_param(
        $check,
        "iii",
        $user_id,
        $roadmap_id,
        $step_id
    );


    if (!mysqli_stmt_execute($check)) {

        mysqli_stmt_close($insert);
        mysqli_stmt_close($update);
        mysqli_stmt_close($check);
        mysqli_stmt_close($getSteps);

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Failed to check roadmap progress',
            'error' => mysqli_stmt_error($check)
        ]);

        exit;
    }


    $checkResult = mysqli_stmt_get_result($check);

    $existing = mysqli_fetch_assoc($checkResult);


    /*
    |--------------------------------------------------------------------------
    | UPDATE existing record
    |--------------------------------------------------------------------------
    */

    if ($existing) {

        $progress_id = (int) $existing['id'];


        mysqli_stmt_bind_param(
            $update,
            "siii",
            $step_name,
            $completed,
            $completed,
            $progress_id
        );


        if (!mysqli_stmt_execute($update)) {

            mysqli_stmt_close($insert);
            mysqli_stmt_close($update);
            mysqli_stmt_close($check);
            mysqli_stmt_close($getSteps);

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Failed to update roadmap progress',
                'error' => mysqli_stmt_error($update)
            ]);

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT new record
    |--------------------------------------------------------------------------
    */

    else {

        mysqli_stmt_bind_param(
            $insert,
            "iiisii",
            $user_id,
            $roadmap_id,
            $step_id,
            $step_name,
            $completed,
            $completed
        );


        if (!mysqli_stmt_execute($insert)) {

            mysqli_stmt_close($insert);
            mysqli_stmt_close($update);
            mysqli_stmt_close($check);
            mysqli_stmt_close($getSteps);

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Failed to insert roadmap progress',
                'error' => mysqli_stmt_error($insert)
            ]);

            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| Close statements
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($insert);
mysqli_stmt_close($update);
mysqli_stmt_close($check);
mysqli_stmt_close($getSteps);


/*
|--------------------------------------------------------------------------
| Success response
|--------------------------------------------------------------------------
*/

echo json_encode([
    'success' => true,
    'message' => 'Roadmap progress saved successfully',
    'user_id' => $user_id,
    'roadmap_id' => $roadmap_id,
    'completed_steps' => $completedSteps
]);

exit;