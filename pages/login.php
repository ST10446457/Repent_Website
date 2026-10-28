<?php
require_once '../includes/auth.php';
require_once '../includes/firebase.php';
if (isLoggedIn()) { header('Location: ../index.php'); exit(); }
$error = '';
$redirect = $_GET['redirect'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email and password.';
    } else {
        try {
            $auth = firebaseAuth('login', $email, $password);
            firebaseSession($auth);
            $doc = firebaseRequest(
                'https://firestore.googleapis.com/v1/projects/repent-48fda/databases/(default)/documents/users/' . rawurlencode($auth['localId']),
                'GET', null, firebaseToken()
            );
            $_SESSION['user_name'] = $doc['fields']['fullname']['stringValue'] ?? $email;
            $dest = $redirect === 'cart' ? 'cart.php' : '../index.php';
            header('Location: ' . $dest);
            exit();
        } catch (Throwable $e) {
            $error = firebaseFriendlyError($e);
            session_unset();
        }
    }
}
require_once '../includes/header.php';
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

        <!-- Firebase Authentication login -->
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
