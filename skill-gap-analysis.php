<?php
session_start();

$analysis = null;
$selectedSkills = [];
$selectedCareer = 'AI/Machine Learning Engineer';
$selectedLevel = 'Beginner';
$selectedDuration = '2 weeks';
$previewText = 'Add your skills to start the analysis.';
$previewBadge = 'Ready';
$previewScore = 0;
$heroScore = '78%';
$scoreRingStyle = '';

$careerProfiles = [

    'AI/Machine Learning Engineer' => [
        'requiredSkills' => [
            'Python',
            'C++',
            'SQL',
            'Java/Scala',
            'Julia',
            'Machine Learning',
            'Deep Learning',
            'Maths & Statistics',
            'Data Engineering',
            'Deployment & MLOps',
            'Problem-Solving'
        ],
        'recommendedSkills' => [
            'Model Deployment',
            'Cloud Computing',
            'TensorFlow',
            'PyTorch'
        ],
        'focus' => 'AI and Machine Learning',
    ],

    'Data Scientist' => [
        'requiredSkills' => [
            'Programming',
            'Maths & Statistics',
            'Databases & SQL',
            'Data Wrangling',
            'Machine Learning',
            'Data Visualization',
            'Big Data & Cloud'
        ],
        'recommendedSkills' => [
            'Python',
            'R',
            'Deep Learning',
            'Statistical Modeling'
        ],
        'focus' => 'Data analysis and predictive modeling',
    ],

    'Data Analyst' => [
        'requiredSkills' => [
            'SQL',
            'Python or R',
            'Data Visualization',
            'Statistics',
            'Data Cleaning',
            'Cloud'
        ],
        'recommendedSkills' => [
            'Excel',
            'Power BI',
            'Tableau',
            'Business Analytics'
        ],
        'focus' => 'Data analysis and visualization',
    ],

    'Cloud Engineer/Architect' => [
        'requiredSkills' => [
            'Cloud Platforms',
            'Operating Systems',
            'Coding and Scripting',
            'Infrastructure as Code (IaC)',
            'Containers',
            'Networking and Security',
            'System Design'
        ],
        'recommendedSkills' => [
            'AWS',
            'Azure',
            'Terraform',
            'Kubernetes'
        ],
        'focus' => 'Cloud infrastructure and architecture',
    ],

    'DevOps Engineer' => [
        'requiredSkills' => [
            'Linux and Networking',
            'Cloud Computing',
            'CI/CD Pipelines',
            'Containers and Orchestration',
            'Infrastructure as Code (IaC)',
            'Scripting Languages',
            'Monitoring and Logging',
            'Version Control'
        ],
        'recommendedSkills' => [
            'Docker',
            'Kubernetes',
            'Jenkins',
            'Terraform'
        ],
        'focus' => 'Automation and continuous delivery',
    ],

    'Network Engineer' => [
        'requiredSkills' => [
            'Networking Fundamentals',
            'Routing and Switching',
            'Hardware Operations',
            'Cybersecurity',
            'Cloud Networking',
            'Scripting and Tools',
            'Monitoring and Troubleshooting'
        ],
        'recommendedSkills' => [
            'Cisco',
            'Network Automation',
            'Firewall Management',
            'Network Monitoring'
        ],
        'focus' => 'Network infrastructure and security',
    ],

    'Cybersecurity Specialist' => [
        'requiredSkills' => [
            'Network Security',
            'Incident Response',
            'Operating Systems',
            'Coding and Scripting',
            'Cloud Security',
            'Vulnerability Testing'
        ],
        'recommendedSkills' => [
            'Ethical Hacking',
            'Penetration Testing',
            'SIEM',
            'Digital Forensics'
        ],
        'focus' => 'Secure operations and threat protection',
    ],

    'Technical Support Specialist' => [
        'requiredSkills' => [
            'Operating Systems',
            'Hardware Troubleshooting',
            'Networking Basics',
            'System Administration',
            'Security Fundamentals'
        ],
        'recommendedSkills' => [
            'Help Desk Tools',
            'Remote Support',
            'Troubleshooting',
            'Customer Service'
        ],
        'focus' => 'Technical troubleshooting and user support',
    ],

    'Software Developer' => [
        'requiredSkills' => [
            'Programming Languages',
            'Data Structures and Algorithms (DSA)',
            'Databases & SQL',
            'Version Control',
            'Testing & Debugging',
            'Cloud & APIs'
        ],
        'recommendedSkills' => [
            'Object-Oriented Programming',
            'Git',
            'Software Architecture',
            'Unit Testing'
        ],
        'focus' => 'Software development and problem-solving',
    ],

    'Full-Stack Developer' => [
        'requiredSkills' => [
            'HTML & CSS',
            'JavaScript',
            'Frameworks',
            'Server Languages',
            'APIs',
            'Databases and Storage',
            'Version Control',
            'Deployment'
        ],
        'recommendedSkills' => [
            'React',
            'Node.js',
            'REST APIs',
            'Git',
            'Cloud Deployment'
        ],
        'focus' => 'Complete web application development',
    ],

    'UI/UX Designer' => [
        'requiredSkills' => [
            'Visual Design',
            'Design Systems',
            'Responsive Layouts',
            'Platform Standards',
            'User Research',
            'Information Architecture',
            'Wireframing & Prototyping',
            'User Flows'
        ],
        'recommendedSkills' => [
            'Figma',
            'Accessibility',
            'Design Systems',
            'User Testing'
        ],
        'focus' => 'User-centered design',
    ],

];

