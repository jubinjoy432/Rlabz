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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(ellipse at 50% 0%, #0b4a8a 0%, #022c5e 55%, #010f2e 100%);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: rgba(2, 12, 42, 0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        .login-box h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            margin-top: 0;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #bae6fd;
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: white;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0ea5e9;
            background: rgba(255,255,255,0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 0.8rem;
            border-radius: 50px;
            border: none;
            background: linear-gradient(135deg, #0b5394, #0ea5e9);
            color: white;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
        }
        .btn-submit:hover {
            box-shadow: 0 4px 15px rgba(14,165,233,0.4);
            transform: translateY(-2px);
        }
        .error {
            color: #ef4444;
            background: rgba(239,68,68,0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: <?php echo isset($_GET['error']) ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Portal</h2>
        <div class="error">
            <?php 
                if(isset($_GET['error'])) {
                    if($_GET['error'] == 'invalid') echo "Invalid username or password.";
                    else if($_GET['error'] == 'empty') echo "Please fill in all fields.";
                }
            ?>
        </div>
        <form action="api/login_action.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>
</body>
</html>
