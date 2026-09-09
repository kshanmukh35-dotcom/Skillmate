<?php
session_start();

if(!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

include "db_connect.php";

$user_id = mysqli_real_escape_string($conn, $_SESSION["user_id"]);

$profileQuery = mysqli_query(
    $conn,
    "SELECT full_name, profile_image FROM profiles WHERE user_id='$user_id'"
);

$profile = mysqli_fetch_assoc($profileQuery);

$profileImage = $profile['profile_image'] ?? '';
$profileName = $profile['full_name'] ?? $_SESSION["full_name"] ?? '';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — Skill Exchange for Students</title>

  <style>
    :root{
      --primary:#2f4d9a;
      --primary-soft:#edf2ff;
      --primary-strong:#1f355f;
      --bg:#f3f5f9;
      --surface:#ffffff;
      --surface-soft:#f8f9fc;
      --text:#18212f;
      --muted:#5f6f86;
      --accent:#2a9d5d;
      --accent-soft:#edf9f1;
      --nav-bg:#111827;
      --nav-text:#f8fafc;
      --border:rgba(17,24,39,0.08);
      --shadow:0 10px 24px rgba(15,23,42,0.05);
      --radius:16px;
    }

    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased}
    .container{max-width:1200px;margin:24px auto 40px;padding:20px}
    .topbar-shell{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 20px;border-radius:18px;background:var(--surface);box-shadow:var(--shadow);border:1px solid var(--border)}
    .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
    .logo{width:58px;height:58px;border-radius:14px;overflow:hidden;display:grid;place-items:center;background:#fff;border:1px solid var(--border);box-shadow:0 8px 18px rgba(15,23,42,0.05);position:relative;isolation:isolate;transition:transform .2s ease}
    .logo::before{display:none}
    .logo img{width:100%;height:100%;object-fit:cover;opacity:1;transform:none;animation:none;filter:none}
    .logo .logo-fallback{display:none;width:100%;height:100%;align-items:center;justify-content:center;display:grid;font-size:18px;font-weight:800;color:var(--primary)}
    .eyebrow{margin:0 0 3px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:var(--primary);font-weight:800}
    h1{margin:0;font-size:clamp(24px,3vw,30px);letter-spacing:-0.02em}
    .topbar-title{margin:0;font-size:clamp(20px,2.3vw,24px);letter-spacing:-0.02em}
    p.lead{color:var(--muted);margin-top:8px;line-height:1.7;max-width:680px}
    .topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .top-nav-links{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:6px;border-radius:999px;background:var(--surface-soft);border:1px solid var(--border)}
    .top-nav-links a{padding:8px 12px;border-radius:999px;text-decoration:none;color:var(--muted);font-weight:700;font-size:13px;transition:all .16s ease}
    .top-nav-links a:hover{background:#fff;color:var(--primary);box-shadow:0 4px 10px rgba(15,23,42,0.04)}
    .top-nav-links a.active{background:var(--primary-soft);color:var(--primary)}
    .icon-button{width:42px;height:42px;border:none;border-radius:50%;display:grid;place-items:center;background:#fff;color:var(--primary);box-shadow:0 6px 16px rgba(15,23,42,0.05);border:1px solid var(--border);cursor:pointer;transition:transform .16s ease,box-shadow .16s ease}
    .icon-button:hover{transform:translateY(-1px);box-shadow:0 10px 18px rgba(15,23,42,0.08)}
    .profile-pill{display:flex;align-items:center;gap:10px;padding:6px 10px;border-radius:999px;background:#fff;border:1px solid var(--border);box-shadow:0 6px 15px rgba(15,23,42,0.04)}
    .profile-pill:hover{transform:translateY(-1px)}
    .avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),#5d78d8);color:#fff;font-weight:800;font-size:14px;border:2px solid #fff;box-shadow:0 4px 12px rgba(15,23,42,0.12)}
    .profile-pill strong{display:block;font-size:13px;color:var(--text)}
    .profile-pill span{display:block;color:var(--muted);font-size:11px}
    .card{background:var(--surface);padding:24px;border-radius:18px;box-shadow:var(--shadow);margin-top:20px;border:1px solid var(--border)}
    .content-panel{display:grid;grid-template-columns:1fr;gap:18px}
    .grid{display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start}
    .leftPanel{display:flex;flex-direction:column;gap:16px}
    .action-box{position:relative;padding:18px;border-radius:16px;background:#fff;border:1px solid var(--border);box-shadow:0 6px 18px rgba(15,23,42,0.03);overflow:hidden;transition:transform .16s ease}
    .action-box:hover{transform:translateY(-2px)}
    .action-box h3{margin:0 0 12px;font-size:16px;color:var(--text)}
    .action-box .actions-grid{display:grid;gap:10px}
    .action-box .act{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;border:1px solid var(--border);background:#fff;color:var(--text);font-weight:700;cursor:pointer;justify-content:flex-start;transition:transform .14s ease,box-shadow .14s ease,background .14s ease;border-left:3px solid transparent}
    .action-box .act:hover{transform:translateX(2px);box-shadow:0 8px 16px rgba(15,23,42,0.04);background:var(--surface-soft)}
    .action-box .act:active{transform:scale(0.98)}
    .action-box .act.active{background:var(--primary-soft);color:var(--primary);border-left-color:var(--primary);border-color:rgba(47,77,154,0.14)}
    .nav-icon{width:32px;height:32px;border-radius:10px;background:var(--surface-soft);display:grid;place-items:center;color:var(--primary);font-size:13px;border:1px solid rgba(47,77,154,0.08)}
    .action-box::before{content:"";position:absolute;left:0;top:0;width:100%;height:3px;background:var(--primary);opacity:0.9}
    .quote-box{padding:16px;border-radius:14px;background:var(--surface-soft);border:1px solid var(--border);box-shadow:0 6px 14px rgba(15,23,42,0.03)}
    .quote-box blockquote{font-style:italic;margin:0;line-height:1.6;color:var(--text)}
    .quote-author{margin-top:10px;color:var(--muted);font-weight:700}
    .features{display:grid;gap:12px;margin-top:18px}
    .hero-panel{display:grid;grid-template-columns:1.2fr 0.8fr;gap:18px;padding:22px;border-radius:18px;background:#fff;border:1px solid var(--border);box-shadow:var(--shadow)}
    .hero-copy h2{margin:0 0 8px;font-size:24px;letter-spacing:-0.02em}
    .hero-copy p{margin:0;color:var(--muted);line-height:1.7}
    .hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:var(--primary-soft);border:1px solid rgba(47,77,154,0.08);font-size:12px;font-weight:800;color:var(--primary);margin-bottom:12px}
    .hero-illustration{min-height:180px;border-radius:16px;background:var(--surface-soft);border:1px solid var(--border);display:grid;place-items:center;position:relative;overflow:hidden}
    .hero-illustration::before,.hero-illustration::after{display:none}
    .hero-illustration svg{width:140px;height:140px;position:relative;z-index:1}
    .feature-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px}
    .feature-card{padding:16px;border-radius:14px;background:#fff;border:1px solid var(--border);box-shadow:0 6px 14px rgba(15,23,42,0.03);display:flex;align-items:flex-start;gap:12px;transition:transform .16s ease,box-shadow .16s ease;min-height:120px}
    .feature-card:hover{transform:translateY(-2px);box-shadow:0 10px 18px rgba(15,23,42,0.05)}
    .feature-icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;font-size:16px;background:var(--primary-soft);color:var(--primary);border:1px solid rgba(47,77,154,0.08)}
    .feature-card strong{display:block;margin-bottom:4px}
    .feature-card p{margin:0;color:var(--muted);font-size:13px;line-height:1.6}
    .actions{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap}
    a.btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:12px;text-decoration:none;color:#fff;background:var(--primary);border:none;box-shadow:0 8px 18px rgba(47,77,154,0.14);transition:transform .12s ease,box-shadow .12s ease,font-weight:700;position:relative;overflow:hidden}
    a.btn::after{display:none}
    a.btn:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(47,77,154,0.18)}
    a.ghost{background:transparent;color:var(--primary);border:1px solid var(--border);box-shadow:none}
    a.ghost:hover{background:var(--surface-soft)}
    .dashboard-cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:20px;align-items:stretch}
    .dashboard-card{padding:22px;border-radius:18px;background:linear-gradient(180deg,#ffffff 0%, #f9fbff 100%);border:1px solid rgba(47,77,154,0.12);box-shadow:0 12px 26px rgba(15,23,42,0.04);transition:transform .16s ease,box-shadow .16s ease;display:flex;flex-direction:column;justify-content:flex-start;min-height:250px;position:relative;overflow:hidden}
    .dashboard-card::before{content:"";position:absolute;inset:0 auto auto 0;width:100%;height:4px;background:linear-gradient(90deg,var(--primary),#6ea0ff);opacity:.9}
    .empty-state{margin-top:18px;padding:16px 18px;border-radius:16px;background:var(--surface-soft);border:1px dashed rgba(47,77,154,0.18);color:var(--muted);display:flex;justify-content:space-between;align-items:center;gap:12px}
    .empty-state strong{color:var(--text);display:block;margin-bottom:2px}
    .dashboard-card:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(15,23,42,0.08)}
    .dashboard-card strong{display:block;font-size:13px;color:var(--primary);letter-spacing:0.07em;margin-bottom:12px;text-transform:uppercase}
    .dashboard-card p{margin:0 0 18px;color:var(--muted);line-height:1.7;font-size:14px;flex:1}
    .dashboard-card-analysis{background:linear-gradient(180deg,#ffffff 0%, #f3f8ff 100%);border-color:rgba(47,77,154,0.12)}
    .dashboard-card-roadmap{background:linear-gradient(180deg,#ffffff 0%, #f4f9f7 100%);border-color:rgba(42,157,93,0.14)}
    .dashboard-card .card-icon{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;margin-bottom:12px;background:var(--primary-soft);box-shadow:none;border:1px solid rgba(47,77,154,0.08);font-size:20px}
    .dashboard-card .btn{border-radius:12px;box-shadow:0 10px 20px rgba(47,77,154,0.10);margin-top:auto;display:inline-flex;align-self:flex-start;padding:11px 16px;width:auto;min-width:160px;justify-content:center}
    .dashboard-card-roadmap .btn{background:linear-gradient(135deg,var(--accent),#2ca977);box-shadow:0 10px 20px rgba(42,157,93,0.12)}
    aside .stat{background:var(--surface-soft);padding:12px;border-radius:12px;border:1px solid var(--border)}
    footer{margin-top:22px;color:var(--muted);font-size:13px;display:flex;justify-content:space-between;align-items:center;padding-top:8px}
    footer{color:var(--primary)}
    @keyframes logoFade{to{opacity:1;transform:translateY(0) scale(1)}}
    @keyframes logoShine{0%{transform:translateX(-120%) rotate(8deg)}100%{transform:translateX(120%) rotate(8deg)}}
    .logo:hover{transform:translateY(-2px);box-shadow:0 12px 22px rgba(15,23,42,0.09)}
    .logo:hover img{transform:none;filter:none}
    .logout-fab{position:fixed;right:22px;bottom:22px;display:inline-flex;align-items:center;gap:10px;padding:12px 14px;border-radius:999px;background:var(--nav-bg);color:var(--nav-text);border:none;box-shadow:0 12px 26px rgba(2,6,23,0.12);cursor:pointer;font-weight:800;font-size:14px;transform:translateY(0);transition:transform .16s ease,box-shadow .16s ease,opacity .2s}
    .logout-fab svg{width:18px;height:18px;opacity:0.95}
    .logout-fab:hover{transform:translateY(-2px);box-shadow:0 18px 30px rgba(2,6,23,0.15)}
    .logout-fab.hide{opacity:0;pointer-events:none;transform:translateY(12px) scale(.98)}
    @media (max-width:900px){.grid{grid-template-columns:1fr}.dashboard-cards{grid-template-columns:1fr}.container{padding:16px}.topbar-shell{flex-direction:column;align-items:flex-start}.topbar-right{width:100%;justify-content:space-between}}
    @media (max-width:680px){.topbar-right{flex-wrap:wrap}.top-nav-links{width:100%;justify-content:center}.hero-panel{grid-template-columns:1fr}.feature-grid{grid-template-columns:1fr}.actions{gap:10px}.empty-state{flex-direction:column;align-items:flex-start}}
    .main-profile-image{width:150px;height:150px;border-radius:50%;object-fit:cover;border:6px solid #fff;box-shadow:0 12px 30px rgba(79,70,229,0.12)}

.default-profile{width:150px;height:150px;border-radius:50%;background:#edf2ff;display:flex;align-items:center;justify-content:center;border:6px solid #fff;box-shadow:0 12px 30px rgba(79,70,229,0.08)}

.default-profile span{font-size:70px;color:var(--primary);font-weight:300}
.notification-bell {width: 52px;height: 52px;border-radius: 50%;display: grid;place-items: center;text-decoration: none;background: #ffffff;color: var(--primary);border: 1px solid rgba(15, 23, 42, 0.08);font-size: 20px;box-shadow:0 8px 24px rgba(15, 23, 42, 0.06);transition:transform .18s ease,box-shadow .18s ease,background .18s ease;}
.notification-bell:hover {transform: translateY(-2px);background: var(--primary-soft);box-shadow:0 14px 30px rgba(79, 70, 229, 0.12);}
    </style>
  </head>
  <body>
  <div id="mainContent" class="container">
    <header class="topbar-shell">
      <div class="topbar-left">
        <div class="logo"><img src="PROJECT LOGO.png" alt="SkillMate logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'"><span class="logo-fallback" style="display:none;font-weight:800;color:var(--primary);">SM</span></div>
        <div>
          <p class="eyebrow">Student dashboard</p>
          <h1 class="topbar-title">Skill Dashboard <span id="userFullName" style="font-size:13px;color:var(--muted);font-weight:700;margin-left:8px;"><?php echo $_SESSION["full_name"];?></span></h1>
          <p class="lead">A student-first skill exchange platform — teach what you know, learn what you need.</p>
        </div>
      </div>
      <div class="topbar-right">
        <div class="top-nav-links">
          <a href="index.php" class="active">Home</a>
          <a href="profile.php">Profile</a>
          <a href="about.php">About</a>
        </div>
        <a href="notification.php" class="notification-bell" title="Notifications">
    🔔
</a>
        
         <div class="profile-pill">

    <?php if (!empty($profileImage)): ?>

        <img
            class="avatar"
            src="<?php echo htmlspecialchars($profileImage); ?>"
            alt="Profile Photo"
        >

    <?php else: ?>

        <div class="avatar" id="avatarInitials">
            SM
        </div>

    <?php endif; ?>

    <div>
        <strong><?php echo htmlspecialchars($profileName); ?></strong>
        <span>Active now</span>
    </div>

</div>
      </div>
    </header>

    <div class="card">
      <div class="content-panel">
        <div class="grid">
          <aside class="leftPanel">
            <div class="action-box" role="navigation" aria-label="Quick actions">
              <h3>Explore</h3>
              <div class="actions-grid">
                <a class="act" href="teach.php"><span class="nav-icon">✦</span><span>Teach</span></a>
                <a class="act active" href="learn.php"><span class="nav-icon">◆</span><span>Learn</span></a>
                <a class="act" href="messages.php"><span class="nav-icon">✉</span><span>Messages</span></a>
                <a class="act" href="requests.php"><span class="nav-icon">☰</span><span>Requests</span></a>
              </div>
            </div>

            <div class="quote-box" aria-live="polite" aria-atomic="true">
              <blockquote id="quoteText" style="margin:0;font-size:15px;color:var(--text);line-height:1.5;opacity:0;transform:translateY(6px);transition:all .45s ease">"Learning never exhausts the mind."</blockquote>
              <div id="quoteAuthor" style="margin-top:10px;color:var(--muted);font-weight:700;opacity:0;transform:translateY(6px);transition:all .45s ease">— Leonardo da Vinci</div>
            </div>
          </aside>

          <div>
            <div class="hero-panel">
              <div class="hero-copy">
                <div class="hero-badge">✨ Welcome back to SkillMate</div>
                <h2>Keep growing with your next skill move.</h2>
                <p>Create a profile, share what you know, and discover the next best step in your learning journey.</p>
                <div class="actions">
                  <a id="getStarted" class="btn" href="profile.php">Get Started</a>
                  <a id="learnMore" class="btn ghost" href="about.php">Learn More</a>
                </div>
              </div>
             <div class="hero-illustration">

    <?php if (!empty($profileImage)): ?>

        <img
            src="<?php echo htmlspecialchars($profileImage); ?>"
            alt="Your Profile Photo"
            class="main-profile-image"
        >

    <?php else: ?>

        <div class="default-profile">
            <span>+</span>
        </div>

    <?php endif; ?>

</div> 
                    

            <div class="feature-grid">
              <div class="feature-card">
                <div class="feature-icon">✦</div>
                <div>
                  <strong>Teach a Skill</strong>
                  <p>Share knowledge and guide other students with short sessions.</p>
                </div>
              </div>
              <div class="feature-card">
                <div class="feature-icon">◆</div>
                <div>
                  <strong>Learn a Skill</strong>
                  <p>Find peers and mentors who can help you grow faster.</p>
                </div>
              </div>
              <div class="feature-card">
                <div class="feature-icon">◌</div>
                <div>
                  <strong>View Profile</strong>
                  <p>Keep your learning goals and expertise visible to the community.</p>
                </div>
              </div>
              <div class="feature-card">
                <div class="feature-icon">✉</div>
                <div>
                  <strong>Messages</strong>
                  <p>Stay connected with matches, tutors, and study partners.</p>
                </div>
              </div>
            </div>

            <div class="empty-state">
              <div>
                <strong>Ready for your next learning step?</strong>
                <div>Jump into your roadmap or keep your profile up to date when you’re ready.</div>
              </div>
              <a class="btn ghost" href="profile.php">Update profile</a>
            </div>

            <div class="dashboard-cards">
              <div class="dashboard-card dashboard-card-analysis">
                <div class="card-icon">📈</div>
                <strong>SKILL GAP ANALYSIS</strong>
                <p>Identify the key skills you have versus the skills you need, then get a focused gap summary to prioritize your next learning steps.</p>
                <a class="btn" href="skill-gap-analysis.php">Open analysis</a>
              </div>
              <div class="dashboard-card dashboard-card-roadmap">
                <div class="card-icon">🧭</div>
                <strong>ROADMAP GENERATOR</strong>
                <p>Create a clear learning roadmap from your current strengths to your target abilities with milestone checkpoints and suggested resources.</p>
                <a class="btn" href="roadmap.php">Open roadmap</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <footer>
      <span id="footerText">Built for students • © SkillMate</span>
      <a href="php/logout.php">
      <button id="logoutBtn" style="float:right;display:inline-block;background:#e74c3c;color:white;border:none;padding:10px 20px;border-radius:8px;font-size:15px;font-weight:bold;cursor:pointer;box-shadow:0 4px 8px rgba(0,0,0,0.2);">Logout</button>
</a>
    </footer>
  <script>
    const user ={
      name:"<?php echo $_SESSION['full_name']; ?>"
    };
      const el = document.getElementById('userFullName');
      const avatarEl = document.getElementById('avatarInitials');
      const topUserName = document.getElementById('topUserName');
      if(el && user.name){ el.textContent = '• ' + user.name; }
      if(avatarEl && user.name){
        const initials = user.name.split(' ').map(part => part[0]).slice(0,2).join('').toUpperCase();
        avatarEl.textContent = initials || 'SM';
      }
      if(topUserName && user.name){ topUserName.textContent = user.name.split(' ')[0]; }
    });
  </script>
  <script>
    (function(){
      const quotes = [
        {text: 'Learning never exhausts the mind.', author: 'Leonardo da Vinci'},
        {text: 'Education is the most powerful weapon which you can use to change the world.', author: 'Nelson Mandela'},
        {text: 'Tell me and I forget. Teach me and I remember. Involve me and I learn.', author: 'Benjamin Franklin'},
        {text: 'The beautiful thing about learning is nobody can take it away from you.', author: 'B.B. King'},
        {text: 'Live as if you were to die tomorrow. Learn as if you were to live forever.', author: 'Mahatma Gandhi'},
        {text: 'You can never be overdressed or overeducated.', author: 'Oscar Wilde'}
      ];

      const quoteText = document.getElementById('quoteText');
      const quoteAuthor = document.getElementById('quoteAuthor');
      if(!quoteText || !quoteAuthor) return;

      function showQuote(q){
        quoteText.style.opacity = 0; quoteText.style.transform = 'translateY(6px)';
        quoteAuthor.style.opacity = 0; quoteAuthor.style.transform = 'translateY(6px)';
        setTimeout(()=>{
          quoteText.textContent = '"' + q.text + '"';
          quoteAuthor.textContent = '— ' + q.author;
          quoteText.style.opacity = 1; quoteText.style.transform = 'translateY(0)';
          quoteAuthor.style.opacity = 1; quoteAuthor.style.transform = 'translateY(0)';
        }, 200);
      }

      function pickRandom(){
        return quotes[Math.floor(Math.random()*quotes.length)];
      }

      // initial
      showQuote(pickRandom());

      // update every minute (60000ms)
      setInterval(()=>{
        showQuote(pickRandom());
      }, 60000);
    })();
  </script>
    <button id="logoutFab" class="logout-fab hide" aria-label="Sign out">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M9 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Sign out</span>
    </button>

    <script>
      (function(){
        const fab = document.getElementById('logoutFab');
        if(!fab) return;
        function getUser(){ return JSON.parse(localStorage.getItem('skillmate_user')||'null'); }
        function showFab(){ fab.classList.remove('hide'); }
        function hideFab(){ fab.classList.add('hide'); }

        // Show when logged in
        if(getUser()){ showFab(); }

        fab.addEventListener('click', ()=>{
          fab.style.transform = 'scale(.96)';
          fab.style.opacity = '0.9';
          // small delay for effect
          setTimeout(()=>{
            localStorage.removeItem('skillmate_user');
            // animate out
            fab.style.transition = 'transform .22s ease,opacity .22s ease';
            fab.style.transform = 'translateY(18px) scale(.9)';
            fab.style.opacity = '0';
            setTimeout(()=>{ window.location.href = 'login.php'; }, 260);
          }, 120);
        });
      })();
    </script>
  </body>
</html>

