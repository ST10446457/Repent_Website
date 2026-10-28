<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';

$checks = 0;

function check(bool $condition, string $description): void {
    global $checks;
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$description}\n");
        exit(1);
    }
    $checks++;
    echo "PASS: {$description}\n";
}

unset($_SESSION['user_id'], $_SESSION['role']);
check(!isLoggedIn(), 'Guest is not logged in');
check(!isAdmin(), 'Guest is not an administrator');

$_SESSION['user_id'] = 42;
$_SESSION['role'] = 'customer';
check(isLoggedIn(), 'Customer is logged in');
check(!isAdmin(), 'Customer does not have administrator permissions');

$_SESSION['role'] = 'admin';
check(isAdmin(), 'Administrator role is recognized');

echo "All {$checks} authentication checks passed.\n";
