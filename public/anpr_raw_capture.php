<?php
/**
 * anpr_raw_capture.php
 * ---------------------
 * Standalone (no CI4) raw POST capture script.
 * Point the camera's HTTP push URL here temporarily to see exactly
 * what the camera sends. Access this via:
 *   http://guardiancontrol.local/anpr_raw_capture.php
 *
 * The camera push URL to set in the camera UI:
 *   http://<server-ip>/anpr_raw_capture.php
 *
 * Output is saved to: C:\Users\Administrator\Documents\GuardianControl\writable\logs\anpr_raw_YYYYMMDD.log
 */

// Allow large uploads (cameras can send ~28KB multipart with a JPEG snapshot)
ini_set('enable_post_data_reading', '0');

$logDir  = __DIR__ . '/../writable/logs/';
$logFile = $logDir . 'anpr_raw_' . date('Ymd') . '.log';

$method      = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'N/A';
$contentLen  = $_SERVER['CONTENT_LENGTH'] ?? 'N/A';
$remoteIP    = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
$uri         = $_SERVER['REQUEST_URI'] ?? 'N/A';
$timestamp   = date('Y-m-d H:i:s');

// Collect all HTTP headers
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $name = str_replace('_', '-', substr($k, 5));
        $headers[] = "$name: $v";
    }
}

// Read raw body (works because enable_post_data_reading = 0)
$rawBody = file_get_contents('php://input');
$bodyLen = strlen($rawBody);

// Sanitise body for logging: replace binary (JPEG bytes) with placeholder
$printableBody = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '.', $rawBody);

$separator = str_repeat('=', 80);
$entry = <<<LOG
$separator
[$timestamp]  IP: $remoteIP  METHOD: $method  URI: $uri
Content-Type: $contentType
Content-Length server: $contentLen  | Read: $bodyLen bytes

--- HEADERS ---
{$separator}
LOG;

foreach ($headers as $h) {
    $entry .= "\n$h";
}

$entry .= "\n\n--- BODY (binary replaced with '.') ---\n{$separator}\n";
$entry .= $printableBody;
$entry .= "\n\n--- HEX DUMP (first 2048 bytes) ---\n{$separator}\n";

// Hex dump of first 2048 bytes
$chunk = substr($rawBody, 0, 2048);
for ($i = 0; $i < strlen($chunk); $i += 16) {
    $slice   = substr($chunk, $i, 16);
    $hex     = implode(' ', array_map('bin2hex', str_split($slice)));
    $ascii   = preg_replace('/[^\x20-\x7E]/', '.', $slice);
    $entry  .= sprintf("%04x  %-47s  %s\n", $i, $hex, $ascii);
}

$entry .= "\n\n";

// Append to log file
file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

// Respond 200 so the camera is happy
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';
