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
    <title>Admin Login - RLabz</title>
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
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .login-header {
            margin-bottom: 2.5rem;
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
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>;
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
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
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

        .form-group input:focus + i {
            color: var(--primary);
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
            max-width: 280px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 15px 25px rgba(0,0,0,0.3));
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
    </style>
</head>
<body>
    <div class="login-container">
        
        <!-- Left Side: Login Form -->
        <div class="login-left">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please sign in to access the RLabz admin panel.</p>
            </div>
            
            <div class="error-msg">
                <?php 
                    if(isset($_GET['error'])) {
                        if($_GET['error'] == 'invalid') echo "Invalid username or password. Please try again.";
                        else if($_GET['error'] == 'empty') echo "Please fill in both username and password.";
                    }
                ?>
            </div>

            <form action="api/login_action.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                        <i class="fa-solid fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Sign In</button>
            </form>
        </div>

        <!-- Right Side: RLabz Logo -->
        <div class="login-right">
            <div class="brand-display">
                <img src="../assets/images/rz-logo.webp" alt="RLabz Logo">
                <div class="brand-text">RLABZ ADMIN</div>
            </div>
        </div>

    </div>
</body>
</html>
