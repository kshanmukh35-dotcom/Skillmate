<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../db_connect.php';


/* ---------------------------------------------------------
   LOGIN CHECK
--------------------------------------------------------- */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* ---------------------------------------------------------
   ONLY POST REQUESTS
--------------------------------------------------------- */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php");
    exit();
}


/* ---------------------------------------------------------
   GET FORM VALUES
--------------------------------------------------------- */

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');

$phone = trim($_POST['phone'] ?? '');
$college = trim($_POST['college'] ?? '');

$education = trim($_POST['education'] ?? '');
$careerGoal = trim($_POST['career_goal'] ?? '');

$interests = trim($_POST['interests'] ?? '');
$aboutMe = trim($_POST['about_me'] ?? '');


/* ---------------------------------------------------------
   VALIDATION
--------------------------------------------------------- */

if (
    $firstName === '' ||
    $lastName === '' ||
    $phone === '' ||
    $college === '' ||
    $education === '' ||
    $careerGoal === ''
) {

    header("Location: ../profile.php?error=missing");
    exit();
}


/* ---------------------------------------------------------
   CHECK WHETHER PROFILE ALREADY EXISTS
--------------------------------------------------------- */

$checkStmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM profiles
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $user_id
);

mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

$existingProfile = mysqli_fetch_assoc($checkResult);

mysqli_stmt_close($checkStmt);


/* ---------------------------------------------------------
   PROFILE IMAGE
--------------------------------------------------------- */

$profileImagePath = '';

if (
    isset($_FILES['profile_image']) &&
    $_FILES['profile_image']['error'] === UPLOAD_ERR_OK
) {

    $fileTmp = $_FILES['profile_image']['tmp_name'];
    $fileName = $_FILES['profile_image']['name'];
    $fileSize = $_FILES['profile_image']['size'];

    /* Maximum 5 MB */

    if ($fileSize > 5 * 1024 * 1024) {
        header("Location: ../profile.php?error=image");
        exit();
    }

    $allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $fileTmp);
    finfo_close($fileInfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        header("Location: ../profile.php?error=image");
        exit();
    }

    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    $extension = $extensionMap[$mimeType];

    $uploadDirectory = __DIR__ . '/../uploads/profile/';

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0777, true);
    }

    $newFileName =
        'profile_' .
        $user_id .
        '_' .
        time() .
        '.' .
        $extension;

    $destination = $uploadDirectory . $newFileName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        header("Location: ../profile.php?error=image");
        exit();
    }

    $profileImagePath =
        'uploads/profile/' . $newFileName;
}


/* ---------------------------------------------------------
   SAVE PROFILE
--------------------------------------------------------- */

if ($existingProfile) {

    /*
     * Existing profile
     */

    if ($profileImagePath !== '') {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE profiles
             SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                college = ?,
                education = ?,
                career_goal = ?,
                interests = ?,
                about_me = ?,
                profile_image = ?
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "sssssssssi",
            $firstName,
            $lastName,
            $phone,
            $college,
            $education,
            $careerGoal,
            $interests,
            $aboutMe,
            $profileImagePath,
            $user_id
        );

    } else {

        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE profiles
             SET
                first_name = ?,
                last_name = ?,
                phone = ?,
                college = ?,
                education = ?,
                career_goal = ?,
                interests = ?,
                about_me = ?
             WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "ssssssssi",
            $firstName,
            $lastName,
            $phone,
            $college,
            $education,
            $careerGoal,
            $interests,
            $aboutMe,
            $user_id
        );
    }

} else {

    /*
     * New profile
     */

    if ($profileImagePath !== '') {

        $updateStmt = mysqli_prepare(
            $conn,
            "INSERT INTO profiles
            (
                user_id,
                first_name,
                last_name,
                phone,
                phone_verified,
                college,
                college_verified,
                education,
                interests,
                career_goal,
                about_me,
                profile_image
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                0,
                ?,
                ?,
                ?,
                ?,
                ?
            )"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "isssssssss",
            $user_id,
            $firstName,
            $lastName,
            $phone,
            $college,
            $education,
            $interests,
            $careerGoal,
            $aboutMe,
            $profileImagePath
        );

    } else {

        $updateStmt = mysqli_prepare(
            $conn,
            "INSERT INTO profiles
            (
                user_id,
                first_name,
                last_name,
                phone,
                phone_verified,
                college,
                college_verified,
                education,
                interests,
                career_goal,
                about_me
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                0,
                ?,
                ?,
                ?,
                ?
            )"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "issssssss",
            $user_id,
            $firstName,
            $lastName,
            $phone,
            $college,
            $education,
            $interests,
            $careerGoal,
            $aboutMe
        );
    }
}


/* ---------------------------------------------------------
   EXECUTE
--------------------------------------------------------- */

if (!$updateStmt) {
    header("Location: ../profile.php?error=db");
    exit();
}


if (!mysqli_stmt_execute($updateStmt)) {

    mysqli_stmt_close($updateStmt);

    header("Location: ../profile.php?error=db");
    exit();
}

mysqli_stmt_close($updateStmt);

$displayName = trim($firstName . ' ' . $lastName);
if ($displayName !== '') {
    $_SESSION['full_name'] = $displayName;

    $userUpdateStmt = mysqli_prepare(
        $conn,
        "UPDATE users SET full_name = ? WHERE user_id = ?"
    );

    if ($userUpdateStmt) {
        mysqli_stmt_bind_param($userUpdateStmt, 'si', $displayName, $user_id);
        mysqli_stmt_execute($userUpdateStmt);
        mysqli_stmt_close($userUpdateStmt);
    }
}

/* ---------------------------------------------------------
   SUCCESS
--------------------------------------------------------- */

header("Location: ../profile.php?success=1");
exit();

?>