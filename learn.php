<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

$currentUserId = (int)$_SESSION['user_id'];

$sessionUser = [
    'name' => isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '',
    'email' => isset($_SESSION['email']) ? $_SESSION['email'] : ''
];

$currentUserName = trim($sessionUser['name'] ?: 'Student');
$currentUserProfileImage = '';

$userProfileStmt = mysqli_prepare($conn, "SELECT p.profile_image FROM profiles p WHERE p.user_id = ? LIMIT 1");
if ($userProfileStmt) {
    mysqli_stmt_bind_param($userProfileStmt, 'i', $currentUserId);
    mysqli_stmt_execute($userProfileStmt);
    mysqli_stmt_bind_result($userProfileStmt, $dbProfileImage);
    if (mysqli_stmt_fetch($userProfileStmt)) {
        $currentUserProfileImage = $dbProfileImage ?: '';
    }
    mysqli_stmt_close($userProfileStmt);
}

$successMessage = isset($_GET['success']) && $_GET['success'] === '1'
    ? 'Your exchange request was sent successfully.'
    : '';

$errorMessage = isset($_GET['error'])
    ? trim($_GET['error'])
    : '';

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/
$categories = [
    'Programming',
    'Design',
    'Editing',
    'Data & Office',
    'Communication',
    'Business',
    'Other'
];

/*
|--------------------------------------------------------------------------
| Load teaching skills from user_skills
|
| IMPORTANT:
| The logged-in user's own skills are excluded.
|
| Example:
| User A logs in
| User A's skills -> NOT shown
| User B's skills -> shown
| User C's skills -> shown
|--------------------------------------------------------------------------
*/

$skillsByCategory = [];

$sql = "
    SELECT
        us.category,
        us.skill_name,
        u.user_id,
        u.full_name,
        p.education,
        p.career_goal,
        p.profile_image
    FROM user_skills us

    INNER JOIN users u
        ON u.user_id = us.user_id

    LEFT JOIN profiles p
        ON p.user_id = u.user_id

    WHERE us.skill_type = 'teach'
      AND us.user_id != ?

    ORDER BY
        FIELD(
            us.category,
            'Programming',
            'Design',
            'Editing',
            'Data & Office',
            'Communication',
            'Business',
            'Other'
        ),
        us.skill_name ASC,
        u.full_name ASC
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $currentUserId);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {

            $cat = trim($row['category'] ?? '');
            $skill = trim($row['skill_name'] ?? '');

            if ($cat === '') {
                $cat = 'Other';
            }

            if ($skill === '') {
                continue;
            }

            $uid = (int)$row['user_id'];

            $name = trim($row['full_name'] ?? '');

            if ($name === '') {
                $name = 'SkillMate Mentor';
            }

            $education = trim($row['education'] ?? '');

            if ($education === '') {
                $education = 'Student mentor';
            }

            $bio = trim($row['career_goal'] ?? '');

            $profileImage = trim($row['profile_image'] ?? '');

            if (!isset($skillsByCategory[$cat])) {
                $skillsByCategory[$cat] = [];
            }

            if (!isset($skillsByCategory[$cat][$skill])) {
                $skillsByCategory[$cat][$skill] = [];
            }

            $skillsByCategory[$cat][$skill][] = [
                'user_id' => $uid,
                'full_name' => $name,
                'education' => $education,
                'bio' => $bio,
                'profile_image' => $profileImage
            ];
        }
    }

    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| Recommended Skills
|--------------------------------------------------------------------------
*/

$recommendedSkills = [];

