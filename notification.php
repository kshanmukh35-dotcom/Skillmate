<?php

session_start();

require_once __DIR__ . '/db_connect.php';


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: login.php');
    exit();
}


$userId = (int) $_SESSION['user_id'];

$userName = $_SESSION['full_name'] ?? 'Student';


/*
|--------------------------------------------------------------------------
| NOTIFICATION ARRAY
|--------------------------------------------------------------------------
*/

$notifications = [];


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function addNotification(
    &$notifications,
    $type,
    $title,
    $message,
    $time,
    $link,
    $icon,
    $label
) {

    $notifications[] = [

        'type'    => $type,
        'title'   => $title,
        'message' => $message,
        'time'    => $time,
        'link'    => $link,
        'icon'    => $icon,
        'label'   => $label
    ];
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatNotificationTime($date)
{

    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '';
    }

    $now = time();

    $difference = $now - $timestamp;


    /*
     * Less than one minute
     */

    if ($difference < 60) {
        return 'Just now';
    }


    /*
     * Minutes
     */

    if ($difference < 3600) {

        $minutes = floor($difference / 60);

        return $minutes . ' min ago';
    }


    /*
     * Hours
     */

    if ($difference < 86400) {

        $hours = floor($difference / 3600);

        return $hours . ' hr ago';
    }


    /*
     * Yesterday
     */

    if ($difference < 172800) {

        return 'Yesterday';
    }


    /*
     * Older
     */

    return date('d M Y, h:i A', $timestamp);
}


/*
|--------------------------------------------------------------------------
| 1. MESSAGE NOTIFICATIONS
|--------------------------------------------------------------------------
|
| Messages received by the current logged-in user.
|
*/

$messageSql = "
    SELECT
        m.id AS message_id,
        m.sender_id,
        m.receiver_id,
        m.message AS message_text,
        m.created_at AS message_created_at,
        u.full_name AS sender_name

    FROM messages AS m

    LEFT JOIN users AS u
        ON u.user_id = m.sender_id

    WHERE m.receiver_id = ?

    ORDER BY m.created_at DESC
";


$messageStmt = mysqli_prepare(
    $conn,
    $messageSql
);


if ($messageStmt) {

    mysqli_stmt_bind_param(
        $messageStmt,
        'i',
        $userId
    );

    mysqli_stmt_execute(
        $messageStmt
    );

    $messageResult =
        mysqli_stmt_get_result(
            $messageStmt
        );


    if ($messageResult) {

        while (
            $messageRow =
            mysqli_fetch_assoc(
                $messageResult
            )
        ) {

            $senderName =
                trim(
                    $messageRow['sender_name'] ?? ''
                );


            if ($senderName === '') {
                $senderName = 'SkillMate user';
            }


            $messageText =
                trim(
                    $messageRow['message_text'] ?? ''
                );


            if ($messageText === '') {

                $messageText =
                    'You have received a new message.';
            }


            /*
             * Keep message preview short
             */

            if (
                mb_strlen($messageText) > 120
            ) {

                $messageText =
                    mb_substr(
                        $messageText,
                        0,
                        120
                    ) . '...';
            }


            addNotification(

                $notifications,

                'message',

                'New message from ' . $senderName,

                $messageText,

                formatNotificationTime(
                    $messageRow['message_created_at']
                ),

                'messages.php',

                '💬',

                'Message'
            );
        }
    }


    mysqli_stmt_close(
        $messageStmt
    );
}


/*
|--------------------------------------------------------------------------
| 2. EXCHANGE REQUEST NOTIFICATIONS
|--------------------------------------------------------------------------
|
| exchange_requests structure:
|
| id
| sender_id
| receiver_id
| teacher_name
| skill_to_learn
| skill_i_can_teach
| preferred_date
| preferred_time
| message
| status
| created_at
|
*/


