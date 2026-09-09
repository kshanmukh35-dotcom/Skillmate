<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';


/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        'success' => false,
        'error' => 'You are not logged in.'
    ]);

    exit();
}


$userId =
    (int)$_SESSION['user_id'];


/* =========================================================
   GET DATA FROM TEACH PAGE
   ========================================================= */

$category =
    trim($_POST['category'] ?? '');

$skill =
    trim($_POST['skill'] ?? '');


/* =========================================================
   VALIDATION
   ========================================================= */

if ($category === '') {

    echo json_encode([
        'success' => false,
        'error' => 'Please select a category.'
    ]);

    exit();
}


if ($skill === '') {

    echo json_encode([
        'success' => false,
        'error' => 'Please enter a skill.'
    ]);

    exit();
}


/* =========================================================
   LIMIT INPUT
   ========================================================= */

if (strlen($skill) > 100) {

    echo json_encode([
        'success' => false,
        'error' => 'Skill name is too long.'
    ]);

    exit();
}


if (strlen($category) > 50) {

    echo json_encode([
        'success' => false,
        'error' => 'Invalid category.'
    ]);

    exit();
}


/* =========================================================
   CHECK DUPLICATE
   =========================================================

   Same user cannot add the same teaching skill twice.

   Example:

   User 4 → Java → Programming → teach

   User 4 trying Java again → BLOCKED

   But another user can also teach Java.
   ========================================================= */

$checkQuery = mysqli_prepare(
    $conn,

    "SELECT id
     FROM user_skills
     WHERE user_id = ?
     AND LOWER(skill_name) = LOWER(?)
     AND skill_type = 'teach'
     LIMIT 1"
);


if (!$checkQuery) {

    echo json_encode([
        'success' => false,
        'error' => 'Database error while checking skill.'
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $checkQuery,
    'is',
    $userId,
    $skill
);


mysqli_stmt_execute(
    $checkQuery
);


mysqli_stmt_store_result(
    $checkQuery
);


if (
    mysqli_stmt_num_rows(
        $checkQuery
    ) > 0
) {

    mysqli_stmt_close(
        $checkQuery
    );


    echo json_encode([
        'success' => false,
        'error' => 'You have already added this skill.'
    ]);

    exit();
}


mysqli_stmt_close(
    $checkQuery
);


/* =========================================================
   INSERT INTO user_skills
   ========================================================= */

$insertQuery = mysqli_prepare(
    $conn,

    "INSERT INTO user_skills
    (
        user_id,
        skill_name,
        category,
        skill_type
    )
    VALUES
    (
        ?,
        ?,
        ?,
        'teach'
    )"
);


if (!$insertQuery) {

    echo json_encode([
        'success' => false,
        'error' => 'Unable to prepare database query.'
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $insertQuery,
    'iss',
    $userId,
    $skill,
    $category
);


/* =========================================================
   EXECUTE INSERT
   ========================================================= */

if (
    mysqli_stmt_execute(
        $insertQuery
    )
) {

    $newSkillId =
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $insertQuery
    );


    echo json_encode([
        'success' => true,
        'id' => $newSkillId,
        'message' => 'Skill added successfully.'
    ]);


    exit();
}


/* =========================================================
   DATABASE ERROR
   ========================================================= */

$error =
    mysqli_error(
        $conn
    );


mysqli_stmt_close(
    $insertQuery
);


echo json_encode([
    'success' => false,
    'error' => 'Unable to save skill: ' . $error
]);


exit();

?>