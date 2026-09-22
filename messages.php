<?php
session_start();
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}
$userId = (int)$_SESSION['user_id'];
$currentUserName = $_SESSION['full_name'] ?? 'Student';
$currentUserProfileImage = '';

$userProfileStmt = mysqli_prepare($conn, "SELECT p.profile_image FROM profiles p WHERE p.user_id = ? LIMIT 1");
if ($userProfileStmt) {
  mysqli_stmt_bind_param($userProfileStmt, 'i', $userId);
  mysqli_stmt_execute($userProfileStmt);
  mysqli_stmt_bind_result($userProfileStmt, $dbProfileImage);
  if (mysqli_stmt_fetch($userProfileStmt)) {
    $currentUserProfileImage = $dbProfileImage ?: '';
  }
  mysqli_stmt_close($userProfileStmt);
}

$requestedOtherId = isset($_GET['other_id']) ? (int)$_GET['other_id'] : 0;

// Build conversations: latest message per other user
$conversations = [];
$convSql = "
SELECT t.other_id, u.full_name, u.role, p.profile_image, m.message AS last_message, m.created_at AS last_time
FROM (
  SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id, MAX(created_at) AS last_at
  FROM messages
  WHERE sender_id = ? OR receiver_id = ?
  GROUP BY other_id
) t
JOIN messages m ON ((m.sender_id = ? AND m.receiver_id = t.other_id) OR (m.receiver_id = ? AND m.sender_id = t.other_id)) AND m.created_at = t.last_at
LEFT JOIN users u ON u.user_id = t.other_id
LEFT JOIN profiles p ON p.user_id = t.other_id
ORDER BY m.created_at DESC
";