$requestSql = "
    SELECT

        r.id AS request_id,

        r.sender_id,

        r.receiver_id,

        r.teacher_name,

        r.skill_to_learn,

        r.skill_i_can_teach,

        r.preferred_date,

        r.preferred_time,

        r.message AS request_message,

        r.status AS request_status,

        r.created_at AS request_created_at,

        sender.full_name AS sender_full_name,

        receiver.full_name AS receiver_full_name

    FROM exchange_requests AS r

    LEFT JOIN users AS sender
        ON sender.user_id = r.sender_id

    LEFT JOIN users AS receiver
        ON receiver.user_id = r.receiver_id

    WHERE
        r.sender_id = ?
        OR r.receiver_id = ?

    ORDER BY r.created_at DESC
";


$requestStmt = mysqli_prepare(
    $conn,
    $requestSql
);


if ($requestStmt) {

    mysqli_stmt_bind_param(
        $requestStmt,
        'ii',
        $userId,
        $userId
    );

    mysqli_stmt_execute(
        $requestStmt
    );

    $requestResult =
        mysqli_stmt_get_result(
            $requestStmt
        );


    if ($requestResult) {

        while (
            $request =
            mysqli_fetch_assoc(
                $requestResult
            )
        ) {

            $senderId =
                (int) $request['sender_id'];

            $receiverId =
                (int) $request['receiver_id'];

            $status =
                strtolower(
                    trim(
                        $request['request_status'] ?? ''
                    )
                );


            $senderName =
                trim(
                    $request['sender_full_name'] ?? ''
                );


            $receiverName =
                trim(
                    $request['receiver_full_name'] ?? ''
                );


            $skillToLearn =
                trim(
                    $request['skill_to_learn'] ?? ''
                );


            $skillCanTeach =
                trim(
                    $request['skill_i_can_teach'] ?? ''
                );


            /*
             * ----------------------------------------------------------
             * INCOMING REQUEST
             * ----------------------------------------------------------
             *
             * User A -> Current User
             */

            if (
                $receiverId === $userId &&
                $status === 'pending'
            ) {

                $message =
                    $senderName .
                    ' sent you a skill exchange request.';


                if ($skillToLearn !== '') {

                    $message .=
                        ' Wants to learn: ' .
                        $skillToLearn . '.';
                }


                addNotification(

                    $notifications,

                    'request',

                    'New skill exchange request',

                    $message,

                    formatNotificationTime(
                        $request['request_created_at']
                    ),

                    'requests.php?id=' .
                    (int) $request['request_id'],

                    '📥',

                    'Request'
                );
            }


            /*
             * ----------------------------------------------------------
             * OUTGOING PENDING REQUEST
             * ----------------------------------------------------------
             *
             * Current User -> User B
             */

            elseif (
                $senderId === $userId &&
                $status === 'pending'
            ) {

                $message =
                    'Your skill exchange request was sent to ' .
                    $receiverName . '.';


                if ($skillToLearn !== '') {

                    $message .=
                        ' Skill: ' .
                        $skillToLearn . '.';
                }


                addNotification(

                    $notifications,

                    'request',

                    'Request sent',

                    $message,

                    formatNotificationTime(
                        $request['request_created_at']
                    ),

                    'requests.php?id=' .
                    (int) $request['request_id'],

                    '📤',

                    'Request'
                );
            }


            /*
             * ----------------------------------------------------------
             * ACCEPTED REQUEST
             * ----------------------------------------------------------
             *
             * Current User sent the request
             * and User B accepted it.
             */

            elseif (
                $senderId === $userId &&
                $status === 'accepted'
            ) {

                $message =
                    $receiverName .
                    ' accepted your skill exchange request.';


                if ($skillToLearn !== '') {

                    $message .=
                        ' You can now start learning ' .
                        $skillToLearn . '.';
                }


                addNotification(

                    $notifications,

                    'request',

                    'Request accepted',

                    $message,

                    formatNotificationTime(
                        $request['request_created_at']
                    ),

                    'requests.php?id=' .
                    (int) $request['request_id'],

                    '✅',

                    'Request'
                );
            }


            /*
             * ----------------------------------------------------------
             * ACCEPTED INCOMING REQUEST
             * ----------------------------------------------------------
             *
             * Current User accepted a request from User A.
             *
             */

            elseif (
                $receiverId === $userId &&
                $status === 'accepted'
            ) {

                $message =
                    'Your skill exchange with ' .
                    $senderName .
                    ' is now accepted.';


                addNotification(

                    $notifications,

                    'request',

                    'Skill exchange accepted',

                    $message,

                    formatNotificationTime(
                        $request['request_created_at']
                    ),

                    'requests.php?id=' .
                    (int) $request['request_id'],

                    '🤝',

                    'Request'
                );
            }


            /*
             * ----------------------------------------------------------
             * REJECTED REQUEST
             * ----------------------------------------------------------
             */

            elseif (
                $senderId === $userId &&
                $status === 'rejected'
            ) {

                $message =
                    $receiverName .
                    ' rejected your skill exchange request.';


                addNotification(

                    $notifications,

                    'request',

                    'Request rejected',

                    $message,

                    formatNotificationTime(
                        $request['request_created_at']
                    ),

                    'requests.php?id=' .
                    (int) $request['request_id'],

                    '❌',

                    'Request'
                );
            }
        }
    }


    mysqli_stmt_close(
        $requestStmt
    );
}


