# REPENT† — Website

Faith-based apparel storefront and customer platform, built with PHP, HTML, CSS, JavaScript and **Firebase Authentication / Cloud Firestore**. Uses the same Firebase project as the Android app: `repent-48fda`.

## Current capabilities
- Browse apparel, cart, shipping and checkout screens.
- Register / sign in via Firebase email/password; email verification required.
- Create customer order requests and access the shared Firestore project.
- Submit prayer requests and newsletter subscriptions.
- **PayFast hosted sandbox checkout (review branch):** card selection calls a shared Firebase HTTPS payment function. No payment is confirmed just because the customer returns to the website.
- Existing `pages/admin.php` still shows **sample dashboard values**, not live Firebase order statistics. Do not use it to approve actual fulfilment.

## Local website setup
1. Install PHP 8+ with cURL, or XAMPP (start Apache).
2. Clone or extract this repository into `C:\xampp\htdocs\Repent_Website`.
3. Enable Email/Password sign-in in Firebase project `repent-48fda`; deploy appropriate Firestore rules.
4. Visit `http://localhost/Repent_Website/`. MySQL is **not required** for the Firebase customer authentication/checkout flow.
5. Use a verified Firebase email/password test account.

## PayFast sandbox (NOT deployed)
The **source code** for the shared backend is in `payments/functions/index.js` and `payments/README.md`.
Both website and Android should use the *same* deployed Firebase HTTPS function.

**Required:** Firebase project with Cloud Functions billing, Firebase CLI, PayFast sandbox account, HTTPS function URL, trusted Firestore product prices, and verified Firebase accounts.

```bash
npm --prefix payments/functions install
firebase login
firebase functions:secrets:set PAYFAST_MERCHANT_ID --project repent-48fda
firebase functions:secrets:set PAYFAST_MERCHANT_KEY --project repent-48fda
firebase functions:secrets:set PAYFAST_PASSPHRASE --project repent-48fda
```

Create an **untracked** `payments/functions/.env.repent-48fda` file with the expected public HTTPS function URL, such as:

```dotenv
PAYMENT_PUBLIC_URL=https://europe-west1-repent-48fda.cloudfunctions.net/repentPayfast
```

Deploy only after reviewing and testing security rules, HTTPS proxy handling and the PayFast ITN validation:

```bash
firebase deploy --only functions:repentPayfast --project repent-48fda
```

Set the website server environment variable `REPENT_PAYMENT_API_URL` to the deployed HTTPS function URL, and restart the web server. **Never add merchant secrets to PHP pages, Android code or GitHub.**

### Checkout behaviour
- **Card**: creates a pending Firestore order using trusted Firestore catalogue prices, then opens PayFast's sandbox-hosted checkout. Only a validated PayFast ITN can set `paymentStatus=paid`.
- **EFT**: legacy request-only workflow: creates a pending order; does not process an electronic payment.
- Returning from the payment browser does **not** mean payment succeeded.
- The website catalogue still contains static content; reconcile catalogue IDs/prices with Firestore before processing orders.

## Firestore order schema
`orders/{id}`: `userId`, `orderNumber`, `items`, `subtotal`, `shipping`, `total`, `shippingAddress`, `city`, `postalCode`, `phone`, `paymentMethod`, `status`, `createdAt`; backend-created card orders additionally have `paymentStatus`, `currency`, and later `payfastPaymentId` / `paidAt` only after verification.

Customers must never be allowed to set or modify server-only payment fields. Deny client access to `payment_sessions` and `payfast_transactions`. Firestore Admin SDK on the deployed backend is responsible for verified payment updates.

## Tests and status
- Website Azure pipeline checks PHP syntax and selected auth smoke tests; this is **not** live payment verification.
- PayFast sandbox backend has **not** been deployed and there is **no verified test transaction**.
- Test paid, cancelled, failed, duplicate, tampered-amount and invalid-source/signature notifications before enabling payment processing.
- Android integration is documented in [the companion repository](https://github.com/ST10446457/Repent_MobileApplication).

## Project files
- `pages/checkout.php` — PHP checkout
- `includes/firebase.php` — Firebase REST helpers
- `payments/functions/index.js` — secure shared server endpoint (sandbox)
- `payments/README.md` — setup, limitations and operational checklist
- `FIREBASE_SETUP.md` — legacy Firebase migration notes
- `repent_database.sql` and `includes/db.php` — legacy MySQL fallback/reference; not primary Firebase authentication

**Security:** never commit merchant secrets, Firebase service-account credentials or real card data. This is a development prototype and not yet ready to charge customers.
