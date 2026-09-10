<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include "db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* ---------------------------------------------------------
   GET USER ACCOUNT INFORMATION
--------------------------------------------------------- */
$userQuery = mysqli_prepare(
    $conn,
    "SELECT user_id, full_name, email
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($userQuery, "i", $user_id);
mysqli_stmt_execute($userQuery);

$userResult = mysqli_stmt_get_result($userQuery);
$user = mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userQuery);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$accountFullName = $user['full_name'] ?? '';
$accountEmail = $user['email'] ?? '';
$currentUserName = trim($_SESSION['full_name'] ?? '') !== '' ? $_SESSION['full_name'] : ($accountFullName ?: 'Student');

/* ---------------------------------------------------------
   GET PROFILE INFORMATION
--------------------------------------------------------- */
$profileQuery = mysqli_prepare(
    $conn,
    "SELECT *
     FROM profiles
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($profileQuery, "i", $user_id);
mysqli_stmt_execute($profileQuery);

$profileResult = mysqli_stmt_get_result($profileQuery);
$profile = mysqli_fetch_assoc($profileResult) ?: [];

mysqli_stmt_close($profileQuery);

/* ---------------------------------------------------------
   PROFILE VALUES
--------------------------------------------------------- */
$firstName = $profile['first_name'] ?? '';
$lastName = $profile['last_name'] ?? '';

$phone = $profile['phone'] ?? '';
$phoneVerified = (int)($profile['phone_verified'] ?? 0);

$college = $profile['college'] ?? '';
$collegeVerified = (int)($profile['college_verified'] ?? 0);

$education = $profile['education'] ?? '';
$careerGoal = $profile['career_goal'] ?? '';
$interests = $profile['interests'] ?? '';
$aboutMe = $profile['about_me'] ?? '';

$profileImage = $profile['profile_image'] ?? '';
$currentUserProfileImage = $profileImage ?: '';

/* ---------------------------------------------------------
   STATUS MESSAGE
--------------------------------------------------------- */
$statusType = '';
$statusMessage = '';

if (isset($_GET['success'])) {

    if ($_GET['success'] === '1') {
        $statusType = 'success';
        $statusMessage = 'Profile updated successfully.';
    }

} elseif (isset($_GET['error'])) {

    $statusType = 'error';

    if ($_GET['error'] === 'missing') {
        $statusMessage = 'Please complete all required fields.';
    } elseif ($_GET['error'] === 'db') {
        $statusMessage = 'Unable to save profile. Please try again.';
    } elseif ($_GET['error'] === 'image') {
        $statusMessage = 'Unable to upload the profile image.';
    } else {
        $statusMessage = 'Something went wrong. Please try again.';
    }
}
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>SkillMate — Student Profile</title>

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
  --border:rgba(15,23,42,0.08);
  --shadow:0 10px 24px rgba(15,23,42,0.05);
}

*{box-sizing:border-box;}

body{
  margin:0;
  font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;
  background:var(--bg);
  color:var(--text);
  -webkit-font-smoothing:antialiased;
}

.page{
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
}

.pageContent{
  max-width:1040px;
  width:100%;
  display:grid;
  grid-template-columns:1.1fr 0.9fr;
  gap:24px;
}

.card{
  background:linear-gradient(
    145deg,
    rgba(255,255,255,0.98),
    rgba(248,251,255,0.97)
  );
  padding:24px;
  border-radius:24px;
  box-shadow:var(--shadow);
  border:1px solid var(--border);
}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:18px;
  padding:18px 20px;
  border-radius:18px;
  background:linear-gradient(135deg,#eef2ff,#f8fbff);
  border:1px solid rgba(79,70,229,0.12);
}

.header-left{
  display:flex;
  align-items:center;
  gap:12px;
  min-width:0;
}

.logo{
  width:58px;
  height:58px;
  border-radius:14px;
  overflow:hidden;
  display:grid;
  place-items:center;
  background:#fff;
  box-shadow:0 14px 30px rgba(79,70,229,0.14);
  position:relative;
}

.logo img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.eyebrow{
  margin:0 0 3px;
  font-size:11px;
  letter-spacing:0.14em;
  text-transform:uppercase;
  color:var(--primary);
  font-weight:800;
}

