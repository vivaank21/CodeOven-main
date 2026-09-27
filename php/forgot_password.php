<?php
date_default_timezone_set('UTC');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$generated_otp = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM tbl_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = "No account found with that email address.";
        } else {
            // Use a cryptographically secure OTP.
            $otp = (string) random_int(100000, 999999);

            // Store the expiry in UTC timestamp (5 minutes).
            $expiry = gmdate('Y-m-d H:i:s', time() + 300);

            $update = $pdo->prepare(
                "UPDATE tbl_users
                 SET otp_code = ?, otp_expiry = ?
                 WHERE user_id = ?"
            );
            $update->execute([$otp, $expiry, $user['user_id']]);

            $_SESSION['reset_email'] = $email;
            $generated_otp = $otp;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — CodeOven Cloud IDE</title>
    <link rel="stylesheet" href="../css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .otp-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            justify-content: center;
            align-items: center;
            visibility: hidden;
            opacity: 0;
            transform: scale(0.95);
            transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 9999;
            padding: 20px;
        }
        .otp-modal.show {
            visibility: visible;
            opacity: 1;
            transform: scale(1);
        }
        .otp-box {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: var(--radius-lg);
            padding: 36px 30px;
            width: min(420px, calc(100% - 30px));
            text-align: center;
            box-shadow: var(--shadow-card);
            position: relative;
        }
        .otp-box h3 {
            font-family: var(--font-heading);
            font-size: 22px;
            color: var(--text-main);
            margin-bottom: 8px;
        }
        .otp-box p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 20px;
        }
        .otp-code-highlight {
            font-family: var(--font-code);
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 6px;
            color: var(--cyan-400);
            background: rgba(6, 182, 212, 0.12);
            border: 1px dashed rgba(6, 182, 212, 0.4);
            border-radius: var(--radius-md);
            padding: 14px 20px;
            margin: 0 auto 24px auto;
            text-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
            display: inline-block;
        }
    </style>
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
                <h2>Forgot Password?</h2>
                <p>Enter your registered email to receive a recovery code</p>
            </div>
            
            <?php if ($message): ?>
                <div class="error-message">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="forgot_password.php" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="email">Account Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="developer@example.com" required autocomplete="email">
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <span>Send Verification Code</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            
            <div class="signup-link">
                <div>Remembered your password? <a href="login.php">Sign in</a></div>
                <a href="../index.html" class="btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </div>

    <div id="otpModal" class="otp-modal">
        <div class="otp-box">
            <div class="brand-icon-wrapper" style="margin: 0 auto 16px auto;">
                <i class="fas fa-key"></i>
            </div>
            <h3>Verification Code</h3>
            <p>Your one-time recovery PIN (valid for 5 minutes):</p>
            <div class="otp-code-highlight" id="otpDisplay">------</div>
            <button type="button" class="login-btn" id="continueBtn">
                <span>Continue to Password Reset</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <?php if ($generated_otp !== null): ?>
    <script>
        const modal = document.getElementById('otpModal');
        const otpDisplay = document.getElementById('otpDisplay');
        const continueBtn = document.getElementById('continueBtn');

        otpDisplay.textContent = <?= json_encode($generated_otp) ?>;
        modal.classList.add('show');

        continueBtn.addEventListener('click', () => {
            window.location.href = 'verify_otp.php';
        });
    </script>
    <?php endif; ?>
</body>
</html>
