<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — Personalized Roadmap Generator</title>
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
        radial-gradient(circle at top left, rgba(79, 70, 229, 0.15), transparent 28%),
        radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.14), transparent 24%),
        var(--bg);
      min-height: 100vh;
    }

    a { color: inherit; text-decoration: none; }

    .page { max-width: 1180px; margin: 0 auto; padding: 24px; }

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
      padding: 9px 14px; border-radius: 999px; color: var(--muted); font-weight: 700; transition: all 0.2s ease;
    }
    .nav-links a:hover, .nav-links a.active { color: var(--primary); background: rgba(79, 70, 229, 0.10); }

    .hero {
      display: grid; grid-template-columns: 1.05fr 0.95fr; gap: 18px; padding: 28px; border-radius: 30px; margin-bottom: 20px;
    }

    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px;
      background: rgba(79, 70, 229, 0.10); color: var(--primary); font-size: 0.79rem; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.14em; margin-bottom: 12px;
    }

    .hero h1 { margin: 0 0 10px; font-size: clamp(1.7rem, 3vw, 2.4rem); line-height: 1.12; letter-spacing: -0.03em; }
    .hero p { margin: 0; color: var(--muted); line-height: 1.75; max-width: 700px; }

    .hero-points {
      list-style: none; padding: 0; margin: 16px 0 0; display: grid; gap: 9px;
    }

    .hero-points li {
      padding: 10px 12px; border-radius: 14px; background: rgba(255,255,255,0.66); border: 1px solid rgba(79, 70, 229, 0.08);
      font-weight: 600; color: var(--text);
    }

    .hero-card {
      padding: 20px; border-radius: 22px; background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(238,242,255,0.92)); border: 1px solid rgba(79, 70, 229, 0.1);
      display: grid; gap: 8px;
    }

    .hero-card .metric { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 0.95rem; }
    .hero-card .big { font-size: 2rem; font-weight: 800; color: var(--primary); }

    .content-grid { display: grid; grid-template-columns: 0.95fr 1.05fr; gap: 20px; }
    .panel { padding: 24px; border-radius: 28px; }
    .panel h2, .panel h3 { margin: 0 0 16px; font-size: 1.2rem; letter-spacing: -0.02em; }

    .field { margin-bottom: 14px; }
    .field label { display: block; margin-bottom: 7px; font-size: 0.92rem; font-weight: 700; color: var(--primary-strong); }
    .field input, .field select {
      width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid rgba(79, 70, 229, 0.14); background: rgba(255,255,255,0.92);
      color: var(--text); outline: none; font: inherit;
    }
    .field input:focus, .field select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.14); }

    .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

    .btn {
      width: 100%; border: 0; padding: 13px 16px; border-radius: 16px; margin-top: 6px; font-weight: 800; cursor: pointer;
      color: white; background: linear-gradient(135deg, var(--primary), var(--secondary)); box-shadow: 0 14px 28px rgba(79, 70, 229, 0.16);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(79, 70, 229, 0.20); }

    .loading-state {
      display: flex; align-items: center; gap: 12px; margin-top: 14px; padding: 12px 14px; border-radius: 14px;
      background: rgba(79, 70, 229, 0.08); color: var(--primary-strong); font-weight: 700;
    }
    .spinner {
      width: 18px; height: 18px; border-radius: 50%; border: 3px solid rgba(79, 70, 229, 0.16); border-top-color: var(--primary);
      animation: spin 0.85s linear infinite;
    }
    .hidden { display: none !important; }

    .timeline {
      position: relative; margin-top: 16px; display: grid; gap: 14px;
    }
    .timeline::before {
      content: ""; position: absolute; left: 18px; top: 8px; bottom: 8px; width: 2px; background: linear-gradient(180deg, var(--primary), rgba(124, 58, 237, 0.25));
    }

    .milestone {
      position: relative; padding: 16px 16px 16px 48px; border-radius: 18px; background: rgba(255,255,255,0.88); border: 1px solid rgba(79, 70, 229, 0.10); box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
    }
    .milestone .step {
      position: absolute; left: 8px; top: 16px; width: 22px; height: 22px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white; display: grid; place-items: center; font-size: 0.8rem; font-weight: 800;
    }
    .milestone-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 8px; }
    .milestone h4 { margin: 0; font-size: 1rem; }
    .milestone .tag { font-size: 0.8rem; font-weight: 800; color: var(--primary); background: rgba(79, 70, 229, 0.10); border-radius: 999px; padding: 6px 9px; }
    .milestone p { margin: 8px 0 0; color: var(--muted); line-height: 1.6; }
    .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; font-size: 0.9rem; color: var(--muted); }
    .meta span { background: rgba(255,255,255,0.7); border: 1px solid rgba(79, 70, 229, 0.08); border-radius: 999px; padding: 6px 9px; }
    .progress-wrap { margin-top: 10px; }
    .progress-bar { height: 10px; border-radius: 999px; background: rgba(79, 70, 229, 0.12); overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--primary), var(--accent)); }
    .complete-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-top: 10px; font-size: 0.92rem; color: var(--muted); }
    .check { display: inline-flex; align-items: center; gap: 8px; }
    .check input { accent-color: var(--accent); width: 16px; height: 16px; }

    .summary-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 16px; }
    .summary-card { padding: 16px; border-radius: 18px; background: rgba(255,255,255,0.88); border: 1px solid rgba(79, 70, 229, 0.10); }
    .summary-card .label { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: var(--primary); }
    .summary-card .value { margin-top: 6px; font-size: 1rem; font-weight: 700; color: var(--text); line-height: 1.5; }

    .quote-box {
      margin-top: 16px; padding: 16px; border-radius: 18px; background: linear-gradient(135deg, rgba(79,70,229,0.10), rgba(124,58,237,0.08)); border: 1px solid rgba(79,70,229,0.12);
      color: var(--primary-strong); font-weight: 700; line-height: 1.6;
    }

    .download-btn {
      margin-top: 16px; border: 0; padding: 12px 16px; border-radius: 14px; background: linear-gradient(135deg, var(--accent), #16a34a); color: white;
      font-weight: 800; cursor: pointer; box-shadow: 0 12px 24px rgba(34, 197, 94, 0.16);
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 900px) {
      .hero, .content-grid { grid-template-columns: 1fr; }
      .summary-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 680px) {
      .page { padding: 16px; }
      .navbar { border-radius: 22px; flex-direction: column; align-items: flex-start; }
      .grid-2 { grid-template-columns: 1fr; }
      .panel { padding: 18px; }
      .hero { padding: 20px; }
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
        <a class="active" href="roadmap.php">Roadmap</a>
      </nav>
    </header>

    <section class="hero glass">
      <div>
        <div class="eyebrow">🧭 Personalized Roadmap Generator</div>
        <h1>Create a focused plan for your next career milestone.</h1>
        <p>Choose your target role, your current level, and the time you can commit. SkillMate will turn that into a simple roadmap with milestones, resources, and progress checkpoints.</p>
        <ul class="hero-points">
          <li>✓ Responsive, premium glassmorphism experience</li>
          <li>✓ Dummy JavaScript roadmap data for demo purposes</li>
          <li>✓ Easy to connect later with PHP and MySQL</li>
        </ul>
      </div>
      <div class="hero-card">
        <div class="metric"><span>Suggested pace</span><strong>Balanced</strong></div>
        <div class="big" id="heroScore">4 Weeks</div>
        <div class="metric"><span>Focus</span><strong>Practical growth</strong></div>
      </div>
    </section>

    <section class="content-grid">
      <form id="roadmapForm" class="panel glass">
        <h2>Build your roadmap</h2>

        <div class="field">
          <label for="career">Career Path</label>
          <select id="career">
            <option value="Frontend Developer">Frontend Developer</option>
            <option value="Product Manager">Product Manager</option>
            <option value="Data Analyst">Data Analyst</option>
            <option value="UI/UX Designer">UI/UX Designer</option>
            <option value="Cybersecurity Specialist">Cybersecurity Specialist</option>
          </select>
        </div>

        <div class="grid-2">
          <div class="field">
            <label for="skillLevel">Current Skill Level</label>
            <select id="skillLevel">
              <option value="Beginner">Beginner</option>
              <option value="Intermediate">Intermediate</option>
              <option value="Advanced">Advanced</option>
            </select>
          </div>
          <div class="field">
            <label for="studyTime">Daily Study Time</label>
            <select id="studyTime">
              <option value="30 min">30 min</option>
              <option value="1 hour">1 hour</option>
              <option value="2 hours">2 hours</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="duration">Roadmap Duration</label>
          <select id="duration">
            <option value="4 weeks">4 weeks</option>
            <option value="6 weeks">6 weeks</option>
            <option value="8 weeks">8 weeks</option>
          </select>
        </div>

        <button class="btn" type="submit">Generate Roadmap</button>

        <div id="loadingState" class="loading-state hidden" aria-live="polite">
          <div class="spinner"></div>
          <span>Designing your personalized roadmap...</span>
        </div>
      </form>

      <aside class="panel glass" aria-label="Roadmap summary">
        <h3>Roadmap preview</h3>
        <div class="summary-grid">
          <div class="summary-card">
            <div class="label">Target Role</div>
            <div class="value" id="previewCareer">Frontend Developer</div>
          </div>
          <div class="summary-card">
            <div class="label">Estimated Pace</div>
            <div class="value" id="previewPace">4 weeks</div>
          </div>
        </div>
        <div class="quote-box" id="previewQuote">
          “Small daily progress creates powerful future results.”
        </div>
      </aside>
    </section>

    <section id="roadmapResults" class="panel glass hidden" aria-live="polite">
      <h3>Your roadmap</h3>
      <div class="timeline" id="timeline"></div>

      <div class="summary-grid">
        <div class="summary-card">
          <div class="label">Total Duration</div>
          <div class="value" id="totalDuration">-</div>
        </div>
        <div class="summary-card">
          <div class="label">Recommended Resources</div>
          <div class="value" id="resourcesList">-</div>
        </div>
      </div>

      <div class="quote-box" id="motivationQuote">“Consistency beats intensity when intensity cannot be maintained.”</div>
      <button class="download-btn" type="button" id="downloadBtn">Download Roadmap</button>
    </section>
  </main>

  <script>
    // ===== Dummy roadmap data =====
    const roadmapData = {
      'Frontend Developer': {
        beginner: [
          { step: 1, topic: 'HTML & CSS Foundations', duration: '1 week', skills: ['Semantic HTML', 'Layout', 'Responsive design'], progress: 35 },
          { step: 2, topic: 'JavaScript Essentials', duration: '1 week', skills: ['Variables', 'Functions', 'DOM'], progress: 55 },
          { step: 3, topic: 'UI Build Practice', duration: '1 week', skills: ['Accessibility', 'Components', 'Debugging'], progress: 72 },
          { step: 4, topic: 'Project Polish', duration: '1 week', skills: ['Performance', 'Deployment', 'Portfolio'], progress: 88 }
        ],
        intermediate: [
          { step: 1, topic: 'Modern CSS Systems', duration: '1 week', skills: ['Grid', 'Flexbox', 'Design tokens'], progress: 42 },
          { step: 2, topic: 'JavaScript Framework Basics', duration: '1 week', skills: ['State', 'Routing', 'API use'], progress: 60 },
          { step: 3, topic: 'Testing & Refinement', duration: '2 weeks', skills: ['Unit tests', 'UI polish', 'Optimization'], progress: 76 },
          { step: 4, topic: 'Portfolio Delivery', duration: '2 weeks', skills: ['Case studies', 'Deployments', 'Storytelling'], progress: 90 }
        ]
      },
      'Product Manager': {
        beginner: [
          { step: 1, topic: 'Product Thinking', duration: '1 week', skills: ['Problem framing', 'Customer needs'], progress: 40 },
          { step: 2, topic: 'Roadmapping', duration: '1 week', skills: ['Prioritization', 'Stakeholders'], progress: 58 },
          { step: 3, topic: 'Metrics & Planning', duration: '1 week', skills: ['KPIs', 'Analytics'], progress: 72 },
          { step: 4, topic: 'Launch Prep', duration: '1 week', skills: ['Communication', 'Execution'], progress: 85 }
        ],
        intermediate: [
          { step: 1, topic: 'User Research', duration: '1 week', skills: ['Interviews', 'Insights'], progress: 44 },
          { step: 2, topic: 'Strategy Workshops', duration: '1 week', skills: ['Opportunity mapping', 'Roadmaps'], progress: 62 },
          { step: 3, topic: 'Growth Metrics', duration: '2 weeks', skills: ['A/B testing', 'Retention'], progress: 78 },
          { step: 4, topic: 'Leadership Readiness', duration: '2 weeks', skills: ['Decision making', 'Storytelling'], progress: 90 }
        ]
      },
      'Data Analyst': {
        beginner: [
          { step: 1, topic: 'Excel & Data Cleaning', duration: '1 week', skills: ['Sorting', 'Formulas', 'Cleaning'], progress: 39 },
          { step: 2, topic: 'SQL Basics', duration: '1 week', skills: ['Queries', 'Joins', 'Filters'], progress: 56 },
          { step: 3, topic: 'Visualization', duration: '1 week', skills: ['Charts', 'Dashboards'], progress: 71 },
          { step: 4, topic: 'Insight Reporting', duration: '1 week', skills: ['Summary', 'Narration'], progress: 86 }
        ],
        intermediate: [
          { step: 1, topic: 'Advanced SQL', duration: '1 week', skills: ['Window functions', 'CTEs'], progress: 45 },
          { step: 2, topic: 'Python for Analysis', duration: '1 week', skills: ['Pandas', 'Numpy'], progress: 61 },
          { step: 3, topic: 'Business Metrics', duration: '2 weeks', skills: ['KPIs', 'Forecasting'], progress: 77 },
          { step: 4, topic: 'Decision Support', duration: '2 weeks', skills: ['Reporting', 'Stakeholder insight'], progress: 91 }
        ]
      },
      'UI/UX Designer': {
        beginner: [
          { step: 1, topic: 'Design Fundamentals', duration: '1 week', skills: ['Color', 'Typography', 'Spacing'], progress: 38 },
          { step: 2, topic: 'Wireframing', duration: '1 week', skills: ['Layouts', 'Hierarchy'], progress: 56 },
          { step: 3, topic: 'Prototyping', duration: '1 week', skills: ['Interactivity', 'Feedback'], progress: 72 },
          { step: 4, topic: 'User Testing', duration: '1 week', skills: ['Insights', 'Iteration'], progress: 87 }
        ],
        intermediate: [
          { step: 1, topic: 'Design Systems', duration: '1 week', skills: ['Components', 'Consistency'], progress: 43 },
          { step: 2, topic: 'Research Methods', duration: '1 week', skills: ['Surveys', 'Interviews'], progress: 60 },
          { step: 3, topic: 'Accessibility', duration: '2 weeks', skills: ['WCAG', 'Inclusive design'], progress: 77 },
          { step: 4, topic: 'Portfolio Case Study', duration: '2 weeks', skills: ['Storytelling', 'Presentation'], progress: 92 }
        ]
      },
      'Cybersecurity Specialist': {
        beginner: [
          { step: 1, topic: 'Security Basics', duration: '1 week', skills: ['Threats', 'Passwords', 'Basics'], progress: 41 },
          { step: 2, topic: 'Networking Essentials', duration: '1 week', skills: ['Ports', 'Protocols', 'Firewalls'], progress: 58 },
          { step: 3, topic: 'Incident Awareness', duration: '1 week', skills: ['Logs', 'Detection', 'Response'], progress: 74 },
          { step: 4, topic: 'Risk Habits', duration: '1 week', skills: ['Safe habits', 'Reporting'], progress: 88 }
        ],
        intermediate: [
          { step: 1, topic: 'Threat Modeling', duration: '1 week', skills: ['Attacks', 'Mitigations'], progress: 46 },
          { step: 2, topic: 'Cloud Security', duration: '1 week', skills: ['Permissions', 'Monitoring'], progress: 63 },
          { step: 3, topic: 'Response Planning', duration: '2 weeks', skills: ['Playbooks', 'Escalation'], progress: 79 },
          { step: 4, topic: 'Leadership Practice', duration: '2 weeks', skills: ['Communication', 'Policies'], progress: 93 }
        ]
      }
    };

    // ===== Utility functions =====
    const roadmapForm = document.getElementById('roadmapForm');
    const loadingState = document.getElementById('loadingState');
    const roadmapResults = document.getElementById('roadmapResults');
    const timeline = document.getElementById('timeline');
    const totalDuration = document.getElementById('totalDuration');
    const resourcesList = document.getElementById('resourcesList');
    const motivationQuote = document.getElementById('motivationQuote');
    const previewCareer = document.getElementById('previewCareer');
    const previewPace = document.getElementById('previewPace');
    const previewQuote = document.getElementById('previewQuote');
    const heroScore = document.getElementById('heroScore');
    const downloadBtn = document.getElementById('downloadBtn');

    function getRoadmapData(career, level) {
      const careerData = roadmapData[career] || roadmapData['Frontend Developer'];
      return careerData[level.toLowerCase()] || careerData.beginner;
    }

    function buildRoadmap(career, level, studyTime, duration) {
      const basePlan = getRoadmapData(career, level);
      const adjusted = basePlan.map((item, index) => ({
        ...item,
        duration: index === basePlan.length - 1 ? item.duration : item.duration,
        studyTime,
        durationLabel: duration
      }));

      const totalWeeks = duration === '8 weeks' ? 8 : duration === '6 weeks' ? 6 : 4;
      const resources = [
        'Coursera Project Network',
        'FreeCodeCamp',
        'YouTube learning playlists',
        'Notion or Google Docs study tracker'
      ];

      return {
        career,
        level,
        studyTime,
        duration,
        totalWeeks,
        milestones: adjusted,
        resources,
        quote: level === 'Advanced' ? 'Mastery grows through deliberate repetition.' : 'Consistency creates confidence one week at a time.'
      };
    }

    function renderRoadmap(plan) {
      timeline.innerHTML = '';
      plan.milestones.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'milestone';
        card.innerHTML = `
          <div class="step">${item.step}</div>
          <div class="milestone-head">
            <h4>${item.topic}</h4>
            <span class="tag">${item.duration}</span>
          </div>
          <p>${item.skills.join(' • ')}</p>
          <div class="meta">
            <span>Study: ${plan.studyTime}</span>
            <span>Focus: ${plan.career}</span>
          </div>
          <div class="progress-wrap">
            <div class="progress-bar">
              <div class="progress-fill" style="width:${item.progress}%"></div>
            </div>
          </div>
          <div class="complete-row">
            <span>Progress ${item.progress}%</span>
            <label class="check">
              <input type="checkbox">
              <span>Complete</span>
            </label>
          </div>
        `;
        timeline.appendChild(card);
      });

      totalDuration.textContent = `${plan.totalWeeks} weeks • ${plan.studyTime} daily`;
      resourcesList.textContent = plan.resources.join(' • ');
      motivationQuote.textContent = `“${plan.quote}”`;
      heroScore.textContent = `${plan.totalWeeks} Weeks`;
      previewCareer.textContent = plan.career;
      previewPace.textContent = `${plan.totalWeeks} weeks`;
      previewQuote.textContent = `“${plan.quote}”`;
      roadmapResults.classList.remove('hidden');
      roadmapResults.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    roadmapForm.addEventListener('submit', (event) => {
      event.preventDefault();
      loadingState.classList.remove('hidden');
      roadmapResults.classList.add('hidden');

      const career = document.getElementById('career').value;
      const level = document.getElementById('skillLevel').value;
      const studyTime = document.getElementById('studyTime').value;
      const duration = document.getElementById('duration').value;

      setTimeout(() => {
        const plan = buildRoadmap(career, level, studyTime, duration);
        renderRoadmap(plan);
        loadingState.classList.add('hidden');
      }, 1100);
    });

    downloadBtn.addEventListener('click', () => {
      const text = `SkillMate Roadmap\nCareer: ${previewCareer.textContent}\nDuration: ${totalDuration.textContent}`;
      const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'skillmate-roadmap.txt';
      link.click();
      URL.revokeObjectURL(url);
    });
  </script>
</body>
</html>

