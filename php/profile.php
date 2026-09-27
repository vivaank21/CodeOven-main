<?php
// php/profile.php - CodeOven User Profile & Account Settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Auto-create tbl_user_profiles table if it does not exist yet (self-healing DB)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tbl_user_profiles` (
      `profile_id` int NOT NULL AUTO_INCREMENT,
      `user_id` int NOT NULL,
      `full_name` varchar(100) DEFAULT NULL,
      `bio` text DEFAULT NULL,
      `avatar_url` varchar(255) DEFAULT NULL,
      `github_url` varchar(255) DEFAULT NULL,
      `website_url` varchar(255) DEFAULT NULL,
      `location` varchar(100) DEFAULT NULL,
      `preferred_language` varchar(50) NOT NULL DEFAULT 'JavaScript',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`profile_id`),
      UNIQUE KEY `uniq_user_profile` (`user_id`),
      CONSTRAINT `tbl_user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {
    // Ignore if table exists
}

// Ensure uploads/avatars directory exists
$upload_dir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Profile Information & Avatar Upload
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        $github_url = trim($_POST['github_url'] ?? '');
        $website_url = trim($_POST['website_url'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $preferred_language = trim($_POST['preferred_language'] ?? 'JavaScript');
        $email = trim($_POST['email'] ?? '');

        // Check if user uploaded a file from device
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['avatar_file']['tmp_name'];
            $file_name = $_FILES['avatar_file']['name'];
            $file_size = $_FILES['avatar_file']['size'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB limit

            if (!in_array($ext, $allowed_exts)) {
                $error_msg = "Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF.";
            } elseif ($file_size > $max_size) {
                $error_msg = "Image size exceeds 5MB limit.";
            } else {
                // Generate safe unique filename
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_path)) {
                    $avatar_url = '../uploads/avatars/' . $new_filename;
                } else {
                    $error_msg = "Failed to save uploaded image. Please check directory permissions.";
                }
            }
        }

        // Email validation
        if (empty($error_msg)) {
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "Please provide a valid email address.";
            } else {
                // Check if email taken by another user
                $email_check = $pdo->prepare("SELECT user_id FROM tbl_users WHERE email = ? AND user_id != ? LIMIT 1");
                $email_check->execute([$email, $user_id]);
                if ($email_check->fetch()) {
                    $error_msg = "Email address is already in use by another account.";
                } else {
                    try {
                        // Update tbl_users email
                        $stmt_u = $pdo->prepare("UPDATE tbl_users SET email = ? WHERE user_id = ?");
                        $stmt_u->execute([$email, $user_id]);

                        // Upsert into tbl_user_profiles
                        $stmt_p = $pdo->prepare("INSERT INTO tbl_user_profiles 
                            (user_id, full_name, bio, avatar_url, github_url, website_url, location, preferred_language) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            full_name = VALUES(full_name),
                            bio = VALUES(bio),
                            avatar_url = IF(VALUES(avatar_url) != '', VALUES(avatar_url), avatar_url),
                            github_url = VALUES(github_url),
                            website_url = VALUES(website_url),
                            location = VALUES(location),
                            preferred_language = VALUES(preferred_language)");
                        $stmt_p->execute([$user_id, $full_name, $bio, $avatar_url, $github_url, $website_url, $location, $preferred_language]);

                        $success_msg = "Profile and avatar updated successfully!";
                    } catch (Exception $e) {
                        $error_msg = "Failed to update profile: " . $e->getMessage();
                    }
                }
            }
        }
    }

    // 2. Change Password
    elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($current_pass === '' || $new_pass === '' || $confirm_pass === '') {
            $error_msg = "All password fields are required.";
        } elseif (strlen($new_pass) < 6) {
            $error_msg = "New password must be at least 6 characters long.";
        } elseif ($new_pass !== $confirm_pass) {
            $error_msg = "New password and confirmation do not match.";
        } else {
            // Verify current password
            $stmt_auth = $pdo->prepare("SELECT password_hash FROM tbl_users WHERE user_id = ? LIMIT 1");
            $stmt_auth->execute([$user_id]);
            $user_rec = $stmt_auth->fetch();

            if (!$user_rec || !password_verify($current_pass, $user_rec['password_hash'])) {
                $error_msg = "Incorrect current password.";
            } else {
                try {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt_up = $pdo->prepare("UPDATE tbl_users SET password_hash = ? WHERE user_id = ?");
                    $stmt_up->execute([$new_hash, $user_id]);
                    $success_msg = "Password changed successfully!";
                } catch (Exception $e) {
                    $error_msg = "Failed to change password: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Current User Details
$stmt_user = $pdo->prepare("SELECT user_id, username, email, created_at FROM tbl_users WHERE user_id = ? LIMIT 1");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Fetch Profile
$stmt_prof = $pdo->prepare("SELECT * FROM tbl_user_profiles WHERE user_id = ? LIMIT 1");
$stmt_prof->execute([$user_id]);
$profile = $stmt_prof->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch Workspace Statistics
$stmt_pcount = $pdo->prepare("SELECT COUNT(*) FROM tbl_projects WHERE user_id = ?");
$stmt_pcount->execute([$user_id]);
$total_projects = (int)$stmt_pcount->fetchColumn();

$stmt_fcount = $pdo->prepare("SELECT COUNT(*) FROM tbl_files WHERE user_id = ?");
$stmt_fcount->execute([$user_id]);
$total_files = (int)$stmt_fcount->fetchColumn();

$stmt_recents = $pdo->prepare("SELECT project_name, updated_at FROM tbl_projects WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5");
$stmt_recents->execute([$user_id]);
$recent_projects = $stmt_recents->fetchAll(PDO::FETCH_ASSOC);

// Avatar helper
$default_avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150";
$avatar = !empty($profile['avatar_url']) ? htmlspecialchars($profile['avatar_url']) : $default_avatar;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> — Developer Profile | CodeOven</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Profile Stylesheet -->
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Top Bar Navigation -->
    <nav class="profile-nav">
        <a href="dashboard.php" class="brand-logo" aria-label="CodeOven Cloud IDE">
            <div class="logo-icon-box">
                <i class="fas fa-fire-burner"></i>
            </div>
            <div class="logo-text">Code<span>Oven</span></div>
        </a>

        <div class="profile-nav-actions">
            <a href="dashboard.php" class="btn-nav">
                <i class="fas fa-code"></i>
                <span>Open Editor</span>
            </a>
            <a href="logout.php" class="btn-nav btn-logout">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <main class="profile-main-container">
        <!-- User Banner Header -->
        <header class="profile-header-card">
            <!-- Interactive Avatar with Edit Trigger -->
            <div class="profile-avatar-container" id="avatarPreviewContainer" title="Click to upload profile photo">
                <img id="mainAvatarImage" src="<?php echo $avatar; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>" class="profile-avatar-img" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=06b6d4&color=fff&size=150'">
                <div class="avatar-upload-overlay">
                    <i class="fas fa-camera"></i>
                    <span>Edit</span>
                </div>
                <div class="avatar-badge" title="Active Developer">
                    <i class="fas fa-check"></i>
                </div>
            </div>

            <div class="profile-info-content">
                <div class="profile-title-row">
                    <h1><?php echo htmlspecialchars(!empty($profile['full_name']) ? $profile['full_name'] : $user['username']); ?></h1>
                    <span class="profile-handle">@<?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="status-chip"><i class="fas fa-bolt"></i> PRO DEVELOPER</span>
                </div>
                
                <p class="profile-bio-text">
                    <?php echo !empty($profile['bio']) ? nl2br(htmlspecialchars($profile['bio'])) : 'No developer bio written yet. Click Edit Profile below to introduce yourself.'; ?>
                </p>

                <div class="profile-meta-chips">
                    <?php if (!empty($profile['location'])): ?>
                        <span class="meta-chip"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($profile['location']); ?></span>
                    <?php endif; ?>
                    <span class="meta-chip"><i class="fas fa-calendar-days"></i> Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    <span class="meta-chip"><i class="fas fa-terminal"></i> <?php echo htmlspecialchars($profile['preferred_language'] ?? 'JavaScript'); ?></span>
                    <?php if (!empty($profile['github_url'])): ?>
                        <a href="<?php echo htmlspecialchars($profile['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="meta-chip meta-link"><i class="fab fa-github"></i> GitHub</a>
                    <?php endif; ?>
                    <?php if (!empty($profile['website_url'])): ?>
                        <a href="<?php echo htmlspecialchars($profile['website_url']); ?>" target="_blank" rel="noopener noreferrer" class="meta-chip meta-link"><i class="fas fa-globe"></i> Website</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stat Badges -->
            <div class="profile-stats-summary">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_projects; ?></div>
                    <div class="stat-label">Projects</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_files; ?></div>
                    <div class="stat-label">Total Files</div>
                </div>
            </div>
        </header>

        <!-- Notification Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success">
                <i class="fas fa-circle-check"></i>
                <span><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <!-- Tabbed Settings & Management -->
        <div class="profile-content-grid">
            <!-- Left Column: Navigation Tabs -->
            <aside class="profile-tabs-sidebar">
                <button class="tab-button active" onclick="showTab('tab-profile')">
                    <i class="fas fa-user-pen"></i>
                    <span>Profile Details</span>
                </button>
                <button class="tab-button" onclick="showTab('tab-security')">
                    <i class="fas fa-shield-halved"></i>
                    <span>Security &amp; Password</span>
                </button>
                <button class="tab-button" onclick="showTab('tab-workspace')">
                    <i class="fas fa-folder-tree"></i>
                    <span>Workspace &amp; Activity</span>
                </button>
            </aside>

            <!-- Right Column: Tab Panels -->
            <section class="profile-panel-wrapper">
                <!-- TAB 1: Profile Details & Picture Upload -->
                <div id="tab-profile" class="tab-panel active">
                    <div class="panel-header">
                        <h2>Personal &amp; Developer Information</h2>
                        <p>Upload a custom photo or customize your public identity and profile links.</p>
                    </div>

                    <form method="POST" action="profile.php" class="panel-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Photo Upload Card -->
                        <div class="photo-upload-section">
                            <div class="photo-upload-preview">
                                <img id="formAvatarPreview" src="<?php echo $avatar; ?>" alt="Preview" class="photo-preview-thumb" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=06b6d4&color=fff&size=150'">
                            </div>
                            <div class="photo-upload-controls">
                                <h3>Profile Picture</h3>
                                <p>Upload an image from your computer (JPG, PNG, WEBP, GIF up to 5MB) or enter an external image URL below.</p>
                                
                                <div class="upload-buttons-row">
                                    <label for="avatar_file_input" class="btn-file-select">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                        <span>Choose Local Image</span>
                                    </label>
                                    <input type="file" id="avatar_file_input" name="avatar_file" accept="image/png, image/jpeg, image/webp, image/gif" style="display: none;">
                                    <span id="selectedFileName" class="selected-file-label">No file selected</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="username_disp">Username</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-at"></i>
                                    <input type="text" id="username_disp" value="<?php echo htmlspecialchars($user['username']); ?>" disabled title="Username cannot be changed">
                                </div>
                                <span class="input-note">Unique workspace handle</span>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="full_name" name="full_name" placeholder="Alex Turner" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="location">Location / Timezone</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-location-dot"></i>
                                    <input type="text" id="location" name="location" placeholder="San Francisco, CA / UTC-7" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio &amp; Tech Stack</label>
                            <textarea id="bio" name="bio" rows="3" placeholder="Tell other developers about yourself, what you are building, and your favorite technologies..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="preferred_language">Primary Programming Language</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-code"></i>
                                    <select id="preferred_language" name="preferred_language">
                                        <?php 
                                        $langs = ['JavaScript', 'HTML/CSS', 'Python', 'C++', 'Java', 'PHP', 'SQL', 'TypeScript'];
                                        $current_lang = $profile['preferred_language'] ?? 'JavaScript';
                                        foreach ($langs as $l) {
                                            $sel = ($current_lang === $l) ? 'selected' : '';
                                            echo "<option value=\"$l\" $sel>$l</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="avatar_url">Or Web Image URL</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-link"></i>
                                    <input type="url" id="avatar_url" name="avatar_url" placeholder="https://example.com/photo.jpg" value="<?php echo htmlspecialchars($profile['avatar_url'] ?? ''); ?>">
                                </div>
                                <span class="input-note">Leave blank to use uploaded file or default avatar</span>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="github_url">GitHub Profile URL</label>
                                <div class="input-icon-wrap">
                                    <i class="fab fa-github"></i>
                                    <input type="url" id="github_url" name="github_url" placeholder="https://github.com/yourhandle" value="<?php echo htmlspecialchars($profile['github_url'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="website_url">Personal Website / Portfolio</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-globe"></i>
                                    <input type="url" id="website_url" name="website_url" placeholder="https://yourportfolio.com" value="<?php echo htmlspecialchars($profile['website_url'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save">
                                <i class="fas fa-floppy-disk"></i>
                                <span>Save Changes</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: Security & Password -->
                <div id="tab-security" class="tab-panel">
                    <div class="panel-header">
                        <h2>Account Security</h2>
                        <p>Change your master account password and manage authentication settings.</p>
                    </div>

                    <form method="POST" action="profile.php" class="panel-form">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="current_password" name="current_password" placeholder="••••••••" required autocomplete="current-password">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" minlength="6" required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="input-icon-wrap">
                                    <i class="fas fa-shield-halved"></i>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat new password" minlength="6" required autocomplete="new-password">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save btn-security">
                                <i class="fas fa-lock"></i>
                                <span>Update Password</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 3: Workspace & Activity -->
                <div id="tab-workspace" class="tab-panel">
                    <div class="panel-header">
                        <h2>Workspace Summary &amp; Projects</h2>
                        <p>Overview of cloud projects and code files linked to your account.</p>
                    </div>

                    <div class="workspace-summary-grid">
                        <div class="workspace-stat-card">
                            <div class="stat-icon-wrap"><i class="fas fa-box-archive"></i></div>
                            <div class="stat-info">
                                <span class="stat-val"><?php echo $total_projects; ?></span>
                                <span class="stat-tit">Active Projects</span>
                            </div>
                        </div>
                        <div class="workspace-stat-card">
                            <div class="stat-icon-wrap"><i class="fas fa-file-code"></i></div>
                            <div class="stat-info">
                                <span class="stat-val"><?php echo $total_files; ?></span>
                                <span class="stat-tit">Stored Code Files</span>
                            </div>
                        </div>
                        <div class="workspace-stat-card">
                            <div class="stat-icon-wrap"><i class="fas fa-cloud-arrow-up"></i></div>
                            <div class="stat-info">
                                <span class="stat-val">Cloud Active</span>
                                <span class="stat-tit">Sync Status</span>
                            </div>
                        </div>
                    </div>

                    <div class="recent-projects-card">
                        <h3><i class="fas fa-clock-rotate-left"></i> Recent Projects</h3>
                        <?php if (empty($recent_projects)): ?>
                            <p class="empty-projects-hint">No projects found. Launch the editor to create your first project!</p>
                        <?php else: ?>
                            <div class="projects-table-wrap">
                                <table class="projects-table">
                                    <thead>
                                        <tr>
                                            <th>Project Name</th>
                                            <th>Last Modified</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_projects as $rp): ?>
                                            <tr>
                                                <td class="project-name-cell"><i class="fas fa-folder text-cyan"></i> <?php echo htmlspecialchars($rp['project_name']); ?></td>
                                                <td><?php echo date('M d, Y · H:i', strtotime($rp['updated_at'])); ?></td>
                                                <td>
                                                    <a href="dashboard.php" class="btn-open-proj">
                                                        <i class="fas fa-arrow-up-right-from-square"></i> Open
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-panel').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
            
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            
            event.currentTarget.classList.add('active');
        }

        // Live Avatar File Preview & Interactive Upload Trigger
        const avatarFileInput = document.getElementById('avatar_file_input');
        const mainAvatarImage = document.getElementById('mainAvatarImage');
        const formAvatarPreview = document.getElementById('formAvatarPreview');
        const selectedFileName = document.getElementById('selectedFileName');
        const avatarPreviewContainer = document.getElementById('avatarPreviewContainer');

        // Clicking the banner avatar opens the file chooser
        if (avatarPreviewContainer && avatarFileInput) {
            avatarPreviewContainer.addEventListener('click', () => {
                showTab('tab-profile');
                avatarFileInput.click();
            });
        }

        // Preview local file immediately on select
        if (avatarFileInput) {
            avatarFileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    selectedFileName.textContent = file.name;

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (mainAvatarImage) mainAvatarImage.src = e.target.result;
                        if (formAvatarPreview) formAvatarPreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Also live preview if typing an external URL
        const avatarUrlInput = document.getElementById('avatar_url');
        if (avatarUrlInput) {
            avatarUrlInput.addEventListener('input', function() {
                if (this.value.trim().startsWith('http')) {
                    if (mainAvatarImage) mainAvatarImage.src = this.value.trim();
                    if (formAvatarPreview) formAvatarPreview.src = this.value.trim();
                }
            });
        }
    </script>
</body>
</html>
