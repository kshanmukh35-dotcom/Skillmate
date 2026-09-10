<?php
session_start();
include __DIR__ . '/db_connect.php';

$login_error = '';
$register_error = '';
$register_success = '';
$active_tab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =========================================================
       LOGIN
       ========================================================= */
    if (isset($_POST['login_submit'])) {

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email !== '' && $password !== '') {

            /*
             * Get the user including ROLE.
             * ROLE decides whether the user is a student or admin.
             */
            $stmt = mysqli_prepare(
                $conn,
                'SELECT user_id, full_name, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            if ($stmt) {

                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                if ($user = mysqli_fetch_assoc($result)) {

                    /*
                     * Passwords in users table are stored using
                     * password_hash(), so use password_verify().
                     */
                    if (password_verify($password, $user['password'])) {

                        /*
                         * Store common login information.
                         */
                        $_SESSION['user_id'] = (int)$user['user_id'];
                        $_SESSION['full_name'] = trim($user['full_name'] ?? '') !== '' ? $user['full_name'] : 'Student';
                        $_SESSION['email'] = $user['email'];

                        /*
                         * Store the role in the session.
                         */
                        $_SESSION['role'] = $user['role'];

                        /*
                         * ADMIN
                         */
                        if ($user['role'] === 'admin') {

                            header('Location: admin/dashboard.php');
                            exit();

                        }

                        /*
                         * STUDENT
                         */
                        header('Location: index.php');
                        exit();

                    } else {

                        $login_error = 'details are not valid!';
                    }

                } else {

                    $login_error = 'details are not valid!';
                }

                mysqli_stmt_close($stmt);

            } else {

                $login_error = 'Unable to process login right now.';
            }

        } else {

            $login_error = 'Please enter email and password.';
        }

        $active_tab = 'login';
    }


    /* =========================================================
       REGISTRATION
       ========================================================= */
    if (isset($_POST['register_submit'])) {

        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $phone = '';

        /*
         * IMPORTANT:
         * Normal registration ALWAYS creates a STUDENT.
         *
         * We do NOT allow the user to choose admin here.
         */
        $role = 'student';

        if (
            $full_name === '' ||
            $email === '' ||
            $password === '' ||
            $confirm_password === ''
        ) {

            $register_error = 'details are not valid!';
            $active_tab = 'register';

          } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {

            $register_error = 'Password must be at least 8 characters and include an uppercase letter, a number, and a special character.';
            $active_tab = 'register';

          } elseif ($password !== $confirm_password) {

            $register_error = 'Passwords do not match.';
            $active_tab = 'register';

        } else {

            /*
             * Check whether email already exists.
             */
            $check = mysqli_prepare(
                $conn,
                'SELECT user_id FROM users WHERE email = ? LIMIT 1'
            );

            if (!$check) {

                $register_error = 'Unable to check account details.';
                $active_tab = 'register';

            } else {

                mysqli_stmt_bind_param($check, 's', $email);
                mysqli_stmt_execute($check);

                $checkResult = mysqli_stmt_get_result($check);

                if (mysqli_num_rows($checkResult) > 0) {

                    $register_error = 'This email is already registered.';
                    $active_tab = 'register';

                } else {

                    /*
                     * Securely hash the password.
                     */
                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    /*
                     * Create STUDENT account.
                     */
                    $insert = mysqli_prepare(
                        $conn,
                        'INSERT INTO users
                        (full_name, email, phone, password, role)
                        VALUES (?, ?, ?, ?, ?)'
                    );

                    if ($insert) {

                        mysqli_stmt_bind_param(
                            $insert,
                            'sssss',
                            $full_name,
                            $email,
                            $phone,
                            $hashedPassword,
                            $role
                        );

                        if (mysqli_stmt_execute($insert)) {

                            $new_user_id = mysqli_insert_id($conn);

                            /*
                             * Log the newly registered student in.
                             */
                            $_SESSION['user_id'] = $new_user_id;
                            $_SESSION['full_name'] = trim($full_name) !== '' ? $full_name : 'Student';
                            $_SESSION['email'] = $email;
                            $_SESSION['role'] = 'student';

                            /*
                             * Students always go to index.php.
                             */
                            header('Location: index.php');
                            exit();

                        } else {

                            $register_error =
                                'Unable to create your account.';
                            $active_tab = 'register';
                        }

                        mysqli_stmt_close($insert);

                    } else {

                        $register_error =
                            'Unable to create your account.';
                        $active_tab = 'register';
                    }
                }

                mysqli_stmt_close($check);
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <title>SkillMate — Login</title>

  <style>

    :root{
      --primary:#2f4d9a;
      --bg:#f3f5f9;
      --surface:#ffffff;
      --surface-soft:#f8f9fc;
      --text:#18212f;
      --muted:#5f6f86;
      --accent:#2a9d5d;
      --border:rgba(15,23,42,0.08);
      --shadow:0 10px 24px rgba(15,23,42,0.05);
    }

    *{box-sizing:border-box}

    body{
      margin:0;
      font-family:Inter,ui-sans-serif,system-ui,Segoe UI,Roboto,'Helvetica Neue',Arial;
      background:var(--bg);
      color:var(--text);
      -webkit-font-smoothing:antialiased
    }

    a{
      color:inherit;
      text-decoration:none
    }

    .page{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px
    }

    .pageContent{
      max-width:1040px;
      width:100%;
      display:grid;
      grid-template-columns:1fr 400px;
      gap:24px
    }

    .hero{
      display:flex;
      flex-direction:column;
      gap:16px;
      justify-content:center
    }

    .logo{
      width:64px;
      height:64px;
      border-radius:18px;
      overflow:hidden;
      display:grid;
      place-items:center;
      background:#fff;
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

    .heroImage{
      width:min(100%,520px);
      max-height:360px;
      object-fit:contain;
      object-position:left center;
      background:transparent;
      opacity:0.92;
      mix-blend-mode:normal;
      filter:drop-shadow(0 0 0 rgba(0,0,0,0))
             saturate(0.8)
             contrast(0.96)
             brightness(1.02);
      border:none;
      outline:none;
      display:block;
    }

    h1{
      margin:0;
      font-size:clamp(32px,4vw,42px);
      line-height:1.05
    }

    p.lead{
      margin:0;
      color:var(--muted);
      max-width:560px;
      line-height:1.7
    }

    .navLinks{
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      margin-top:12px
    }

    .navLinks a{
      background:#fff;
      color:var(--primary);
      border:1px solid rgba(79,70,229,0.16);
      padding:10px 14px;
      border-radius:12px;
      transition:transform .12s ease,box-shadow .12s ease
    }

    .navLinks a:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 24px rgba(15,23,42,0.06)
    }

    .authModal{
      background:linear-gradient(
        145deg,
        rgba(255,255,255,0.98),
        rgba(248,251,255,0.97)
      );
      backdrop-filter:blur(6px) saturate(.9);
      padding:24px;
      border-radius:24px;
      box-shadow:var(--shadow);
      border:1px solid var(--border);
      display:grid;
      gap:18px
    }

    .authHeader{
      display:flex;
      justify-content:space-between;
      align-items:center
    }

    .authTitle{
      font-size:22px;
      font-weight:800;
      color:var(--text);
      letter-spacing:0.2px
    }

    .closeBtn{
      background:transparent;
      border:none;
      font-size:22px;
      cursor:pointer;
      color:var(--muted);
      padding:6px;
      border-radius:10px;
      transition:background .12s ease
    }

    .closeBtn:hover{
      background:rgba(15,23,42,0.04)
    }

    .authTabs{
      display:flex;
      gap:10px;
      margin-bottom:8px
    }

    .tabBtn{
      padding:10px 14px;
      border-radius:12px;
      border:1px solid transparent;
      background:transparent;
      cursor:pointer;
      font-weight:700;
      color:var(--muted);
      transition:all .12s ease;
      font-size:14px
    }

    .tabBtn.active{
      background:linear-gradient(
        90deg,
        rgba(79,70,229,0.10),
        rgba(34,197,94,0.08)
      );
      color:var(--primary);
      border-color:rgba(79,70,229,0.12);
      box-shadow:inset 0 -1px 0 rgba(255,255,255,0.4)
    }

    .tabBtn:focus{
      outline:none;
      box-shadow:0 6px 18px rgba(79,70,229,0.06)
    }

    .statusBanner{
      padding:14px 18px;
      border-radius:16px;
      background:#ecfdf5;
      color:#166534;
      border:1px solid rgba(22,163,74,0.18);
      margin-bottom:14px;
      font-weight:700
    }

    .errorBanner{
      background:#fee2e2;
      color:#991b1b;
      border-color:rgba(248,113,113,0.3)
    }

    .input{
      width:100%;
      padding:14px 16px;
      margin-bottom:10px;
      border-radius:14px;
      border:1px solid #e6eef9;
      background:linear-gradient(180deg,#fff,#fbfdff);
      outline:none;
      font-size:15px;
      color:var(--text);
      box-shadow:0 6px 18px rgba(16,24,40,0.04) inset;
      transition:box-shadow .12s ease,border-color .12s ease,transform .08s ease
    }

    .input::placeholder{
      color:#9aa4b2
    }

    .input:focus{
      box-shadow:0 10px 30px rgba(79,70,229,0.08);
      border-color:rgba(79,70,229,0.28);
      transform:translateY(-1px)
    }

    .helper{
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-size:13px;
      color:var(--muted);
      margin-bottom:10px
    }

    .passwordWrap{
      position:relative;
      display:block;
      margin-bottom:10px;
    }

    .passwordWrap .input{
      margin-bottom:0;
      padding-right:46px;
    }

    .togglePassword{
      position:absolute;
      top:50%;
      right:12px;
      transform:translateY(-50%);
      width:28px;
      height:28px;
      border:none;
      background:transparent;
      display:grid;
      place-items:center;
      cursor:pointer;
      color:#5f6f86;
      padding:0;
      border-radius:8px;
      transition:background .12s ease, color .12s ease;
    }

    .togglePassword:hover{
      background:rgba(15,23,42,0.04);
      color:var(--primary);
    }

    .togglePassword svg{
      width:18px;
      height:18px;
      stroke:currentColor;
      fill:none;
      stroke-width:2;
      stroke-linecap:round;
      stroke-linejoin:round;
    }

    .smallMuted{
      color:var(--muted);
      font-size:14px;
      line-height:1.7
    }

    .authAside{
      background:linear-gradient(135deg,#eef2ff,#f0fdf4);
      padding:24px;
      border-radius:22px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      gap:14px;
      border:1px solid rgba(79,70,229,0.08)
    }

    .socialRow{
      display:grid;
      gap:10px;
      margin-top:12px
    }

    .socialBtn{
      padding:12px;
      border-radius:14px;
      border:1px solid rgba(15,23,42,0.08);
      background:#fff;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:10px;
      justify-content:center;
      font-weight:700;
      color:var(--text);
      transition:transform .12s ease,box-shadow .12s ease
    }

    .socialBtn:hover{
      transform:translateY(-2px);
      box-shadow:0 14px 28px rgba(2,6,23,0.08)
    }

    .socialBtn svg{
      width:18px;
      height:18px;
      opacity:0.9
    }

    .btn{
      cursor:pointer;
      border:none;
      border-radius:14px;
      padding:13px 18px;
      font-weight:700;
      font-size:15px;
      transition:transform .12s ease,box-shadow .12s ease
    }

    .btn.primary{
      background:linear-gradient(135deg,var(--accent),#16a34a);
      color:#fff;
      box-shadow:0 14px 30px rgba(34,197,94,0.16)
    }

    .btn.ghost{
      background:transparent;
      color:var(--primary);
      border:1px solid rgba(79,70,229,0.16);
      box-shadow:none
    }

    .btn:hover{
      transform:translateY(-2px)
    }

    .statusCard{
      padding:20px;
      border-radius:16px;
      background:#fafbff;
      border:1px solid rgba(79,70,229,0.12);
      display:grid;
      gap:14px
    }

    .statusCard strong{
      display:block;
      font-size:18px;
      color:var(--text)
    }

    .statusCard p{
      margin:0;
      color:var(--muted);
      line-height:1.7
    }

    .actions{
      display:flex;
      gap:10px;
      flex-wrap:wrap
    }

    @media (max-width:900px){
      .pageContent{
        grid-template-columns:1fr;
      }

      .authAside{
        order:-1
      }
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

    .logo:hover{
      transform:translateY(-2px);
      box-shadow:0 18px 34px rgba(79,70,229,0.18)
    }

    .logo:hover img{
      transform:scale(1.05) translateY(-2px) rotate(-2deg);
      filter:drop-shadow(0 12px 24px rgba(79,70,229,0.2))
    }

  </style>

</head>

<body>

  <main class="page">

    <div class="pageContent">

      <section class="hero">

        
        <img
          class="heroImage"
          src="PROJECT_LOGO_transparent.png"
          alt="SkillMate learning community logo"
        >

        <h1>Login to SkillMate</h1>

        <p class="lead">
          Sign in or register to access personalized roadmaps,
          peer matching, and your skill dashboard.
        </p>

        <div class="navLinks">
          <a href="about.php">About</a>
        </div>

      </section>


      <section class="authModal" id="authSection">

        <div class="authHeader">

          <div>

            <div class="authTitle">
              Welcome back
            </div>

            <div class="smallMuted" id="authSubtitle">
              Use your account to continue.
            </div>

          </div>

          <button
            id="closePage"
            class="closeBtn"
            aria-label="Close"
            type="button"
          >
            ×
          </button>

        </div>


        <?php if (!empty($login_error)): ?>

          <div class="statusBanner errorBanner">
            <?php echo htmlspecialchars(
                $login_error,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
          </div>

        <?php elseif (!empty($register_success)): ?>

          <div class="statusBanner">
            <?php echo htmlspecialchars(
                $register_success,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
          </div>

        <?php endif; ?>


        <?php if (!empty($register_error)): ?>

          <div class="statusBanner errorBanner">
            <?php echo htmlspecialchars(
                $register_error,
                ENT_QUOTES,
                'UTF-8'
            ); ?>
          </div>

        <?php endif; ?>


        <div class="authTabs" role="tablist">

          <button
            id="tabLogin"
            class="tabBtn <?php echo $active_tab === 'login' ? 'active' : ''; ?>"
            type="button"
          >
            Sign in
          </button>

          <button
            id="tabRegister"
            class="tabBtn <?php echo $active_tab === 'register' ? 'active' : ''; ?>"
            type="button"
          >
            Create account
          </button>

        </div>


        <!-- LOGIN FORM -->

        <form
          id="loginForm"
          action="login.php"
          method="POST"
          style="<?php echo $active_tab === 'register'
              ? 'display:none'
              : 'display:block'; ?>"
        >

          <input
            id="loginEmail"
            name="email"
            class="input"
            type="email"
            required
            placeholder="Email address"
          >

          <div class="passwordWrap">
            <input
              id="loginPass"
              name="password"
              class="input"
              type="password"
              required
              placeholder="Password"
            >
            <button
              type="button"
              class="togglePassword"
              data-target="loginPass"
              aria-label="Show password"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>

          <div class="helper">

            <span class="smallMuted">
              Need an account? Create one above.
            </span>

            <a
    href="forgot_password.php"
    class="btn ghost"
    style="display:inline-block;text-align:center;text-decoration:none;"
>
    Forgot?
</a>

          </div>

          <button
            id="signInBtn"
            class="btn primary"
            type="submit"
            name="login_submit"
            value="1"
          >
            Sign in
          </button>

        </form>


        <!-- REGISTER FORM -->

        <form
          id="registerForm"
          action="login.php"
          method="POST"
          style="<?php echo $active_tab === 'register'
              ? 'display:block'
              : 'display:none'; ?>"
        >

          <input
            id="regName"
            name="full_name"
            class="input"
            required
            placeholder="Full name"
          >

          <input
            id="regEmail"
            name="email"
            class="input"
            type="email"
            required
            placeholder="Email address"
          >

          <div class="passwordWrap">
            <input
              id="regPass"
              name="password"
              class="input"
              type="password"
              minlength="8"
              pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
              required
              placeholder="Need: uppercase letter, number, letter"
              title="Need: uppercase letter, number, letter"
            >
            <button
              type="button"
              class="togglePassword"
              data-target="regPass"
              aria-label="Show password"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>

          <div class="passwordWrap">
            <input
              id="regConfirmPass"
              name="confirm_password"
              class="input"
              type="password"
              required
              placeholder="Re-enter password"
            >
            <button
              type="button"
              class="togglePassword"
              data-target="regConfirmPass"
              aria-label="Show password"
            >
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </button>
          </div>

          <!--
             Role input REMOVED.
             Every normal registration creates a STUDENT.
          -->

          <div class="actions">

            <button
              id="createAccountBtn"
              class="btn primary"
              type="submit"
              name="register_submit"
              value="1"
            >
              Create account
            </button>

            <button
              id="cancelRegister"
              class="btn ghost"
              type="button"
            >
              Cancel
            </button>

          </div>

        </form>


        <div
          id="messageBanner"
          class="statusBanner"
          style="display:none;"
        ></div>

      </section>


      <aside class="authAside">

        <div
          style="font-weight:700;font-size:18px;color:#0f172a"
        >
          Why join SkillMate?
        </div>

        <div class="smallMuted">

          • Build skills with peers through bite-sized sessions<br>

          • Personalized roadmaps and skill analysis tools<br>

          • Connect locally or online with student mentors

        </div>

      </aside>

    </div>

  </main>


  <script>

    const tabLogin =
      document.getElementById('tabLogin');

    const tabRegister =
      document.getElementById('tabRegister');

    const loginForm =
      document.getElementById('loginForm');

    const registerForm =
      document.getElementById('registerForm');

    const authSubtitle =
      document.getElementById('authSubtitle');

    const closePage =
      document.getElementById('closePage');

    const cancelRegister =
      document.getElementById('cancelRegister');

    document.querySelectorAll('.togglePassword').forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (!input) {
          return;
        }

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.setAttribute(
          'aria-label',
          isPassword ? 'Hide password' : 'Show password'
        );

        button.innerHTML = isPassword
          ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path d="M3 3l18 18"></path></svg>'
          : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
      });
    });


    function setActiveTab(tab){

      [
        tabLogin,
        tabRegister
      ].forEach(btn => {
        btn.classList.remove('active');
      });

      tab.classList.add('active');


      if (tab === tabLogin) {

        loginForm.style.display = 'block';

        registerForm.style.display = 'none';

        authSubtitle.textContent =
          'Use your account to continue.';

      } else {

        loginForm.style.display = 'none';

        registerForm.style.display = 'block';

        authSubtitle.textContent =
          'Create a new account and start learning.';

      }

    }


    tabLogin.addEventListener(
      'click',
      () => setActiveTab(tabLogin)
    );


    tabRegister.addEventListener(
      'click',
      () => setActiveTab(tabRegister)
    );


    cancelRegister.addEventListener(
      'click',
      () => setActiveTab(tabLogin)
    );


    closePage.addEventListener(
      'click',
      () => window.location.href = 'index.php'
    );


    const initialTab =
      '<?php echo $active_tab; ?>';


    if (initialTab === 'register') {

      setActiveTab(tabRegister);

    } else {

      setActiveTab(tabLogin);

    }

  </script>

</body>
</html>