/*
|--------------------------------------------------------------------------
| 3. DAILY LEARNING MOTIVATION
|--------------------------------------------------------------------------
|
| One motivation notification per day.
|
| This does not require a database table.
|
*/


$motivations = [

    'Small progress every day creates big results. Keep learning!',

    'Your future skills are built by what you learn today.',

    'Do not wait for motivation. Start learning and motivation will follow.',

    'One new concept learned today is one step closer to your goal.',

    'Keep going! Every hour you invest in learning makes you stronger.',

    'Learn something today that your future self will thank you for.',

    'Consistency is more powerful than perfection. Keep learning!',

    'Your skills grow when you practice, not when you only read.',

    'Stay curious. Ask questions. Practice. Improve.',

    'A focused learning session today can change your tomorrow.',

    'You do not need to learn everything today. Just learn something.',

    'Keep building your knowledge one skill at a time.'

];


/*
|--------------------------------------------------------------------------
| Choose one motivation based on today's date
|--------------------------------------------------------------------------
|
| This guarantees one motivation entry for the current day.
|
*/

$dayNumber =
    (int) date('z');

$motivationIndex =
    $dayNumber %
    count($motivations);

$todayMotivation =
    $motivations[$motivationIndex];


/*
|--------------------------------------------------------------------------
| Add motivation notification
|--------------------------------------------------------------------------
*/

addNotification(

    $notifications,

    'motivation',

    'Today’s learning motivation',

    $todayMotivation,

    'Today',

    '#',

    '🌟',

    'Daily'
);


/*
|--------------------------------------------------------------------------
| SORT NOTIFICATIONS
|--------------------------------------------------------------------------
|
| Keep today's motivation visible near the top.
|
*/

usort(
    $notifications,
    function ($a, $b) {

        if (
            $a['type'] === 'motivation'
        ) {

            return -1;
        }

        if (
            $b['type'] === 'motivation'
        ) {

            return 1;
        }

        return 0;
    }
);


/*
|--------------------------------------------------------------------------
| COUNT
|--------------------------------------------------------------------------
*/

$notificationCount =
    count($notifications);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>SkillMate — Notifications</title>


<style>

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

:root {

    --primary: #4f46e5;

    --primary-dark: #3730a3;

    --green: #22c55e;

    --green-dark: #16a34a;

    --background: #f5f7ff;

    --surface: #ffffff;

    --surface-soft: #f8fbff;

    --text: #14213d;

    --muted: #64748b;

    --border: rgba(15, 23, 42, 0.08);

    --shadow:
        0 18px 60px
        rgba(15, 23, 42, 0.08);
}


/*
|--------------------------------------------------------------------------
| GLOBAL
|--------------------------------------------------------------------------
*/

* {

    box-sizing: border-box;
}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #eef6ff 100%
        );

    color: var(--text);

    -webkit-font-smoothing: antialiased;
}


