<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — My Requests</title>
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

    .page { max-width: 1220px; margin: 0 auto; padding: 24px; }

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
      display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 18px; padding: 28px; border-radius: 30px; margin-bottom: 20px; overflow: hidden;
      position: relative;
    }
    .hero::before {
      content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,0.55), rgba(255,255,255,0)); pointer-events: none;
    }
    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: rgba(79, 70, 229, 0.10); color: var(--primary);
      font-size: 0.79rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.14em; margin-bottom: 12px;
    }
    .hero h1 { margin: 0 0 10px; font-size: clamp(1.7rem, 3vw, 2.4rem); line-height: 1.12; letter-spacing: -0.03em; }
    .hero p { margin: 0; color: var(--muted); line-height: 1.75; max-width: 700px; }

    .stats-grid {
      display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 16px;
    }
    .stat-card {
      padding: 16px; border-radius: 18px; background: rgba(255,255,255,0.7); border: 1px solid rgba(79, 70, 229, 0.08);
      display: flex; flex-direction: column; gap: 4px;
      animation: fadeUp 0.6s ease both;
    }
    .stat-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--muted); font-weight: 800; }
    .stat-value { font-size: 1.35rem; font-weight: 800; color: var(--primary-strong); }

    .panel { padding: 24px; border-radius: 28px; margin-top: 20px; }
    .panel-head {
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .tabs {
      display: inline-flex; gap: 10px; padding: 8px; border-radius: 999px; background: rgba(255,255,255,0.7); border: 1px solid rgba(79, 70, 229, 0.12);
    }
    .tab-btn {
      border: 0; padding: 10px 14px; border-radius: 999px; background: transparent; cursor: pointer; font-weight: 800; color: var(--muted);
      transition: all 0.2s ease;
    }
    .tab-btn.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }

    .toolbar {
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .search-box {
      display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px; background: rgba(255,255,255,0.9);
      border: 1px solid rgba(79, 70, 229, 0.12); min-width: min(320px, 100%);
    }
    .search-box input {
      border: 0; outline: none; background: transparent; width: 100%; color: var(--text); font: inherit;
    }
    .filter-select {
      padding: 10px 14px; border-radius: 999px; border: 1px solid rgba(79, 70, 229, 0.12); background: rgba(255,255,255,0.92); color: var(--text); font: inherit; outline: none;
    }

    .cards-grid { display: grid; gap: 14px; }
    .request-card {
      display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: center; padding: 16px; border-radius: 22px;
      background: rgba(255,255,255,0.88); border: 1px solid rgba(79, 70, 229, 0.1); box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
      transition: transform 0.2s ease, box-shadow 0.2s ease; animation: fadeUp 0.45s ease both;
    }
    .request-card:hover { transform: translateY(-3px); box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08); }
    .avatar {
      width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(79, 70, 229, 0.18);
      box-shadow: 0 10px 18px rgba(79, 70, 229, 0.16);
    }
    .request-main { min-width: 0; }
    .request-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
    .request-title strong { font-size: 1rem; }
    .muted { color: var(--muted); font-size: 0.92rem; }
    .meta-line { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; color: var(--muted); font-size: 0.9rem; }
    .meta-pill {
      display: inline-flex; align-items: center; gap: 6px; padding: 6px 9px; border-radius: 999px; background: rgba(79, 70, 229, 0.08); color: var(--primary-strong);
      font-weight: 700;
    }
    .status-pill {
      display: inline-flex; align-items: center; justify-content: center; min-width: 96px; padding: 8px 10px; border-radius: 999px; font-weight: 800; font-size: 0.84rem; text-transform: uppercase; letter-spacing: 0.08em;
    }
    .status-pending { background: rgba(234, 179, 8, 0.16); color: #92400e; }
    .status-accepted { background: rgba(34, 197, 94, 0.16); color: #166534; }
    .status-rejected { background: rgba(248, 113, 113, 0.18); color: #991b1b; }

    .request-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .btn-small {
      border: 0; padding: 8px 12px; border-radius: 999px; cursor: pointer; font-weight: 800; transition: transform 0.2s ease;
    }
    .btn-small:hover { transform: translateY(-1px); }
    .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
    .btn-ghost { background: rgba(79, 70, 229, 0.10); color: var(--primary-strong); }
    .btn-success { background: rgba(34, 197, 94, 0.14); color: #166534; }
    .btn-danger { background: rgba(248, 113, 113, 0.16); color: #991b1b; }

    .empty-state {
      padding: 28px; text-align: center; border-radius: 22px; background: linear-gradient(135deg, rgba(255,255,255,0.88), rgba(248,250,255,0.92)); border: 1px dashed rgba(79, 70, 229, 0.2);
      color: var(--muted);
    }
    .empty-illustration {
      width: 140px; height: 140px; border-radius: 50%; margin: 0 auto 12px; background: radial-gradient(circle at 30% 30%, rgba(79,70,229,0.3), rgba(255,255,255,0.4)); display: grid; place-items: center; font-size: 3rem;
    }

    .hidden { display: none !important; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 960px) {
      .hero { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .request-card { grid-template-columns: auto 1fr; }
      .request-actions { grid-column: 2; justify-content: flex-start; }
    }

    @media (max-width: 680px) {
      .page { padding: 16px; }
      .navbar { border-radius: 22px; flex-direction: column; align-items: flex-start; }
      .stats-grid { grid-template-columns: 1fr; }
      .panel { padding: 18px; }
      .hero { padding: 20px; }
      .toolbar { align-items: stretch; }
      .search-box { min-width: 100%; }
      .request-card { grid-template-columns: 1fr; }
      .request-actions { grid-column: auto; }
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
        <a class="active" href="my-requests.php">Requests <span class="badge-dot" id="notifBadge">3</span></a>
      </nav>
    </header>

    <section class="hero glass">
      <div>
        <div class="eyebrow">📬 Learning Requests</div>
        <h1>Manage incoming and outgoing learning requests efficiently.</h1>
        <p>Keep your learning network organized with a polished dashboard for conversations, approvals, and progress updates.</p>
      </div>
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
          <div class="stat-label">Incoming Requests</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending Requests</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Accepted Requests</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Rejected Requests</div>
          <div class="stat-value" data-count="0">0</div>
        </div>
      </div>
    </section>

    <section class="panel glass">
      <div class="panel-head">
        <div class="tabs" role="tablist" aria-label="Request tabs">
          <button class="tab-btn active" data-tab="incoming" type="button">Incoming Requests</button>
          <button class="tab-btn" data-tab="sent" type="button">Sent Requests</button>
        </div>
      </div>

      <div class="toolbar">
        <div class="search-box">
          <span>🔎</span>
          <input id="searchInput" type="text" placeholder="Search by name...">
        </div>
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
    // ===== Dummy data for future PHP/MySQL replacement =====
    const incomingRequests = [
      {
        id: 1,
        name: 'Aisha Khan',
        profileImg: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
        skill: 'UI/UX Design',
        level: 'Intermediate',
        time: 'Evening',
        date: '2026-07-20',
        message: 'I would love to improve my design system knowledge with your guidance.',
        status: 'Pending'
      },
      {
        id: 2,
        name: 'Daniel Mwangi',
        profileImg: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=300&q=80',
        skill: 'JavaScript',
        level: 'Beginner',
        time: 'Weekend',
        date: '2026-07-18',
        message: 'Can we schedule a short session to review DOM manipulation?',
        status: 'Pending'
      },
      {
        id: 3,
        name: 'Mina Patel',
        profileImg: 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=300&q=80',
        skill: 'Public Speaking',
        level: 'Advanced',
        time: 'Morning',
        date: '2026-07-15',
        message: 'I am preparing for a presentation and would value your feedback.',
        status: 'Accepted'
      }
    ];

    const sentRequests = [
      {
        id: 101,
        name: 'Noah Kim',
        profileImg: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=300&q=80',
        skill: 'React Basics',
        date: '2026-07-22',
        status: 'Pending'
      },
      {
        id: 102,
        name: 'Sara Ali',
        profileImg: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=300&q=80',
        skill: 'Data Visualization',
        date: '2026-07-10',
        status: 'Accepted'
      },
      {
        id: 103,
        name: 'Liam Brooks',
        profileImg: 'https://images.unsplash.com/photo-1504593811423-6dd665756598?auto=format&fit=crop&w=300&q=80',
        skill: 'Cybersecurity Essentials',
        date: '2026-07-07',
        status: 'Rejected'
      }
    ];

    // ===== DOM references =====
    const incomingPanel = document.getElementById('incomingPanel');
    const sentPanel = document.getElementById('sentPanel');
    const tabs = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const emptyState = document.getElementById('emptyState');
    const notifBadge = document.getElementById('notifBadge');

    let activeTab = 'incoming';

    // ===== Rendering helpers =====
    function createStatusBadge(status) {
      const className = status === 'Accepted' ? 'status-accepted' : status === 'Rejected' ? 'status-rejected' : 'status-pending';
      return `<span class="status-pill ${className}">${status}</span>`;
    }

    function renderIncoming() {
      const query = searchInput.value.trim().toLowerCase();
      const filter = statusFilter.value;
      const filtered = incomingRequests.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(query);
        const matchesFilter = filter === 'all' || item.status === filter;
        return matchesSearch && matchesFilter;
      });

      incomingPanel.innerHTML = '';
      if (!filtered.length) {
        showEmptyState();
        return;
      }
      hideEmptyState();
      filtered.forEach(item => {
        const card = document.createElement('article');
        card.className = 'request-card';
        card.innerHTML = `
          <img class="avatar" src="${item.profileImg}" alt="${item.name}">
          <div class="request-main">
            <div class="request-title">
              <strong>${item.name}</strong>
              ${createStatusBadge(item.status)}
            </div>
            <div class="muted">${item.message}</div>
            <div class="meta-line">
              <span class="meta-pill">Skill: ${item.skill}</span>
              <span class="meta-pill">Level: ${item.level}</span>
              <span class="meta-pill">Time: ${item.time}</span>
              <span class="meta-pill">Date: ${item.date}</span>
            </div>
          </div>
          <div class="request-actions">
            <button class="btn-small btn-success" data-action="accept" data-id="${item.id}">Accept</button>
            <button class="btn-small btn-danger" data-action="reject" data-id="${item.id}">Reject</button>
            <button class="btn-small btn-ghost" data-action="profile" data-id="${item.id}">View Profile</button>
          </div>
        `;
        incomingPanel.appendChild(card);
      });
    }

    function renderSent() {
      const query = searchInput.value.trim().toLowerCase();
      const filter = statusFilter.value;
      const filtered = sentRequests.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(query);
        const matchesFilter = filter === 'all' || item.status === filter;
        return matchesSearch && matchesFilter;
      });

      sentPanel.innerHTML = '';
      if (!filtered.length) {
        showEmptyState();
        return;
      }
      hideEmptyState();
      filtered.forEach(item => {
        const card = document.createElement('article');
        card.className = 'request-card';
        let actionMarkup = '';
        if (item.status === 'Accepted') {
          actionMarkup = `<button class="btn-small btn-primary">Open Chat</button>`;
        } else if (item.status === 'Pending') {
          actionMarkup = `<span class="muted">Waiting for Response</span>`;
        } else {
          actionMarkup = `<span class="muted">Request Declined</span>`;
        }
        card.innerHTML = `
          <img class="avatar" src="${item.profileImg}" alt="${item.name}">
          <div class="request-main">
            <div class="request-title">
              <strong>${item.name}</strong>
              ${createStatusBadge(item.status)}
            </div>
            <div class="meta-line">
              <span class="meta-pill">Skill: ${item.skill}</span>
              <span class="meta-pill">Sent: ${item.date}</span>
            </div>
          </div>
          <div class="request-actions">${actionMarkup}</div>
        `;
        sentPanel.appendChild(card);
      });
    }

    function renderLists() {
      if (activeTab === 'incoming') {
        incomingPanel.classList.remove('hidden');
        sentPanel.classList.add('hidden');
      } else {
        incomingPanel.classList.add('hidden');
        sentPanel.classList.remove('hidden');
      }
      if (activeTab === 'incoming') {
        renderIncoming();
      } else {
        renderSent();
      }
    }

    function showEmptyState() {
      emptyState.classList.remove('hidden');
    }

    function hideEmptyState() {
      emptyState.classList.add('hidden');
    }

    function updateStats() {
      const incomingCount = incomingRequests.length;
      const pendingCount = incomingRequests.filter(item => item.status === 'Pending').length;
      const acceptedCount = incomingRequests.filter(item => item.status === 'Accepted').length;
      const rejectedCount = incomingRequests.filter(item => item.status === 'Rejected').length;
      const statValues = document.querySelectorAll('[data-count]');
      const values = [incomingCount, pendingCount, acceptedCount, rejectedCount];

      statValues.forEach((node, index) => {
        animateValue(node, values[index]);
      });
    }

    function animateValue(node, target) {
      let start = 0;
      const duration = 900;
      const stepTime = 16;
      const totalSteps = Math.round(duration / stepTime);
      const increment = target / totalSteps;
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
    tabs.forEach(button => {
      button.addEventListener('click', () => {
        activeTab = button.dataset.tab;
        tabs.forEach(btn => btn.classList.toggle('active', btn === button));
        renderLists();
      });
    });

    searchInput.addEventListener('input', renderLists);
    statusFilter.addEventListener('change', renderLists);

    incomingPanel.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      if (!button) return;
      const id = Number(button.dataset.id);
      const request = incomingRequests.find(item => item.id === id);
      if (!request) return;
      if (button.dataset.action === 'accept') {
        request.status = 'Accepted';
      } else if (button.dataset.action === 'reject') {
        request.status = 'Rejected';
      }
      renderLists();
      updateStats();
      notifBadge.textContent = incomingRequests.filter(item => item.status === 'Pending').length;
    });

    // ===== Initial render =====
    updateStats();
    renderLists();
    notifBadge.textContent = incomingRequests.filter(item => item.status === 'Pending').length;
  </script>
</body>
</html>

