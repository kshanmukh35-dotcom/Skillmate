<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include 'db_connect.php';

mysqli_report(MYSQLI_REPORT_OFF);

$user_id = (int)$_SESSION['user_id'];

/* =========================================================
   1. GET LOGGED-IN USER PROFILE
   ========================================================= */

$profile = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT full_name, education, skills_teach, skills_learn, interests, career_goal
     FROM profiles
     WHERE user_id = ?
     LIMIT 1"
);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $profile = mysqli_fetch_assoc($result);
    }

    mysqli_stmt_close($stmt);
}

$fullName   = $profile['full_name'] ?? '';
$education  = $profile['education'] ?? '';
$skillsTeach = $profile['skills_teach'] ?? '';
$skillsLearn = $profile['skills_learn'] ?? '';
$interests  = $profile['interests'] ?? '';
$careerGoal = $profile['career_goal'] ?? '';

/* ============================================================
   2. GET SAVED ROADMAP PROGRESS
   ============================================================ */

$savedProgress = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT step_id, step_name, completed
     FROM roadmap_progress
     WHERE user_id = ?
       AND completed = 1"
);

if ($stmt) {

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $savedProgress[] = [
            'step_id' => (int)$row['step_id'],
            'step_name' => $row['step_name'],
            'completed' => (int)$row['completed']
        ];
    }

    mysqli_stmt_close($stmt);
}



    
/* =========================================================
   3. GET ALL ROADMAPS FROM DATABASE
   ========================================================= */

$roadmaps = [];

$sql = "
    SELECT
        roadmap_id,
        title,
        description,
        career_goal
    FROM roadmaps
    ORDER BY roadmap_id ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $roadmaps[] = [
            'roadmap_id' => (int)$row['roadmap_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'career_goal' => $row['career_goal'],
            'steps' => []
        ];
    }
}

/* =========================================================
   4. GET ALL ROADMAP STEPS FROM DATABASE
   ========================================================= */

$sql = "
    SELECT
        step_id,
        roadmap_id,
        step_number,
        step_name,
        description,
        duration,
        skill_level
    FROM roadmap_steps
    ORDER BY roadmap_id ASC, step_number ASC
";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        foreach ($roadmaps as &$roadmap) {

            if (
                (int)$roadmap['roadmap_id']
                === (int)$row['roadmap_id']
            ) {

                $roadmap['steps'][] = [
                    'step_id' => (int)$row['step_id'],
                    'step_number' => (int)$row['step_number'],
                    'step_name' => $row['step_name'],
                    'description' => $row['description'],
                    'duration' => $row['duration'],
                    'skill_level' => $row['skill_level']
                ];

                break;
            }
        }

        unset($roadmap);
    }
}

/* =========================================================
   5. CONVERT DATABASE DATA TO JAVASCRIPT
   ========================================================= */

$roadmapsJson = json_encode(
    $roadmaps,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
);

$savedProgressJson = json_encode(
    $savedProgress,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

function sanitize($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>SkillMate — Learning Roadmap</title>

<style>

/* =========================================================
   BASIC
   ========================================================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Inter,
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #eef4ff,
            #f8fbff
        );

    color: #14213d;
}

button,
select {
    font: inherit;
}

button {
    cursor: pointer;
}

.container {
    width: min(1180px, 92%);
    margin: auto;
    padding: 30px 0 50px;
}

/* =========================================================
   HEADER
   ========================================================= */

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;

    margin-bottom: 25px;
}

.header h1 {
    margin: 0;
    font-size: 34px;
}

.header p {
    color: #64748b;
    line-height: 1.6;
}

.back-btn {
    text-decoration: none;

    background: white;
    color: #4f46e5;

    padding: 12px 18px;

    border-radius: 12px;

    font-weight: bold;

    border: 1px solid #e2e8f0;
}

/* =========================================================
   PROFILE CARDS
   ========================================================= */

.profile-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}

.profile-card {
    background: white;

    padding: 20px;

    border-radius: 20px;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 10px 30px
        rgba(15, 23, 42, 0.06);
}