$validLevels = ['Beginner', 'Intermediate', 'Advanced'];
$validDurations = ['2 weeks', '1 month', '3 months', '6 months'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawSkills = $_POST['skills'] ?? [];

if (!is_array($rawSkills)) {
    $rawSkills = [];
}

$skillSet = [];

foreach ($rawSkills as $skill) {

    if (!is_string($skill)) {
        continue;
    }

    /*
     * If multiple skills are sent together as:
     * "python,sql,c++"
     * split them into separate skills.
     */
    $skillsFromInput = preg_split('/\s*,\s*/', $skill);

    foreach ($skillsFromInput as $singleSkill) {

        $singleSkill = trim($singleSkill);

        if ($singleSkill === '') {
            continue;
        }

        /*
         * Normalize the skill so:
         * Python = python = PYTHON
         */
        $normalized = mb_strtolower($singleSkill, 'UTF-8');

        $normalized = preg_replace('/\s+/', ' ', $normalized);

        if (!isset($skillSet[$normalized])) {
            $skillSet[$normalized] = $singleSkill;
        }
    }
}

$selectedSkills = array_values($skillSet);

    $submittedCareer = isset($_POST['targetCareer']) ? trim((string) $_POST['targetCareer']) : '';
    if (isset($careerProfiles[$submittedCareer])) {
        $selectedCareer = $submittedCareer;
    }

    $submittedLevel = isset($_POST['skillLevel']) ? trim((string) $_POST['skillLevel']) : '';
    if (in_array($submittedLevel, $validLevels, true)) {
        $selectedLevel = $submittedLevel;
    }

    $submittedDuration = isset($_POST['learningDuration']) ? trim((string) $_POST['learningDuration']) : '';
    if (in_array($submittedDuration, $validDurations, true)) {
        $selectedDuration = $submittedDuration;
    }

    $profile = $careerProfiles[$selectedCareer];
   
    $recommendedSkills = [];
    foreach ($profile['recommendedSkills'] as $skill) {
        if (!isset($normalizedSkills[mb_strtolower(trim($skill), 'UTF-8')])) {
            $recommendedSkills[] = $skill;
        }
    }
$normalizedSkills = [];

/*
 * Store every skill entered by the user in normalized form.
 * This makes comparison case-insensitive and ignores extra spaces.
 */
foreach ($selectedSkills as $skill) {

    $normalized = mb_strtolower(trim($skill), 'UTF-8');

    $normalized = preg_replace('/\s+/', ' ', $normalized);

    $normalizedSkills[$normalized] = true;
}


/*
 * Find missing skills.
 *
 * IMPORTANT:
 * Any skill already entered under Current Skills
 * will NOT be added to Missing Skills.
 */
$missingSkills = [];

foreach ($profile['requiredSkills'] as $requiredSkill) {

    $normalizedRequired = mb_strtolower(
        trim($requiredSkill),
        'UTF-8'
    );

    $normalizedRequired = preg_replace(
        '/\s+/',
        ' ',
        $normalizedRequired
    );

    if (isset($normalizedSkills[$normalizedRequired])) {
        continue;
    }

    $missingSkills[] = $requiredSkill;
}
    $matchCount = count($profile['requiredSkills']) - count($missingSkills);
    $levelBoost = $selectedLevel === 'Advanced' ? 8 : ($selectedLevel === 'Intermediate' ? 5 : 2);
    $durationBoost = $selectedDuration === '6 months' ? 9 : ($selectedDuration === '3 months' ? 6 : ($selectedDuration === '1 month' ? 3 : 2));
    $baseScore = count($profile['requiredSkills']) > 0 ? ($matchCount / count($profile['requiredSkills'])) * 100 : 0;

    $readiness = round($baseScore + $levelBoost + $durationBoost);
    $readiness = max(38, min(95, $readiness));

    if ($readiness < 60) {
        $priority = 'High';
    } elseif ($readiness < 80) {
        $priority = 'Medium';
    } else {
        $priority = 'Low';
    }

    if ($priority === 'High') {
        $nextStep = 'Start with ' . ($recommendedSkills[0] ?? $profile['recommendedSkills'][0]) . ' and build one small project this week.';
    } else {
        $nextStep = 'Strengthen ' . ($missingSkills[0] ?? $profile['requiredSkills'][0]) . ' and keep practicing with real examples.';
    }

    $analysis = [
        'readiness' => $readiness,
        'priority' => $priority,
        'currentSkills' => count($selectedSkills) ? $selectedSkills : ['Problem Solving'],
        'missingSkills' => count($missingSkills) ? $missingSkills : ['No major gaps detected'],
        'recommendedSkills' => count($recommendedSkills) ? $recommendedSkills : ['Keep refining your core strengths'],
        'nextStep' => $nextStep,
        'targetCareer' => $selectedCareer,
        'focus' => $profile['focus'],
        'level' => $selectedLevel,
        'duration' => $selectedDuration,
    ];

    $previewBadge = $analysis['priority'];
    $previewScore = $analysis['readiness'];
    $heroScore = $analysis['readiness'] . '%';
    $previewText = $analysis['targetCareer'] . ' • ' . $analysis['focus'];
    $scoreRingStyle = 'style="--progress: ' . $analysis['readiness'] . '; background: conic-gradient(var(--primary) ' . $analysis['readiness'] . '%, rgba(79, 70, 229, 0.12) 0);"';
    $_SESSION['skill_analysis_data'] = [
    'analysis' => $analysis,
    'selectedSkills' => $selectedSkills,
    'selectedCareer' => $selectedCareer,
    'selectedLevel' => $selectedLevel,
    'selectedDuration' => $selectedDuration
];

header('Location: skill-gap-analysis.php');
exit;
}

