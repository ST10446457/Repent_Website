<?php
/**
 * Firebase REST integration for REPENT web. Uses the SAME Firebase project,
 * Firebase Authentication accounts and Firestore collections as Android.
 * No service-account key is stored here. Firestore rules remain authoritative.
 */
const REPENT_FIREBASE_PROJECT = 'repent-48fda';
const REPENT_FIREBASE_API_KEY = 'AIzaSyAF7x0VNB7HoFdrl03V3iCkmhI67CfL0AI';

function firebaseRequest(string $url, string $method = 'POST', ?array $payload = null, ?string $token = null): array {
    if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('Firebase network error: ' . $error);
    $data = json_decode($body, true) ?: [];
    if ($status < 200 || $status >= 300) {
        $detail = $data['error']['message'] ?? 'Firebase request failed';
        throw new RuntimeException($detail);
    }
    return $data;
}

function firebaseAuth(string $action, string $email, string $password): array {
    $endpoint = $action === 'register' ? 'signUp' : 'signInWithPassword';
    return firebaseRequest('https://identitytoolkit.googleapis.com/v1/accounts:' . $endpoint . '?key=' . REPENT_FIREBASE_API_KEY,
        'POST', ['email' => $email, 'password' => $password, 'returnSecureToken' => true]);
}

function firebaseSession(array $auth, string $name = ''): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $auth['localId'];
    $_SESSION['user_email'] = $auth['email'] ?? '';
    $_SESSION['user_name'] = $name ?: ($_SESSION['user_email'] ?? 'Customer');
    $_SESSION['role'] = 'customer'; // Never derive admin access from untrusted client input.
    $_SESSION['firebase_id_token'] = $auth['idToken'];
    $_SESSION['firebase_refresh_token'] = $auth['refreshToken'];
    $_SESSION['firebase_expires_at'] = time() + (int)($auth['expiresIn'] ?? 3600) - 90;
}

function firebaseToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['firebase_id_token'])) throw new RuntimeException('Please sign in again.');
    if (time() < (int)($_SESSION['firebase_expires_at'] ?? 0)) return $_SESSION['firebase_id_token'];
    $response = firebaseRequest('https://securetoken.googleapis.com/v1/token?key=' . REPENT_FIREBASE_API_KEY,
        'POST', ['grant_type' => 'refresh_token', 'refresh_token' => $_SESSION['firebase_refresh_token']]);
    $_SESSION['firebase_id_token'] = $response['id_token'];
    $_SESSION['firebase_refresh_token'] = $response['refresh_token'];
    $_SESSION['firebase_expires_at'] = time() + (int)$response['expires_in'] - 90;
    return $_SESSION['firebase_id_token'];
}

function firestoreValue($value): array {
    if ($value === null) return ['nullValue' => null];
    if (is_bool($value)) return ['booleanValue' => $value];
    if (is_int($value)) return ['integerValue' => (string)$value];
    if (is_float($value)) return ['doubleValue' => $value];
    if (is_array($value)) {
        if (array_is_list($value)) return ['arrayValue' => ['values' => array_map('firestoreValue', $value)]];
        return ['mapValue' => ['fields' => firestoreFields($value)]];
    }
    return ['stringValue' => (string)$value];
}
function firestoreFields(array $values): array {
    $fields = [];
    foreach ($values as $key => $value) $fields[$key] = firestoreValue($value);
    return $fields;
}
function firestoreWrite(string $collection, array $data, ?string $documentId = null): array {
    $path = 'https://firestore.googleapis.com/v1/projects/' . REPENT_FIREBASE_PROJECT . '/databases/(default)/documents/' . rawurlencode($collection);
    $method = 'POST';
    if ($documentId !== null) {
        $path .= '/' . rawurlencode($documentId);
        $method = 'PATCH';
    }
    return firebaseRequest($path, $method, ['fields' => firestoreFields($data)], firebaseToken());
}
function firebaseFriendlyError(Throwable $error): string {
    $code = $error->getMessage();
    if (str_contains($code, 'EMAIL_EXISTS')) return 'This email already has an account.';
    if (str_contains($code, 'INVALID_LOGIN_CREDENTIALS') || str_contains($code, 'INVALID_PASSWORD')) return 'Incorrect email or password.';
    if (str_contains($code, 'PERMISSION_DENIED')) return 'Firebase permission denied. Check Firestore rules.';
    return 'Firebase error: ' . $code;
}