.profile-card .label {
    color: #4f46e5;

    font-size: 13px;

    font-weight: bold;

    text-transform: uppercase;

    margin-bottom: 8px;
}

.profile-card .value {
    font-size: 18px;

    font-weight: 700;
}

/* =========================================================
   PANEL
   ========================================================= */

.panel {
    background: white;

    padding: 25px;

    border-radius: 25px;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 15px 40px
        rgba(15, 23, 42, 0.07);

    margin-bottom: 25px;
}

.panel h2 {
    margin-top: 0;
}

/* =========================================================
   FORM
   ========================================================= */

.form-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;
}

.field {
    display: flex;

    flex-direction: column;

    gap: 8px;
}

.field label {
    font-weight: 700;

    color: #4f46e5;
}

.field select {
    padding: 13px 15px;

    border-radius: 12px;

    border: 1px solid #dbe3f0;

    background: #f8fafc;

    outline: none;
}

.field select:focus {
    border-color: #4f46e5;
}

/* =========================================================
   BUTTON
   ========================================================= */

.btn {
    margin-top: 20px;

    border: none;

    padding: 14px 22px;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );

    color: white;

    font-weight: 800;

    box-shadow:
        0 10px 25px
        rgba(79, 70, 229, 0.20);
}

.btn:hover {
    transform: translateY(-1px);
}

/* =========================================================
   ROADMAP
   ========================================================= */

.hidden {
    display: none;
}

.roadmap-header {
    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;
}

.roadmap-header h2 {
    margin: 0 0 8px;
}

.roadmap-description {
    color: #64748b;

    line-height: 1.6;
}

/* =========================================================
   PROGRESS
   ========================================================= */

.progress-container {
    margin-top: 20px;
}

.progress-top {
    display: flex;

    justify-content: space-between;

    margin-bottom: 8px;

    font-weight: bold;
}

.progress-bar {
    width: 100%;

    height: 13px;

    background: #e8eaf8;

    border-radius: 999px;

    overflow: hidden;
}

.progress-fill {
    height: 100%;

    width: 0%;

    background:
        linear-gradient(
            90deg,
            #4f46e5,
            #22c55e
        );

    border-radius: 999px;

    transition: width 0.3s ease;
}

/* =========================================================
   STEPS
   ========================================================= */

.steps {
    display: grid;

    gap: 18px;

    margin-top: 25px;
}

.step {
    border: 1px solid #e2e8f0;

    border-radius: 20px;

    padding: 20px;

    background: #ffffff;

    box-shadow:
        0 8px 25px
        rgba(15, 23, 42, 0.05);
}

.step-top {
    display: flex;

    gap: 15px;

    align-items: flex-start;
}

.step-number {
    min-width: 40px;

    height: 40px;

    display: grid;

    place-items: center;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #4f46e5,
            #7c3aed
        );

    color: white;

    font-weight: bold;
}

.step-content {
    flex: 1;
}

.step-title {
    margin: 0 0 8px;

    font-size: 20px;
}

.step-description {
    color: #64748b;

    line-height: 1.6;

    margin: 0;
}

.step-info {
    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 15px;
}

.badge {
    background: #f1f5f9;

    padding: 8px 12px;

    border-radius: 999px;

    font-size: 13px;

    color: #475569;
}

.complete-row {
    margin-top: 18px;

    padding-top: 15px;

    border-top: 1px solid #eef2f7;
}

.complete-row label {
    display: flex;

    align-items: center;

    gap: 10px;

    cursor: pointer;

    font-weight: 700;
}

.complete-row input {
    width: 18px;

    height: 18px;

    accent-color: #22c55e;
}

/* =========================================================
   EMPTY MESSAGE
   ========================================================= */

.empty {
    text-align: center;

    padding: 40px;

    color: #64748b;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width: 850px) {

    .profile-grid,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .header {
        flex-direction: column;

        align-items: flex-start;
    }

    .roadmap-header {
        flex-direction: column;
    }
}

</style>

</head>

<body>

