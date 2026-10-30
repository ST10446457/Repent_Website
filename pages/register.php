<?php
require_once '../includes/auth.php';
require_once '../includes/firebase.php';
if (isLoggedIn()) { header('Location: ../index.php'); exit(); }
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if ($fullname === '') $errors[] = 'Full name is required.';
    if (!repentValidEmail($email)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters long.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        try {
            $auth = firebaseAuth('register', $email, $password);
            firebaseSession($auth, $fullname);
            firestoreWrite('users', [
                'uid' => $auth['localId'], 'fullname' => $fullname,
                'email' => $email, 'role' => 'customer',
                'createdAt' => gmdate('Y-m-d\TH:i:s\Z')
            ], $auth['localId']);
            firebaseSendVerification($auth['idToken']);
            session_unset();
            $success = 'Account created. Please check your email and click the verification link before signing in.';
        } catch (Throwable $e) {
            $errors[] = firebaseFriendlyError($e);
            session_unset(); // Avoid a signed-in session without a Firestore profile.
        }
    }
}
require_once '../includes/header.php';
?>

<!-- ===== REGISTRATION FORM ===== -->
<div class="form-page">
    <div class="form-card">

        <!-- Card Header -->
        <div class="form-card-header">
            <?php $logoColor = "black"; $logoHeight = 42; include "../includes/logo.php"; ?>
            <h1 class="form-card-title">Create Account</h1>
            <p class="form-card-sub">Join the REPENT† community today</p>
        </div>

        <!-- Show validation errors -->
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <!-- Show success message -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" action="register.php">

            <!-- Full Name -->
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname"
                       placeholder="e.g. Luthando Dlamini"
                       value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                       required>
            </div>

            <!-- Email Address -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
            </div>

            <!-- Password + Confirm side by side -->
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password"
                           placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <!-- Terms agreement note -->
            <p style="font-size:12px;color:var(--gray);margin-bottom:20px;">
                By creating an account you agree to our terms of service and privacy policy.
            </p>

            <!-- Submit button -->
            <button type="submit" class="btn btn-gold btn-full">Create My Account</button>
        </form>

        <!-- Link to login -->
        <div class="form-footer-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>

    </div><!-- end .form-card -->
</div>

<?php require_once '../includes/footer.php'; ?>
