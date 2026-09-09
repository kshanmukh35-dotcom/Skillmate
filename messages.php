<?php
session_start();
require_once __DIR__ . '/db_connect.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}
$userId = (int)$_SESSION['user_id'];

// Build conversations: latest message per other user
$conversations = [];
$convSql = "
SELECT t.other_id, u.full_name, u.role, m.message AS last_message, m.created_at AS last_time
FROM (
  SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS other_id, MAX(created_at) AS last_at
  FROM messages
  WHERE sender_id = ? OR receiver_id = ?
  GROUP BY other_id
) t
JOIN messages m ON ((m.sender_id = ? AND m.receiver_id = t.other_id) OR (m.receiver_id = ? AND m.sender_id = t.other_id)) AND m.created_at = t.last_at
LEFT JOIN users u ON u.user_id = t.other_id
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
      'profileImg' => '',
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

    .navbar {
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
      padding: 18px 22px; border-radius: 999px; margin-bottom: 20px;
    }

    .brand { font-weight: 800; font-size: 1.05rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--primary); }
    .nav-links { display: flex; gap: 8px; flex-wrap: wrap; }
    .nav-links a {
      padding: 9px 14px; border-radius: 999px; color: var(--muted); font-weight: 700; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 7px;
    }
    .nav-links a:hover, .nav-links a.active { color: var(--primary); background: rgba(79, 70, 229, 0.10); }
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
    .sidebar, .chat-panel { padding: 18px; border-radius: 28px; }

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
    .avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(79, 70, 229, 0.2); }
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
      display: flex; align-items: center; gap: 8px; padding: 10px; border-radius: 18px; background: rgba(255,255,255,0.9); border: 1px solid rgba(79,70,229,0.12); margin-top: 10px;
    }
    .message-input input { flex: 1; border: 0; outline: none; background: transparent; font: inherit; color: var(--text); }
    .mini-btn { border: 0; width: 38px; height: 38px; border-radius: 50%; background: rgba(79,70,229,0.1); color: var(--primary); cursor: pointer; }
    .send-btn { width: auto; padding: 0 14px; border-radius: 999px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-weight: 800; }

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
    <header class="navbar glass">
      <a href="index.php" class="brand"><img src="PROJECT LOGO.png" alt="SkillMate logo" style="height:32px; display:inline-block; vertical-align:middle;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block'"><span style="display:none; vertical-align:middle; font-weight:800; color:#4f46e5;">SM</span></a>
      <nav class="nav-links" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a class="active" href="messages.php">Messages <span class="badge-dot" id="notifBadge">2</span></a>
      </nav>
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
              <img id="chatAvatar" class="avatar" src="" alt="">
              <div>
                <div id="chatName" class="chat-name"></div>
                <div id="chatSkill" class="chat-subtitle"></div>
              </div>
            </div>
            <div class="header-actions">
              <button class="icon-btn" type="button" title="Voice Call">📞</button>
              <button class="icon-btn" type="button" title="Video Call">📹</button>
              <button class="icon-btn" type="button" title="More options">⋯</button>
            </div>
          </div>

          <div id="messagesArea" class="messages-area"></div>

          <div class="message-input">
            <button class="mini-btn" type="button" title="Emoji">😊</button>
            <button class="mini-btn" type="button" title="Attachment">📎</button>
            <input id="messageInput" type="text" placeholder="Type a message...">
            <button class="mini-btn send-btn" id="sendBtn" type="button">Send</button>
          </div>
        </div>
      </section>
    </section>
  </main>

  <script>
    // ===== Server-provided conversations =====
    const conversations = <?php echo json_encode($conversations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    // ===== DOM references =====
    const chatList = document.getElementById('chatList');
    const searchConversation = document.getElementById('searchConversation');
    const filters = document.querySelectorAll('.filter-btn');
    const emptyState = document.getElementById('emptyState');
    const chatContent = document.getElementById('chatContent');
    const chatAvatar = document.getElementById('chatAvatar');
    const chatName = document.getElementById('chatName');
    const chatSkill = document.getElementById('chatSkill');
    const messagesArea = document.getElementById('messagesArea');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const notifBadge = document.getElementById('notifBadge');

    let activeFilter = 'all';
    let activeConversationId = null;

    // ===== Rendering =====
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
        const card = document.createElement('div');
        card.className = `chat-item ${item.id === activeConversationId ? 'active' : ''}`;
        card.innerHTML = `
          <img class="avatar" src="${item.profileImg}" alt="${item.name}">
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
      const conv = conversations.find(item => item.id === id) || {};
      chatAvatar.src = conv.profileImg || '';
      chatAvatar.alt = conv.name || '';
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
      selectConversation(conversations[0].id);
    }
  </script>
</body>
</html>