/*
 * Restore the saved analysis after redirect.
 */
if (isset($_SESSION['skill_analysis_data'])) {

    $saved = $_SESSION['skill_analysis_data'];

    $analysis = $saved['analysis'];
    $selectedSkills = $saved['selectedSkills'];
    $selectedCareer = $saved['selectedCareer'];
    $selectedLevel = $saved['selectedLevel'];
    $selectedDuration = $saved['selectedDuration'];

    $previewBadge = $analysis['priority'];
    $previewScore = $analysis['readiness'];
    $heroScore = $analysis['readiness'] . '%';
    $previewText = $analysis['targetCareer'] . ' • ' . $analysis['focus'];

    $scoreRingStyle = 'style="--progress: ' . $analysis['readiness'] . '; background: conic-gradient(var(--primary) ' . $analysis['readiness'] . '%, rgba(79, 70, 229, 0.12) 0);"';
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SkillMate — Skill Gap Analysis Tool</title>
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

    .page {
      max-width: 1180px;
      margin: 0 auto;
      padding: 24px;
    }

    .glass {
      background: var(--surface);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
    }

    .navbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 22px;
      border-radius: 999px;
      margin-bottom: 20px;
    }

    .brand {
      font-weight: 800;
      font-size: 1.05rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--primary);
    }

    .nav-links {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .nav-links a {
      padding: 9px 14px;
      border-radius: 999px;
      color: var(--muted);
      font-weight: 700;
      transition: all 0.2s ease;
    }

    .nav-links a:hover,
    .nav-links a.active {
      color: var(--primary);
      background: rgba(79, 70, 229, 0.10);
    }

    .hero {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 20px;
      padding: 28px;
      border-radius: 30px;
      margin-bottom: 22px;
      overflow: hidden;
      position: relative;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(120deg, rgba(255,255,255,0.55), rgba(255,255,255,0));
      pointer-events: none;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 12px;
      border-radius: 999px;
      background: rgba(79, 70, 229, 0.10);
      color: var(--primary);
      font-size: 0.79rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      margin-bottom: 12px;
    }

    .hero h1 {
      margin: 0 0 12px;
      font-size: clamp(1.7rem, 3vw, 2.45rem);
      line-height: 1.15;
      letter-spacing: -0.03em;
    }

    .hero p {
      margin: 0;
      color: var(--muted);
      font-size: 1rem;
      line-height: 1.75;
      max-width: 680px;
    }

    .hero-points {
      display: grid;
      gap: 10px;
      margin: 18px 0 0;
      padding: 0;
      list-style: none;
      color: var(--text);
      font-weight: 600;
    }

    .hero-points li {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(255,255,255,0.65);
      border: 1px solid rgba(79, 70, 229, 0.08);
    }

    .hero-visual {
      display: grid;
      gap: 12px;
      align-content: center;
    }

    .hero-card {
      padding: 20px;
      border-radius: 22px;
      background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(238,242,255,0.92));
      border: 1px solid rgba(79, 70, 229, 0.1);
    }

    .hero-card .stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .hero-card .big-score {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary);
      margin: 4px 0;
    }

    .hero-card .tip {
      margin-top: 8px;
      color: var(--muted);
      font-size: 0.95rem;
      line-height: 1.6;
    }

    .content-grid {
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 20px;
    }

    .panel {
      padding: 24px;
      border-radius: 28px;
      position: relative;
    }

    .panel h2,
    .panel h3 {
      margin: 0 0 16px;
      font-size: 1.2rem;
      letter-spacing: -0.02em;
    }

    .field {
      margin-bottom: 14px;
    }

    .field label {
      display: block;
      margin-bottom: 7px;
      font-size: 0.92rem;
      font-weight: 700;
      color: var(--primary-strong);
    }

    .field input,
    .field select,
    .field textarea {
      width: 100%;
      padding: 12px 14px;
      border-radius: 14px;
      border: 1px solid rgba(79, 70, 229, 0.14);
      background: rgba(255,255,255,0.92);
      color: var(--text);
      outline: none;
      font: inherit;
    }

    .field input:focus,
    .field select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.14);
    }

    .tag-row {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
      padding: 10px;
      border-radius: 16px;
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(79, 70, 229, 0.12);
    }

    .tag-list {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      border-radius: 999px;
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(124, 58, 237, 0.10));
      color: var(--primary-strong);
      font-weight: 700;
      font-size: 0.9rem;
    }

    .chip button {
      border: 0;
      background: transparent;
      color: inherit;
      cursor: pointer;
      font-size: 0.95rem;
      padding: 0;
    }

    .mini-btn {
      border: 0;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 10px 12px;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 700;
      box-shadow: 0 10px 20px rgba(79, 70, 229, 0.16);
    }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      border: 0;
      padding: 13px 16px;
      border-radius: 16px;
      margin-top: 8px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 14px 28px rgba(79, 70, 229, 0.16);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn:hover { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(79, 70, 229, 0.2); }

    .hint {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 0.86rem;
    }

    .loading-state {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: 14px;
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(79, 70, 229, 0.08);
      color: var(--primary-strong);
      font-weight: 700;
    }

    .spinner {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      border: 3px solid rgba(79, 70, 229, 0.16);
      border-top-color: var(--primary);
      animation: spin 0.85s linear infinite;
    }

    .hidden { display: none !important; }

    .summary-card {
      padding: 20px;
      border-radius: 22px;
      background: linear-gradient(135deg, rgba(255,255,255,0.96), rgba(248,250,255,0.93));
      border: 1px solid rgba(79, 70, 229, 0.08);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .summary-title {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--primary-strong);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .summary-value {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text);
    }

    .score-ring {
      --progress: 0;
      width: 150px;
      height: 150px;
      border-radius: 50%;
      margin: 0 auto 12px;
      display: grid;
      place-items: center;
      background: conic-gradient(var(--primary) calc(var(--progress) * 1%), rgba(79, 70, 229, 0.12) 0);
      box-shadow: inset 0 0 0 10px rgba(255,255,255,0.45);
    }

    .score-inner {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      background: rgba(255,255,255,0.95);
      display: grid;
      place-items: center;
      text-align: center;
      padding: 10px;
      box-shadow: inset 0 0 0 1px rgba(79, 70, 229, 0.08);
    }

    .score-inner strong {
      display: block;
      font-size: 1.4rem;
      color: var(--primary);
    }

    .score-inner span {
      font-size: 0.82rem;
      color: var(--muted);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .results-section {
      margin-top: 20px;
      display: grid;
      gap: 18px;
    }

    .results-header {
      padding: 18px 24px;
      border-radius: 20px;
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(124, 58, 237, 0.10));
      color: var(--primary-strong);
      font-weight: 800;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .results-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }

    .result-card {
      padding: 18px;
      border-radius: 22px;
      background: rgba(255,255,255,0.88);
      border: 1px solid rgba(79, 70, 229, 0.1);
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .result-card .label {
      font-size: 0.83rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--primary);
    }

    .result-card .value {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text);
      line-height: 1.5;
    }

    .result-card .icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(124, 58, 237, 0.08));
      color: var(--primary);
      margin-bottom: 4px;
      font-size: 1.1rem;
    }

    .list-stack {
      display: grid;
      gap: 10px;
    }

    .list-item {
      padding: 12px 14px;
      border-radius: 14px;
      background: rgba(255,255,255,0.82);
      border: 1px solid rgba(79, 70, 229, 0.09);
      color: var(--text);
      font-weight: 600;
    }

    .priority-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(34, 197, 94, 0.14);
      color: #166534;
      font-weight: 800;
      width: fit-content;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 900px) {
      .hero, .content-grid { grid-template-columns: 1fr; }
      .results-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 680px) {
      .page { padding: 16px; }
      .navbar { border-radius: 22px; flex-direction: column; align-items: flex-start; }
      .grid-2 { grid-template-columns: 1fr; }
      .hero { padding: 20px; }
      .panel { padding: 18px; }
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
        <a class="active" href="skill-gap-analysis.php">Analysis</a>
      </nav>
    </header>

    <section class="hero glass">
      <div>
        <div class="eyebrow">✨ Skill Gap Analysis Tool</div>
        <h1>Turn your goals into a practical learning plan.</h1>
        <p>Describe what you already know, choose your next target role, and let SkillMate highlight the gaps, priorities, and next steps for your growth.</p>
        <ul class="hero-points">
          <li>✓ Smart, dummy-ready analysis logic for your project demo</li>
          <li>✓ Beautiful glassmorphism UI with blue, purple and white tones</li>
          <li>✓ Easy to connect later with PHP and MySQL</li>
        </ul>
      </div>

      <div class="hero-visual">
        <div class="hero-card">
          <div class="stat-row">
            <span>Suggested pace</span>
            <strong>Fast-track</strong>
          </div>
          <div class="big-score" id="heroScore"><?php echo htmlspecialchars($heroScore, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="tip">A strong foundation often means focusing on one high-impact skill at a time.</div>
        </div>
      </div>
    </section>

    <section class="content-grid">
      <form id="analysisForm" class="panel glass" method="POST" action="skill-gap-analysis.php">
        <h2>Analyze your next skill move</h2>

        <div class="field">
          <label for="skillInput">Current Skills</label>
          <div class="tag-row">
            <div id="tagList" class="tag-list" aria-live="polite"></div>
            <input id="skillInput" type="text" placeholder="Type a skill and press Enter" autocomplete="off">
            <button class="mini-btn" type="button" id="addSkillBtn">Add</button>
          </div>
          <div id="hiddenSkills"></div>
          <p class="hint">Example: HTML, Python, Figma, Communication</p>
        </div>

        <div class="field">
          <label for="targetCareer">Target Career</label>
          <select id="targetCareer" name="targetCareer">
            <?php foreach ($careerProfiles as $careerName => $careerData): ?>
    <option
        value="<?php echo htmlspecialchars($careerName, ENT_QUOTES, 'UTF-8'); ?>"
        <?php echo $selectedCareer === $careerName ? 'selected' : ''; ?>
    >
        <?php echo htmlspecialchars($careerName, ENT_QUOTES, 'UTF-8'); ?>
    </option>
<?php endforeach; ?>
          </select>
        </div>

        <div class="grid-2">
          <div class="field">
            <label for="skillLevel">Skill Level</label>
            <select id="skillLevel" name="skillLevel">
              <option value="Beginner"<?php echo $selectedLevel === 'Beginner' ? ' selected' : ''; ?>>Beginner</option>
              <option value="Intermediate"<?php echo $selectedLevel === 'Intermediate' ? ' selected' : ''; ?>>Intermediate</option>
              <option value="Advanced"<?php echo $selectedLevel === 'Advanced' ? ' selected' : ''; ?>>Advanced</option>
            </select>
          </div>

          <div class="field">
            <label for="learningDuration">Learning Duration</label>
            <select id="learningDuration" name="learningDuration">
              <option value="2 weeks"<?php echo $selectedDuration === '2 weeks' ? ' selected' : ''; ?>>2 weeks</option>
              <option value="1 month"<?php echo $selectedDuration === '1 month' ? ' selected' : ''; ?>>1 month</option>
              <option value="3 months"<?php echo $selectedDuration === '3 months' ? ' selected' : ''; ?>>3 months</option>
              <option value="6 months"<?php echo $selectedDuration === '6 months' ? ' selected' : ''; ?>>6 months</option>
            </select>
          </div>
        </div>

        <button class="btn" type="submit" id="analyzeBtn">Analyze Skills</button>

        <div id="loadingState" class="loading-state hidden" aria-live="polite">
          <div class="spinner"></div>
          <span>Comparing your profile with the target role...</span>
        </div>
      </form>

      <aside class="panel glass" aria-label="Analysis summary preview">
        <h3>Preview Snapshot</h3>
        <div class="summary-card">
          <div class="summary-title">
            <span>Readiness Preview</span>
            <span id="previewBadge"><?php echo htmlspecialchars($previewBadge, ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="score-ring" id="scoreRing" <?php echo $scoreRingStyle; ?>>
            <div class="score-inner">
              <div>
                <strong id="previewScore"><?php echo htmlspecialchars($previewScore, ENT_QUOTES, 'UTF-8'); ?>%</strong>
                <span>Fit</span>
              </div>
            </div>
          </div>
          <div class="summary-value" id="previewText"><?php echo htmlspecialchars($previewText, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </aside>
    </section>

    <?php if ($analysis): ?>
    <section id="resultsSection" class="results-section" aria-live="polite">
      <div class="results-header">
        <span>Analysis Results</span>
        <span id="resultTitle"><?php echo htmlspecialchars($analysis['targetCareer'] . ' • ' . $analysis['level'], ENT_QUOTES, 'UTF-8'); ?></span>
      </div>

      <div class="results-grid">
        <article class="result-card">
          <div class="icon">📈</div>
          <div class="label">Readiness Score</div>
          <div class="value" id="readinessScore"><?php echo htmlspecialchars($analysis['readiness'], ENT_QUOTES, 'UTF-8'); ?>%</div>
        </article>

        <article class="result-card">
          <div class="icon">🎯</div>
          <div class="label">Priority</div>
          <div class="value" id="priorityValue"><?php echo htmlspecialchars($analysis['priority'], ENT_QUOTES, 'UTF-8'); ?></div>
        </article>

        <article class="result-card">
          <div class="icon">🧠</div>
          <div class="label">Current Skills</div>
          <div class="value" id="currentSkillsValue"><?php echo htmlspecialchars(implode(', ', $analysis['currentSkills']), ENT_QUOTES, 'UTF-8'); ?></div>
        </article>

        <article class="result-card">
          <div class="icon">🛤️</div>
          <div class="label">Suggested Next Step</div>
          <div class="value" id="nextStepValue"><?php echo htmlspecialchars($analysis['nextStep'], ENT_QUOTES, 'UTF-8'); ?></div>
        </article>
      </div>

      <div class="results-grid">
        <article class="result-card">
          <div class="icon">❗</div>
          <div class="label">Missing Skills</div>
          <div class="list-stack" id="missingSkillsList">
            <?php foreach ($analysis['missingSkills'] as $skill): ?>
              <div class="list-item"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="result-card">
          <div class="icon">✨</div>
          <div class="label">Recommended Skills</div>
          <div class="list-stack" id="recommendedSkillsList">
            <?php foreach ($analysis['recommendedSkills'] as $skill): ?>
              <div class="list-item"><?php echo htmlspecialchars($skill, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
          </div>
        </article>
      </div>
    </section>
    <?php endif; ?>
  </main>

  <script>
    const skillTags = <?php echo json_encode($selectedSkills, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const skillInput = document.getElementById('skillInput');
    const tagList = document.getElementById('tagList');
    const hiddenSkillsContainer = document.getElementById('hiddenSkills');
    const addSkillBtn = document.getElementById('addSkillBtn');

    function renderTags() {
      tagList.innerHTML = '';
      hiddenSkillsContainer.innerHTML = '';

      if (skillTags.length === 0) {
        const empty = document.createElement('span');
        empty.className = 'hint';
        empty.textContent = 'Add at least one skill to begin.';
        tagList.appendChild(empty);
        return;
      }

      skillTags.forEach((skill, index) => {
        const chip = document.createElement('span');
        chip.className = 'chip';
        chip.innerHTML = `<span>${skill}</span><button type="button" aria-label="Remove ${skill}">×</button>`;
        chip.querySelector('button').addEventListener('click', () => {
          skillTags.splice(index, 1);
          renderTags();
        });
        tagList.appendChild(chip);

        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'skills[]';
        hiddenInput.value = skill;
        hiddenSkillsContainer.appendChild(hiddenInput);
      });
    }

    function addSkill() {
      const value = skillInput.value.trim();
      if (!value) {
        return;
      }

      const normalized = value.toLowerCase();
      if (skillTags.some(skill => skill.toLowerCase() === normalized)) {
        skillInput.value = '';
        return;
      }

      skillTags.push(value);
      skillInput.value = '';
      renderTags();
    }

    addSkillBtn.addEventListener('click', addSkill);
    skillInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addSkill();
      }
    });

    renderTags();
  </script>
</body>
</html>