if ($stmt = mysqli_prepare($conn, $convSql)) {
  mysqli_stmt_bind_param($stmt, 'iiiii', $userId, $userId, $userId, $userId, $userId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) {
    $otherId = (int)$row['other_id'];
    $conversations[] = [
      'id' => $otherId,
      'name' => $row['full_name'] ?: 'Student',
      'role' => $row['role'] ?: 'Member',
      'skill' => '',
      'status' => 'offline',
      'profileImg' => $row['profile_image'] ?: '',
      'unread' => 0,
      'lastMessage' => $row['last_message'] ?: '',
      'lastTime' => $row['last_time'] ?: ''
    ];
  }
  mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — Messages</title>
  <style>
    :root {
      --primary: #4f46e5;
      --primary-strong: #312e81;
      --secondary: #7c3aed;
      --accent: #22c55e;
      --bg: #f5f7ff;
      --surface: rgba(255,255,255,0.78);
      --surface-strong: rgba(255,255,255,0.96);
      --text: #14213d;
      --muted: #64748b;
      --border: rgba(255,255,255,0.55);
      --shadow: 0 22px 60px rgba(15, 23, 42, 0.13);
      --radius: 24px;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top left, rgba(79, 70, 229, 0.16), transparent 28%),
        radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.14), transparent 24%),
        var(--bg);
      min-height: 100vh;
    }

    a { color: inherit; text-decoration: none; }

    .page { max-width: 1320px; margin: 0 auto; padding: 24px; }

    .glass {
      background: var(--surface);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .topbar-shell{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      padding:18px 20px;
      border-radius:18px;
      background:var(--surface-strong);
      box-shadow:var(--shadow);
      border:1px solid rgba(15,23,42,0.08);
      margin-bottom:20px;
    }
    .topbar-left{display:flex;align-items:center;gap:12px;min-width:0}
    .topbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .logo{width:58px;height:58px;border-radius:14px;overflow:hidden;display:grid;place-items:center;background:#fff;border:1px solid rgba(15,23,42,0.08);box-shadow:0 8px 18px rgba(15,23,42,0.05);position:relative;isolation:isolate;transition:transform .2s ease}
    .logo img{width:100%;height:100%;object-fit:cover}
    .logo-fallback{display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:var(--primary)}
    .eyebrow{margin:0 0 3px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:var(--primary);font-weight:800}
    .topbar-title{margin:0;font-size:clamp(20px,2.3vw,24px);letter-spacing:-0.02em}
    .lead{color:var(--muted);margin-top:8px;line-height:1.7;max-width:680px}
    .top-nav-links{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:6px;border-radius:999px;background:rgba(79, 70, 229, 0.04);border:1px solid rgba(79, 70, 229, 0.08)}
    .top-nav-links a{padding:8px 12px;border-radius:999px;text-decoration:none;color:var(--muted);font-weight:700;font-size:13px;transition:all .16s ease}
    .top-nav-links a:hover{background:#fff;color:var(--primary);box-shadow:0 4px 10px rgba(15,23,42,0.04)}
    .top-nav-links a.active{background:rgba(79, 70, 229, 0.10);color:var(--primary)}
    .notification-bell{width:42px;height:42px;border:none;border-radius:50%;display:grid;place-items:center;background:#fff;color:var(--primary);box-shadow:0 6px 16px rgba(15,23,42,0.05);border:1px solid rgba(15,23,42,0.08);cursor:pointer;transition:transform .16s ease,box-shadow .16s ease;text-decoration:none}
    .notification-bell:hover{transform:translateY(-1px);box-shadow:0 10px 18px rgba(15,23,42,0.08)}
    .profile-pill{display:flex;align-items:center;gap:10px;padding:6px 10px;border-radius:999px;background:#fff;border:1px solid rgba(15,23,42,0.08);box-shadow:0 6px 15px rgba(15,23,42,0.04)}
    .profile-pill .avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),#8b5cf6);color:#fff;font-weight:800;font-size:14px;border:2px solid #fff;box-shadow:0 4px 12px rgba(15,23,42,0.12)}
    .profile-pill strong{display:block;font-size:13px;color:var(--text)}
    .profile-pill span{display:block;color:var(--muted);font-size:11px}
    .badge-dot {
      display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px;
      background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-size: 0.78rem; font-weight: 800;
    }

    .hero {
      display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 18px; padding: 28px; border-radius: 30px; margin-bottom: 20px; overflow: hidden; position: relative;
    }
    .hero::before { content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.55), rgba(255,255,255,0)); pointer-events: none; }
    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: rgba(79, 70, 229, 0.10); color: var(--primary);
      font-size: 0.79rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 12px;
    }
    .hero h1 { margin: 0 0 10px; font-size: clamp(1.7rem, 3vw, 2.4rem); line-height: 1.12; letter-spacing: -0.03em; }
    .hero p { margin: 0; color: var(--muted); line-height: 1.75; max-width: 700px; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .stat-card {
      padding: 14px; border-radius: 18px; background: rgba(255,255,255,0.72); border: 1px solid rgba(79, 70, 229, 0.08);
      display: flex; flex-direction: column; gap: 4px; animation: fadeUp 0.6s ease both;
    }
    .stat-label { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); font-weight: 800; }
    .stat-value { font-size: 1.24rem; font-weight: 800; color: var(--primary-strong); }

    .messaging-shell {
      display: grid; grid-template-columns: 360px 1fr; gap: 18px; margin-top: 8px;
    }
    .sidebar, .chat-panel { padding: 18px; border-radius: 28px; position:relative }

    .search-box {
      display: flex; align-items: center; gap: 8px; padding: 11px 13px; border-radius: 999px; background: rgba(255,255,255,0.92);
      border: 1px solid rgba(79, 70, 229, 0.12); margin-bottom: 12px;
    }
    .search-box input { border: 0; outline: none; background: transparent; width: 100%; font: inherit; color: var(--text); }

    .filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
    .filter-btn {
      border: 0; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.72); color: var(--muted); cursor: pointer; font-weight: 700;
      transition: all 0.2s ease;
    }
    .filter-btn.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }

    .chat-list { display: grid; gap: 10px; }
    .chat-item {
      display: grid; grid-template-columns: auto 1fr auto; gap: 10px; align-items: center; padding: 12px; border-radius: 18px;
      background: rgba(255,255,255,0.78); border: 1px solid rgba(79, 70, 229, 0.08); cursor: pointer; transition: all 0.2s ease;
    }
    .chat-item:hover, .chat-item.active { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08); border-color: rgba(79, 70, 229, 0.2); }
    .avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(79, 70, 229, 0.2); background: linear-gradient(135deg, #eef2ff, #e0e7ff); }
    .avatar-fallback {
      width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
      color: #312e81; font-weight: 800; display: flex; align-items: center; justify-content: center;
      border: 2px solid rgba(79, 70, 229, 0.2); box-shadow: 0 8px 16px rgba(79, 70, 229, 0.08);
      font-size: 0.92rem; letter-spacing: 0.06em;
    }
    .chat-meta { min-width: 0; }
    .chat-name { font-weight: 800; display: flex; align-items: center; gap: 6px; }
    .chat-subtitle { color: var(--muted); font-size: 0.9rem; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-time { color: var(--muted); font-size: 0.8rem; text-align: right; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); display: inline-block; }
    .status-dot.offline { background: #94a3b8; }
    .unread-badge {
      display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-size: 0.74rem; font-weight: 800; margin-top: 5px;
    }

    .chat-panel { display: flex; flex-direction: column; min-height: 620px; }
    .chat-header {
      display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-bottom: 14px; border-bottom: 1px solid rgba(79,70,229,0.12); margin-bottom: 12px;
    }
    .profile-row { display: flex; align-items: center; gap: 10px; }
    .chat-content-inner {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-height: 0;
    }
    .profile-row .avatar { width: 46px; height: 46px; }
    .header-actions { display: flex; gap: 8px; }
    .icon-btn {
      border: 0; width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.88); color: var(--primary); cursor: pointer; box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06);
    }

    .messages-area { flex: 1; overflow: auto; display: flex; flex-direction: column; gap: 10px; padding: 4px 2px 8px; }
    .bubble {
      display: inline-block; max-width: min(76%, 520px); padding: 10px 12px; border-radius: 16px; line-height: 1.5; animation: fadeUp 0.35s ease both;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .incoming { align-self: flex-start; background: rgba(255,255,255,0.92); color: var(--text); border-top-left-radius: 6px; }
    .outgoing { align-self: flex-end; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-top-right-radius: 6px; }
    .bubble-meta { display: flex; align-items: center; justify-content: flex-end; gap: 6px; font-size: 0.75rem; margin-top: 6px; opacity: 0.82; }
    .bubble.incoming .bubble-meta { justify-content: flex-start; }
    .message-input {
      display: flex;
      align-items: center;
      gap: 10px;
      width: 100%;
      padding: 10px 12px 10px 16px;
      border-radius: 18px;
      background: rgba(255,255,255,0.96);
      border: 1px solid rgba(79,70,229,0.18);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.7), 0 6px 18px rgba(79,70,229,0.08);
      margin-top: 10px;
      min-height: 56px;
    }
    .message-input input {
      flex: 1;
      border: 0;
      outline: none;
      background: transparent;
      font: inherit;
      color: var(--text);
      min-width: 0;
      font-size: 1rem;
    }
    .mini-btn { border: 0; width: 38px; height: 38px; border-radius: 50%; background: rgba(79,70,229,0.1); color: var(--primary); cursor: pointer; }
    .send-btn {
      width: auto;
      min-width: 92px;
      height: 42px;
      padding: 0 20px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      font-weight: 800;
      box-shadow: 0 10px 22px rgba(79,70,229,0.22);
    }

    .empty-state {
      display: grid; place-items: center; text-align: center; padding: 28px; border-radius: 22px; background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(248,250,255,0.92)); color: var(--muted); min-height: 240px; border: 1px dashed rgba(79,70,229,0.2);
    }
    .empty-illustration { width: 140px; height: 140px; border-radius: 50%; margin-bottom: 12px; background: radial-gradient(circle at 30% 30%, rgba(79,70,229,0.25), rgba(255,255,255,0.4)); display: grid; place-items: center; font-size: 3rem; }
    .hidden { display: none !important; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 1050px) {
      .messaging-shell { grid-template-columns: 1fr; }
      .chat-panel { min-height: 520px; }
    }

    @media (max-width: 680px) {
      .page { padding: 16px; }
      .navbar { border-radius: 22px; flex-direction: column; align-items: flex-start; }
      .hero { padding: 20px; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .sidebar, .chat-panel { padding: 16px; }
      .chat-item { grid-template-columns: auto 1fr; }
      .chat-time { grid-column: 2; text-align: left; }
      .unread-badge { grid-column: 2; justify-self: start; }
    }
  </style>
</head>
<body>
  <main class="page">
    <header class="topbar-shell glass">
      <div class="topbar-left">
        <div class="logo">
          <img src="PROJECT LOGO.png" alt="SkillMate logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
          <span class="logo-fallback" style="display:none;">SM</span>
        </div>
        <div>
          <p class="eyebrow">Student dashboard</p>
          <h1 class="topbar-title">Skill Dashboard <span style="font-size:13px;color:var(--muted);font-weight:700;margin-left:8px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?></span></h1>
          <p class="lead">A student-first skill exchange platform — teach what you know, learn what you need.</p>
        </div>
      </div>
      <div class="topbar-right">
        <nav class="top-nav-links" aria-label="Main navigation">
          <a href="index.php">Home</a>
          <a href="teach.php">Teach</a>
          <a href="learn.php">Learn</a>
          <a href="requests.php">Requests</a>
          <a href="messages.php" class="active">Messages <span class="badge-dot" id="notifBadge">2</span></a>
          <a href="profile.php">Profile</a>
          <a href="about.php">About</a>
        </nav>
        <a href="notification.php" class="notification-bell" title="Notifications" aria-label="Notifications">🔔</a>
        
      </div>
    </header>

    <section class="hero glass">
      <div>
        <div class="eyebrow">💬 Messages</div>
        <h1>Communicate with your learning partners after accepting learning requests.</h1>
        <p>Stay connected with teachers and learners in a polished, professional messaging experience built for your SkillMate project.</p>
      </div>
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
          <div class="stat-label">Total Chats</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Unread Messages</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Teachers Connected</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Learners Connected</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
      </div>
    </section>

    <section class="messaging-shell">
      <aside class="sidebar glass">
        <div class="search-box">
          <span>🔎</span>
          <input id="searchConversation" type="text" placeholder="Search conversation">
        </div>
        <div class="filters" role="tablist" aria-label="Chat filters">
          <button class="filter-btn active" data-filter="all">All Chats</button>
          <button class="filter-btn" data-filter="teacher">Teachers</button>
          <button class="filter-btn" data-filter="learner">Learners</button>
          <button class="filter-btn" data-filter="unread">Unread</button>
        </div>
        <div class="chat-list" id="chatList"></div>
      </aside>

      <section class="chat-panel glass" id="chatPanel">
        <div id="emptyState" class="empty-state">
          <div>
            <div class="empty-illustration">💬</div>
            <h3>Select a conversation to start chatting.</h3>
            <p>Choose a person from the left to view messages and continue your learning conversation.</p>
          </div>
        </div>
        <div id="chatContent" class="hidden">
          <div class="chat-header">
            <div class="profile-row">
              <img id="chatAvatar" class="avatar" src="" alt="" style="display:none;">
              <div id="chatAvatarFallback" class="avatar-fallback" style="display:flex;">NA</div>
              <div>
                <div id="chatName" class="chat-name"></div>
                <div id="chatSkill" class="chat-subtitle"></div>
              </div>
            </div>
            <div class="header-actions">
              <button
    class="icon-btn"
    id="voiceCallBtn"
    type="button"
    title="Voice Call">
    📞
</button>
              
            </div>
          </div>
          <div id="callBox" style="
    display:none;
    position:absolute;
    inset:0;
    z-index:1000;
    background:rgba(15,23,42,0.96);
    color:white;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    text-align:center;
">
    <div id="incomingCallBox" style="
    display:none;
    position:fixed;
    top:30px;
    right:30px;
    z-index:2000;
    width:320px;
    padding:25px;
    background:white;
    border-radius:20px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    text-align:center;
">

    <div style="font-size:45px;">📞</div>

    <h3 id="incomingCaller">Incoming Call</h3>

    <p>Someone is calling you...</p>

    <div style="
        display:flex;
        gap:10px;
        justify-content:center;
    ">

        <button id="acceptCallBtn" style="
            border:none;
            padding:12px 20px;
            border-radius:12px;
            background:#22c55e;
            color:white;
            font-weight:700;
            cursor:pointer;
        ">
            🟢 Accept
        </button>

        <button id="rejectCallBtn" style="
            border:none;
            padding:12px 20px;
            border-radius:12px;
            background:#ef4444;
            color:white;
            font-weight:700;
            cursor:pointer;
        ">
            🔴 Reject
        </button>

    </div>
</div>

    <div style="font-size:70px;">📞</div>

    <h2 id="callTitle">Calling...</h2>

    <p id="callStatus">Connecting...</p>

    <button id="endCallBtn" style="
        margin-top:30px;
        border:none;
        border-radius:50px;
        padding:14px 28px;
        background:#ef4444;
        color:white;
        font-size:16px;
        font-weight:700;
        cursor:pointer;
    ">
        🔴 End Call
    </button>

</div>

          <div class="chat-content-inner">
            <div id="messagesArea" class="messages-area"></div>

            <div class="message-input">
              <input id="messageInput" type="text" placeholder="Type a message...">
              <button class="mini-btn send-btn" id="sendBtn" type="button">Send</button>
            </div>
          </div>
        </div>
      </section>
    </section>
  </main>

<script src="https://unpkg.com/peerjs@1.5.4/dist/peerjs.min.js"></script>

  <script>
    // ===== Server-provided conversations =====
    const conversations = <?php echo json_encode($conversations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const requestedOtherId = <?php echo (int)$requestedOtherId; ?>;

    // ===== DOM references =====
    const chatList = document.getElementById('chatList');
    const searchConversation = document.getElementById('searchConversation');
    const filters = document.querySelectorAll('.filter-btn');
    const emptyState = document.getElementById('emptyState');
    const chatContent = document.getElementById('chatContent');
    const chatAvatar = document.getElementById('chatAvatar');
    const chatAvatarFallback = document.getElementById('chatAvatarFallback');
    const chatName = document.getElementById('chatName');
    const chatSkill = document.getElementById('chatSkill');
    const messagesArea = document.getElementById('messagesArea');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const notifBadge = document.getElementById('notifBadge');

    let activeFilter = 'all';
    let activeConversationId = null;

    // ===== Rendering =====
    function getInitials(name) {
      if (!name) return 'U';
      return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0].toUpperCase())
        .join('') || 'U';
    }

    function renderChatList() {
      const query = searchConversation.value.trim().toLowerCase();
      const filtered = conversations.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(query) || item.skill.toLowerCase().includes(query);
        const matchesFilter = activeFilter === 'all'
          ? true
          : activeFilter === 'teacher'
            ? item.role === 'Teacher'
            : activeFilter === 'learner'
              ? item.role === 'Learner'
              : item.unread > 0;
        return matchesSearch && matchesFilter;
      });

      chatList.innerHTML = '';
      if (!filtered.length) {
        chatList.innerHTML = '<div class="empty-state">No conversations match this filter.</div>';
        return;
      }

      filtered.forEach(item => {
        const avatarHtml = item.profileImg
          ? `<img class="avatar" src="${item.profileImg}" alt="${item.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><div class="avatar-fallback" style="display:none;">${getInitials(item.name)}</div>`
          : `<div class="avatar-fallback">${getInitials(item.name)}</div>`;

        const card = document.createElement('div');
        card.className = `chat-item ${item.id === activeConversationId ? 'active' : ''}`;
        card.innerHTML = `
          ${avatarHtml}
          <div class="chat-meta">
            <div class="chat-name">${item.name} <span class="status-dot ${item.status === 'online' ? '' : 'offline'}"></span></div>
            <div class="chat-subtitle">${item.skill}</div>
            <div class="chat-subtitle">${item.lastMessage}</div>
          </div>
          <div style="text-align:right;">
            <div class="chat-time">${item.lastTime}</div>
            ${item.unread ? `<div class="unread-badge">${item.unread}</div>` : ''}
          </div>
        `;
        card.addEventListener('click', () => selectConversation(item.id));
        chatList.appendChild(card);
      });
    }

    function selectConversation(id) {
      activeConversationId = id;
      const conv = conversations.find(item => Number(item.id) === Number(id)) || {};
      const hasImage = !!(conv.profileImg && conv.profileImg.trim());
      if (hasImage) {
        chatAvatar.src = conv.profileImg;
        chatAvatar.style.display = 'block';
        chatAvatarFallback.style.display = 'none';
        chatAvatar.onerror = function () {
          this.style.display = 'none';
          chatAvatarFallback.textContent = getInitials(conv.name || 'U');
          chatAvatarFallback.style.display = 'flex';
        };
      } else {
        chatAvatar.removeAttribute('src');
        chatAvatar.style.display = 'none';
        chatAvatarFallback.textContent = getInitials(conv.name || 'U');
        chatAvatarFallback.style.display = 'flex';
      }
      chatAvatar.alt = conv.name || 'User';
      chatName.textContent = conv.name || 'User';
      chatSkill.textContent = conv.skill ? `${conv.skill} • ${conv.role}` : (conv.role || '');
      // Fetch thread from server
      fetch(`php/message_thread.php?other_id=${id}`)
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            messagesArea.innerHTML = '<div class="empty-state">Unable to load messages.</div>';
            return;
          }
          const msgs = data.messages.map(m => ({ sender: m.sender_id === <?php echo $userId; ?> ? 'outgoing' : 'incoming', text: m.message, time: m.created_at }));
          renderMessages(msgs);
        })
        .catch(() => { messagesArea.innerHTML = '<div class="empty-state">Unable to load messages.</div>'; });
      renderChatList();
      emptyState.classList.add('hidden');
      chatContent.classList.remove('hidden');
    }

    function renderMessages(messages) {
      messagesArea.innerHTML = '';
      messages.forEach(msg => {
        const bubble = document.createElement('div');
        bubble.className = `bubble ${msg.sender}`;
        const div = document.createElement('div');
        div.textContent = msg.text;
        const meta = document.createElement('div');
        meta.className = 'bubble-meta';
        meta.innerHTML = `<span>${msg.time}</span>` + (msg.sender === 'outgoing' ? '<span>✓</span>' : '');
        bubble.appendChild(div);
        bubble.appendChild(meta);
        messagesArea.appendChild(bubble);
      });
      messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function addMessage(text) {
      if (!activeConversationId) return;
      const receiverId = activeConversationId;
      messageInput.disabled = true;
      fetch('php/message_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receiver_id=${encodeURIComponent(receiverId)}&message=${encodeURIComponent(text)}`
      })
      .then(r => r.json())
      .then(data => {
        messageInput.disabled = false;
        if (!data.success) {
          alert(data.error || 'Unable to send message');
          return;
        }
        // append outgoing message locally
        const now = new Date().toISOString().replace('T', ' ').split('.')[0];
        renderMessages([{ sender: 'outgoing', text, time: now }]);
        // reload thread to reflect server state
        selectConversation(receiverId);
      })
      .catch(() => { messageInput.disabled = false; alert('Network error'); });
    }

    function updateStats() {
      const statValues = document.querySelectorAll('[data-count]');
      const values = [
        conversations.length,
        conversations.reduce((sum, item) => sum + item.unread, 0),
        conversations.filter(item => item.role === 'Teacher').length,
        conversations.filter(item => item.role === 'Learner').length
      ];
      statValues.forEach((node, index) => animateValue(node, values[index]));
      notifBadge.textContent = conversations.reduce((sum, item) => sum + item.unread, 0);
    }

    function animateValue(node, target) {
      let start = 0;
      const duration = 900;
      const stepTime = 16;
      const steps = Math.round(duration / stepTime);
      const increment = target / steps;
      const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
          node.textContent = target;
          clearInterval(timer);
        } else {
          node.textContent = Math.round(start);
        }
      }, stepTime);
    }

    // ===== Events =====
    filters.forEach(button => {
      button.addEventListener('click', () => {
        activeFilter = button.dataset.filter;
        filters.forEach(btn => btn.classList.toggle('active', btn === button));
        renderChatList();
      });
    });

    searchConversation.addEventListener('input', renderChatList);

    sendBtn.addEventListener('click', () => {
      const text = messageInput.value.trim();
      if (!text) return;
      addMessage(text);
      messageInput.value = '';
    });

    messageInput.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        sendBtn.click();
      }
    });

    // ===== Initial state =====
    updateStats();
    renderChatList();
    if (conversations.length) {
      const preferredConversation = requestedOtherId > 0
        ? conversations.find(item => Number(item.id) === requestedOtherId)
        : null;
      selectConversation(preferredConversation ? preferredConversation.id : conversations[0].id);
    }
    // ======================================================