h1{
  margin:0;
  font-size:clamp(24px,3vw,30px);
  letter-spacing:-0.02em;
}

p.lead{
  margin:8px 0 0;
  color:var(--muted);
  line-height:1.7;
}

.topbar-right{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
}

.menu{
  display:inline-flex;
  align-items:center;
  gap:8px;
  flex-wrap:wrap;
  padding:6px;
  border-radius:999px;
  background:var(--surface-soft);
  border:1px solid var(--border);
  box-shadow:0 8px 18px rgba(79,70,229,0.06);
}

.menu a{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:8px 12px;
  border-radius:999px;
  text-decoration:none;
  color:var(--muted);
  font-weight:700;
  white-space:nowrap;
  font-size:13px;
  transition:all .16s ease;
}

.menu a:hover{
  background:#fff;
  color:var(--primary);
  box-shadow:0 4px 10px rgba(15,23,42,0.04);
}

.menu a.active{
  background:var(--primary-soft);
  color:var(--primary);
}

.notification-bell {
  width:42px;
  height:42px;
  border:none;
  border-radius:50%;
  display:grid;
  place-items:center;
  text-decoration:none;
  background:#fff;
  color:var(--primary);
  border:1px solid rgba(15, 23, 42, 0.08);
  font-size:20px;
  box-shadow:0 8px 24px rgba(15, 23, 42, 0.06);
  transition:transform .18s ease,box-shadow .18s ease,background .18s ease;
}

.notification-bell:hover {
  transform:translateY(-2px);
  background:var(--primary-soft);
  box-shadow:0 14px 30px rgba(79, 70, 229, 0.12);
}

.profile-pill{
  display:flex;
  align-items:center;
  gap:10px;
  padding:6px 10px;
  border-radius:999px;
  background:#fff;
  border:1px solid var(--border);
  box-shadow:0 6px 15px rgba(15,23,42,0.04);
}

.avatar{
  width:38px;
  height:38px;
  border-radius:50%;
  object-fit:cover;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg,var(--primary),#5d78d8);
  color:#fff;
  font-weight:800;
  font-size:14px;
  border:2px solid #fff;
  box-shadow:0 4px 12px rgba(15,23,42,0.12);
}

.profile-pill strong{ display:block; font-size:13px; color:var(--text); }
.profile-pill span{ display:block; color:var(--muted); font-size:11px; }

form{
  display:grid;
  gap:12px;
}

label{
  font-weight:700;
  font-size:14px;
  color:var(--text);
}

.input,
.textarea{
  width:100%;
  padding:13px 14px;
  border-radius:14px;
  border:1px solid #e2e8f0;
  background:#fff;
  outline:none;
  font-size:15px;
  color:var(--text);
}

.input:focus,
.textarea:focus{
  border-color:rgba(79,70,229,0.3);
  box-shadow:0 8px 22px rgba(79,70,229,0.08);
}

.textarea{
  min-height:120px;
  resize:vertical;
}

.grid-2{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:14px;
}

.profile-picture-box{
  display:flex;
  align-items:center;
  gap:18px;
  padding:16px;
  border-radius:18px;
  background:#f8fbff;
  border:1px solid rgba(79,70,229,0.1);
}

