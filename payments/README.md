# REPENT PayFast sandbox (website and Android)

This module is a **sandbox-only** proof of concept. It is not deployed or payment-tested by this commit.

## Deploy shared Firebase Function
1. Create your own PayFast sandbox account at https://sandbox.payfast.co.za/ .
2. Run from repository root:
   npm --prefix payments/functions install
   firebase login
   firebase use repent-48fda
   firebase functions:secrets:set PAYFAST_MERCHANT_ID
   firebase functions:secrets:set PAYFAST_MERCHANT_KEY
   firebase functions:secrets:set PAYFAST_PASSPHRASE
3. Create payments/functions/.env.repent-48fda (NEVER commit):
   PAYMENT_PUBLIC_URL=https://europe-west1-repent-48fda.cloudfunctions.net/repentPayfast
4. Deploy from repo root:
   firebase deploy --project repent-48fda --only functions:repentPayfast
5. On PHP hosting/XAMPP set environment variable:
   REPENT_PAYMENT_API_URL=https://europe-west1-repent-48fda.cloudfunctions.net/repentPayfast
   Restart Apache after changing environment configuration.
6. In Android app/build.gradle.kts check PAYFAST_API_BASE_URL matches the deployed HTTPS URL; rebuild APK.
7. Log in using a verified Firebase test account. Choose Card in either client to open PayFast sandbox.

## What the gateway does
- POST /create: authorises Firebase ID token, recalculates cart total from Firestore products, creates pending order and 15-minute checkout session.
- GET /pay/:token: generates signed hosted checkout form for PayFast sandbox.
- POST /notify: verifies PayFast IP, signature, merchant, amount, COMPLETE status and provider validation endpoint; uses idempotent Firestore transaction to mark paymentStatus=paid.
- GET /status/:orderId: authenticated customer can read only their own order's paymentStatus.
- GET /return and /cancel: informational pages only; NEVER mark payment paid based on return URL.

## Required Firestore rules
- Users must NOT be allowed to create/update paymentStatus, paidAt, payfastPaymentId.
- Deny all client access to payment_sessions and payfast_transactions.
- Keep customers able to read only their own orders; only trusted backend Admin SDK writes payment fields.
- Existing legacy EFT order creation can still use the old allowed-field schema.

## Important limitations
- Current website catalog contains static prices and products; card payment validation uses actual Firestore products. If they diverge, checkout may show a different total. Sync the website cart to Firestore before production.
- No stock reservation or fulfilment automation. Payment success alone does not approve shipping.
- Do not use production merchant credentials or actual bank cards in this sandbox.
- Cloud Functions requires a Firebase billing plan and publicly reachable HTTPS callback URL.
- Cloud reverse-proxy source IP forwarding must be checked against PayFast's actual ITN source. This implementation deliberately fails closed (403) on mismatch.
- Test paid, cancelled, invalid amount, forged signature, duplicate webhook and expired checkout. Check PayFast sandbox ITN logs.
- Secrets must be configured using Secret Manager; do not commit them or paste them into support chats.
- This code has not yet completed CI, deployed sandbox validation, or real payment testing.

See https://developers.payfast.co.za/documentation/ and https://support.payfast.help/ for PayFast integration requirements.