// SKILLMATE VOICE CALL
// ======================================================

const myPeerId = "skillmate-user-<?php echo $userId; ?>";

let peer = new Peer(myPeerId);

let localStream = null;
let currentCall = null;
let incomingCall = null;


// ------------------------------------------------------
// PEER CONNECTED
// ------------------------------------------------------

peer.on("open", function(id) {

    console.log("SkillMate Peer ID:", id);

});


// ------------------------------------------------------
// PEER ERROR
// ------------------------------------------------------

peer.on("error", function(error) {

    console.error("PeerJS Error:", error);

});


// ------------------------------------------------------
// START CALL
// ------------------------------------------------------

async function startVoiceCall() {

    if (!activeConversationId) {

        alert("Please select a person first.");

        return;
    }


    try {

        // Ask microphone permission

        localStream = await navigator.mediaDevices.getUserMedia({
            audio: true,
            video: false
        });


        const receiverPeerId =
            "skillmate-user-" + activeConversationId;


        console.log(
            "Calling:",
            receiverPeerId
        );


        showCallBox(
            "Calling " + chatName.textContent + "..."
        );


        currentCall = peer.call(
            receiverPeerId,
            localStream
        );


        currentCall.on("stream", function(remoteStream) {

            playRemoteAudio(remoteStream);

            document.getElementById("callStatus")
                .textContent = "Connected";

        });


        currentCall.on("close", function() {

            endCall();

        });


        currentCall.on("error", function(error) {

            console.error(error);

            alert("Unable to connect the call.");

            endCall();

        });

    }

    catch(error) {

        console.error(error);

        alert(
            "Microphone permission is required for calling."
        );

    }

}