a {

    color: inherit;

    text-decoration: none;
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

.page {

    width: 100%;

    min-height: 100vh;

    padding: 38px 5%;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.header {

    width: 100%;

    max-width: 1450px;

    margin: 0 auto 28px;

    background:
        rgba(255, 255, 255, 0.96);

    border:
        1px solid
        rgba(15, 23, 42, 0.06);

    border-radius: 28px;

    padding: 24px 32px;

    box-shadow: var(--shadow);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;
}


/*
|--------------------------------------------------------------------------
| BRAND
|--------------------------------------------------------------------------
*/

.brand {

    display: flex;

    align-items: center;

    gap: 18px;

    min-width: 0;
}


.logo {

    width: 62px;

    height: 62px;

    border-radius: 18px;

    overflow: hidden;

    background: white;

    box-shadow:
        0 12px 28px
        rgba(79,70,229,.12);

    flex-shrink: 0;
}


.logo img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.brandText h1 {

    margin: 0;

    font-size: 28px;

    line-height: 1.1;

    font-weight: 800;
}


.brandText p {

    margin: 6px 0 0;

    color: var(--muted);

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| NAVIGATION
|--------------------------------------------------------------------------
*/

.nav {

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 7px;

    background: #f8fafc;

    border:
        1px solid
        rgba(15, 23, 42, .06);

    border-radius: 18px;
}


.nav a {

    padding: 11px 16px;

    border-radius: 13px;

    color: var(--muted);

    font-size: 14px;

    font-weight: 750;

    transition:
        .15s ease;
}


.nav a:hover {

    background: white;

    color: var(--primary);
}


.nav a.active {

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #f0fdf4
        );

    color: var(--primary);

    box-shadow:
        0 5px 14px
        rgba(79,70,229,.07);
}


/*
|--------------------------------------------------------------------------
| PROFILE CHIP
|--------------------------------------------------------------------------
*/

.profileChip {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 8px 12px;

    border-radius: 16px;

    background: #fafbff;

    border:
        1px solid
        rgba(79,70,229,.08);
}


.profileAvatar {

    width: 42px;

    height: 42px;

    display: grid;

    place-items: center;

    border-radius: 50%;

    color: white;

    font-weight: 800;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );
}


.profileInfo strong {

    display: block;

    font-size: 14px;
}


.profileInfo span {

    display: block;

    margin-top: 2px;

    color: var(--muted);

    font-size: 12px;
}


/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/

.main {

    width: 100%;

    max-width: 1450px;

    margin: 0 auto;
}


/*
|--------------------------------------------------------------------------
| TITLE CARD
|--------------------------------------------------------------------------
*/

.titleCard {

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #f0fdf4
        );

    border:
        1px solid
        rgba(79,70,229,.08);

    border-radius: 28px;

    padding: 30px;

    margin-bottom: 24px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;
}


.titleLeft h2 {

    margin: 0;

    font-size: 32px;

    line-height: 1.15;
}


.titleLeft p {

    margin: 9px 0 0;

    color: var(--muted);

    line-height: 1.7;

    max-width: 700px;
}


.countBadge {

    min-width: 58px;

    height: 58px;

    display: grid;

    place-items: center;

    border-radius: 18px;

    background: white;

    color: var(--primary);

    font-size: 20px;

    font-weight: 850;

    box-shadow:
        0 10px 25px
        rgba(15,23,42,.06);
}


/*
|--------------------------------------------------------------------------
| NOTIFICATION LIST
|--------------------------------------------------------------------------
*/

.notificationList {

    display: grid;

    gap: 14px;
}


/*
|--------------------------------------------------------------------------
| NOTIFICATION CARD
|--------------------------------------------------------------------------
*/

.notification {

    position: relative;

    display: flex;

    align-items: flex-start;

    gap: 17px;

    padding: 21px;

    background: white;

    border:
        1px solid
        rgba(15,23,42,.07);

    border-radius: 21px;

    box-shadow:
        0 10px 35px
        rgba(15,23,42,.045);

    transition:
        transform .15s ease,
        box-shadow .15s ease,
        border-color .15s ease;
}


