<?php
/**
 * Raw HTTP dump endpoint - catches EVERYTHING the camera sends.
 * Place this at the root of the web server as anpr_catch.php
 * Then set camera to push to: http://192.168.1.2/anpr_catch.php
 * 
 * Access via browser: http://192.168.1.2/anpr_catch.php?show=1
 */

$logFile = __DIR__ . '/writable/logs/anpr_catch_all.log';

if (isset($_GET['show'])) {
    // Display mode
    header('Content-Type: text/plain');
    if (!file_exists($logFile)) {
        echo "No data received yet.\n";
        echo "Set camera HTTP push URL to: http://192.168.1.2/anpr_catch.php\n";
    } else {
        $sz = filesize($logFile);
        echo "File size: {$sz} bytes\n\n";
        echo file_get_contents($logFile);
    }
    exit;
}

// Capture mode - log everything
$data  = "=====================================\n";
$data .= date('Y-m-d H:i:s') . "\n";
$data .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$data .= "URI: " . $_SERVER['REQUEST_URI'] . "\n";
$data .= "Remote: " . $_SERVER['REMOTE_ADDR'] . "\n";
$data .= "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'none') . "\n";
$data .= "Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? '0') . "\n";
$data .= "Headers:\n";
foreach (getallheaders() as $k => $v) {
    $data .= "  {$k}: {$v}\n";
}
$body = file_get_contents('php://input');
$data .= "Body (" . strlen($body) . " bytes):\n";
$data .= substr($body, 0, 8000) . "\n";
$data .= "=====================================\n\n";

// Write to log
@mkdir(dirname($logFile), 0777, true);
file_put_contents($logFile, $data, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo "OK";