// ------------------------------------------------------
// RECEIVE CALL
// ------------------------------------------------------

peer.on("call", function(call) {

    incomingCall = call;

    const callerPeerId = call.peer;

    console.log(
        "Incoming call from:",
        callerPeerId
    );


    document.getElementById("incomingCaller")
        .textContent = "Incoming SkillMate Call";


    document.getElementById("incomingCallBox")
        .style.display = "block";

});


// ------------------------------------------------------
// ACCEPT CALL
// ------------------------------------------------------

document.getElementById("acceptCallBtn")
    .addEventListener("click", async function() {

        try {

            localStream =
                await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: false
                });


            incomingCall.answer(localStream);


            currentCall = incomingCall;


            currentCall.on("stream", function(remoteStream) {

                playRemoteAudio(remoteStream);

                document.getElementById("incomingCallBox")
                    .style.display = "none";


                showCallBox("Call Connected");


                document.getElementById("callStatus")
                    .textContent = "Connected";

            });


            currentCall.on("close", function() {

                endCall();

            });


            document.getElementById("incomingCallBox")
                .style.display = "none";

            showCallBox("Connecting...");

        }

        catch(error) {

            console.error(error);

            alert(
                "Microphone permission is required."
            );

        }

    });


// ------------------------------------------------------
// REJECT CALL
// ------------------------------------------------------

