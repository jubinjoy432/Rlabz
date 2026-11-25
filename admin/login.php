<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - RLabZ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Space+Grotesk:wght@300;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-base: #f4f7f6;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --surface: #0b5394;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: var(--bg-base);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            height: 650px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin: 2rem;
        }

        /* --- Left Side: Form --- */
        .login-left {
            flex: 1;
            padding: 3rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .login-header {
            margin-bottom: 1.5rem;
        }

        .btn-login-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.95rem;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 50px;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-login-back:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: #f8fafc;
            transform: translateX(-2px);
        }

        .login-header h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            color: var(--text-main);
            margin: 0 0 0.5rem 0;
        }

        .login-header p {
            color: var(--text-muted);
            margin: 0;
            font-size: 1.1rem;
        }

        .error-msg {
            color: #ef4444;
            background: #fee2e2;
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.92rem;
            font-weight: 500;
            display: <?php echo isset($_GET['error']) ? 'flex' : 'none'; ?>;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border: 1px solid #fca5a5;
        }

        body.dark-mode .error-msg {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.05rem;
            transition: color 0.3s;
            pointer-events: none;
        }

        .input-wrapper .toggle-password-btn {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            cursor: pointer;
            padding: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .input-wrapper .toggle-password-btn:hover {
            color: var(--primary);
        }

        .form-group input {
            width: 100%;
            padding: 0.95rem 2.85rem 0.95rem 2.85rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            background: #f8fafc;
            color: var(--text-main);
            font-family: inherit;
            font-size: 1rem;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-group input:focus + i.input-icon {
            color: var(--primary);
        }

        /* Password Strength Meter */
        .password-strength-wrap {
            margin-top: 0.5rem;
            display: none;
        }

        .strength-bars {
            display: flex;
            gap: 4px;
            height: 4px;
            border-radius: 2px;
            overflow: hidden;
            background: rgba(0,0,0,0.08);
            margin-bottom: 0.35rem;
        }

        body.dark-mode .strength-bars {
            background: rgba(255,255,255,0.1);
        }

        .strength-bar {
            flex: 1;
            background: #e2e8f0;
            border-radius: 2px;
            transition: all 0.25s ease;
        }

        body.dark-mode .strength-bar {
            background: #334155;
        }

        .strength-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
        }

        .strength-status {
            font-weight: 600;
            color: var(--text-muted);
        }

        .strength-hint {
            color: var(--text-muted);
            font-size: 0.72rem;
        }

        .field-feedback {
            display: none;
            color: #ef4444;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.35rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Space Grotesk', sans-serif;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
            padding: 1rem;
            border-radius: 12px;
            border: none;
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Space Grotesk', sans-serif;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(37, 99, 235, 0.5);
        }

        /* --- Right Side: Image/Logo --- */
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, var(--surface) 0%, #063463 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Decorative circles for professional feel */
        .login-right::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .login-right::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -150px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .brand-display {
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .brand-display img {
            width: 225px;
            max-width: 225px;
            height: auto;
            object-fit: contain;
            margin-bottom: 1.25rem;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.12));
            animation: float 6s ease-in-out infinite;
        }
        
        .brand-text {
            color: rgba(255, 255, 255, 0.9);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 2px;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }

        /* Responsive */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                height: auto;
                max-width: 500px;
            }
            .login-right {
                display: none; /* Hide image on small screens */
            }
            .login-left {
                padding: 3rem 2rem;
            }
        }

        @media (max-width: 600px) {
            body {
                height: auto;
                min-height: 100vh;
                padding: 1.5rem 0;
                align-items: flex-start;
            }
            .login-container {
                margin: 0.75rem auto;
                width: calc(100% - 1.5rem);
                border-radius: 18px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            }
            .login-left {
                padding: 2rem 1.25rem;
            }
            .login-header h2 {
                font-size: 1.85rem;
            }
            .login-header p {
                font-size: 0.95rem;
            }
            .input-wrapper input {
                font-size: 16px; /* Prevents auto-zoom on iOS Safari */
            }
        }
        body.dark-mode { background: #0b1120; color: #f1f5f9; }
        body.dark-mode .login-container { background: #1e293b; border: 1px solid #334155; box-shadow: 0 25px 60px rgba(0,0,0,0.5); }
        body.dark-mode .login-left { background: #1e293b; }
        body.dark-mode .login-header h2 { color: #f8fafc; }
        body.dark-mode .login-header p { color: #94a3b8; }
        body.dark-mode .btn-login-back { background: #0f172a; border-color: #334155; color: #cbd5e1; }
        body.dark-mode .btn-login-back:hover { background: #334155; color: #38bdf8; border-color: #38bdf8; }
        body.dark-mode .form-group label { color: #cbd5e1; }
        body.dark-mode .input-wrapper input { background: #0f172a; border-color: #334155; color: #f8fafc; }
        body.dark-mode .input-wrapper input:focus { border-color: #38bdf8; box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15); }
        body.dark-mode .input-wrapper i { color: #64748b; }
        body.dark-mode .theme-toggle-btn { background: #0f172a !important; border-color: #334155 !important; color: #f59e0b !important; }
    </style>
    <script src="../assets/js/theme.js"></script>
</head>
<body>
    <div class="login-container">
        
        <!-- Left Side: Login Form -->
        <div class="login-left">
            <div class="login-back-wrapper" style="margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn-login-back" onclick="if(window.history.length > 1 && document.referrer){ window.history.back(); } else { window.location.href='../index.html'; }" aria-label="Go back">
                    <i class="fa-solid fa-arrow-left"></i> <span>Back to Website</span>
                </button>
                <button type="button" class="theme-toggle-btn" aria-label="Toggle dark/light theme" title="Toggle theme" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1); cursor: pointer; display: flex; align-items: center; justify-content: center; color: inherit;">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please sign in to access the RLabz admin panel.</p>
            </div>
            
            <div class="error-msg" id="loginErrorMsg">
                <span>
                    <i class="fa-solid fa-circle-exclamation" style="margin-right: 6px;"></i>
                    <?php 
                        if(isset($_GET['error'])) {
                            if($_GET['error'] == 'invalid') echo "Invalid username or password. Please try again.";
                            else if($_GET['error'] == 'empty') echo "Please fill in both username and password.";
                            else if($_GET['error'] == 'locked') echo "Too many failed attempts. Please wait 30 seconds before trying again.";
                        }
                    ?>
                </span>
                <button type="button" onclick="document.getElementById('loginErrorMsg').style.display='none'" style="background:none; border:none; color:inherit; cursor:pointer; font-size:1.1rem; padding:0; line-height:1;"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form action="api/login_action.php" method="POST" id="adminLoginForm" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username" minlength="3">
                    </div>
                    <div class="field-feedback" id="usernameFeedback">Please enter at least 3 characters.</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password" minlength="6">
                        <button type="button" class="toggle-password-btn" id="toggleLoginPassword" aria-label="Show password" title="Show password">
                            <i class="fa-solid fa-eye" id="toggleLoginPasswordIcon"></i>
                        </button>
                    </div>
                    <div class="field-feedback" id="passwordFeedback">Password must be at least 6 characters.</div>

                    <!-- Live Password Strength Meter -->
                    <div class="password-strength-wrap" id="loginStrengthWrap">
                        <div class="strength-bars">
                            <div class="strength-bar" id="loginStr1"></div>
                            <div class="strength-bar" id="loginStr2"></div>
                            <div class="strength-bar" id="loginStr3"></div>
                            <div class="strength-bar" id="loginStr4"></div>
                        </div>
                        <div class="strength-meta">
                            <span class="strength-status" id="loginStrLabel">Strength: Evaluating...</span>
                            <span class="strength-hint">Min 6 chars</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit" id="loginSubmitBtn">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> <span>Sign In</span>
                </button>
            </form>
        </div>

        <!-- Right Side: RLabz Logo -->
        <div class="login-right">
            <div class="brand-display">
                <img src="../assets/images/rlabz-logo-white.png" alt="RLabz Logo" style="width: 225px; max-width: 225px; height: auto; object-fit: contain; filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.12));">
                <div class="brand-text">RLabZ ADMIN</div>
            </div>
        </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('adminLoginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('toggleLoginPassword');
        const toggleIcon = document.getElementById('toggleLoginPasswordIcon');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const usernameFb = document.getElementById('usernameFeedback');
        const passwordFb = document.getElementById('passwordFeedback');
        const strengthWrap = document.getElementById('loginStrengthWrap');
        const strengthLabel = document.getElementById('loginStrLabel');
        const strBars = [
            document.getElementById('loginStr1'),
            document.getElementById('loginStr2'),
            document.getElementById('loginStr3'),
            document.getElementById('loginStr4')
        ];

        // 1. Password Visibility Toggle
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const isPass = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPass ? 'text' : 'password');
                toggleIcon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                toggleBtn.setAttribute('title', isPass ? 'Hide password' : 'Show password');
                toggleBtn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
            });
        }

        // 2. Real-time Password Strength Evaluation
        function evaluateStrength(val) {
            if (!val) return 0;
            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
            if (/\d/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;
            return Math.min(4, Math.max(1, score));
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                const val = passwordInput.value;
                if (!val) {
                    strengthWrap.style.display = 'none';
                    passwordFb.style.display = 'none';
                    return;
                }
                strengthWrap.style.display = 'block';
                passwordFb.style.display = 'none';

                const score = evaluateStrength(val);
                const colors = ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
                const labels = ['Weak', 'Fair', 'Good', 'Strong'];

                strBars.forEach((bar, idx) => {
                    if (idx < score) {
                        bar.style.background = colors[score - 1];
                    } else {
                        bar.style.background = document.body.classList.contains('dark-mode') ? '#334155' : '#e2e8f0';
                    }
                });

                strengthLabel.textContent = `Strength: ${labels[score - 1]}`;
                strengthLabel.style.color = colors[score - 1];
            });
        }

        if (usernameInput) {
            usernameInput.addEventListener('input', () => {
                usernameFb.style.display = 'none';
            });
        }

        // 3. Form Submission Client Validation
        if (form) {
            form.addEventListener('submit', (e) => {
                let isValid = true;
                const uVal = usernameInput.value.trim();
                const pVal = passwordInput.value;

                if (!uVal || uVal.length < 3) {
                    isValid = false;
                    usernameFb.style.display = 'block';
                    usernameInput.style.borderColor = '#ef4444';
                    usernameInput.focus();
                } else {
                    usernameFb.style.display = 'none';
                    usernameInput.style.borderColor = '';
                }

                if (!pVal || pVal.length < 6) {
                    isValid = false;
                    passwordFb.style.display = 'block';
                    passwordInput.style.borderColor = '#ef4444';
                    if (isValid) passwordInput.focus();
                } else {
                    passwordFb.style.display = 'none';
                    passwordInput.style.borderColor = '';
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // Loading State
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Signing In...</span>';
            });
        }
    });
    </script>
</body>
</html>
