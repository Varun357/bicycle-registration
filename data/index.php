<?php
session_start();

// If user is already logged in, redirect to the view page
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: view.php');
    exit;
}

// --- DEFINE YOUR USERNAME AND PASSWORD HERE ---
$correct_username = 'admin_rocks';
$correct_password = 'wof#123$$$'; // Change this to a strong password!

$login_error = '';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Verify credentials
    if ($username === $correct_username && $password === $correct_password) {
        // Success: Set session variables and redirect
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        header('Location: view.php');
        exit;
    } else {
        // Failure: Set an error message
        $login_error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Wheels of Freedom</title>
    <link rel="icon" type="image/png" href="/imgs/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Teachers&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Teachers', sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #1a237e; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { display: block; width: 100%; background: #ff6f00; color: #fff; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 18px; }
        .btn:hover { background: #e65100; }
        .error-message { color: #D8000C; background-color: #FFD2D2; border: 1px solid; padding: 10px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Admin Login</h1>
        <form action="index.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <?php if (!empty($login_error)): ?>
            <p class="error-message"><?php echo $login_error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>