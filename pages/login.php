<?php
/**
 * ============================================================
 * REPENT† — Login Page
 * File: pages/login.php
 *
 * PURPOSE: Allows registered users to sign in.
 * - If the database IS connected: verifies credentials from DB.
 * - If the database is NOT connected (demo/presentation mode):
 *   uses hardcoded demo accounts so the site still works.
 *
 * DEMO ACCOUNTS (no database needed):
 *   Admin : admin@repent.co.za  / admin123
 *   User  : user@repent.co.za   / user123
 * ============================================================
 */

// Load header (also starts session + loads auth helpers)
require_once '../includes/header.php';
// Load database connection
require_once '../includes/db.php';

// If already logged in, redirect to homepage
if (isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// ---------- Variables ----------
$error    = '';
$redirect = $_GET['redirect'] ?? ''; // Where to send user after login

// ---------- Handle form submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise inputs
    $email    = trim(strtolower($_POST['email']    ?? ''));
    $password = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both your email and password.";

    } else {

        // ---- Try real database login ----
        if ($conn) {
            $stmt = $conn->prepare("SELECT id, fullname, email, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                // Password matched — store user data in session
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];

                // Redirect
                $dest = ($redirect === 'cart') ? 'cart.php' : '../index.php';
                header("Location: $dest");
                exit();
            } else {
                $error = "Incorrect email or password. Please try again.";
            }

        } else {
            // ---- Demo mode: no database connected ----
            // These hardcoded accounts let the site work for presentations
            $demoAccounts = [
                [
                    'email'    => 'admin@repent.co.za',
                    'password' => 'admin123',
                    'role'     => 'admin',
                    'name'     => 'Admin User',
                    'id'       => 1,
                ],
                [
                    'email'    => 'user@repent.co.za',
                    'password' => 'user123',
                    'role'     => 'customer',
                    'name'     => 'Demo User',
                    'id'       => 2,
                ],
            ];

            $loggedIn = false;
            foreach ($demoAccounts as $demo) {
                if ($demo['email'] === $email && $demo['password'] === $password) {
                    // Match found — create session
                    $_SESSION['user_id']    = $demo['id'];
                    $_SESSION['user_name']  = $demo['name'];
                    $_SESSION['user_email'] = $demo['email'];
                    $_SESSION['role']       = $demo['role'];

                    $dest = ($redirect === 'cart') ? 'cart.php' : '../index.php';
                    header("Location: $dest");
                    exit();
                }
            }

            // No demo match found
            $error = "Incorrect email or password.";
        }
    }
}
?>

<!-- ===== LOGIN FORM ===== -->
<div class="form-page">
    <div class="form-card">

        <!-- Card Header -->
        <div class="form-card-header">
            <?php $logoColor = "black"; $logoHeight = 42; include "../includes/logo.php"; ?>
            <h1 class="form-card-title">Welcome Back</h1>
            <p class="form-card-sub">Sign in to your REPENT† account</p>
        </div>

        <!-- Error message (shown if login fails) -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Demo mode info box -->
        <?php if (!$conn): ?>
            <div class="alert alert-info">
                <strong>Demo Mode</strong> — Database not connected.<br>
                Try: <strong>admin@repent.co.za / admin123</strong><br>
                Or:  <strong>user@repent.co.za / user123</strong>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php<?= $redirect ? '?redirect=' . htmlspecialchars($redirect) : '' ?>">

            <!-- Email field -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>

            <!-- Password field -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password"
                       required autocomplete="current-password">
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn btn-gold btn-full">Sign In</button>
        </form>

        <!-- Link to registration page -->
        <div class="form-footer-link">
            New to REPENT†? <a href="register.php">Create an account</a>
        </div>

    </div><!-- end .form-card -->
</div>

<?php require_once '../includes/footer.php'; ?>
