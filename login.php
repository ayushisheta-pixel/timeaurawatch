<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// If user is already logged in, redirect to index
if (isUserLoggedIn()) {
    header("Location: index.php");
    exit();
}

$errorMsg = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($usernameVal) || empty($password)) {
        $errorMsg = "Please enter both your Username/Email and Password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$usernameVal, $usernameVal]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_username']  = $user['username'];
            $_SESSION['user_name']      = $user['name'];
            $_SESSION['user_email']     = $user['email'];

            // Associate guest cart and wishlist with logged-in user
            $sessionId = session_id();
            try {
                $pdo->prepare("UPDATE cart SET user_id = ? WHERE session_id = ? AND (user_id IS NULL OR user_id = ?)")->execute([$user['id'], $sessionId, $user['id']]);
                ensureWishlistTable($pdo);
                $pdo->prepare("UPDATE wishlist SET user_id = ? WHERE session_id = ? AND (user_id IS NULL OR user_id = ?)")->execute([$user['id'], $sessionId, $user['id']]);
            } catch (Exception $e) {
                error_log("Login Cart/Wishlist Sync Error: " . $e->getMessage());
            }

            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '! You have successfully logged in.');

            // Redirect back or to index page
            $redirectUrl = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : 'index.php';
            header("Location: " . $redirectUrl);
            exit();
        } else {
            $errorMsg = "Invalid username/email or password. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header / Breadcrumb Section -->
<div class="page-header" style="padding: 40px 0; background: linear-gradient(180deg, #111827 0%, #0b0f19 100%); border-bottom: 1px solid var(--border-color);">
    <div class="header-container" style="display: block; text-align: center;">
        <h1 style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--text-main); margin-bottom: 8px;">
            ACCOUNT <span style="color: var(--primary-gold);">LOGIN</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Access your TIMEAURA exclusive watch collection, orders & wishlist</p>
    </div>
</div>

<!-- Login Container -->
<section class="auth-section" style="padding: 60px 20px; background-color: var(--bg-dark);">
    <div class="auth-card">
        <div class="auth-card-header">
            <div class="auth-logo">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2>Member Login</h2>
            <p>Welcome back! Please enter your details below</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($errorMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess = getFlash('success')): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo sanitize($flashSuccess); ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError = getFlash('error')): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> <?php echo sanitize($flashError); ?>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" id="userLoginForm" class="auth-form">
            
            <!-- Field 1: Username or Email -->
            <div class="form-group">
                <label for="usernameInput" class="form-label">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="text" 
                           id="usernameInput" 
                           name="username" 
                           class="form-control" 
                           placeholder="e.g. john_doe or john@example.com" 
                           required 
                           value="<?php echo sanitize($usernameVal); ?>"
                           autocomplete="username">
                </div>
            </div>

            <!-- Field 2: Password -->
            <div class="form-group">
                <div class="label-row" style="display: flex; justify-content: space-between; align-items: center;">
                    <label for="passwordInput" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                </div>
                <div class="input-with-icon">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" 
                           id="passwordInput" 
                           name="password" 
                           class="form-control" 
                           placeholder="••••••••" 
                           required 
                           autocomplete="current-password">
                    <button type="button" class="toggle-password" id="togglePasswordBtn" title="Show/Hide Password">
                        <i class="fas fa-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold auth-submit-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In to Account
            </button>
        </form>

        <!-- Demo Account Shortcut Box -->
        <div class="demo-credentials-box">
            <div class="demo-title"><i class="fas fa-key"></i> Quick Demo Account</div>
            <div class="demo-info">
                <span>Username: <strong>john_doe</strong></span>
                <span>Password: <strong>password123</strong></span>
            </div>
            <button type="button" class="btn-fill-demo" id="fillDemoBtn">
                <i class="fas fa-magic"></i> Auto-fill Demo Credentials
            </button>
        </div>

        <div class="auth-card-footer">
            <p>Don't have a TIMEAURA account yet?</p>
            <a href="register.php" class="auth-link"><i class="fas fa-user-plus"></i> Create New Account</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleIcon.classList.toggle('fa-eye');
            toggleIcon.classList.toggle('fa-eye-slash');
        });
    }

    // Auto-fill demo credentials
    const fillDemoBtn = document.getElementById('fillDemoBtn');
    if (fillDemoBtn) {
        fillDemoBtn.addEventListener('click', function() {
            document.getElementById('usernameInput').value = 'john_doe';
            document.getElementById('passwordInput').value = 'password123';
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
