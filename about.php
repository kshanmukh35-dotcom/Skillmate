<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SkillMate — About</title>


    <style>

        :root{
            --primary:#2f4d9a;
            --primary-soft:#edf2ff;
            --bg:#f3f5f9;
            --surface:#ffffff;
            --text:#18212f;
            --muted:#5f6f86;
            --accent:#2a9d5d;
            --border:rgba(15,23,42,0.08);
            --shadow:0 10px 24px rgba(15,23,42,0.05);
        }

        *{box-sizing:border-box;}

        html{scroll-behavior:smooth;}

        body{
            margin:0;
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
            color:var(--text);
            min-height:100vh;
            background:var(--bg);
            -webkit-font-smoothing:antialiased;
        }

        .page-background{
            min-height:100vh;
            background:linear-gradient(180deg,#f4f6fb 0%, #edf2ff 100%);
            padding:30px 20px 50px;
        }

        .container{max-width:1100px;margin:0 auto;}

        .top-nav{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            padding:14px 18px;
            margin-bottom:30px;
            background:rgba(255,255,255,0.94);
            border:1px solid var(--border);
            border-radius:16px;
            box-shadow:var(--shadow);
        }

        .brand{
            display:flex;
            align-items:center;
            gap:12px;
            font-size:19px;
            font-weight:800;
            color:var(--text);
        }

        .brand-logo{
            width:42px;
            height:42px;
            border-radius:12px;
            overflow:hidden;
            background:#fff;
            border:1px solid var(--border);
            box-shadow:0 8px 20px rgba(79,70,229,0.08);
        }

        .brand-logo img{width:100%;height:100%;object-fit:cover;}

        .nav-links{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            justify-content:flex-end;
        }

        .nav-links a{
            text-decoration:none;
            color:var(--primary);
            font-size:14px;
            font-weight:700;
            padding:9px 13px;
            border-radius:10px;
            transition:background .2s ease, transform .2s ease;
        }

        .nav-links a:hover{
            background:var(--primary-soft);
            transform:translateY(-1px);
        }

            transform:translateY(-1px);
        }


        /* =========================================
           HERO SECTION
        ========================================= */

        .hero{

            min-height:420px;

            display:flex;

            flex-direction:column;

            justify-content:center;

            align-items:center;

            text-align:center;

            padding:50px 25px;

            color:white;

            margin-bottom:25px;
        }


        .hero-logo{

            width:80px;

            height:80px;

            border-radius:22px;

            overflow:hidden;

            background:#fff;

            box-shadow:
                0 20px 45px
                rgba(0,0,0,0.22);

            margin-bottom:22px;
        }


        .hero-logo img{

            width:100%;

            height:100%;

            object-fit:cover;
        }


        .hero h1{

            margin:0;

            font-size:
                clamp(38px,6vw,64px);

            line-height:1.05;

            font-weight:900;

            letter-spacing:-1px;

            text-shadow:
                0 8px 25px
                rgba(0,0,0,0.25);
        }


        .hero h1 span{

            color:rgba(70, 224, 229, 0.96);
        }


        .hero p{

            max-width:720px;

            margin:20px auto 0;

            font-size:18px;

            line-height:1.8;

            color:rgba(255,255,255,0.92);

            text-shadow:
                0 4px 15px
                rgba(0,0,0,0.25);
        }


        /* =========================================
           CONTENT CARD
        ========================================= */

        .card{

            background:
                rgba(255,255,255,0.96);

            backdrop-filter:blur(12px);

            padding:38px;

            border-radius:28px;

            border:1px solid
                rgba(255,255,255,0.7);

            box-shadow:var(--shadow);

            margin-bottom:25px;
        }


        .section-title{

            margin:0 0 12px;

            font-size:28px;

            font-weight:850;

            color:var(--text);
        }


        .section-text{

            margin:0;

            color:var(--muted);

            line-height:1.85;

            font-size:16px;
        }


        /* =========================================
           FEATURE CARDS
        ========================================= */

        .features{

            display:grid;

            grid-template-columns:
                repeat(3,1fr);

            gap:18px;

            margin-top:28px;
        }


        .feature{

            padding:24px;

            border-radius:20px;

            background:
                linear-gradient(
                    145deg,
                    #f8fbff,
                    #eef2ff
                );

            border:1px solid
                rgba(79,70,229,0.10);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .feature:hover{

            transform:translateY(-5px);

            box-shadow:
                0 18px 35px
                rgba(15,23,42,0.09);
        }


        .feature-icon{

            width:48px;

            height:48px;

            display:grid;

            place-items:center;

            border-radius:14px;

            background:
                linear-gradient(
                    135deg,
                    #eef2ff,
                    #dcfce7
                );

            font-size:24px;

            margin-bottom:15px;
        }


        .feature h3{

            margin:0 0 8px;

            font-size:18px;

            color:var(--text);
        }


        .feature p{

            margin:0;

            color:var(--muted);

            line-height:1.65;

            font-size:14px;
        }


        /* =========================================
           HOW IT WORKS
        ========================================= */

        .steps{

            display:grid;

            gap:14px;

            margin-top:25px;
        }


        .step{

            display:flex;

            align-items:flex-start;

            gap:16px;

            padding:17px;

            border-radius:16px;

            background:#fafbff;

            border:1px solid
                rgba(79,70,229,0.08);
        }


        .step-number{

            flex-shrink:0;

            width:38px;

            height:38px;

            display:grid;

            place-items:center;

            border-radius:50%;

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    #7c3aed
                );

            color:white;

            font-weight:800;
        }


        .step h3{

            margin:0 0 5px;

            font-size:16px;
        }


        .step p{

            margin:0;

            color:var(--muted);

            font-size:14px;

            line-height:1.6;
        }


        /* =========================================
           CALL TO ACTION
        ========================================= */

        .cta{

            text-align:center;

            background:
                linear-gradient(
                    135deg,
                    rgba(79,70,229,0.96),
                    rgba(124,58,237,0.96)
                );

            color:white;

            padding:40px 25px;

            border-radius:26px;

            box-shadow:
                0 20px 50px
                rgba(79,70,229,0.25);
        }


        .cta h2{

            margin:0 0 10px;

            font-size:28px;
        }


        .cta p{

            margin:0 auto 22px;

            max-width:650px;

            color:rgba(255,255,255,0.88);

            line-height:1.7;
        }


        .cta-button{

            display:inline-block;

            padding:13px 22px;

            border-radius:13px;

            background:var(--accent);

            color:white;

            text-decoration:none;

            font-weight:800;

            box-shadow:
                0 10px 25px
                rgba(34,197,94,0.25);

            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }


        .cta-button:hover{

            transform:translateY(-2px);

            box-shadow:
                0 15px 30px
                rgba(34,197,94,0.30);
        }


        /* =========================================
           FOOTER
        ========================================= */

        footer{

            text-align:center;

            color:rgba(255,255,255,0.85);

            font-size:13px;

            margin-top:28px;

            padding-bottom:5px;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media(max-width:850px){

            .features{

                grid-template-columns:1fr;
            }

            .top-nav{

                align-items:flex-start;

                flex-direction:column;
            }

            .nav-links{

                justify-content:flex-start;
            }

            .card{

                padding:25px;
            }
        }


        @media(max-width:600px){

            .page-background{

                padding:
                    15px
                    12px
                    30px;
            }

            .hero{

                min-height:360px;

                padding:
                    35px
                    15px;
            }

            .hero h1{

                font-size:40px;
            }

            .hero p{

                font-size:15px;
            }

            .card{

                border-radius:22px;

                padding:22px;
            }
        }

    </style>

</head>


<body>


<div class="page-background">


    <div class="container">


        <!-- =====================================
             NAVIGATION
        ====================================== -->

        <nav class="top-nav">

            <div class="brand">

                <div class="brand-logo">

                    <img
                        src="PROJECT LOGO.png"
                        alt="SkillMate Logo"
                    >

                </div>

                SkillMate

            </div>


            <div class="nav-links">

                <a href="index.php">
                    Home
                </a>

                <a href="profile.php">
                    Profile
                </a>

                <a href="teach.php">
                    Teach
                </a>

                <a href="learn.php">
                    Learn
                </a>

            </div>

        </nav>



        <!-- =====================================
             HERO
        ====================================== -->

        <section class="hero">


            


            <h1 align="center">
                About <span>SkillMate</span>
            </h1>


            


        </section>



        <!-- =====================================
             ABOUT SKILLMATE
        ====================================== -->

        <section class="card">


            <h2 class="section-title">
                What is SkillMate?
            </h2>


            <p class="section-text">

                SkillMate is a web-based Skill
                Exchange Platform designed to
                help students learn new skills
                by exchanging knowledge instead
                of depending only on paid courses.

                Users can create their profiles,
                add the skills they can teach,
                select the skills they want to
                learn, and discover suitable
                learning partners.

            </p>


            <!-- FEATURES -->

            <div class="features">


                <div class="feature">

                    <div class="feature-icon">
                        🤝
                    </div>

                    <h3>
                        Skill Exchange
                    </h3>

                    <p>

                        Exchange your knowledge
                        with other students and
                        learn from their skills.

                    </p>

                </div>



                <div class="feature">

                    <div class="feature-icon">
                        📚
                    </div>

                    <h3>
                        Learn Together
                    </h3>

                    <p>

                        Discover people who can
                        help you learn the skills
                        you are interested in.

                    </p>

                </div>



                <div class="feature">

                    <div class="feature-icon">
                        🚀
                    </div>

                    <h3>
                        Grow Your Skills
                    </h3>

                    <p>

                        Build your knowledge,
                        improve your abilities,
                        and achieve your learning
                        goals.

                    </p>

                </div>


            </div>


        </section>



        <!-- =====================================
             HOW SKILLMATE WORKS
        ====================================== -->

        <section class="card">


            <h2 class="section-title">
                How SkillMate Works
            </h2>


            <p class="section-text">

                SkillMate makes the process of
                finding and exchanging skills
                simple.

            </p>


            <div class="steps">


                <div class="step">

                    <div class="step-number">
                        1
                    </div>

                    <div>

                        <h3>
                            Create Your Profile
                        </h3>

                        <p>

                            Add your name,
                            education, interests,
                            skills and career goal.

                        </p>

                    </div>

                </div>



                <div class="step">

                    <div class="step-number">
                        2
                    </div>

                    <div>

                        <h3>
                            Add Your Skills
                        </h3>

                        <p>

                            Tell the community
                            what skills you can
                            teach and what skills
                            you want to learn.

                        </p>

                    </div>

                </div>



                <div class="step">

                    <div class="step-number">
                        3
                    </div>

                    <div>

                        <h3>
                            Find Learning Partners
                        </h3>

                        <p>

                            Discover students
                            whose skills match
                            your learning needs.

                        </p>

                    </div>

                </div>



                <div class="step">

                    <div class="step-number">
                        4
                    </div>

                    <div>

                        <h3>
                            Exchange Knowledge
                        </h3>

                        <p>

                            Send an exchange
                            request and learn
                            together after
                            mutual acceptance.

                        </p>

                    </div>

                </div>


            </div>


        </section>



        <!-- =====================================
             PUBLIC CTA
        ====================================== -->

        <section class="cta">


            <h2>
                Ready to Learn & Share?
            </h2>


            <p>

                Join SkillMate and connect
                with students who can teach
                what you want to learn while
                learning from what you know.

            </p>


            <a
                href="teach.php"
                class="cta-button"
            >
                Get Started
            </a>


        </section>



        <!-- =====================================
             FOOTER
        ====================================== -->

        <footer>

            © <?php echo date('Y'); ?>
            SkillMate.
            Learn • Share • Grow.

        </footer>


    </div>


</div>


</body>

</html>