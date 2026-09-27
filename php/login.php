<?php
require_once __DIR__ . '/../includes/auth.php';

// If form posted, attempt login using DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login_user($username, $password)) {
        // redirect to dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        $login_error = 'Invalid username or password.';
    }
}
// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — CodeOven Cloud IDE</title>
    <link rel="stylesheet" href="../css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-card">
            <a href="../index.html" class="brand">
                <div class="brand-icon-wrapper">
                    <i class="fas fa-fire-flame-curved"></i>
                </div>
                <h1>Code<span class="accent">Oven</span></h1>
            </a>
            
            <div class="welcome">
                <h2>Welcome Back</h2>
                <p>Sign in to access your projects and cloud execution</p>
            </div>
            
            <?php if (isset($login_error)): ?>
                <div class="error-message">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($login_error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" class="login-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="developer@example.com" required autocomplete="username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <i class="fas fa-eye toggle-password" id="togglePassword" title="Show/Hide Password"></i>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn">
                    <span>Sign In to Workspace</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="signup-link">
                <div>Don't have an account? <a href="signup.php">Create an account</a></div>
                <a href="../index.html" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </div>

    <script src="../js/login.js"></script>
</body>
</html>