foreach ($categories as $category) {

    if (!isset($skillsByCategory[$category])) {
        continue;
    }

    foreach (array_keys($skillsByCategory[$category]) as $skill) {

        if (!in_array($skill, $recommendedSkills, true)) {
            $recommendedSkills[] = $skill;
        }

        if (count($recommendedSkills) >= 6) {
            break 2;
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width,initial-scale=1">

<title>SkillMate — Learn New Skills</title>

<style>

:root{
  --primary:#2f4d9a;
  --primary-soft:#edf2ff;
  --bg:#f3f5f9;
  --surface:#ffffff;
  --surface-soft:#f8f9fc;
  --text:#18212f;
  --muted:#5f6f86;
  --accent:#2a9d5d;
  --accent-soft:#edf9f1;
  --nav-bg:#111827;
  --nav-text:#f8fafc;
  --border:rgba(15,23,42,0.08);
  --shadow:0 10px 24px rgba(15,23,42,0.05);
}

*{box-sizing:border-box}

body{
  margin:0;
  font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;
  background:var(--bg);

.container{
  max-width:1200px;
  margin:24px auto 40px;
  padding:20px
}

.topbar-shell{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  padding:20px 22px;
  border-radius:26px;
  background:rgba(255,255,255,0.96);
  box-shadow:var(--shadow);
  backdrop-filter:blur(10px);
  border:1px solid var(--border)
}

.topbar-left{
  display:flex;
  align-items:center;
  gap:12px;
  min-width:0
}

.logo{
  width:58px;
  height:58px;
  border-radius:16px;
  overflow:hidden;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg,#ffffff,#eef2ff);
  box-shadow:0 16px 34px rgba(79,70,229,0.16);
  position:relative;
  isolation:isolate;
  transition:transform .25s ease,box-shadow .25s ease
}

.logo::before{
  content:"";
  position:absolute;
  inset:-20%;
  background:linear-gradient(120deg,transparent 0%,rgba(255,255,255,0.78) 30%,transparent 60%);
  transform:translateX(-120%) rotate(8deg);
  animation:logoShine 2.8s ease-in-out infinite
}

.logo img{
  width:100%;
  height:100%;
  object-fit:cover;
  opacity:0;
  transform:translateY(8px) scale(0.96);
  transition:transform .35s ease,opacity .35s ease,filter .35s ease;
  animation:logoFade .45s cubic-bezier(.2,.8,.2,1) .08s forwards;
  filter:drop-shadow(0 8px 18px rgba(79,70,229,0.14))
}

.logo .logo-fallback{
  display:grid;
  width:100%;
  height:100%;
  align-items:center;
  justify-content:center;
  font-size:18px;
  font-weight:800;
  color:var(--primary)
}

.eyebrow{
  margin:0 0 3px;
  font-size:11px;
  letter-spacing:0.16em;
  text-transform:uppercase;
  color:var(--primary);
  font-weight:800
}

.topbar-title{
  margin:0;
  font-size:clamp(20px,2.3vw,24px);
  letter-spacing:-0.02em
}

p.lead{
  color:var(--muted);
  margin-top:8px;
  line-height:1.7;
  max-width:680px
}

.topbar-right{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap
}

.top-nav-links{
  display:flex;
  align-items:center;
  gap:8px;
  flex-wrap:wrap;
  padding:6px;
  border-radius:999px;
  background:var(--surface-soft);
  border:1px solid var(--border)
}

.top-nav-links a{
  padding:8px 12px;
  border-radius:999px;
  text-decoration:none;
  color:var(--muted);
  font-weight:700;
  font-size:13px;
  transition:all .16s ease
}

.top-nav-links a:hover{
  background:#fff;
  color:var(--primary);
  box-shadow:0 8px 16px rgba(15,23,42,0.05)
}

.top-nav-links a.active{
  background:var(--primary-soft);
  color:var(--primary)
}

.icon-button{
  width:42px;
  height:42px;
  border:none;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:#fff;
  color:var(--primary);
  box-shadow:0 10px 22px rgba(15,23,42,0.06);
  border:1px solid var(--border);
  cursor:pointer;
  transition:transform .16s ease,box-shadow .16s ease
}

.icon-button:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 24px rgba(15,23,42,0.08)
}

.profile-pill{
  display:flex;
  align-items:center;
  gap:10px;
  padding:6px 10px;
  border-radius:999px;
  background:linear-gradient(135deg,#ffffff,#f7f9ff);
  border:1px solid var(--border);
  box-shadow:0 8px 18px rgba(15,23,42,0.05)
}

.profile-pill:hover{
  transform:translateY(-1px)
}

.avatar{
  width:38px;
  height:38px;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg,var(--primary),#8b5cf6);
  color:#fff;
  font-weight:800;
  font-size:14px
}

.profile-pill strong{
  display:block;
  font-size:13px;
  color:var(--text)
}

.profile-pill span{
  display:block;
  color:var(--muted);
  font-size:11px
}

.card{
  background:linear-gradient(145deg,rgba(255,255,255,0.98),rgba(248,251,255,0.97));
  padding:24px;
  border-radius:28px;
  box-shadow:var(--shadow);
  margin-top:20px;
  border:1px solid var(--border)
}

.content-panel{
  display:grid;
  grid-template-columns:1fr;
  gap:18px
}

.grid{
  display:grid;
  grid-template-columns:320px 1fr;
  gap:24px;
  align-items:start
}

.leftPanel{
  display:flex;
  flex-direction:column;
  gap:16px
}

.quote-box{
  padding:16px;
  border-radius:16px;
  background:linear-gradient(180deg,#ffffff,#f7fcff);
  border:1px solid var(--border);
  box-shadow:0 12px 24px rgba(15,23,42,0.05)
}

.quote-box blockquote{
  font-style:italic;
  margin:0;
  line-height:1.6;
  color:var(--text)
}

.quote-author{
  margin-top:10px;
  color:var(--muted);
  font-weight:700
}

.learn-main{
  display:flex;
  flex-direction:column;
  gap:16px
}

.page-heading{
  padding:22px 24px;
  border-radius:24px;
  background:linear-gradient(135deg,#eef2ff 0%, #f8fbff 100%);
  border:1px solid var(--border);
  box-shadow:0 14px 34px rgba(79,70,229,0.08)
}

.page-badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  background:#fff;
  border:1px solid rgba(79,70,229,0.12);
  font-size:12px;
  font-weight:800;
  color:var(--primary);
  margin-bottom:12px
}

.page-heading h2{
  margin:0 0 8px;
  font-size:24px;
  letter-spacing:-0.02em
}

.page-heading p{
  margin:0;
  color:var(--muted);
  line-height:1.7
}

.controls-card{
  padding:18px;
  border-radius:22px;
  background:linear-gradient(180deg,#ffffff,#f9fcff);
  border:1px solid var(--border);
  box-shadow:0 12px 30px rgba(15,23,42,0.05)
}

.search-row{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border-radius:16px;
  background:var(--surface-soft);
  border:1px solid var(--border)
}

.search-row input{
  border:none;
  background:transparent;
  outline:none;
  flex:1;
  font-size:14px;
  color:var(--text)
}

.search-row svg{
  color:var(--primary)
}

.filters{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:12px
}

.filter-pill{
  padding:9px 12px;
  border-radius:999px;
  background:#fff;
  border:1px solid var(--border);
  color:var(--muted);
  font-weight:700;
  font-size:13px;
  cursor:pointer;
  transition:all .16s ease
}

.filter-pill:hover{
  transform:translateY(-1px);
  color:var(--primary);
  box-shadow:0 8px 16px rgba(15,23,42,0.05)
}

.filter-pill.active{
  background:var(--primary-soft);
  color:var(--primary);
  border-color:rgba(79,70,229,0.2)
}

.section-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  margin-bottom:12px
}

.section-header h3{
  margin:0;
  font-size:17px
}

.section-header span{
  color:var(--muted);
  font-size:13px
}

.teachers-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:14px
}

.teacher-card{
  padding:18px;
  border-radius:20px;
  background:linear-gradient(180deg,#ffffff,#f8fbff);
  border:1px solid var(--border);
  box-shadow:0 10px 24px rgba(15,23,42,0.04);
  transition:transform .16s ease,box-shadow .16s ease;
  display:flex;
  flex-direction:column;
  gap:12px
}

.teacher-card:hover{
  transform:translateY(-3px);
  box-shadow:0 14px 28px rgba(15,23,42,0.06)
}

.teacher-top{
  display:flex;
  align-items:center;
  gap:12px
}

.teacher-avatar{
  width:48px;
  height:48px;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg,var(--primary),#8b5cf6);
  color:#fff;
  font-weight:800
}

.teacher-name{
  font-weight:800;
  color:var(--text)
}

.teacher-title{
  font-size:13px;
  color:var(--muted)
}

.teacher-meta{
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-size:13px;
  color:var(--muted)
}

.badge{
  display:inline-flex;
  align-items:center;
  padding:6px 9px;
  border-radius:999px;
  font-size:11px;
  font-weight:800;
  letter-spacing:0.04em;
  text-transform:uppercase
}

.badge.expert{
  background:#eef2ff;
  color:#4338ca
}

.badge.advanced{
  background:#ecfdf3;
  color:#166534
}

.teacher-bio{
  margin:0;
  color:var(--muted);
  line-height:1.6;
  font-size:14px
}

.teacher-actions{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
  margin-top:2px
}

.rating{
  font-size:13px;
  color:var(--primary);
  font-weight:700
}

.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:10px 12px;
  border-radius:12px;
  text-decoration:none;
  color:#fff;
  background:linear-gradient(135deg,var(--accent),#16a34a);
  border:none;
  box-shadow:0 10px 22px rgba(34,197,94,0.16);
  font-weight:700;
  font-size:13px;
  cursor:pointer;
  transition:transform .12s ease,box-shadow .12s ease
}

.btn:hover{
  transform:translateY(-2px);
  box-shadow:0 14px 28px rgba(34,197,94,0.2)
}

.btn-secondary{
  background:linear-gradient(135deg,#fff,#f8fafc);
  color:var(--muted);
  border:1px solid var(--border);
  box-shadow:none
}

.btn-secondary:hover{
  box-shadow:0 10px 20px rgba(15,23,42,0.06)
}

.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(2,6,23,0.62);
  display:grid;
  place-items:center;
  padding:20px;
  z-index:1200;
  opacity:0;
  visibility:hidden;
  transition:opacity .24s ease,visibility .24s ease
}

.modal-overlay.show{
  opacity:1;
  visibility:visible
}

.exchange-modal{
  width:min(100%,560px);
  background:linear-gradient(145deg,#ffffff,#f8fbff);
  border:1px solid var(--border);
  border-radius:24px;
  box-shadow:0 24px 70px rgba(2,6,23,0.24);
  padding:22px;
  transform:translateY(16px) scale(.98);
  transition:transform .24s ease,opacity .24s ease;
  opacity:0
}

.modal-overlay.show .exchange-modal{
  transform:translateY(0) scale(1);
  opacity:1
}

.modal-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:10px;
  margin-bottom:16px
}

.modal-title{
  margin:0;
  font-size:20px;
  letter-spacing:-0.02em
}

.modal-subtitle{
  margin:6px 0 0;
  color:var(--muted);
  font-size:13px;
  line-height:1.6
}

.modal-close{
  width:40px;
  height:40px;
  border:none;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:var(--surface-soft);
  color:var(--muted);
  cursor:pointer
}

.modal-form{
  display:grid;
  gap:14px
}

.form-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px
}

.field{
  display:flex;
  flex-direction:column;
  gap:7px
}

.field.full{
  grid-column:1 / -1
}

.field label{
  font-size:13px;
  font-weight:800;
  color:var(--text)
}

.field input,
.field select,
.field textarea{
  width:100%;
  padding:11px 12px;
  border:1px solid var(--border);
  border-radius:12px;
  background:#fff;
  color:var(--text);
  font:inherit;
  outline:none;
  transition:border-color .16s ease,box-shadow .16s ease
}

.field input:focus,
.field select:focus,
.field textarea:focus{
  border-color:rgba(79,70,229,0.36);
  box-shadow:0 0 0 4px rgba(79,70,229,0.12)
}

.field textarea{
  min-height:100px;
  resize:vertical
}

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:10px;
  margin-top:6px
}

body.modal-open{
  overflow:hidden
}

.status-banner{
  margin:0 0 16px;
  padding:12px 14px;
  border-radius:14px;
  font-size:13px;
  font-weight:700;
  border:1px solid transparent
}

.status-banner.success{
  background:var(--accent-soft);
  color:#166534;
  border-color:rgba(34,197,94,0.2)
}

.status-banner.error{
  background:#fef2f2;
  color:#991b1b;
  border-color:rgba(239,68,68,0.18)
}

.recommended-row{
  display:grid;
  grid-template-columns:repeat(6,minmax(0,1fr));
  gap:12px
}

.skill-pill{
  padding:12px 10px;
  border-radius:16px;
  background:linear-gradient(135deg,#ffffff,#f7f9ff);
  border:1px solid var(--border);
  box-shadow:0 10px 22px rgba(15,23,42,0.04);
  text-align:center;
  font-weight:800;
  color:var(--text);
  transition:transform .16s ease,box-shadow .16s ease
}

.skill-pill:hover{
  transform:translateY(-3px);
  box-shadow:0 14px 28px rgba(15,23,42,0.06)
}

.tips-card{
  padding:18px;
  border-radius:22px;
  background:linear-gradient(135deg,#f4fdf7,#fbfffc);
  border:1px solid rgba(34,197,94,0.15);
  box-shadow:0 12px 30px rgba(15,23,42,0.05)
}

.tips-card h3{
  margin:0 0 12px;
  font-size:17px;
  color:var(--text)
}

.tips-list{
  display:grid;
  gap:10px
}

.tip-item{
  display:flex;
  gap:10px;
  padding:12px 14px;
  border-radius:16px;
  background:rgba(255,255,255,0.9);
  border:1px solid rgba(34,197,94,0.12)
}

.tip-item strong{
  color:var(--text);
  display:block;
  margin-bottom:3px
}

.tip-item span{
  color:var(--muted);
  font-size:13px;
  line-height:1.5
}

.tip-icon{
  width:32px;
  height:32px;
  border-radius:10px;
  background:var(--accent-soft);
  display:grid;
  place-items:center;
  color:var(--accent);
  font-size:14px;
  flex-shrink:0
}

footer{
  margin-top:22px;
  color:var(--muted);
  font-size:13px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding-top:8px
}

.logout-fab{
  position:fixed;
  right:22px;
  bottom:22px;
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:12px 14px;
  border-radius:999px;
  background:linear-gradient(90deg,var(--nav-bg),#1f2937);
  color:var(--nav-text);
  border:none;
  box-shadow:0 14px 40px rgba(2,6,23,0.18);
  cursor:pointer;
  font-weight:800;
  font-size:14px;
  backdrop-filter:blur(6px);
  transform:translateY(0);
  transition:transform .16s ease,box-shadow .16s ease,opacity .2s
}

.logout-fab svg{
  width:18px;
  height:18px;
  opacity:0.95
}

.logout-fab:hover{
  transform:translateY(-6px);
  box-shadow:0 20px 48px rgba(2,6,23,0.22)
}

.logout-fab.hide{
  opacity:0;
  pointer-events:none;
  transform:translateY(12px) scale(.98)
}

@keyframes logoFade{
  to{
    opacity:1;
    transform:translateY(0) scale(1)
  }
}

@keyframes logoShine{
  0%{
    transform:translateX(-120%) rotate(8deg)
  }
  100%{
    transform:translateX(120%) rotate(8deg)
  }
}

.logo:hover{
  transform:translateY(-2px);
  box-shadow:0 18px 34px rgba(79,70,229,0.18)
}

.logo:hover img{
  transform:scale(1.05) translateY(-2px) rotate(-2deg);
  filter:drop-shadow(0 12px 24px rgba(79,70,229,0.2))
}

@media (max-width:900px){

  .grid{
    grid-template-columns:1fr
  }

  .teachers-grid{
    grid-template-columns:1fr
  }

  .recommended-row{
    grid-template-columns:repeat(3,minmax(0,1fr))
  }

  .container{
    padding:16px
  }

  .topbar-shell{
    flex-direction:column;
    align-items:flex-start
  }

  .topbar-right{
    width:100%;
    justify-content:space-between
  }
}

@media (max-width:680px){

  .topbar-right{
    flex-wrap:wrap
  }

  .top-nav-links{
    width:100%;
    justify-content:center
  }

  .recommended-row{
    grid-template-columns:repeat(2,minmax(0,1fr))
  }

  .section-header{
    flex-direction:column;
    align-items:flex-start
  }

  .search-row{
    padding:10px 12px
  }

  .form-grid{
    grid-template-columns:1fr
  }

  .exchange-modal{
    padding:18px;
    border-radius:20px
  }

  .modal-actions{
    flex-direction:column-reverse
  }

  .modal-actions .btn,
  .modal-actions .btn-secondary{
    width:100%
  }
}

</style>

</head>

<body>

<div class="container">

<header class="topbar-shell">

  <div class="topbar-left">

    <div class="logo">

      <img
        src="PROJECT LOGO.png"
        alt="SkillMate logo"
        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"
      >

      <span
        class="logo-fallback"
        style="display:none;font-weight:800;color:var(--primary);"
      >
        SM
      </span>

    </div>

    <div>

      <p class="eyebrow">
        Student dashboard
      </p>

      <h1 class="topbar-title">

        Skill Dashboard

        <span
          id="userFullName"
          style="font-size:13px;color:var(--muted);font-weight:700;margin-left:8px;"
        ></span>

      </h1>

      <p class="lead">
        A student-first skill exchange platform — teach what you know, learn what you need.
      </p>

    </div>

  </div>

  <div class="topbar-right">

    <div class="top-nav-links">

      <a href="index.php">Home</a>

      <a href="teach.php">Teach</a>

      <a href="learn.php" class="active">Learn</a>

      

      <a href="messages.php">Messages</a>

      <a href="profile.php">Profile</a>

      <a href="about.php">About</a>

    </div>


    <a
    href="notification.php"
    class="icon-button"
    aria-label="Notifications"
    title="Notifications"
>
    <!-- Keep the existing notification SVG here -->
    🔔
</a>

    


  </div>

</header>


<div class="card">

  <div class="content-panel">

    <div class="grid">

      <aside class="leftPanel">

        <div
          class="quote-box"
          aria-live="polite"
          aria-atomic="true"
        >

          <blockquote id="quoteText">
            "Learning never exhausts the mind."
          </blockquote>

          <div
            id="quoteAuthor"
            class="quote-author"
          >
            — Leonardo da Vinci
          </div>

        </div>

      </aside>


      <div class="learn-main">


        <div class="page-heading">

          <div class="page-badge">
            ✨ Learning Hub
          </div>


          <?php if (!empty($successMessage)): ?>

            <div class="status-banner success">

              <?php
              echo htmlspecialchars(
                  $successMessage,
                  ENT_QUOTES,
                  'UTF-8'
              );
              ?>

            </div>

          <?php endif; ?>


          <?php if (!empty($errorMessage)): ?>

            <div class="status-banner error">

              <?php
              echo htmlspecialchars(
                  rawurldecode($errorMessage),
                  ENT_QUOTES,
                  'UTF-8'
              );
              ?>

            </div>

          <?php endif; ?>


          <h2>
            Learn New Skills
          </h2>

          <p>
            Find people who can teach you.
          </p>

        </div>


        <div class="controls-card">

          <div class="search-row">

            <svg
              viewBox="0 0 24 24"
              width="18"
              height="18"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              aria-hidden="true"
            >

              <circle
                cx="11"
                cy="11"
                r="6"
                stroke="currentColor"
                stroke-width="1.8"
              />

              <path
                d="m16 16 4 4"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
              />

            </svg>

            <input
              id="skillSearch"
              type="text"
              placeholder="What do you want to learn?"
            >

          </div>


          <div
            class="filters"
            aria-label="Category filter"
          >

            <button
              class="filter-pill active"
              type="button"
              data-category="All"
            >
              All
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Programming"
            >
              Programming
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Design"
            >
              Design
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Editing"
            >
              Editing
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Data & Office"
            >
              Data & Office
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Communication"
            >
              Communication
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Business"
            >
              Business
            </button>

            <button
              class="filter-pill"
              type="button"
              data-category="Other"
            >
              Other
            </button>

          </div>

        </div>


        <div class="section-header">

          <h3>
            Available Skills & Teachers
          </h3>

          <span>
            Find people who teach the skills you want
          </span>

        </div>


        <div id="skillsContainer">

        <?php

        $hasAnySkills = false;

        foreach ($categories as $category):

            if (!isset($skillsByCategory[$category])) {
                continue;
            }

            $hasAnySkills = true;

        ?>

          <div
            class="category-section"
            data-category="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"
            style="margin-top:12px;"
          >

            <h4
              style="margin:8px 0 6px;font-size:15px;color:var(--primary);text-transform:uppercase"
            >

              <?php
              echo htmlspecialchars(
                  $category,
                  ENT_QUOTES,
                  'UTF-8'
              );
              ?>

            </h4>


            <div class="teachers-grid">


              <?php foreach ($skillsByCategory[$category] as $skillName => $teachersForSkill): ?>


                <?php foreach ($teachersForSkill as $teacher): ?>


                  <?php

                  $teacherName = htmlspecialchars(
                      $teacher['full_name'],
                      ENT_QUOTES,
                      'UTF-8'
                  );

                  $teacherSkill = htmlspecialchars(
                      $skillName,
                      ENT_QUOTES,
                      'UTF-8'
                  );

                  $teacherBio = htmlspecialchars(
                      $teacher['bio'] ?? '',
                      ENT_QUOTES,
                      'UTF-8'
                  );

                  $teacherExperience = htmlspecialchars(
                      $teacher['education'] ?? '',
                      ENT_QUOTES,
                      'UTF-8'
                  );

                  $originalName = $teacher['full_name'] ?? 'SkillMate Mentor';

                  $teacherInitials = '';

                  $nameParts = preg_split(
                      '/\s+/',
                      trim($originalName)
                  );

                  foreach ($nameParts as $part) {

                      if ($part !== '') {

                          $teacherInitials .= mb_substr(
                              $part,
                              0,
                              1,
                              'UTF-8'
                          );
                      }

                      if (mb_strlen($teacherInitials, 'UTF-8') >= 2) {
                          break;
                      }
                  }

                  if ($teacherInitials === '') {
                      $teacherInitials = 'SM';
                  }

                  $teacherInitials = htmlspecialchars(
                      mb_strtoupper(
                          $teacherInitials,
                          'UTF-8'
                      ),
                      ENT_QUOTES,
                      'UTF-8'
                  );

                  ?>


                  <article
                    class="teacher-card"
                    data-skill="<?php echo strtolower(htmlspecialchars($skillName, ENT_QUOTES, 'UTF-8')); ?>"
                    data-category="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"
                  >

                    <div class="teacher-top">

                      <div class="teacher-avatar">

                        <?php
                        echo $teacherInitials;
                        ?>

                      </div>

                      <div>

                        <div class="teacher-name">

                          <?php
                          echo $teacherName;
                          ?>

                        </div>

                        <div class="teacher-title">

                          <?php
                          echo $teacherSkill;
                          ?>

                        </div>

                      </div>

                    </div>


                    <div class="teacher-meta">

                      <span class="badge expert">

                        <?php
                        echo $teacherExperience ?: 'Mentor';
                        ?>

                      </span>

                      <span class="rating">
                        ★ 4.8
                      </span>

                    </div>


                    <p class="teacher-bio">

                      <?php

                      echo $teacherBio
                          ?: 'Ready to share knowledge and support a peer exchange.';

                      ?>

                    </p>


                    <div class="teacher-actions">

                      <span class="teacher-title">
                        Exchange ready
                      </span>

                      <button
                        class="btn request-exchange-btn"
                        type="button"
                        data-teacher-id="<?php echo (int)$teacher['user_id']; ?>"
                        data-teacher-name="<?php echo $teacherName; ?>"
                        data-teacher-skill="<?php echo $teacherSkill; ?>"
                      >
                        Request Exchange
                      </button>

                    </div>

                  </article>


                <?php endforeach; ?>

              <?php endforeach; ?>


            </div>

          </div>

        <?php endforeach; ?>


        <?php if (!$hasAnySkills): ?>

          <div
            class="status-banner"
            style="background:#f8fafc;color:var(--muted);border-color:var(--border);"
          >

            No teaching skills from other users are available yet.

          </div>

        <?php endif; ?>


        </div>


        <div
          id="noSearchResults"
          class="status-banner"
          style="display:none;background:#f8fafc;color:var(--muted);border-color:var(--border);"
        >

          No matching skills found.

        </div>


        <div class="section-header" style="margin-top:10px;">

          <h3>
            Recommended Skills
          </h3>

          <span>
            Popular next steps
          </span>

        </div>


        <div class="recommended-row">

          <?php foreach ($recommendedSkills as $skill): ?>

            <div class="skill-pill">

              <?php
              echo htmlspecialchars(
                  $skill,
                  ENT_QUOTES,
                  'UTF-8'
              );
              ?>

            </div>

          <?php endforeach; ?>

        </div>


        <div
          class="tips-card"
          style="margin-top:14px;"
        >

          <h3>
            Learning tips
          </h3>

          <div class="tips-list">

            <div class="tip-item">

              <div class="tip-icon">
                ✓
              </div>

              <div>

                <strong>
                  Start small
                </strong>

                <span>
                  Choose one skill to practice daily and build momentum with consistent sessions.
                </span>

              </div>

            </div>


            <div class="tip-item">

              <div class="tip-icon">
                ⏱
              </div>

              <div>

                <strong>
                  Set a routine
                </strong>

                <span>
                  Short focused practice blocks often lead to stronger long-term progress.
                </span>

              </div>

            </div>


            <div class="tip-item">

              <div class="tip-icon">
                💬
              </div>

              <div>

                <strong>
                  Ask questions
                </strong>

                <span>
                  Great learners stay curious and make the most of every exchange.
                </span>

              </div>

            </div>

          </div>

        </div>


      </div>

    </div>

  </div>

</div>


<footer>

  <span id="footerText">
    Built for students • © SkillMate
  </span>

  <button
    id="logoutBtn"
    style="float:right;display:none;background:transparent;border:none;color:var(--muted);cursor:pointer"
  >
    Logout
  </button>

</footer>


</div>


<!-- =========================================================
     EXCHANGE REQUEST MODAL
========================================================= -->

<div
  id="exchangeModal"
  class="modal-overlay"
  aria-hidden="true"
>

  <div
    class="exchange-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="exchangeModalTitle"
  >

    <div class="modal-header">

      <div>

        <h3
          id="exchangeModalTitle"
          class="modal-title"
        >
          Request an Exchange
        </h3>

        <p class="modal-subtitle">
          Share your availability and goals with the teacher.
        </p>

      </div>

      <button
        id="closeModalBtn"
        class="modal-close"
        type="button"
        aria-label="Close dialog"
      >
        ✕
      </button>

    </div>


    <form
      id="exchangeForm"
      class="modal-form"
      method="post"
      action="php/request_process.php"
    >

      <input
        type="hidden"
        id="receiverId"
        name="receiver_id"
        value="0"
      >


      <div class="form-grid">

        <div class="field">

          <label for="teacherName">
            Teacher Name
          </label>

          <input
            id="teacherName"
            name="teacher_name"
            type="text"
            readonly
          >

        </div>


        <div class="field">

          <label for="skillToLearn">
            Skill to Learn
          </label>

          <input
            id="skillToLearn"
            name="skill_to_learn"
            type="text"
            readonly
          >

        </div>

      </div>


      <div class="field full">

        <label for="skillITeach">
          Skill I Can Teach
        </label>

        <select
          id="skillITeach"
          name="skill_i_can_teach"
          required
        >

          <option value="">
            Select a skill
          </option>

          <option value="Programming">
            Programming
          </option>

          <option value="UI Design">
            UI Design
          </option>

          <option value="Video Editing">
            Video Editing
          </option>

          <option value="Public Speaking">
            Public Speaking
          </option>

          <option value="Communication">
            Communication
          </option>

          <option value="AI Basics">
            AI Basics
          </option>

          <option value="Excel">
            Excel
          </option>

        </select>

      </div>


      <div class="form-grid">

        <div class="field">

          <label for="preferredDate">
            Preferred Date
          </label>

          <input
            id="preferredDate"
            name="preferred_date"
            type="date"
            required
          >

        </div>


        <div class="field">

          <label for="preferredTime">
            Preferred Time
          </label>

          <input
            id="preferredTime"
            name="preferred_time"
            type="time"
            required
          >

        </div>

      </div>


      <div class="field full">

        <label for="message">
          Message (optional)
        </label>

        <textarea
          id="message"
          name="message"
          placeholder="Share what you hope to learn or any notes for the meeting."
        ></textarea>

      </div>


      <div class="modal-actions">

        <button
          id="cancelExchangeBtn"
          class="btn btn-secondary"
          type="button"
        >
          Cancel
        </button>

        <button
          class="btn"
          type="submit"
        >
          Send Request
        </button>

      </div>

    </form>

  </div>

</div>


<!-- =========================================================
     LOGOUT BUTTON
========================================================= -->

<button
  id="logoutFab"
  class="logout-fab hide"
  aria-label="Sign out"
>

  <svg
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
  >

    <path
      d="M16 17l5-5-5-5"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round"
    />

    <path
      d="M21 12H9"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round"
    />

    <path
      d="M9 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round"
    />

  </svg>

  <span>
    Sign out
  </span>

</button>


<script>

/*
|--------------------------------------------------------------------------
| Current logged-in user
|--------------------------------------------------------------------------
*/

const sessionUser = <?php

echo json_encode(
    $sessionUser,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

?>;


function getUser(){

  const storedUser = localStorage.getItem('skillmate_user');

  if(storedUser){

    try {

      return JSON.parse(storedUser);

    } catch(e){

      return null;

    }

  }

  return sessionUser && sessionUser.name
    ? sessionUser
    : null;
}


window.addEventListener('load', ()=>{

  const user = getUser();

  if(!user){

    window.location.href = 'login.php';

    return;
  }


  const el = document.getElementById('userFullName');

  const avatarEl = document.getElementById('avatarInitials');

  const topUserName = document.getElementById('topUserName');


  if(el && user.name){

    el.textContent = '• ' + user.name;

  }


  if(avatarEl && user.name){

    const initials = user.name
      .split(' ')
      .map(part => part[0])
      .slice(0,2)
      .join('')
      .toUpperCase();

    avatarEl.textContent = initials || 'SM';

  }


  if(topUserName && user.name){

    topUserName.textContent =
      user.name.split(' ')[0];

  }

});


/*
|--------------------------------------------------------------------------
| SEARCH + CATEGORY FILTER
|--------------------------------------------------------------------------
*/

(function(){

  const searchInput =
    document.getElementById('skillSearch');

  const filterButtons =
    document.querySelectorAll('.filter-pill');

  const cards =
    document.querySelectorAll('.teacher-card');

  const categorySections =
    document.querySelectorAll('.category-section');

  const noResults =
    document.getElementById('noSearchResults');


  let selectedCategory = 'All';


  function filterSkills(){

    const searchText =
      searchInput
        ? searchInput.value.trim().toLowerCase()
        : '';


    let visibleCount = 0;


    cards.forEach(card => {

      const skill =
        (card.dataset.skill || '').toLowerCase();

      const category =
        card.dataset.category || '';


      const matchesSearch =
        searchText === '' ||
        skill.includes(searchText);


      const matchesCategory =
        selectedCategory === 'All' ||
        category === selectedCategory;


      if(matchesSearch && matchesCategory){

        card.style.display = '';

        visibleCount++;

      }else{

        card.style.display = 'none';

      }

    });


    categorySections.forEach(section => {

      const sectionCategory =
        section.dataset.category || '';

      const visibleCards =
        section.querySelectorAll(
          '.teacher-card:not([style*="display: none"])'
        );


      const categoryMatches =
        selectedCategory === 'All' ||
        selectedCategory === sectionCategory;


      if(categoryMatches && visibleCards.length > 0){

        section.style.display = '';

      }else{

        section.style.display = 'none';

      }

    });


    if(noResults){

      noResults.style.display =
        visibleCount === 0
          ? 'block'
          : 'none';

    }

  }


  filterButtons.forEach(button => {

    button.addEventListener('click', ()=>{

      filterButtons.forEach(btn => {

        btn.classList.remove('active');

      });


      button.classList.add('active');


      selectedCategory =
        button.dataset.category || 'All';


      filterSkills();

    });

  });


  if(searchInput){

    searchInput.addEventListener(
      'input',
      filterSkills
    );

  }


})();


/*
|--------------------------------------------------------------------------
| EXCHANGE REQUEST MODAL
|--------------------------------------------------------------------------
*/

(function(){

  const modal =
    document.getElementById('exchangeModal');

  const form =
    document.getElementById('exchangeForm');

  const teacherNameInput =
    document.getElementById('teacherName');

  const skillToLearnInput =
    document.getElementById('skillToLearn');

  const skillITeachSelect =
    document.getElementById('skillITeach');

  const receiverIdInput =
    document.getElementById('receiverId');

  const closeModalBtn =
    document.getElementById('closeModalBtn');

  const cancelExchangeBtn =
    document.getElementById('cancelExchangeBtn');


  if(
    !modal ||
    !form ||
    !teacherNameInput ||
    !skillToLearnInput ||
    !skillITeachSelect ||
    !closeModalBtn ||
    !cancelExchangeBtn
  ){

    return;

  }


  function closeModal(){

    modal.classList.remove('show');

    modal.setAttribute(
      'aria-hidden',
      'true'
    );

    document.body.classList.remove(
      'modal-open'
    );

  }


  function openModal(button){

    const teacherId =
      button.dataset.teacherId || '0';

    const teacherName =
      button.dataset.teacherName || '';

    const teacherSkill =
      button.dataset.teacherSkill || '';


    form.reset();


    teacherNameInput.value =
      teacherName;

    skillToLearnInput.value =
      teacherSkill;


    if(receiverIdInput){

      receiverIdInput.value =
        teacherId;

    }


    skillITeachSelect.value = '';


    modal.classList.add('show');

    modal.setAttribute(
      'aria-hidden',
      'false'
    );

    document.body.classList.add(
      'modal-open'
    );


    skillITeachSelect.focus();

  }


  document.addEventListener(
    'click',
    (event) => {

      const button =
        event.target.closest(
          '.request-exchange-btn'
        );


      if(!button){

        return;

      }


      openModal(button);

    }
  );


  closeModalBtn.addEventListener(
    'click',
    closeModal
  );


  cancelExchangeBtn.addEventListener(
    'click',
    closeModal
  );


  modal.addEventListener(
    'click',
    (event) => {

      if(event.target === modal){

        closeModal();

      }

    }
  );


  document.addEventListener(
    'keydown',
    (event) => {

      if(
        event.key === 'Escape' &&
        modal.classList.contains('show')
      ){

        closeModal();

      }

    }
  );


})();


/*
|--------------------------------------------------------------------------
| QUOTES
|--------------------------------------------------------------------------
*/

(function(){

  const quotes = [

    {
      text:'Learning never exhausts the mind.',
      author:'Leonardo da Vinci'
    },

    {
      text:'Education is the most powerful weapon which you can use to change the world.',
      author:'Nelson Mandela'
    },

    {
      text:'Tell me and I forget. Teach me and I remember. Involve me and I learn.',
      author:'Benjamin Franklin'
    }

  ];


  const quoteText =
    document.getElementById('quoteText');

  const quoteAuthor =
    document.getElementById('quoteAuthor');


  if(!quoteText || !quoteAuthor){

    return;

  }


  let index = 0;


  function showQuote(){

    const q =
      quotes[index % quotes.length];


    quoteText.textContent =
      '"' + q.text + '"';


    quoteAuthor.textContent =
      '— ' + q.author;


    index += 1;

  }


  showQuote();

  setInterval(
    showQuote,
    60000
  );

})();


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

(function(){

  const fab =
    document.getElementById('logoutFab');


  if(!fab){

    return;

  }


  function getStoredUser(){

    const storedUser =
      localStorage.getItem('skillmate_user');


    if(storedUser){

      try{

        return JSON.parse(storedUser);

      }catch(e){

        return null;

      }

    }


    return sessionUser && sessionUser.name
      ? sessionUser
      : null;

  }


  if(getStoredUser()){

    fab.classList.remove('hide');

  }


  fab.addEventListener(
    'click',
    ()=>{

      fab.style.transform =
        'scale(.96)';

      fab.style.opacity =
        '0.9';


      setTimeout(()=>{

        localStorage.removeItem(
          'skillmate_user'
        );


        fab.style.transition =
          'transform .22s ease,opacity .22s ease';


        fab.style.transform =
          'translateY(18px) scale(.9)';


        fab.style.opacity =
          '0';


        setTimeout(()=>{

          window.location.href =
            'login.php';

        },260);


      },120);

    }
  );

})();

</script>

</body>

</html>