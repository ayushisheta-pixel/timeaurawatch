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
$successMsg = '';
$usernameVal = '';
$nameVal = '';
$emailVal = '';
$mobileVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = trim($_POST['username'] ?? '');
    $nameVal     = trim($_POST['name'] ?? '');
    $emailVal    = trim($_POST['email'] ?? '');
    $mobileVal   = trim($_POST['mobile'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    // Validation
    if (empty($usernameVal) || empty($nameVal) || empty($emailVal) || empty($password)) {
        $errorMsg = "Please fill in all required fields.";
    } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $errorMsg = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$usernameVal, $emailVal]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['username'] === $usernameVal) {
                $errorMsg = "Username is already taken. Please choose another.";
            } else {
                $errorMsg = "An account with this email address already exists.";
            }
        } else {
            // Hash password and insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (username, name, email, mobile, password) VALUES (?, ?, ?, ?, ?)");
            $success = $insertStmt->execute([$usernameVal, $nameVal, $emailVal, $mobileVal, $hashedPassword]);

            if ($success) {
                $newUserId = $pdo->lastInsertId();

                // Auto login new user
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id']        = $newUserId;
                $_SESSION['user_username']  = $usernameVal;
                $_SESSION['user_name']      = $nameVal;
                $_SESSION['user_email']     = $emailVal;

                setFlash('success', 'Account created successfully! Welcome to TIMEAURA.');
                header("Location: index.php");
                exit();
            } else {
                $errorMsg = "Failed to create account. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header" style="padding: 40px 0; background: linear-gradient(180deg, #111827 0%, #0b0f19 100%); border-bottom: 1px solid var(--border-color);">
    <div class="header-container" style="display: block; text-align: center;">
        <h1 style="font-family: var(--font-heading); font-size: 2.2rem; color: var(--text-main); margin-bottom: 8px;">
            CREATE AN <span style="color: var(--primary-gold);">ACCOUNT</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Join TIMEAURA to enjoy premium watch perks, fast checkout, and order tracking</p>
    </div>
</div>

<section class="auth-section" style="padding: 60px 20px; background-color: var(--bg-dark);">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-card-header">
            <div class="auth-logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Register Account</h2>
            <p>Enter your information below to sign up</p>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo sanitize($errorMsg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="userRegisterForm" class="auth-form">
            <div class="form-group">
                <label for="regUsername" class="form-label"><i class="fas fa-at"></i> Username *</label>
                <div class="input-with-icon">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="regUsername" name="username" class="form-control" placeholder="e.g. alex_watch" required value="<?php echo sanitize($usernameVal); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="regName" class="form-label"><i class="fas fa-id-card"></i> Full Name *</label>
                <div class="input-with-icon">
                    <i class="fas fa-user-tag input-icon"></i>
                    <input type="text" id="regName" name="name" class="form-control" placeholder="e.g. Alex Morgan" required value="<?php echo sanitize($nameVal); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="regEmail" class="form-label"><i class="fas fa-envelope"></i> Email Address *</label>
                <div class="input-with-icon">
                    <i class="fas fa-paper-plane input-icon"></i>
                    <input type="email" id="regEmail" name="email" class="form-control" placeholder="alex@example.com" required value="<?php echo sanitize($emailVal); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="regMobile" class="form-label"><i class="fas fa-phone"></i> Mobile Number</label>
                <div class="input-with-icon">
                    <i class="fas fa-mobile-alt input-icon"></i>
                    <input type="text" id="regMobile" name="mobile" class="form-control" placeholder="+91 98765 43210" value="<?php echo sanitize($mobileVal); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="regPassword" class="form-label"><i class="fas fa-lock"></i> Password *</label>
                <div class="input-with-icon">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" id="regPassword" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn btn-gold auth-submit-btn">
                <i class="fas fa-check-circle"></i> Create Account
            </button>
        </form>

        <div class="auth-card-footer">
            <p>Already have a TIMEAURA account?</p>
            <a href="login.php" class="auth-link"><i class="fas fa-sign-in-alt"></i> Back to Login</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