.profile-preview{
  width:90px;
  height:90px;
  border-radius:50%;
  object-fit:cover;
  background:linear-gradient(135deg,#eef2ff,#f8fbff);
  border:3px solid #fff;
  box-shadow:0 10px 25px rgba(79,70,229,0.12);
}

.profile-placeholder{
  width:90px;
  height:90px;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg,var(--primary),#8b5cf6);
  color:#fff;
  font-size:26px;
  font-weight:800;
}

.readonly-field{
  background:#f8fafc;
  color:#475569;
}

.verification-row{
  display:flex;
  align-items:center;
  gap:10px;
}

.verification-status{
  font-size:12px;
  font-weight:800;
  padding:6px 10px;
  border-radius:999px;
}

.verification-status.verified{
  background:#ecfdf3;
  color:#166534;
}

.verification-status.pending{
  background:#fff7ed;
  color:#9a3412;
}

.verify-note{
  margin:5px 0 0;
  font-size:12px;
  color:var(--muted);
}

.btn{
  cursor:pointer;
  border:none;
  border-radius:14px;
  padding:13px 16px;
  font-weight:700;
  font-size:15px;
  transition:transform .12s ease,
             box-shadow .12s ease,
             background .12s ease;
}

.btn.primary{
  background:linear-gradient(135deg,var(--accent),#16a34a);
  color:#fff;
  box-shadow:0 12px 28px rgba(34,197,94,0.18);
}

.btn.primary:hover{
  background:linear-gradient(135deg,#22c55e,#15803d);
}

.btn.ghost{
  background:#fff;
  color:var(--primary);
  border:1px solid rgba(15,23,42,0.08);
}

.btn.ghost:hover{
  background:#eef2ff;
}

.btn:hover{
  transform:translateY(-2px);
}

.status{
  margin-top:18px;
  padding:16px 18px;
  border-radius:18px;
  background:#f0fdf4;
  color:#166534;
  border:1px solid rgba(34,197,94,0.2);
  display:none;
  box-shadow:0 12px 28px rgba(15,23,42,0.06);
}

.status.visible{
  display:block;
}

.status.error{
  background:#fef2f2;
  color:#991b1b;
  border-color:rgba(248,113,113,0.2);
}

.infoCard{
  display:grid;
  gap:18px;
  align-content:start;
}

.infoBox{
  padding:20px;
  border-radius:20px;
  background:#fff;
  border:1px solid rgba(79,70,229,0.1);
  box-shadow:0 12px 28px rgba(79,70,229,0.04);
}

.infoBox strong{
  display:block;
  margin-bottom:6px;
  font-size:17px;
}

.infoBox p{
  margin:0;
  color:var(--muted);
  line-height:1.7;
}

@media (max-width:800px){

  .pageContent{
    grid-template-columns:1fr;
  }

  .header{
    flex-wrap:wrap;
  }

  .menu{
    width:100%;
    justify-content:center;
    flex-wrap:wrap;
  }

}

@media (max-width:600px){

  .grid-2{
    grid-template-columns:1fr;
  }

  .profile-picture-box{
    flex-direction:column;
    align-items:flex-start;
  }

}

</style>

</head>

<body>

<main class="page">

<div class="pageContent">

<section class="card">

<div class="header">

<div class="header-left">

<div class="logo">
<img src="PROJECT LOGO.png" alt="SkillMate logo">
</div>

<div>

<p class="eyebrow">Student dashboard</p>
<h1>Complete your profile</h1>



</div>

</div>

<div class="topbar-right">

<nav class="menu" aria-label="Main navigation">

<a href="index.php">Home</a>

<a href="about.php">About</a>

</nav>

<a href="notification.php" class="notification-bell" title="Notifications" aria-label="Notifications">🔔</a>


</div>

</div>


<form
id="profileForm"
action="php/save_profile.php"
method="POST"
enctype="multipart/form-data"
>


<!-- PROFILE IMAGE -->

<div>

<label for="profile_image">
Profile Picture
</label>

<div class="profile-picture-box">

<?php if (!empty($profileImage)): ?>

<img
src="<?php echo htmlspecialchars($profileImage); ?>"
class="profile-preview"
alt="Profile picture"
>

<?php else: ?>

<div class="profile-placeholder">

<?php
$initial = strtoupper(substr($accountFullName, 0, 1));
echo htmlspecialchars($initial ?: 'S');
?>

</div>

<?php endif; ?>

<div>

<input
type="file"
id="profile_image"
name="profile_image"
class="input"
accept="image/*"
>

<p class="verify-note">
Use a clear profile picture.
</p>

</div>

</div>

</div>


<!-- FIRST + LAST NAME -->

<div class="grid-2">

<div>

<label for="first_name">
First Name
</label>

<input
id="first_name"
name="first_name"
class="input"
type="text"
required
value="<?php echo htmlspecialchars($firstName); ?>"
placeholder="Enter your first name"
>

</div>


<div>

<label for="last_name">
Last Name
</label>

<input
id="last_name"
name="last_name"
class="input"
type="text"
required
value="<?php echo htmlspecialchars($lastName); ?>"
placeholder="Enter your last name"
>

</div>

</div>


<!-- FULL NAME FROM USERS -->

<div>

<label for="full_name">
Full Name
</label>

<input
id="full_name"
class="input readonly-field"
type="text"
readonly
value="<?php echo htmlspecialchars($accountFullName); ?>"
>

<p class="verify-note">
Your account name is taken from your registration details.
</p>

</div>


<!-- EMAIL -->

<div>

<label for="email">
Email
</label>

<input
id="email"
class="input readonly-field"
type="email"
readonly
value="<?php echo htmlspecialchars($accountEmail); ?>"
>

<p class="verify-note">
Email is linked to your SkillMate account.
</p>

</div>


<!-- PHONE -->

<div>

<label for="phone">
Phone Number
</label>

<div class="verification-row">

<input
id="phone"
name="phone"
class="input"
type="tel"
required
value="<?php echo htmlspecialchars($phone); ?>"
placeholder="Enter your phone number"
>

<?php if ($phoneVerified): ?>

<span class="verification-status verified">
✓ Verified
</span>

<?php else: ?>

<span class="verification-status pending">
Not verified
</span>

<?php endif; ?>

</div>

<p class="verify-note">
Phone verification will be handled through OTP verification.
</p>

</div>


<!-- COLLEGE -->

<div>

<label for="college">
College / University
</label>

<div class="verification-row">

<input
id="college"
name="college"
class="input"
type="text"
required
value="<?php echo htmlspecialchars($college); ?>"
placeholder="Enter your college name"
>

<?php if ($collegeVerified): ?>

<span class="verification-status verified">
✓ Verified
</span>

<?php else: ?>

<span class="verification-status pending">
Pending
</span>

<?php endif; ?>

</div>

<p class="verify-note">
College verification can be reviewed by the SkillMate administrator.
</p>

</div>


<!-- EDUCATION -->

<div>

<label for="education">
Education
</label>

<select
id="education"
name="education"
class="input"
required
>

<option value="">Select your education</option>

<option value="Intermediate - 1st Year"
<?php echo $education === 'Intermediate - 1st Year' ? 'selected' : ''; ?>>
Intermediate - 1st Year
</option>

<option value="Intermediate - 2nd Year"
<?php echo $education === 'Intermediate - 2nd Year' ? 'selected' : ''; ?>>
Intermediate - 2nd Year
</option>

<option value="Diploma - 1st Year"
<?php echo $education === 'Diploma - 1st Year' ? 'selected' : ''; ?>>
Diploma - 1st Year
</option>

<option value="Diploma - 2nd Year"
<?php echo $education === 'Diploma - 2nd Year' ? 'selected' : ''; ?>>
Diploma - 2nd Year
</option>

<option value="Diploma - 3rd Year"
<?php echo $education === 'Diploma - 3rd Year' ? 'selected' : ''; ?>>
Diploma - 3rd Year
</option>

<option value="B.Tech - 1st Year"
<?php echo $education === 'B.Tech - 1st Year' ? 'selected' : ''; ?>>
B.Tech - 1st Year
</option>

<option value="B.Tech - 2nd Year"
<?php echo $education === 'B.Tech - 2nd Year' ? 'selected' : ''; ?>>
B.Tech - 2nd Year
</option>

<option value="B.Tech - 3rd Year"
<?php echo $education === 'B.Tech - 3rd Year' ? 'selected' : ''; ?>>
B.Tech - 3rd Year
</option>

<option value="B.Tech - 4th Year"
<?php echo $education === 'B.Tech - 4th Year' ? 'selected' : ''; ?>>
B.Tech - 4th Year
</option>

<option value="Student / Learner"
<?php echo $education === 'Student / Learner' ? 'selected' : ''; ?>>
Student / Learner
</option>

<option value="Other"
<?php echo $education === 'Other' ? 'selected' : ''; ?>>
Other
</option>

</select>

</div>


<!-- CAREER GOAL -->

<div>

<label for="career_goal">
Career Goal
</label>

<select
id="career_goal"
name="career_goal"
class="input"
required
>

<option value="">Choose a career goal</option>

<option value="Web Developer"
<?php echo $careerGoal === 'Web Developer' ? 'selected' : ''; ?>>
Web Developer
</option>

<option value="Java Developer"
<?php echo $careerGoal === 'Java Developer' ? 'selected' : ''; ?>>
Java Developer
</option>

<option value="Python Developer"
<?php echo $careerGoal === 'Python Developer' ? 'selected' : ''; ?>>
Python Developer
</option>

<option value="Software Developer"
<?php echo $careerGoal === 'Software Developer' ? 'selected' : ''; ?>>
Software Developer
</option>

<option value="Android Developer"
<?php echo $careerGoal === 'Android Developer' ? 'selected' : ''; ?>>
Android Developer
</option>

<option value="Data Analyst"
<?php echo $careerGoal === 'Data Analyst' ? 'selected' : ''; ?>>
Data Analyst
</option>

<option value="Data Scientist"
<?php echo $careerGoal === 'Data Scientist' ? 'selected' : ''; ?>>
Data Scientist
</option>

<option value="AI/ML Engineer"
<?php echo $careerGoal === 'AI/ML Engineer' ? 'selected' : ''; ?>>
AI/ML Engineer
</option>

<option value="Cloud Engineer"
<?php echo $careerGoal === 'Cloud Engineer' ? 'selected' : ''; ?>>
Cloud Engineer
</option>

<option value="Cybersecurity Engineer"
<?php echo $careerGoal === 'Cybersecurity Engineer' ? 'selected' : ''; ?>>
Cybersecurity Engineer
</option>

<option value="UI/UX Designer"
<?php echo $careerGoal === 'UI/UX Designer' ? 'selected' : ''; ?>>
UI/UX Designer
</option>

<option value="Database Administrator"
<?php echo $careerGoal === 'Database Administrator' ? 'selected' : ''; ?>>
Database Administrator
</option>

<option value="Network Engineer"
<?php echo $careerGoal === 'Network Engineer' ? 'selected' : ''; ?>>
Network Engineer
</option>

<option value="Other"
<?php echo $careerGoal === 'Other' ? 'selected' : ''; ?>>
Other
</option>

</select>

</div>


<!-- INTERESTS -->

<div>

<label for="interests">
Interests
</label>

<input
id="interests"
name="interests"
class="input"
type="text"
value="<?php echo htmlspecialchars($interests); ?>"
placeholder="Example: Java, Web Development, AI, Design"
>

<p class="verify-note">
Separate multiple interests using commas.
</p>

</div>


<!-- ABOUT ME -->

<div>

<label for="about_me">
About Me
</label>

<textarea
id="about_me"
name="about_me"
class="textarea"
placeholder="Tell other SkillMate students a little about yourself..."
><?php echo htmlspecialchars($aboutMe); ?></textarea>

</div>


<!-- BUTTONS -->

<div
class="grid-2"
style="align-items:center;gap:14px;margin-top:8px;"
>

<button
class="btn primary"
type="submit"
>
Save Profile
</button>

<a
class="btn ghost"
href="teach.php"
style="text-align:center;text-decoration:none;"
>
Manage Teaching Skills
</a>

</div>

</form>


<div
id="status"
class="status<?php
echo $statusType === 'error'
    ? ' error visible'
    : ($statusType === 'success' ? ' visible' : '');
?>"
>

<?php echo htmlspecialchars($statusMessage); ?>

</div>

</section>


<!-- RIGHT SIDE -->

<aside class="infoCard">

<div class="infoBox">

<strong>Why this matters</strong>

<p>
Your profile helps SkillMate understand your education,
career goals and interests so that other students can
know more about you before starting a skill exchange.
</p>

</div>


<div class="infoBox">

<strong>Your Skills</strong>

<p>
Teaching skills are now managed separately from your
profile. Go to the Teach page to add skills and select
their categories.
</p>

</div>


<div class="infoBox">

<strong>Verification</strong>

<p>
Phone verification will use OTP verification, while
college verification can be reviewed by the SkillMate
administrator.
</p>

</div>


<div class="infoBox">

<strong>What happens next?</strong>

<p>
After saving your profile, you can add your teaching
skills from the Teach page. Other users will then be
able to discover those skills through the Learn page.
</p>

</div>

</aside>

</div>

</main>

</body>
</html>