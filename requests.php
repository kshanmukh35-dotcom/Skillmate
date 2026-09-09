<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit();
}

require_once __DIR__ . '/db_connect.php';

$userId = (int)$_SESSION['user_id'];

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
  $requestId = (int)$_POST['request_id'];
  $action = $_POST['action'] === 'accept' ? 'Accepted' : 'Rejected';

  $updateSql = "UPDATE exchange_requests SET status = '$action' WHERE id = $requestId AND receiver_id = $userId";
  if (mysqli_query($conn, $updateSql)) {
    $successMessage = 'Request updated successfully.';
  } else {
    $errorMessage = 'Unable to update the request right now.';
  }
}

$incomingSql = "
  SELECT er.id, er.sender_id, er.receiver_id, er.teacher_name, er.skill_to_learn, er.skill_i_can_teach, er.preferred_date, er.preferred_time, er.message, er.status, er.created_at,
         u.full_name AS sender_name
  FROM exchange_requests er
  LEFT JOIN users u ON u.user_id = er.sender_id
  WHERE er.receiver_id = $userId
  ORDER BY er.created_at DESC
";
$incomingResult = mysqli_query($conn, $incomingSql);

$sentSql = "
  SELECT er.id, er.sender_id, er.receiver_id, er.teacher_name, er.skill_to_learn, er.skill_i_can_teach, er.preferred_date, er.preferred_time, er.message, er.status, er.created_at,
         u.full_name AS receiver_name
  FROM exchange_requests er
  LEFT JOIN users u ON u.user_id = er.receiver_id
  WHERE er.sender_id = $userId
  ORDER BY er.created_at DESC
";
$sentResult = mysqli_query($conn, $sentSql);

$incomingRequests = [];
if ($incomingResult) {
  while ($row = mysqli_fetch_assoc($incomingResult)) {
    $incomingRequests[] = [
      'id' => (int)$row['id'],
      'name' => $row['sender_name'] ?: 'Student',
      'profileImg' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
      'skill' => $row['skill_to_learn'] ?: 'Skill Exchange',
      'level' => $row['skill_i_can_teach'] ?: 'Shared skill',
      'time' => $row['preferred_time'] ?: 'Flexible',
      'date' => $row['preferred_date'] ?: date('Y-m-d', strtotime($row['created_at'])),
      'message' => $row['message'] ?: 'Would like to connect for a learning exchange.',
      'status' => $row['status'] ?: 'Pending'
    ];
  }
}