<div class="container">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="header">

        <div>

            <h1>
                Learning Roadmap Generator
            </h1>

            <p>
                Create a step-by-step learning roadmap
                using the roadmap information stored in
                the SkillMate database.
            </p>

        </div>

        <a
            href="index.php"
            class="back-btn"
        >
            ← Back to HOME
        </a>

    </div>


    <!-- =====================================================
         PROFILE INFORMATION
    ====================================================== -->

    <div class="profile-grid">

        <div class="profile-card">

            <div class="label">
                PROFESSIONAL
            </div>

            <div class="value">
                <?php
                echo sanitize(
                    $fullName ?: 'Student'
                );
                ?>
            </div>

        </div>


        <div class="profile-card">

            <div class="label">
                Career Goal
            </div>

            <div class="value">
                <?php
                echo sanitize(
                    $careerGoal ?: 'Not Set'
                );
                ?>
            </div>

        </div>


        <div class="profile-card">

            <div class="label">
                Education
            </div>

            <div class="value">
                <?php
                echo sanitize(
                    $education ?: 'Not Set'
                );
                ?>
            </div>

        </div>

    </div>


    <!-- =====================================================
         SETTINGS
    ====================================================== -->

    <section class="panel">

        <h2>
            Roadmap Settings
        </h2>

        <div class="form-grid">

            <!-- Career Goal -->

            <div class="field">

                <label for="goalSelect">
                    Career Goal
                </label>

                <select id="goalSelect">

                    <option value="">
                        Select Career Goal
                    </option>

                    <?php foreach ($roadmaps as $roadmap): ?>

                        <option
                            value="<?php
                                echo sanitize(
                                    $roadmap['career_goal']
                                );
                            ?>"
                        >

                            <?php
                            echo sanitize(
                                $roadmap['career_goal']
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Skill Level -->

            <div class="field">

                <label for="levelSelect">
                    Skill Level
                </label>

                <select id="levelSelect">

                    <option value="Beginner">
                        Beginner
                    </option>

                    <option value="Intermediate">
                        Intermediate
                    </option>

                    <option value="Advanced">
                        Advanced
                    </option>

                </select>

            </div>


            <!-- Duration -->

            <div class="field">

                <label for="durationSelect">
                    Roadmap Length
                </label>

                <select id="durationSelect">

                    <option value="4 weeks">
                        4 weeks
                    </option>

                    <option value="6 weeks">
                        6 weeks
                    </option>

                    <option value="8 weeks">
                        8 weeks
                    </option>

                </select>

            </div>


            <!-- Study Time -->

            <div class="field">

                <label for="studyTimeSelect">
                    Daily Study Time
                </label>

                <select id="studyTimeSelect">

                    <option value="30 min">
                        30 minutes
                    </option>

                    <option value="1 hour" selected>
                        1 hour
                    </option>

                    <option value="2 hours">
                        2 hours
                    </option>

                </select>

            </div>

        </div>


        <button
            class="btn"
            id="generateBtn"
        >
            Generate Roadmap
        </button>

    </section>


    <!-- =====================================================
         ROADMAP OUTPUT
    ====================================================== -->

    <section
        class="panel hidden"
        id="roadmapArea"
    >

        <div class="roadmap-header">

            <div>

                <h2 id="roadmapTitle">
                    Learning Roadmap
                </h2>

                <p
                    class="roadmap-description"
                    id="roadmapDescription"
                >
                </p>

            </div>

            <button
                class="btn"
                id="saveBtn"
            >
                Save Progress
            </button>

        </div>


        <!-- Progress -->

        <div class="progress-container">

            <div class="progress-top">

                <span>
                    Progress
                </span>

                <span id="progressText">
                    0%
                </span>

            </div>

            <div class="progress-bar">

                <div
                    class="progress-fill"
                    id="progressFill"
                ></div>

            </div>

        </div>


        <!-- Steps -->

        <div
            class="steps"
            id="stepsContainer"
        ></div>

    </section>

</div>


<script>

/* =========================================================
   DATABASE DATA FROM PHP
   ========================================================= */

const roadmaps =
    <?php echo $roadmapsJson ?: '[]'; ?>;

const savedProgress =
    <?php echo $savedProgressJson ?: '[]'; ?>;


/* =========================================================
   ELEMENTS
   ========================================================= */

const goalSelect =
    document.getElementById('goalSelect');

const levelSelect =
    document.getElementById('levelSelect');

const durationSelect =
    document.getElementById('durationSelect');

const studyTimeSelect =
    document.getElementById('studyTimeSelect');

const generateBtn =
    document.getElementById('generateBtn');

const roadmapArea =
    document.getElementById('roadmapArea');

const roadmapTitle =
    document.getElementById('roadmapTitle');

const roadmapDescription =
    document.getElementById('roadmapDescription');

const stepsContainer =
    document.getElementById('stepsContainer');

const progressFill =
    document.getElementById('progressFill');

const progressText =
    document.getElementById('progressText');

const saveBtn =
    document.getElementById('saveBtn');


/* =========================================================
   CURRENT DATA
   ========================================================= */

let currentRoadmap = null;

let currentSteps = [];


/* =========================================================
   FIND ROADMAP
   ========================================================= */

function findRoadmap(goal) {

    return roadmaps.find(
        roadmap =>
            roadmap.career_goal === goal
    );
}


/* =========================================================
   GET STEPS BASED ON SKILL LEVEL
   ========================================================= */

function getSteps(roadmap, level) {

    if (!roadmap) {
        return [];
    }

    let steps =
        roadmap.steps.filter(
            step =>
                step.skill_level === level
        );

    /*
       If there are no steps for the selected
       level, show Beginner steps.
    */

    if (steps.length === 0) {

        steps =
            roadmap.steps.filter(
                step =>
                    step.skill_level === 'Beginner'
            );
    }

    return steps;
}


/* =========================================================
   UPDATE PROGRESS
   ========================================================= */

function updateProgress() {

    if (currentSteps.length === 0) {

        progressFill.style.width = '0%';

        progressText.textContent = '0%';

        return;
    }


    const completed =
        currentSteps.filter(
            step => step.completed
        ).length;


    const percentage =
        Math.round(
            (completed /
                currentSteps.length) *
            100
        );


    progressFill.style.width =
        percentage + '%';

    progressText.textContent =
        percentage + '%';
}


/* =========================================================
   RENDER STEPS
   ========================================================= */

function renderSteps() {

    stepsContainer.innerHTML = '';


    if (currentSteps.length === 0) {

        stepsContainer.innerHTML = `
            <div class="empty">
                No roadmap steps were found
                for this career goal and skill level.
            </div>
        `;

        updateProgress();

        return;
    }


    currentSteps.forEach(
        (step, index) => {

            const stepElement =
                document.createElement('div');

            stepElement.className =
                'step';


            stepElement.innerHTML = `

                <div class="step-top">

                    <div class="step-number">
                        ${step.step_number}
                    </div>

                    <div class="step-content">

                        <h3 class="step-title">
                            ${escapeHtml(
                                step.step_name
                            )}
                        </h3>

                        <p class="step-description">
                            ${escapeHtml(
                                step.description
                            )}
                        </p>

                    </div>

                </div>


                <div class="step-info">

                    <span class="badge">
                        Duration:
                        ${escapeHtml(
                            step.duration
                        )}
                    </span>

                    <span class="badge">
                        Level:
                        ${escapeHtml(
                            step.skill_level
                        )}
                    </span>

                </div>


                <div class="complete-row">

                    <label>

                        <input
                            type="checkbox"
                            data-index="${index}"
                            ${step.completed
                                ? 'checked'
                                : ''}
                        >

                        Mark this step as completed

                    </label>

                </div>

            `;


            stepsContainer.appendChild(
                stepElement
            );
        }
    );


    /*
       Checkbox events
    */

    document
        .querySelectorAll(
            '#stepsContainer input[type="checkbox"]'
        )
        .forEach(
            checkbox => {

                checkbox.addEventListener(
                    'change',
                    function () {

                        const index =
                            Number(
                                this.dataset.index
                            );

                        currentSteps[index]
                            .completed =
                            this.checked;

                        updateProgress();

                    }
                );

            }
        );


    updateProgress();
}


/* =========================================================
   GENERATE ROADMAP
   ========================================================= */

generateBtn.addEventListener(
    'click',
    function () {

        const goal =
            goalSelect.value;

        const level =
            levelSelect.value;


        if (!goal) {

            alert(
                'Please select a career goal.'
            );

            return;
        }


        currentRoadmap =
            findRoadmap(goal);


        if (!currentRoadmap) {

            alert(
                'No roadmap found for this career goal.'
            );

            return;
        }


        const steps =
            getSteps(
                currentRoadmap,
                level
            );


        /*
           Copy steps so that we can
           modify completed state.
        */

        currentSteps =
            steps.map(
                step => {

                    return {
                        ...step,
                        completed: false
                    };

                }
            );


        /* 
   Load saved completed steps
*/

if (
    Array.isArray(savedProgress)
) {

    currentSteps.forEach(step => {

        const savedStep = savedProgress.find(
            saved => Number(saved.step_id) === Number(step.step_id)
        );

        if (savedStep && Number(savedStep.completed) === 1) {
            step.completed = true;
        }

    });

}


        roadmapTitle.textContent =
            currentRoadmap.title;


        roadmapDescription.textContent =
            currentRoadmap.description;


        renderSteps();


        roadmapArea.classList.remove(
            'hidden'
        );


        /*
           Scroll to roadmap
        */

        roadmapArea.scrollIntoView({
            behavior: 'smooth'
        });

    }
);


/* =========================================================
   SAVE PROGRESS
   ========================================================= */

saveBtn.addEventListener(
    'click',
    function () {

        if (!currentRoadmap) {

            alert(
                'Generate a roadmap first.'
            );

            return;
        }


        const completedSteps =
            currentSteps
                .filter(
                    step =>
                        step.completed
                )
                .map(
                    step =>
                        step.step_id
                );
console.log("COMPLETED STEPS BEING SENT:", completedSteps);

        const data = {

            roadmap_id:
                currentRoadmap.roadmap_id,

            career_goal:
                goalSelect.value,

            skill_level:
                levelSelect.value,

            duration:
                durationSelect.value,

            study_time:
                studyTimeSelect.value,

            steps:
                completedSteps
        };


        saveBtn.textContent =
            'Saving...';


        fetch(
            'php/save_roadmap.php',
            {

                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                body:
                    JSON.stringify(data)

            }
        )

        .then(
            response =>
                response.json()
        )

        .then(
            result => {

                if (result.success) {

                    saveBtn.textContent =
                        'Saved ✓';

                    /*
                       Update local saved progress
                    */

                    savedProgress.length = 0;

                completedSteps.forEach(step_id => {
             savedProgress.push({
          step_id: step_id,
        completed: 1
    });
});
                }
                else {

                    saveBtn.textContent =
                        'Save Failed';
alert(
    (result.message || 'Could not save progress.') +
    '\n\n' +
    (result.error || 'No error details returned.') +
    '\n\nFile: ' +
    (result.file || 'Unknown') +
    '\nLine: ' +
    (result.line || 'Unknown')
);

                }


                setTimeout(
                    () => {

                        saveBtn.textContent =
                            'Save Progress';

                    },
                    2000
                );

            }
        )

        
            .catch(error => {
    console.error('Save roadmap error:', error);

    saveBtn.textContent = 'Save Failed';

    alert(
        'Server error while saving progress.\n\n' +
        error.message
    );

    setTimeout(() => {
        saveBtn.textContent = 'Save Progress';
    }, 2000);
});

    }
);


/* =========================================================
   ESCAPE HTML
   ========================================================= */

function escapeHtml(value) {

    const div =
        document.createElement('div');

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


/* =========================================================
   LOAD PROFILE CAREER GOAL AUTOMATICALLY
   ========================================================= */

const profileCareerGoal =
    <?php
    echo json_encode(
        $careerGoal,
        JSON_UNESCAPED_UNICODE
    );
    ?>;


/*
   If user's profile career goal
   exists in the roadmap database,
   select it automatically.
*/

if (profileCareerGoal) {

    const option =
        Array.from(
            goalSelect.options
        ).find(
            option =>
                option.value ===
                profileCareerGoal
        );

    if (option) {

        goalSelect.value =
            profileCareerGoal;

    }
}

</script>

</body>
</html>