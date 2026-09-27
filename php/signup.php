<?php
require_once __DIR__ . '/../includes/auth.php';
// handle signup POST
$signup_success = false;
$signup_error = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    }
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }
    if ($password !== $confirm) {
        $signup_error = 'Passwords do not match.';
        $errors['confirm_password'] = 'Passwords do not match.';
    } else if (empty($errors)) {
        $res = register_user($username, $email, $password);
        if ($res['success']) {
            // After signup, auto-login user
            login_user($username, $password);
            header('Location: dashboard.php');
            exit();
        } else {
            $signup_error = $res['message'] ?? 'Signup failed.';
            $errors['signup'] = $signup_error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — CodeOven Cloud IDE</title>
    <link rel="stylesheet" href="../css/signup.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="signup-card">
            <a href="../index.html" class="brand">
                <div class="brand-icon-wrapper">
                    <i class="fas fa-fire-flame-curved"></i>
                </div>
                <h1>Code<span class="accent">Oven</span></h1>
            </a>
            
            <div class="welcome">
                <h2>Create Your Account</h2>
                <p>Start coding, compiling, and collaborating instantly</p>
            </div>
            
            <?php if (!empty($signup_error)): ?>
                <div class="error-message">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($signup_error); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="signup.php" class="signup-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="codeninja" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               class="<?php echo isset($errors['username']) ? 'error' : ''; ?>" required autocomplete="username">
                    </div>
                    <?php if (isset($errors['username'])): ?>
                        <div class="field-error"><i class="fas fa-circle-xmark"></i> <?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="dev@domain.com" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               class="<?php echo isset($errors['email']) ? 'error' : ''; ?>" required autocomplete="email">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error"><i class="fas fa-circle-xmark"></i> <?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" 
                               class="<?php echo isset($errors['password']) ? 'error' : ''; ?>" required autocomplete="new-password">
                        <i class="fas fa-eye toggle-password" id="togglePassword" title="Show/Hide Password"></i>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error"><i class="fas fa-circle-xmark"></i> <?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-shield-halved"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" 
                               class="<?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" required autocomplete="new-password">
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword" title="Show/Hide Password"></i>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="field-error"><i class="fas fa-circle-xmark"></i> <?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="agree_terms" id="agree_terms" 
                               <?php echo (isset($_POST['agree_terms']) && $_POST['agree_terms']) ? 'checked' : ''; ?>>
                        <span class="checkmark"></span>
                        I agree to the <a href="#">Terms &amp; Privacy Policy</a>
                    </label>
                    <?php if (isset($errors['agree_terms'])): ?>
                        <div class="field-error"><i class="fas fa-circle-xmark"></i> <?php echo $errors['agree_terms']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="signup-btn">
                    <span>Create Free Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="login-link">
                <div>Already have an account? <a href="login.php">Sign in here</a></div>
                <a href="../index.html" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </div>
    
    <script src="../js/signup.js"></script>
</body>
</html>