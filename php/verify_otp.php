<?php
date_default_timezone_set('UTC');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = trim($_SESSION['reset_email']);
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^\d{6}$/', $input_otp)) {
        $message = 'Please enter the 6-digit OTP.';
    } elseif ($new_password === '') {
        $message = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT user_id, otp_code, otp_expiry
             FROM tbl_users
             WHERE email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = 'No account found for this email.';
        } else {
            $db_otp = trim((string)($user['otp_code'] ?? ''));
            $db_expiry = $user['otp_expiry'] ?? null;

            $is_not_expired = false;
            if ($db_expiry) {
                // Try parsing DB expiry directly or as UTC
                $expiry_ts = strtotime($db_expiry);
                if ($expiry_ts === false) {
                    $expiry_ts = strtotime($db_expiry . ' UTC');
                }
                $is_not_expired = ($expiry_ts !== false && time() <= $expiry_ts);
            }

            $otp_matches = ($db_otp !== '' && hash_equals($db_otp, $input_otp));

            if (!$otp_matches) {
                $message = 'Invalid verification code. Please check and try again.';
            } elseif (!$is_not_expired) {
                $message = 'Verification code has expired. Please request a new code.';
            } else {
                try {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);

                    $update = $pdo->prepare(
                        'UPDATE tbl_users
                         SET password_hash = ?, otp_code = NULL, otp_expiry = NULL
                         WHERE user_id = ?'
                    );
                    $update->execute([$hash, $user['user_id']]);

                    unset($_SESSION['reset_email'], $_SESSION['debug_otp']);
                    $success = true;
                    $message = 'Password updated successfully! You can now sign in with your new password.';
                } catch (Exception $e) {
                    $message = 'Failed to update password in database: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code &amp; Reset Password — CodeOven Cloud IDE</title>
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
                <h2>Security Verification</h2>
                <p>Enter the 6-digit code sent for <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            
            <?php if ($message): ?>
                <div class="<?= $success ? 'success-message' : 'error-message' ?>">
                    <i class="fas <?= $success ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="verify_otp.php" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="otp">6-Digit Recovery PIN</label>
                    <div class="input-with-icon">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" id="otp" name="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="123456" required style="font-family: var(--font-code); letter-spacing: 4px; font-size: 16px; font-weight: 600;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" minlength="6" required autocomplete="new-password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-shield-halved"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-type new password" minlength="6" required autocomplete="new-password">
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <span>Set New Password</span>
                    <i class="fas fa-check"></i>
                </button>
            </form>
            <?php else: ?>
                <div style="margin-top: 20px;">
                    <a href="login.php" class="login-btn" style="text-decoration: none;">
                        <span>Proceed to Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="signup-link">
                <div>Need a new code? <a href="forgot_password.php">Request again</a></div>
                <a href="../index.html" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