document.getElementById("rejectCallBtn")
    .addEventListener("click", function() {

        if (incomingCall) {

            incomingCall.close();

        }

        incomingCall = null;

        document.getElementById("incomingCallBox")
            .style.display = "none";

    });


// ------------------------------------------------------
// DISPLAY CALL BOX
// ------------------------------------------------------

function showCallBox(title) {

    const box =
        document.getElementById("callBox");

    box.style.display = "flex";

    document.getElementById("callTitle")
        .textContent = title;

    document.getElementById("callStatus")
        .textContent = "Connecting...";

}


// ------------------------------------------------------
// REMOTE AUDIO
// ------------------------------------------------------

function playRemoteAudio(stream) {

    let audio =
        document.getElementById("remoteAudio");


    if (!audio) {

        audio = document.createElement("audio");

        audio.id = "remoteAudio";

        audio.autoplay = true;

        document.body.appendChild(audio);

    }


    audio.srcObject = stream;

    audio.play().catch(function(error) {

        console.error(
            "Audio playback error:",
            error
        );

    });

}


// ------------------------------------------------------
// END CALL
// ------------------------------------------------------

function endCall() {

    console.log("Call ended");


    if (currentCall) {

        currentCall.close();

        currentCall = null;

    }


    if (incomingCall) {

        incomingCall.close();

        incomingCall = null;

    }


    if (localStream) {

        localStream.getTracks().forEach(function(track) {

            track.stop();

        });

        localStream = null;

    }


    document.getElementById("callBox")
        .style.display = "none";


    document.getElementById("incomingCallBox")
        .style.display = "none";

}


// ------------------------------------------------------
// END CALL BUTTON
// ------------------------------------------------------

document.getElementById("endCallBtn")
    .addEventListener(
        "click",
        endCall
    );
    document.getElementById("voiceCallBtn")
    .addEventListener(
        "click",
        startVoiceCall
    );
  </script>
</body>
</html>

