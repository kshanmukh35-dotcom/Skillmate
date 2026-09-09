<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/db_connect.php';

$userId = (int)$_SESSION['user_id'];


/* =========================================================
   GET CURRENT USER NAME
   ========================================================= */

$userQuery = mysqli_prepare(
    $conn,
    "SELECT full_name FROM users WHERE user_id = ? LIMIT 1"
);

$currentUserName = 'Student';

if ($userQuery) {

    mysqli_stmt_bind_param(
        $userQuery,
        'i',
        $userId
    );

    mysqli_stmt_execute($userQuery);

    mysqli_stmt_bind_result(
        $userQuery,
        $dbFullName
    );

    if (mysqli_stmt_fetch($userQuery)) {
        $currentUserName = $dbFullName;
    }

    mysqli_stmt_close($userQuery);
}


/* =========================================================
   DELETE SKILL
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'delete_skill') {

        header('Content-Type: application/json');

        $skillId = isset($_POST['skill_id'])
            ? (int)$_POST['skill_id']
            : 0;

        if ($skillId <= 0) {

            echo json_encode([
                'success' => false,
                'error' => 'Invalid skill ID.'
            ]);

            exit();
        }


        /*
         * The user_id condition is important.
         * A user can delete only their own skill.
         */

        $deleteQuery = mysqli_prepare(
            $conn,
            "DELETE FROM user_skills
             WHERE id = ?
             AND user_id = ?
             AND skill_type = 'teach'"
        );

        if (!$deleteQuery) {

            echo json_encode([
                'success' => false,
                'error' => 'Unable to prepare delete query.'
            ]);

            exit();
        }


        mysqli_stmt_bind_param(
            $deleteQuery,
            'ii',
            $skillId,
            $userId
        );


        if (mysqli_stmt_execute($deleteQuery)) {

            if (mysqli_stmt_affected_rows($deleteQuery) > 0) {

                echo json_encode([
                    'success' => true,
                    'message' => 'Skill deleted successfully.'
                ]);

            } else {

                echo json_encode([
                    'success' => false,
                    'error' => 'Skill not found.'
                ]);
            }

        } else {

            echo json_encode([
                'success' => false,
                'error' => 'Unable to delete skill.'
            ]);
        }


        mysqli_stmt_close($deleteQuery);

        exit();
    }
}


/* =========================================================
   LOAD TEACHING SKILLS FROM user_skills
   ========================================================= */

$teachingSkills = [];

$skillsQuery = mysqli_prepare(
    $conn,
    "SELECT
        id,
        skill_name,
        category,
        created_at
     FROM user_skills
     WHERE user_id = ?
     AND skill_type = 'teach'
     ORDER BY id DESC"
);


if ($skillsQuery) {

    mysqli_stmt_bind_param(
        $skillsQuery,
        'i',
        $userId
    );

    mysqli_stmt_execute($skillsQuery);

    mysqli_stmt_bind_result(
        $skillsQuery,
        $skillId,
        $skillName,
        $category,
        $createdAt
    );


    while (mysqli_stmt_fetch($skillsQuery)) {

        $teachingSkills[] = [
            'id' => (int)$skillId,
            'skill_name' => $skillName,
            'category' => $category,
            'created_at' => $createdAt
        ];
    }


    mysqli_stmt_close($skillsQuery);
}

?>
<!doctype html>

<html lang="en">