$sentRequests = [];
if ($sentResult) {
  while ($row = mysqli_fetch_assoc($sentResult)) {
    $sentRequests[] = [
      'id' => (int)$row['id'],
      'name' => $row['receiver_name'] ?: 'Teacher',
      'profileImg' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=300&q=80',
      'skill' => $row['skill_to_learn'] ?: 'Skill Exchange',
      'date' => $row['preferred_date'] ?: date('Y-m-d', strtotime($row['created_at'])),
      'status' => $row['status'] ?: 'Pending'
    ];
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — Requests</title>
  <style>
    :root {
      --primary: #2f4d9a;
      --primary-strong: #1f355f;
      --secondary: #5d78d8;
      --accent: #2a9d5d;
      --bg: #f3f5f9;
      --surface: rgba(255,255,255,0.96);
      --surface-strong: rgba(255,255,255,1);
      --text: #18212f;
      --muted: #5f6f86;
      --border: rgba(15,23,42,0.08);
      --shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
      --radius: 18px;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
      color: var(--text);
      background: var(--bg);
      min-height: 100vh;
    }
    a { color: inherit; text-decoration: none; }
    .page { max-width: 1220px; margin: 0 auto; padding: 24px; }
    .glass {
      background: var(--surface);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }
    .navbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 22px; border-radius: 999px; margin-bottom: 20px; }
    .brand { font-weight: 800; font-size: 1.05rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--primary); }
    .nav-links { display: flex; gap: 8px; flex-wrap: wrap; }
    .nav-links a { padding: 9px 14px; border-radius: 999px; color: var(--muted); font-weight: 700; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 7px; }
    .nav-links a:hover, .nav-links a.active { color: var(--primary); background: rgba(79,70,229,0.10); }
    .badge-dot { display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-size: 0.78rem; font-weight: 800; }

    .hero { display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 18px; padding: 28px; border-radius: 30px; margin-bottom: 20px; overflow: hidden; position: relative; }
    .hero::before { content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.55), rgba(255,255,255,0)); pointer-events: none; }
    .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: rgba(79,70,229,0.10); color: var(--primary); font-size: 0.79rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 12px; }
    .hero h1 { margin: 0 0 10px; font-size: clamp(1.7rem, 3vw, 2.4rem); line-height: 1.12; letter-spacing: -0.03em; }
    .hero p { margin: 0; color: var(--muted); line-height: 1.75; max-width: 700px; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .stat-card { padding: 16px; border-radius: 18px; background: rgba(255,255,255,0.7); border: 1px solid rgba(79,70,229,0.08); display: flex; flex-direction: column; gap: 4px; animation: fadeUp 0.6s ease both; }
    .stat-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); font-weight: 800; }
    .stat-value { font-size: 1.35rem; font-weight: 800; color: var(--primary-strong); }

    .panel { padding: 24px; border-radius: 28px; margin-top: 20px; }
    .panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .tabs { display: inline-flex; gap: 10px; padding: 8px; border-radius: 999px; background: rgba(255,255,255,0.7); border: 1px solid rgba(79,70,229,0.12); }
    .tab-btn { border: 0; padding: 10px 14px; border-radius: 999px; background: transparent; cursor: pointer; font-weight: 800; color: var(--muted); transition: all 0.2s ease; }
    .tab-btn.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
    .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .search-box { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; background: rgba(255,255,255,0.9); border: 1px solid rgba(79,70,229,0.12); min-width: min(320px, 100%); }
    .search-box input { border: 0; outline: none; background: transparent; width: 100%; color: var(--text); font: inherit; }
    .filter-select { padding: 10px 14px; border-radius: 999px; border: 1px solid rgba(79,70,229,0.12); background: rgba(255,255,255,0.92); color: var(--text); font: inherit; outline: none; }

    .cards-grid { display: grid; gap: 14px; }
    .request-card { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: center; padding: 16px; border-radius: 22px; background: rgba(255,255,255,0.88); border: 1px solid rgba(79,70,229,0.1); box-shadow: 0 12px 30px rgba(15,23,42,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; animation: fadeUp 0.45s ease both; }
    .request-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(15,23,42,0.08); }
    .avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(79,70,229,0.18); box-shadow: 0 10px 18px rgba(79,70,229,0.16); }
    .request-main { min-width: 0; }
    .request-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
    .request-title strong { font-size: 1rem; }
    .muted { color: var(--muted); font-size: 0.92rem; }
    .meta-line { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; color: var(--muted); font-size: 0.9rem; }
    .meta-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 9px; border-radius: 999px; background: rgba(79,70,229,0.08); color: var(--primary-strong); font-weight: 700; }
    .status-pill { display: inline-flex; align-items: center; justify-content: center; min-width: 96px; padding: 8px 10px; border-radius: 999px; font-weight: 800; font-size: 0.84rem; text-transform: uppercase; letter-spacing: 0.08em; }
    .status-pending { background: rgba(234,179,8,0.16); color: #92400e; }
    .status-accepted { background: rgba(34,197,94,0.16); color: #166534; }
    .status-rejected { background: rgba(248,113,113,0.18); color: #991b1b; }
    .request-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .btn-small { border: 0; padding: 8px 12px; border-radius: 999px; cursor: pointer; font-weight: 800; transition: transform 0.2s ease; }
    .btn-small:hover { transform: translateY(-1px); }
    .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
    .btn-ghost { background: rgba(79,70,229,0.10); color: var(--primary-strong); }
    .btn-success { background: rgba(34,197,94,0.14); color: #166534; }
    .btn-danger { background: rgba(248,113,113,0.16); color: #991b1b; }
    .empty-state { padding: 28px; text-align: center; border-radius: 22px; background: linear-gradient(135deg, rgba(255,255,255,0.88), rgba(248,250,255,0.92)); border: 1px dashed rgba(79,70,229,0.2); color: var(--muted); }
    .status-banner{margin-bottom:14px;padding:12px 14px;border-radius:14px;font-size:13px;font-weight:700;border:1px solid transparent}
    .status-banner.success{background:rgba(34,197,94,0.12);color:#166534;border-color:rgba(34,197,94,0.2)}
    .status-banner.error{background:rgba(248,113,113,0.12);color:#991b1b;border-color:rgba(248,113,113,0.2)}
    .empty-illustration { width: 140px; height: 140px; border-radius: 50%; margin: 0 auto 12px; background: radial-gradient(circle at 30% 30%, rgba(79,70,229,0.3), rgba(255,255,255,0.4)); display: grid; place-items: center; font-size: 3rem; }
    .hidden { display: none !important; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 960px) { .hero { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .request-card { grid-template-columns: auto 1fr; } .request-actions { grid-column: 2; justify-content: flex-start; } }
    @media (max-width: 680px) { .page { padding: 16px; } .navbar { border-radius: 22px; flex-direction: column; align-items: flex-start; } .stats-grid { grid-template-columns: 1fr; } .panel { padding: 18px; } .hero { padding: 20px; } .toolbar { align-items: stretch; } .search-box { min-width: 100%; } .request-card { grid-template-columns: 1fr; } .request-actions { grid-column: auto; } }
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
        <a class="active" href="requests.php">Requests</a>
      </nav>
    </header>

    <section class="hero glass">
      <div>
        <div class="eyebrow">📬 Learning Requests</div>
        <h1>Manage incoming and outgoing learning requests efficiently.</h1>
        <p>Keep your learning network organized with a polished dashboard for conversations, approvals, and progress updates.</p>
      </div>
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="stat-label">Incoming Requests</div><div class="stat-value" data-count="0">0</div></div>
        <div class="stat-card"><div class="stat-label">Pending Requests</div><div class="stat-value" data-count="0">0</div></div>
        <div class="stat-card"><div class="stat-label">Accepted Requests</div><div class="stat-value" data-count="0">0</div></div>
        <div class="stat-card"><div class="stat-label">Rejected Requests</div><div class="stat-value" data-count="0">0</div></div>
      </div>
    </section>

    <section class="panel glass">
      <?php if (!empty($successMessage)): ?>
        <div class="status-banner success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <?php if (!empty($errorMessage)): ?>
        <div class="status-banner error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <div class="panel-head">
        <div class="tabs" role="tablist" aria-label="Request tabs">
          <button class="tab-btn active" data-tab="incoming" type="button">Incoming Requests</button>
          <button class="tab-btn" data-tab="sent" type="button">Sent Requests</button>
        </div>
      </div>
      <div class="toolbar">
        <div class="search-box"><span>🔎</span><input id="searchInput" type="text" placeholder="Search by name..."></div>
        <select id="statusFilter" class="filter-select">
          <option value="all">All</option>
          <option value="Pending">Pending</option>
          <option value="Accepted">Accepted</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
      <div id="incomingPanel" class="cards-grid"></div>
      <div id="sentPanel" class="cards-grid hidden"></div>
      <div id="emptyState" class="empty-state hidden">
        <div class="empty-illustration">✨</div>
        <h3>No requests found</h3>
        <p>There are currently no matching requests. Try another filter or search term.</p>
      </div>
    </section>
  </main>

  <script>
    const incomingRequests = <?php echo json_encode($incomingRequests, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const sentRequests = <?php echo json_encode($sentRequests, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    const incomingPanel = document.getElementById('incomingPanel');
    const sentPanel = document.getElementById('sentPanel');
    const tabs = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const emptyState = document.getElementById('emptyState');

    let activeTab = 'incoming';

    function createStatusBadge(status) {
      const className = status === 'Accepted' ? 'status-accepted' : status === 'Rejected' ? 'status-rejected' : 'status-pending';
      return `<span class="status-pill ${className}">${status}</span>`;
    }

    function renderIncoming() {
      const query = searchInput.value.trim().toLowerCase();
      const filter = statusFilter.value;
      const filtered = incomingRequests.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(query);
        return (filter === 'all' || item.status === filter) && matchesSearch;
      });
      incomingPanel.innerHTML = '';
      if (!filtered.length) { showEmptyState(); return; }
      hideEmptyState();
      filtered.forEach(item => {
        const card = document.createElement('article');
        card.className = 'request-card';
        card.innerHTML = `
          <img class="avatar" src="${item.profileImg}" alt="${item.name}">
          <div class="request-main">
            <div class="request-title"><strong>${item.name}</strong>${createStatusBadge(item.status)}</div>
            <div class="muted">${item.message}</div>
            <div class="meta-line">
              <span class="meta-pill">Skill: ${item.skill}</span>
              <span class="meta-pill">Level: ${item.level}</span>
              <span class="meta-pill">Time: ${item.time}</span>
              <span class="meta-pill">Date: ${item.date}</span>
            </div>
          </div>
          <div class="request-actions">
            ${item.status === 'Pending' ? `<form method="post" style="display:inline">
              <input type="hidden" name="request_id" value="${item.id}">
              <input type="hidden" name="action" value="accept">
              <button class="btn-small btn-success" type="submit">Accept</button>
            </form>
            <form method="post" style="display:inline">
              <input type="hidden" name="request_id" value="${item.id}">
              <input type="hidden" name="action" value="reject">
              <button class="btn-small btn-danger" type="submit">Reject</button>
            </form>` : ''}
            ${item.status === 'Accepted' ? `<a class="btn-small btn-primary" href="chat.php?request_id=${item.id}" style="display:inline-flex; align-items:center; justify-content:center;">Chat</a>` : ''}
            <button class="btn-small btn-ghost" data-action="profile" data-id="${item.id}">View Profile</button>
          </div>`;
        incomingPanel.appendChild(card);
      });
    }

    function renderSent() {
      const query = searchInput.value.trim().toLowerCase();
      const filter = statusFilter.value;
      const filtered = sentRequests.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(query);
        return (filter === 'all' || item.status === filter) && matchesSearch;
      });
      sentPanel.innerHTML = '';
      if (!filtered.length) { showEmptyState(); return; }
      hideEmptyState();
      filtered.forEach(item => {
        const card = document.createElement('article');
        card.className = 'request-card';
        let actionMarkup = '';
        if (item.status === 'Accepted') actionMarkup = `<a class="btn-small btn-primary" href="chat.php?request_id=${item.id}" style="display:inline-flex; align-items:center; justify-content:center;">Open Chat</a>`;
        else if (item.status === 'Pending') actionMarkup = '<span class="muted">Waiting for Response</span>';
        else actionMarkup = '<span class="muted">Request Declined</span>';
        card.innerHTML = `
          <img class="avatar" src="${item.profileImg}" alt="${item.name}">
          <div class="request-main">
            <div class="request-title"><strong>${item.name}</strong>${createStatusBadge(item.status)}</div>
            <div class="meta-line"><span class="meta-pill">Skill: ${item.skill}</span><span class="meta-pill">Sent: ${item.date}</span></div>
          </div>
          <div class="request-actions">${actionMarkup}</div>`;
        sentPanel.appendChild(card);
      });
    }

    function renderLists() {
      if (activeTab === 'incoming') { incomingPanel.classList.remove('hidden'); sentPanel.classList.add('hidden'); renderIncoming(); }
      else { incomingPanel.classList.add('hidden'); sentPanel.classList.remove('hidden'); renderSent(); }
    }

    function showEmptyState() { emptyState.classList.remove('hidden'); }
    function hideEmptyState() { emptyState.classList.add('hidden'); }

    function updateStats() {
      const statValues = document.querySelectorAll('[data-count]');
      const values = [incomingRequests.length, incomingRequests.filter(item => item.status === 'Pending').length, incomingRequests.filter(item => item.status === 'Accepted').length, incomingRequests.filter(item => item.status === 'Rejected').length];
      statValues.forEach((node, index) => animateValue(node, values[index]));
    }

    function animateValue(node, target) {
      let start = 0; const duration = 900; const stepTime = 16; const steps = Math.round(duration / stepTime); const increment = target / steps; const timer = setInterval(() => { start += increment; if (start >= target) { node.textContent = target; clearInterval(timer); } else { node.textContent = Math.round(start); } }, stepTime);
    }

    tabs.forEach(button => button.addEventListener('click', () => { activeTab = button.dataset.tab; tabs.forEach(btn => btn.classList.toggle('active', btn === button)); renderLists(); }));
    searchInput.addEventListener('input', renderLists);
    statusFilter.addEventListener('change', renderLists);

    updateStats();
    renderLists();
  </script>
</body>
</html>

