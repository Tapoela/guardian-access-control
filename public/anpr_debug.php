<?php
/**
 * ANPR Debug Capture
 * Temporarily place this at /anpr_debug.php to see exactly what the camera sends.
 * Remove after debugging.
 */

$logFile = __DIR__ . '/../writable/logs/anpr_raw_capture.log';

$timestamp   = date('Y-m-d H:i:s');
$method      = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$contentType = $_SERVER['CONTENT_TYPE']   ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? 'none';
$bodyLen     = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

// Try reading raw body — works when PHP doesn't consume the stream
$rawBody = '';
// Method 1: php://input
$rawBody = (string)@file_get_contents('php://input');
// Method 2: if empty, try reading directly (CGI/FastCGI mode)
if (strlen($rawBody) === 0 && $bodyLen > 0) {
    $rawBody = (string)@fread(fopen('php://stdin', 'rb'), $bodyLen);
}

// Collect all headers
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_') || in_array($k, ['CONTENT_TYPE','CONTENT_LENGTH','REQUEST_METHOD','REQUEST_URI'])) {
        $headers[$k] = $v;
    }
}

$entry  = "=== {$timestamp} ===\n";
$entry .= "Method:       {$method}\n";
$entry .= "Content-Type: {$contentType}\n";
$entry .= "Content-Length header: {$bodyLen}\n";
$entry .= "php://input length: " . strlen($rawBody) . " bytes\n";
$entry .= "Headers:\n";
foreach ($headers as $k => $v) {
    $entry .= "  {$k}: {$v}\n";
}

// $_POST fields
$entry .= "\n_POST keys: " . (count($_POST) ? implode(', ', array_keys($_POST)) : '(none)') . "\n";
foreach ($_POST as $k => $v) {
    $entry .= "  POST[{$k}]:\n" . substr((string)$v, 0, 3000) . "\n";
}

// $_FILES
$entry .= "_FILES keys: " . (count($_FILES) ? implode(', ', array_keys($_FILES)) : '(none)') . "\n";
foreach ($_FILES as $k => $info) {
    $entry .= "  FILE[{$k}]: name={$info['name']} size={$info['size']}\n";
    if (!empty($info['tmp_name']) && file_exists($info['tmp_name'])) {
        $fc = file_get_contents($info['tmp_name']);
        $entry .= "  Content (first 2000):\n" . substr($fc, 0, 2000) . "\n";
    }
}

// Raw body
if (strlen($rawBody) > 0) {
    $entry .= "\nRaw body (first 5000):\n" . substr($rawBody, 0, 5000) . "\n";
} else {
    $entry .= "\n[RAW BODY IS EMPTY — PHP consumed multipart or boundary malformed]\n";
    // Try to manually parse the malformed multipart
    // The camera sends boundary=--boundary, so actual delimiter is ----boundary
    // Let's try to get the body via a workaround: check all input streams
    $entry .= "Attempting workaround parse...\n";
}

$entry .= str_repeat('-', 80) . "\n\n";

// Collect all headers
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_') || in_array($k, ['CONTENT_TYPE','CONTENT_LENGTH','REQUEST_METHOD','REQUEST_URI'])) {
        $headers[$k] = $v;
    }
}

$entry  = "=== {$timestamp} ===\n";
$entry .= "Method:       {$method}\n";
$entry .= "Content-Type: {$contentType}\n";
$entry .= "php://input length: " . strlen($rawBody) . " bytes\n";
$entry .= "Headers:\n";
foreach ($headers as $k => $v) {
    $entry .= "  {$k}: {$v}\n";
}

// $_POST fields (XML is often in a named part)
$entry .= "\n_POST keys: " . implode(', ', array_keys($_POST)) . "\n";
foreach ($_POST as $k => $v) {
    $entry .= "  POST[{$k}] (first 3000):\n" . substr($v, 0, 3000) . "\n";
}

// $_FILES
$entry .= "\n_FILES keys: " . implode(', ', array_keys($_FILES)) . "\n";
foreach ($_FILES as $k => $info) {
    $entry .= "  FILE[{$k}]: name={$info['name']} size={$info['size']} tmp={$info['tmp_name']}\n";
    if ($info['tmp_name'] && file_exists($info['tmp_name'])) {
        $content = file_get_contents($info['tmp_name']);
        // Check if it looks like XML
        if (str_contains($content, '<') ) {
            $entry .= "  Content (first 3000):\n" . substr($content, 0, 3000) . "\n";
        } else {
            $entry .= "  Content: [binary, " . strlen($content) . " bytes]\n";
        }
    }
}

// Raw body (in case Content-Type was overridden or it's not true multipart)
if (strlen($rawBody) > 0) {
    $entry .= "\nRaw php://input (first 4000):\n" . substr($rawBody, 0, 4000) . "\n";
}

$entry .= str_repeat('-', 80) . "\n\n";

file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo 'OK';