.notification:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 18px 40px
        rgba(15,23,42,.08);

    border-color:
        rgba(79,70,229,.16);
}


/*
|--------------------------------------------------------------------------
| ICON
|--------------------------------------------------------------------------
*/

.notificationIcon {

    width: 52px;

    height: 52px;

    flex-shrink: 0;

    display: grid;

    place-items: center;

    border-radius: 16px;

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #f0fdf4
        );

    font-size: 23px;
}


/*
|--------------------------------------------------------------------------
| CONTENT
|--------------------------------------------------------------------------
*/

.notificationContent {

    flex: 1;

    min-width: 0;
}


.notificationTop {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}


.notificationTitle {

    margin: 0;

    font-size: 16px;

    font-weight: 800;

    color: var(--text);
}


.notificationTime {

    flex-shrink: 0;

    color: #94a3b8;

    font-size: 12px;

    font-weight: 600;
}


.notificationMessage {

    margin: 7px 0 11px;

    color: var(--muted);

    line-height: 1.65;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| LABEL
|--------------------------------------------------------------------------
*/

.notificationLabel {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 9px;

    background: #f8fafc;

    color: var(--primary);

    font-size: 11px;

    font-weight: 800;

    border:
        1px solid
        rgba(79,70,229,.08);
}


/*
|--------------------------------------------------------------------------
| CLICK ARROW
|--------------------------------------------------------------------------
*/

.notificationArrow {

    width: 38px;

    height: 38px;

    flex-shrink: 0;

    display: grid;

    place-items: center;

    border-radius: 12px;

    background: #f8fafc;

    color: var(--primary);

    font-size: 18px;

    transition: .15s ease;
}


.notification:hover
.notificationArrow {

    background: #eef2ff;

    transform: translateX(2px);
}


/*
|--------------------------------------------------------------------------
| MOTIVATION
|--------------------------------------------------------------------------
*/

.notification.motivation {

    border-color:
        rgba(34,197,94,.18);

    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f5fff8
        );
}


.notification.motivation
.notificationIcon {

    background:
        linear-gradient(
            135deg,
            #dcfce7,
            #ecfdf5
        );
}


/*
|--------------------------------------------------------------------------
| REQUEST
|--------------------------------------------------------------------------
*/

.notification.request
.notificationIcon {

    background:
        linear-gradient(
            135deg,
            #eef2ff,
            #f5f3ff
        );
}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

.notification.message
.notificationIcon {

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #eef2ff
        );
}


/*
|--------------------------------------------------------------------------
| EMPTY STATE
|--------------------------------------------------------------------------
*/

.empty {

    background: white;

    border:
        1px solid
        rgba(15,23,42,.07);

    border-radius: 24px;

    padding: 55px 25px;

    text-align: center;

    box-shadow: var(--shadow);
}


.emptyIcon {

    font-size: 48px;

    margin-bottom: 12px;
}


.empty h3 {

    margin: 0;

    font-size: 21px;
}


.empty p {

    margin: 8px auto 0;

    max-width: 500px;

    color: var(--muted);

    line-height: 1.7;
}


