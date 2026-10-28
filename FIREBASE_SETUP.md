# REPENT website Firebase setup (shared with Android)

The website now uses Firebase project **repent-48fda** for account registration/login and Firestore writes for orders, prayer requests, and newsletter subscriptions. The existing MySQL database and SQL export remain untouched as a rollback copy.

## Prerequisites
- PHP 8.0+ with **cURL** enabled, and HTTPS internet access.
- Firebase Console > Authentication > Sign-in method > **Email/Password enabled**.
- Firestore Database created in Firebase project **repent-48fda**.
- Firestore security rules configured to permit signed-in customers to create their own records. Use and review the rules from the current Android application project (especially `users`, `orders`, `prayer_requests`, and `newsletter_subscribers`).
- For local development, open the PHP site through XAMPP or `php -S localhost:8000`. Do not open `.php` as a local file.

## Shared Firestore structure
- `users/{firebaseUid}`: uid, fullname, email, role=customer, createdAt
- `orders/{autoId}`: userId, orderNumber, items[{productId, productName, price, quantity}], subtotal, shipping, total, shippingAddress, city, postalCode, phone, paymentMethod, status=pending, createdAt
- `prayer_requests/{autoId}`: userId, name, email, subject, prayer, isPrivate, submittedAt
- `newsletter_subscribers/{autoId}`: userId, name, email, frequency, subscribedAt

No real payment processing occurs. Orders are **requests only**, with status pending. No email delivery or stock reservation is implied.

## Verify before submission
1. Check the Azure DevOps website pipeline passes PHP syntax checks.
2. Open the website through a PHP server, register a unique test account.
3. Firebase Console > Authentication > Users: verify the new UID.
4. Firestore > users > UID: verify fullname and email.
5. Log out and log in using the same credentials on the website.
6. Log into the Android app using the same email/password. It should recognize the same Firebase user.
7. Add a product to the website cart, submit a **test order request**; verify `orders` contains a document with `userId` matching the UID and `status=pending`.
8. On Android, check My Orders for the website order (matching userId).
9. Test incorrect passwords and required-form validation. No fake success messages should appear on Firebase errors.

## Important limitations
- Website shop catalogue currently uses static product data; it is **not** dynamically synchronized to Android's Firestore products.
- Website admin dashboard currently shows sample values; do **not** claim these are live Firestore statistics.
- This repository change does not deploy Firestore rules or publish the website to a hosting provider.
- Firebase Google sign-in is supported in the Android app; the website currently supports shared email/password sign-in, not a Google sign-in button.
- Server-side Firebase requests require cURL to be enabled and the web server to reach Google APIs.

## If Firebase reports permission denied
Verify the *deployed* Firestore rules in Firebase Console. A `firestore.rules` file stored in a repository does not deploy itself. Never open the entire database to anonymous writes just to pass the demonstration.

## Rollback
The unchanged `includes/db.php` and `repent_database.sql` remain in Git history. Revert the Firebase integration commit if necessary.
