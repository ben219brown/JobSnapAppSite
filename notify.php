<?php
// notify.php
// 1) Always output JSON
header('Content-Type: application/json');

// 2) Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ─── Rate Limiting ─────────────────────────────────────────────────
// Allow max 5 requests per 60 seconds per IP
$maxRequests = 5;
$period      = 60;  // seconds
$logFile     = __DIR__ . '/notify_rate.log';
$ip          = $_SERVER['REMOTE_ADDR'];
$now         = time();

// Read previous entries
$entries = file_exists($logFile)
    ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
    : [];

$valid = [];
$count = 0;
foreach ($entries as $line) {
    list($ts, $entryIp) = explode(',', $line);
    if ($ts > $now - $period) {
        $valid[] = $line;
        if ($entryIp === $ip) {
            $count++;
        }
    }
}

// If over limit, return 429 with Retry-After
if ($count >= $maxRequests) {
    $oldest = min(array_column(array_map(function($l){
        return explode(',', $l)[0];
    }, $valid), 0));
    $retryAfter = $period - ($now - $oldest);
    header('Retry-After: ' . $retryAfter);
    http_response_code(429);
    echo json_encode(['error' => "Rate limit exceeded. Try again in {$retryAfter} seconds."]);
    exit;
}

// Log this request
$valid[] = "{$now},{$ip}";
file_put_contents($logFile, implode("\n", $valid) . "\n", LOCK_EX);


// ─── Email Validation ───────────────────────────────────────────────
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}


// ─── Duplication Check ───────────────────────────────────────────────
// Read existing emails (lowercased + trimmed)
$file = __DIR__ . '/emails.txt';
$existing = [];
if (file_exists($file)) {
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $existing[] = strtolower(trim($line));
    }
}
$key = strtolower($email);
if (in_array($key, $existing, true)) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already on the list']);
    exit;
}


// ─── Append New Email ────────────────────────────────────────────────
if (file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save email']);
    exit;
}


// ─── Success Response ────────────────────────────────────────────────
http_response_code(200);
echo json_encode(['success' => true]);
exit;