/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.footer {

    text-align: center;

    color: #94a3b8;

    font-size: 12px;

    margin: 28px 0 10px;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media
(max-width: 1050px) {

    .header {

        flex-wrap: wrap;
    }

    .nav {

        order: 3;

        width: 100%;

        justify-content: center;
    }
}


@media
(max-width: 700px) {

    .page {

        padding: 18px 4%;
    }


    .header {

        padding: 20px;

        border-radius: 22px;
    }


    .brandText h1 {

        font-size: 23px;
    }


    .profileChip {

        margin-left: auto;
    }


    .profileInfo {

        display: none;
    }


    .nav {

        overflow-x: auto;

        justify-content: flex-start;
    }


    .nav a {

        white-space: nowrap;
    }


    .titleCard {

        padding: 23px;

        border-radius: 22px;
    }


    .titleLeft h2 {

        font-size: 27px;
    }


    .notification {

        padding: 17px;

        gap: 12px;
    }


    .notificationIcon {

        width: 45px;

        height: 45px;

        font-size: 20px;
    }


    .notificationTop {

        align-items: flex-start;

        flex-direction: column;

        gap: 3px;
    }


    .notificationArrow {

        width: 34px;

        height: 34px;
    }
}

</style>

</head>


<body>


<div class="page">


    <!-- ==========================================================
         HEADER
         ========================================================== -->

    <header class="header">


        <div class="brand">


            <a
                href="index.php"
                class="logo"
            >

                <img
                    src="PROJECT LOGO.png"
                    alt="SkillMate"
                >

            </a>


            <div class="brandText">

                <h1>
                    SkillMate
                </h1>

                <p>
                    Notifications & updates
                </p>

            </div>


        </div>


        <!-- NAVIGATION -->

        <nav class="nav">

            <a href="index.php">
                Home
            </a>

            <a href="profile.php">
                Profile
            </a>

            <a
                href="notification.php"
                class="active"
            >
                🔔 Notifications
            </a>

            <a href="requests.php">
                Requests
            </a>

            <a href="messages.php">
                Messages
            </a>

        </nav>


        <!-- PROFILE -->

        <div class="profileChip">


            <div class="profileAvatar">

                <?php

                echo htmlspecialchars(
                    strtoupper(
                        mb_substr(
                            $userName,
                            0,
                            2
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </div>


            <div class="profileInfo">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $userName,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    ?>

                </strong>

                <span>
                    Active now
                </span>

            </div>


        </div>


    </header>



    <!-- ==========================================================
         MAIN
         ========================================================== -->

    <main class="main">


        <!-- TITLE -->

        <section class="titleCard">


            <div class="titleLeft">

                <h2>
                    Notifications
                </h2>

                <p>
                    Stay updated with your messages,
                    skill exchange requests and your
                    daily learning motivation.
                </p>

            </div>


            <div class="countBadge">

                <?php
                echo $notificationCount;
                ?>

            </div>


        </section>



        <!-- ======================================================
             NOTIFICATIONS
             ====================================================== -->

        <?php if (count($notifications) > 0): ?>


            <section class="notificationList">


                <?php foreach (
                    $notifications
                    as $notification
                ): ?>


                    <?php

                    $typeClass =
                        htmlspecialchars(
                            $notification['type'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                    ?>


                    <article
                        class="
                            notification
                            <?php
                            echo $typeClass;
                            ?>
                        "
                    >


                        <!-- ICON -->

                        <div class="notificationIcon">

                            <?php

                            echo $notification['icon'];

                            ?>

                        </div>


                        <!-- CONTENT -->

                        <div class="notificationContent">


                            <div class="notificationTop">


                                <h3
                                    class="notificationTitle"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $notification['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </h3>


                                <span
                                    class="notificationTime"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $notification['time'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </span>


                            </div>


                            <p
                                class="notificationMessage"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $notification['message'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </p>


                            <span
                                class="notificationLabel"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $notification['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </span>


                        </div>


                        <!-- CLICK ARROW -->

                        <?php

                        if (
                            $notification['link'] !== '#'
                        ):

                        ?>

                            <a
                                class="notificationArrow"
                                href="<?php
                                echo htmlspecialchars(
                                    $notification['link'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                title="Open"
                            >
                                →
                            </a>

                        <?php else: ?>

                            <div
                                class="notificationArrow"
                                title="Daily motivation"
                            >
                                ✨
                            </div>

                        <?php endif; ?>


                    </article>


                <?php endforeach; ?>


            </section>


        <?php else: ?>


            <section class="empty">

                <div class="emptyIcon">
                    🔔
                </div>

                <h3>
                    No notifications yet
                </h3>

                <p>
                    When you receive messages,
                    skill exchange requests or
                    updates, they will appear here.
                </p>

            </section>


        <?php endif; ?>


        <!-- FOOTER -->

        <div class="footer">

            SkillMate • Learn together. Grow together.

        </div>


    </main>


</div>


</body>

</html>