<head>

  <meta charset="utf-8">

  <meta
      name="viewport"
      content="width=device-width,initial-scale=1"
  >

  <title>SkillMate — Teach Skills</title>


  <style>

    :root{
      --primary:#4f46e5;
      --primary-soft:#eef2ff;
      --bg:#f5f7ff;
      --surface:#ffffff;
      --surface-soft:#f8fbff;
      --text:#14213d;
      --muted:#64748b;
      --accent:#22c55e;
      --accent-soft:#ecfdf3;
      --nav-bg:#111827;
      --nav-text:#f8fafc;
      --border:rgba(15,23,42,0.08);
      --shadow:0 18px 60px rgba(15,23,42,0.08);
    }


    *{
      box-sizing:border-box
    }


    body{
      margin:0;
      font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;
      background:linear-gradient(135deg,#f8fbff 0%, #eef6ff 100%);
      color:var(--text);
      -webkit-font-smoothing:antialiased
    }


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
      background:linear-gradient(
        120deg,
        transparent 0%,
        rgba(255,255,255,0.78) 30%,
        transparent 60%
      );
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
      display:none;
      width:100%;
      height:100%;
      align-items:center;
      justify-content:center;
      display:grid;
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
      background:linear-gradient(
        145deg,
        rgba(255,255,255,0.98),
        rgba(248,251,255,0.97)
      );
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


    .action-box{
      position:relative;
      padding:18px;
      border-radius:22px;
      background:linear-gradient(145deg,#ffffff,#f8fbff);
      border:1px solid var(--border);
      box-shadow:0 10px 26px rgba(15,23,42,0.05);
      overflow:hidden;
      transition:transform .16s ease
    }


    .action-box:hover{
      transform:translateY(-2px)
    }


    .action-box h3{
      margin:0 0 12px;
      font-size:16px;
      color:var(--text)
    }


    .action-box .actions-grid{
      display:grid;
      gap:10px
    }


    .action-box .act{
      display:flex;
      align-items:center;
      gap:10px;
      padding:11px 12px;
      border-radius:14px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--text);
      font-weight:700;
      cursor:pointer;
      justify-content:flex-start;
      transition:transform .14s ease,box-shadow .14s ease,background .14s ease;
      border-left:3px solid transparent
    }


    .action-box .act:hover{
      transform:translateX(3px);
      box-shadow:0 10px 20px rgba(15,23,42,0.05);
      background:var(--primary-soft)
    }


    .action-box .act.active{
      background:linear-gradient(
        90deg,
        rgba(79,70,229,0.12),
        rgba(79,70,229,0.04)
      );
      color:var(--primary);
      border-left-color:var(--primary);
      border-color:rgba(79,70,229,0.16)
    }


    .nav-icon{
      width:32px;
      height:32px;
      border-radius:10px;
      background:var(--surface-soft);
      display:grid;
      place-items:center;
      color:var(--primary);
      font-size:13px;
      box-shadow:inset 0 1px 0 rgba(255,255,255,0.6)
    }


    .action-box::before{
      content:"";
      position:absolute;
      left:0;
      top:0;
      width:100%;
      height:3px;
      background:linear-gradient(90deg,var(--primary),var(--accent));
      opacity:0.9
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


    .teach-main{
      display:flex;
      flex-direction:column;
      gap:16px
    }


    .page-heading{
      padding:22px 24px;
      border-radius:24px;
      background:linear-gradient(
        135deg,
        #eef2ff 0%,
        #f8fbff 100%
      );
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


    .skills-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:14px
    }


    .skill-card{
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


    .skill-card:hover{
      transform:translateY(-3px);
      box-shadow:0 14px 28px rgba(15,23,42,0.06)
    }


    .skill-top{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px
    }


    .skill-name{
      font-size:16px;
      font-weight:800;
      color:var(--text)
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


    .badge.beginner{
      background:#ecfdf3;
      color:#166534
    }


    .badge.intermediate{
      background:#eef2ff;
      color:#4338ca
    }


    .badge.advanced{
      background:#fef3c7;
      color:#b45309
    }


    .skill-card p{
      margin:0;
      color:var(--muted);
      line-height:1.6;
      font-size:14px
    }


    .skill-meta{
      display:flex;
      justify-content:space-between;
      align-items:center;
      color:var(--muted);
      font-size:13px
    }


    .skill-actions{
      display:flex;
      gap:8px;
      flex-wrap:wrap
    }


    .tiny-btn{
      padding:8px 10px;
      border-radius:10px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--text);
      font-weight:700;
      font-size:12px;
      cursor:pointer;
      transition:all .16s ease
    }


    .tiny-btn:hover{
      transform:translateY(-1px);
      box-shadow:0 8px 16px rgba(15,23,42,0.05)
    }


    .tiny-btn.edit{
      color:var(--primary)
    }


    .tiny-btn.delete{
      color:#dc2626
    }


    .add-card{
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:10px;
      padding:24px;
      border-radius:20px;
      background:linear-gradient(135deg,#f7f8ff,#fbfdff);
      border:1px dashed rgba(79,70,229,0.2);
      min-height:190px;
      cursor:pointer;
      transition:transform .16s ease,box-shadow .16s ease;
      text-align:center
    }


    .add-card:hover{
      transform:translateY(-3px);
      box-shadow:0 12px 24px rgba(15,23,42,0.05)
    }


    .add-icon{
      width:48px;
      height:48px;
      border-radius:14px;
      background:linear-gradient(135deg,var(--primary),#8b5cf6);
      display:grid;
      place-items:center;
      color:#fff;
      font-size:24px;
      box-shadow:0 10px 20px rgba(79,70,229,0.16)
    }


    .add-card strong{
      font-size:16px;
      color:var(--text)
    }


    .add-card span{
      color:var(--muted);
      font-size:14px;
      line-height:1.6
    }


    .skill-form{
      position:relative;
      overflow:hidden;
      margin-top:18px;
      padding:18px 18px 16px;
      border-radius:22px;
      background:linear-gradient(
        135deg,
        #ffffff 0%,
        #f7f9ff 45%,
        #eef4ff 100%
      );
      border:1px solid rgba(79,70,229,0.1);
      box-shadow:0 18px 36px rgba(79,70,229,0.08)
    }


    .skill-form::before{
      content:"";
      position:absolute;
      inset:-30% -20% auto auto;
      width:160px;
      height:160px;
      background:radial-gradient(
        circle,
        rgba(79,70,229,0.18),
        transparent 68%
      );
      animation:floatGlow 8s ease-in-out infinite alternate;
      pointer-events:none
    }


    .skill-form::after{
      content:"";
      position:absolute;
      inset:auto auto -40% -20%;
      width:180px;
      height:180px;
      background:radial-gradient(
        circle,
        rgba(34,197,94,0.14),
        transparent 70%
      );
      animation:floatGlow 9s ease-in-out infinite alternate-reverse;
      pointer-events:none
    }


    .skill-form-row{
      position:relative;
      z-index:1;
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center
    }


    .skill-form label{
      font-weight:800;
      color:var(--text);
      letter-spacing:0.02em
    }


    .animated-field{
      appearance:none;
      outline:none;
      border:1px solid rgba(148,163,184,0.35);
      background:linear-gradient(
        180deg,
        #ffffff,
        rgba(248,250,252,0.9)
      );
      color:var(--text);
      padding:11px 14px;
      border-radius:14px;
      min-height:46px;
      box-shadow:
        inset 0 1px 1px rgba(255,255,255,0.9),
        0 8px 18px rgba(15,23,42,0.03);
      transition:all .22s ease;
      position:relative
    }


    .animated-field:hover{
      border-color:rgba(79,70,229,0.3);
      transform:translateY(-1px)
    }


    .animated-field:focus{
      border-color:rgba(79,70,229,0.7);
      background:linear-gradient(
        180deg,
        #ffffff,
        #f5f3ff
      );
      box-shadow:
        0 0 0 4px rgba(79,70,229,0.12),
        0 12px 24px rgba(79,70,229,0.08);
      transform:translateY(-1px)
    }


    select.animated-field{
      min-width:180px
    }


    input.animated-field{
      min-width:230px;
      flex:1
    }


    .skill-form-button{
      position:relative;
      overflow:hidden;
      border:none;
      padding:12px 20px;
      border-radius:14px;
      font-weight:900;
      letter-spacing:0.01em;
      color:#fff;
      cursor:pointer;
      background:linear-gradient(
        135deg,
        #4f46e5 0%,
        #7c3aed 35%,
        #ec4899 100%
      );
      background-size:200% 200%;
      box-shadow:0 16px 28px rgba(124,58,237,0.28);
      transition:transform .2s ease,
                  box-shadow .2s ease,
                  filter .2s ease;
      animation:pulseButton 3.3s ease-in-out infinite
    }


    .skill-form-button::before{
      content:"";
      position:absolute;
      inset:0;
      background:linear-gradient(
        120deg,
        transparent 0%,
        rgba(255,255,255,0.32) 45%,
        transparent 100%
      );
      transform:translateX(-120%) skewX(-18deg);
      transition:transform .7s ease
    }


    .skill-form-button:hover{
      transform:translateY(-2px) scale(1.01);
      box-shadow:0 18px 30px rgba(124,58,237,0.32);
      filter:saturate(1.1)
    }


    .skill-form-button:hover::before{
      transform:translateX(140%) skewX(-18deg)
    }


    .skill-form-button:active{
      transform:translateY(0) scale(.99)
    }


    .skill-form-button:disabled{
      cursor:not-allowed;
      opacity:0.8;
      animation:none
    }


    .skill-form-message{
      position:relative;
      z-index:1;
      margin-top:10px;
      color:var(--muted);
      font-size:13px;
      font-weight:700
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


    .empty-state{
      display:none;
      padding:24px;
      border-radius:20px;
      background:linear-gradient(135deg,#f8fbff,#f3f7ff);
      border:1px dashed rgba(79,70,229,0.2);
      text-align:center;
      color:var(--muted)
    }


    .empty-state.show{
      display:block
    }


    .empty-state strong{
      display:block;
      color:var(--text);
      margin-bottom:6px;
      font-size:16px
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
      transition:transform .16s ease,
                  box-shadow .16s ease,
                  opacity .2s
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


    @keyframes pulseButton{
      0%,100%{
        background-position:0% 50%;
        box-shadow:0 12px 24px rgba(124,58,237,0.22)
      }

      50%{
        background-position:100% 50%;
        box-shadow:0 18px 30px rgba(236,72,153,0.22)
      }
    }


    @keyframes floatGlow{
      0%{
        transform:translate(-6px,0) scale(.96);
        opacity:.8
      }

      100%{
        transform:translate(10px,-12px) scale(1.08);
        opacity:1
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

      .skills-grid{
        grid-template-columns:1fr
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

      .section-header{
        flex-direction:column;
        align-items:flex-start
      }

      .search-row{
        padding:10px 12px
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
        onerror="this.style.display='none';this.nextElementSibling.style.display='block'"
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
          style="
            font-size:13px;
            color:var(--muted);
            font-weight:700;
            margin-left:8px;
          "
        >
        </span>

      </h1>


      <p class="lead">
        A student-first skill exchange platform —
        teach what you know, learn what you need.
      </p>

    </div>

  </div>


  <div class="topbar-right">

    <div class="top-nav-links">

      <a href="index.php">
        Home
      </a>

      <a href="profile.php">
        Profile
      </a>

      <a href="about.php">
        About
      </a>

      <a href="teach.php" class="active">
        Teach
      </a>

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


    <div class="profile-pill">

      <div
        class="avatar"
        id="avatarInitials"
      >
        SM
      </div>


      <div>

        <strong id="topUserName">
          Student
        </strong>

        <span>
          Active now
        </span>

      </div>

    </div>

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


      <div class="teach-main">


        <div class="page-heading">

          <div class="page-badge">
            ✨ Teaching Hub
          </div>

          <h2>
            Teach Skills
          </h2>

          <p>
            Share your knowledge and help others learn.
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
              type="text"
              id="skillSearch"
              placeholder="Search my teaching skills"
            >

          </div>


          <div
            class="filters"
            aria-label="Category filter"
          >

            <button
              class="filter-pill active"
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
            My Teaching Skills
          </h3>

          <span id="skillCount">
            <?php
            echo count($teachingSkills);
            ?>
            skills ready to share
          </span>

        </div>


        <div
          id="skillsGrid"
          class="skills-grid"
        >
        </div>


        <div
          id="emptyState"
          class="empty-state"
        >

          <strong>
            No skills added yet
          </strong>

          <div>
            Start by adding a new skill card
            to share knowledge with your community.
          </div>

        </div>


        <div class="skill-form">

          <h3
            style="
              margin:0 0 12px;
              position:relative;
              z-index:1;
            "
          >
            Add a Skill
          </h3>


          <div class="skill-form-row">

            <label for="skillCategory">
              Category:
            </label>


            <select
              id="skillCategory"
              class="animated-field"
            >

              <option value="Programming">
                Programming
              </option>

              <option value="Design">
                Design
              </option>

              <option value="Editing">
                Editing
              </option>

              <option value="Data & Office">
                Data & Office
              </option>

              <option value="Communication">
                Communication
              </option>

              <option value="Business">
                Business
              </option>

              <option value="Other">
                Other
              </option>

            </select>


            <label for="skillName">
              Skill:
            </label>


            <input
              id="skillName"
              class="animated-field"
              type="text"
              maxlength="100"
              placeholder="e.g. Java, UI Design"
            >


            <button
              id="addSkillBtn"
              class="skill-form-button"
              type="button"
            >
              Add Skill
            </button>

          </div>


          <div
            id="addSkillMsg"
            class="skill-form-message"
          >
          </div>

        </div>


        <div
          class="skills-grid"
          style="margin-top:14px;"
        >

          <div
            class="add-card"
            role="button"
            tabindex="0"
            aria-label="Add new skill"
          >

            <div class="add-icon">
              ＋
            </div>

            <strong>
              Add New Skill
            </strong>

            <span>
              Create a skill card and help students
              discover what you teach.
            </span>

          </div>

        </div>


        <div
          class="tips-card"
          style="margin-top:14px;"
        >

          <h3>
            Teaching tips
          </h3>


          <div class="tips-list">


            <div class="tip-item">

              <div class="tip-icon">
                ✓
              </div>

              <div>

                <strong>
                  Keep it simple
                </strong>

                <span>
                  Break down concepts into short,
                  clear steps that are easy to follow.
                </span>

              </div>

            </div>


            <div class="tip-item">

              <div class="tip-icon">
                ⏱
              </div>

              <div>

                <strong>
                  Set a rhythm
                </strong>

                <span>
                  Use short sessions and regular
                  check-ins to keep learners engaged.
                </span>

              </div>

            </div>


            <div class="tip-item">

              <div class="tip-icon">
                💡
              </div>

              <div>

                <strong>
                  Share examples
                </strong>

                <span>
                  Practical examples make your teaching
                  more relatable and memorable.
                </span>

              </div>

            </div>


            <div class="tip-item">

              <div class="tip-icon">
                🤝
              </div>

              <div>

                <strong>
                  Encourage questions
                </strong>

                <span>
                  A friendly learning space helps students
                  feel confident and supported.
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
    style="
      float:right;
      display:none;
      background:transparent;
      border:none;
      color:var(--muted);
      cursor:pointer
    "
  >
    Logout
  </button>

</footer>


</div>


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


<!-- =====================================================
     USER DISPLAY
     ===================================================== -->

<script>

window.__skillmateUserName =
<?php
echo json_encode(
    $currentUserName,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);
?>;


function getUser(){

    return JSON.parse(
        localStorage.getItem('skillmate_user') || 'null'
    );

}


window.addEventListener(
    'load',
    function(){

        const storedUser =
            getUser();

        const user =
            storedUser ||
            (
                window.__skillmateUserName
                    ? {
                        name:
                            window.__skillmateUserName
                    }
                    : null
            );


        if(!user){

            window.location.href =
                'login.php';

            return;

        }


        const el =
            document.getElementById(
                'userFullName'
            );


        const avatarEl =
            document.getElementById(
                'avatarInitials'
            );


        const topUserName =
            document.getElementById(
                'topUserName'
            );


        if(
            el &&
            user.name
        ){

            el.textContent =
                '• ' + user.name;

        }


        if(
            avatarEl &&
            user.name
        ){

            const initials =
                user.name
                    .split(' ')
                    .map(
                        part => part[0]
                    )
                    .slice(0,2)
                    .join('')
                    .toUpperCase();


            avatarEl.textContent =
                initials || 'SM';

        }


        if(
            topUserName &&
            user.name
        ){

            topUserName.textContent =
                user.name.split(' ')[0];

        }

    }
);

</script>


<!-- =====================================================
     TEACHING SKILLS
     ===================================================== -->

<script>

(function(){

    /*
     * IMPORTANT:
     * This data comes directly from user_skills table.
     */

    let skills =
    <?php

    echo json_encode(
        $teachingSkills,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    );

    ?>;


    const grid =
        document.getElementById(
            'skillsGrid'
        );


    const emptyState =
        document.getElementById(
            'emptyState'
        );


    const searchInput =
        document.getElementById(
            'skillSearch'
        );


    const filterButtons =
        document.querySelectorAll(
            '.filter-pill'
        );


    const skillCount =
        document.getElementById(
            'skillCount'
        );


    let selectedCategory =
        'Programming';


    if(!grid){
        return;
    }


    /* =====================================================
       ESCAPE HTML
       ===================================================== */

    function escapeHtml(value){

        return String(
            value ?? ''
        ).replace(
            /[&<>"']/g,
            function(character){

                const entities = {

                    '&':'&amp;',
                    '<':'&lt;',
                    '>':'&gt;',
                    '"':'&quot;',
                    "'":'&#039;'

                };

                return entities[
                    character
                ];

            }
        );

    }


    /* =====================================================
       UPDATE COUNT
       ===================================================== */

    function updateCount(number){

        if(!skillCount){
            return;
        }


        skillCount.textContent =
            number +
            (
                number === 1
                    ? ' skill ready to share'
                    : ' skills ready to share'
            );

    }


    /* =====================================================
       RENDER SKILLS
       ===================================================== */

    function renderSkills(list){

        grid.innerHTML = '';


        updateCount(
            skills.length
        );


        if(list.length === 0){

            emptyState.classList.add(
                'show'
            );


            emptyState.innerHTML = `

                <strong>
                    No teaching skills found.
                </strong>

                <div>
                    Add a skill or select
                    another category.
                </div>

            `;


            return;

        }


        emptyState.classList.remove(
            'show'
        );


        list.forEach(
            function(skill){

                const card =
                    document.createElement(
                        'article'
                    );


                card.className =
                    'skill-card';


                const safeName =
                    escapeHtml(
                        skill.skill_name
                    );


                const safeCategory =
                    escapeHtml(
                        skill.category
                    );


                card.innerHTML = `

                    <div class="skill-top">

                        <div class="skill-name">
                            ${safeName}
                        </div>

                        <span
                            class="badge intermediate"
                        >
                            ${safeCategory}
                        </span>

                    </div>


                    <p>
                        Teaching skill from
                        your SkillMate profile.
                    </p>


                    <div class="skill-meta">

                        <span>
                            Shared skill
                        </span>

                        <span>
                            ${safeCategory}
                        </span>

                    </div>


                    <div class="skill-actions">

                        <button
                            class="tiny-btn edit"
                            type="button"
                            data-skill-id="${skill.id}"
                        >
                            Edit
                        </button>


                        <button
                            class="tiny-btn delete"
                            type="button"
                            data-skill-id="${skill.id}"
                        >
                            Delete
                        </button>

                    </div>

                `;


                grid.appendChild(
                    card
                );

            }
        );


        attachDeleteButtons();

    }


    /* =====================================================
       FILTER + SEARCH
       ===================================================== */

    function applyFilters(){

        const searchText =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';


        const filtered =
            skills.filter(
                function(skill){

                    const categoryMatch =
                        skill.category ===
                        selectedCategory;


                    const searchMatch =
                        searchText === '' ||

                        skill.skill_name
                            .toLowerCase()
                            .includes(
                                searchText
                            ) ||

                        skill.category
                            .toLowerCase()
                            .includes(
                                searchText
                            );


                    return (
                        categoryMatch &&
                        searchMatch
                    );

                }
            );


        renderSkills(
            filtered
        );

    }


    /* =====================================================
       CATEGORY BUTTONS
       ===================================================== */

    filterButtons.forEach(
        function(button){

            button.addEventListener(
                'click',
                function(){

                    filterButtons.forEach(
                        function(btn){

                            btn.classList.remove(
                                'active'
                            );

                        }
                    );


                    button.classList.add(
                        'active'
                    );


                    selectedCategory =
                        button.dataset.category ||
                        button.textContent.trim();


                    applyFilters();

                }
            );

        }
    );


    /* =====================================================
       SEARCH
       ===================================================== */

    if(searchInput){

        searchInput.addEventListener(
            'input',
            function(){

                applyFilters();

            }
        );

    }


    /* =====================================================
       DELETE
       ===================================================== */

    function attachDeleteButtons(){

        const deleteButtons =
            document.querySelectorAll(
                '.tiny-btn.delete'
            );


        deleteButtons.forEach(
            function(button){

                button.addEventListener(
                    'click',
                    function(){

                        const skillId =
                            parseInt(
                                button.dataset.skillId,
                                10
                            );


                        if(!skillId){
                            return;
                        }


                        if(
                            !confirm(
                                'Are you sure you want to delete this skill?'
                            )
                        ){

                            return;

                        }


                        button.disabled =
                            true;


                        fetch(
                            'teach.php',
                            {

                                method:'POST',

                                headers:{
                                    'Content-Type':
                                        'application/x-www-form-urlencoded'
                                },

                                body:
                                    'action=delete_skill' +
                                    '&skill_id=' +
                                    encodeURIComponent(
                                        skillId
                                    )

                            }
                        )

                        .then(
                            function(response){

                                return response.json();

                            }
                        )

                        .then(
                            function(data){

                                if(!data.success){

                                    alert(
                                        data.error ||
                                        'Unable to delete the skill.'
                                    );


                                    button.disabled =
                                        false;

                                    return;

                                }


                                /*
                                 * Remove from
                                 * JavaScript array.
                                 */

                                skills =
                                    skills.filter(
                                        function(skill){

                                            return (
                                                parseInt(
                                                    skill.id,
                                                    10
                                                ) !==
                                                skillId
                                            );

                                        }
                                    );


                                applyFilters();

                            }
                        )

                        .catch(
                            function(){

                                alert(
                                    'Network error. Please try again.'
                                );


                                button.disabled =
                                    false;

                            }
                        );

                    }
                );

            }
        );

    }


    /* =====================================================
       MAKE FUNCTIONS AVAILABLE TO ADD-SKILL SCRIPT
       ===================================================== */

    window.skillmateSkills =
        skills;


    window.skillmateRefresh =
        function(){

            /*
             * Keep global array synchronized.
             */

            skills =
                window.skillmateSkills;

            applyFilters();

        };


    /* =====================================================
       INITIAL DISPLAY
       ===================================================== */

    renderSkills(
        skills.filter(
            function(skill){

                return (
                    skill.category ===
                    selectedCategory
                );

            }
        )
    );


})();

</script>


<!-- =====================================================
     ADD SKILL
     ===================================================== -->

<script>

(function(){

    const addBtn =
        document.getElementById(
            'addSkillBtn'
        );


    const catEl =
        document.getElementById(
            'skillCategory'
        );


    const nameEl =
        document.getElementById(
            'skillName'
        );


    const msgEl =
        document.getElementById(
            'addSkillMsg'
        );


    if(!addBtn){
        return;
    }


    addBtn.addEventListener(
        'click',
        function(){

            const category =
                catEl.value.trim();


            const skill =
                nameEl.value.trim();


            msgEl.textContent =
                '';


            if(!category){

                msgEl.textContent =
                    'Please select a category.';

                return;

            }


            if(!skill){

                msgEl.textContent =
                    'Enter a skill name.';

                nameEl.focus();

                return;

            }


            /*
             * Front-end duplicate check.
             */

            const duplicate =
                window.skillmateSkills.some(
                    function(existingSkill){

                        return (
                            existingSkill.skill_name
                                .trim()
                                .toLowerCase() ===
                            skill.toLowerCase()
                        );

                    }
                );


            if(duplicate){

                msgEl.textContent =
                    'You have already added this skill.';

                return;

            }


            addBtn.disabled =
                true;


            msgEl.textContent =
                'Saving skill...';


            fetch(
                'php/skill_save.php',
                {

                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/x-www-form-urlencoded'
                    },

                    body:
                        'category=' +
                        encodeURIComponent(
                            category
                        ) +

                        '&skill=' +
                        encodeURIComponent(
                            skill
                        )

                }
            )

            .then(
                function(response){

                    return response.json();

                }
            )

            .then(
                function(data){

                    addBtn.disabled =
                        false;


                    if(!data.success){

                        msgEl.textContent =
                            data.error ||
                            'Unable to add skill.';

                        return;

                    }


                    /*
                     * Add database record
                     * to current page.
                     */

                    window.skillmateSkills.unshift({

                        id:
                            parseInt(
                                data.id,
                                10
                            ),

                        skill_name:
                            skill,

                        category:
                            category,

                        created_at:
                            new Date().toISOString()

                    });


                    nameEl.value =
                        '';


                    msgEl.textContent =
                        'Skill added successfully.';


                    /*
                     * Automatically select
                     * the category that was added.
                     */

                    document
                        .querySelectorAll(
                            '.filter-pill'
                        )
                        .forEach(
                            function(button){

                                button.classList.remove(
                                    'active'
                                );


                                if(
                                    button.dataset.category ===
                                    category
                                ){

                                    button.classList.add(
                                        'active'
                                    );

                                }

                            }
                        );


                    /*
                     * Refresh skill display.
                     */

                    if(
                        typeof window.skillmateRefresh ===
                        'function'
                    ){

                        window.skillmateRefresh();

                    }

                }
            )

            .catch(
                function(error){

                    console.error(
                        error
                    );


                    addBtn.disabled =
                        false;


                    msgEl.textContent =
                        'Network error. Please try again.';

                }
            );

        }
    );


    /*
     * Allow Enter key to add skill.
     */

    nameEl.addEventListener(
        'keydown',
        function(event){

            if(
                event.key ===
                'Enter'
            ){

                event.preventDefault();

                addBtn.click();

            }

        }
    );

})();

</script>


<!-- =====================================================
     QUOTES
     ===================================================== -->

<script>

(function(){

    const quotes = [

        {
            text:
                'Learning never exhausts the mind.',
            author:
                'Leonardo da Vinci'
        },

        {
            text:
                'Education is the most powerful weapon which you can use to change the world.',
            author:
                'Nelson Mandela'
        },

        {
            text:
                'Tell me and I forget. Teach me and I remember. Involve me and I learn.',
            author:
                'Benjamin Franklin'
        }

    ];


    const quoteText =
        document.getElementById(
            'quoteText'
        );


    const quoteAuthor =
        document.getElementById(
            'quoteAuthor'
        );


    if(
        !quoteText ||
        !quoteAuthor
    ){

        return;

    }


    let index = 0;


    function showQuote(){

        const q =
            quotes[
                index %
                quotes.length
            ];


        quoteText.textContent =
            '"' +
            q.text +
            '"';


        quoteAuthor.textContent =
            '— ' +
            q.author;


        index += 1;

    }


    showQuote();


    setInterval(
        showQuote,
        60000
    );


})();

</script>


<!-- =====================================================
     LOGOUT
     ===================================================== -->

<script>

(function(){

    const fab =
        document.getElementById(
            'logoutFab'
        );


    if(!fab){
        return;
    }


    function getUser(){

        return JSON.parse(
            localStorage.getItem(
                'skillmate_user'
            ) || 'null'
        );

    }


    function showFab(){

        fab.classList.remove(
            'hide'
        );

    }


    function hideFab(){

        fab.classList.add(
            'hide'
        );

    }


    if(getUser()){

        showFab();

    }


    fab.addEventListener(
        'click',
        function(){

            fab.style.transform =
                'scale(.96)';

            fab.style.opacity =
                '0.9';


            setTimeout(
                function(){

                    localStorage.removeItem(
                        'skillmate_user'
                    );


                    fab.style.transition =
                        'transform .22s ease,opacity .22s ease';


                    fab.style.transform =
                        'translateY(18px) scale(.9)';


                    fab.style.opacity =
                        '0';


                    setTimeout(
                        function(){

                            window.location.href =
                                'login.php';

                        },
                        260
                    );

                },
                120
            );

        }
    );

})();

</script>


</body>

</html>