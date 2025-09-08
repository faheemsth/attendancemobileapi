<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

// Configuration
$secret = 'VGNHKI*&^%$455tGFR!@#az_mobile_api_live_!@@';
$project_path = '/home/convosoftserver/public_html/api.convosoftserver.com';
$branch = 'live';
$log_file = 'live-deploy.log';

// Get the GitHub event type
$github_event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

// Log the initial request
file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Event: $github_event\n", FILE_APPEND);

// 1. Handle ping event (GitHub's test)
if ($github_event === 'ping') {
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Ping received\n", FILE_APPEND);
    die("Ping received successfully");
}

// 2. Only process push events
if ($github_event !== 'push') {
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Ignored event: $github_event\n", FILE_APPEND);
    die("Only push events are handled");
}

// 3. Verify the payload signature (SHA-256)
$hub_signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if (empty($hub_signature)) {
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Missing signature\n", FILE_APPEND);
    die("Missing signature");
}

list($algo, $hash) = explode('=', $hub_signature, 2);
$payload_hash = hash_hmac($algo, $payload, $secret);

if (!hash_equals($hash, $payload_hash)) {
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " Invalid secret\n", FILE_APPEND);
    die("Invalid secret");
}

// 4. Execute Git commands
$commands = [
    "cd $project_path",
    "git fetch origin",
    "git reset --hard origin/$branch",
    "git pull origin $branch 2>&1",
    "git submodule update --init --recursive"
];

$output = [];
foreach ($commands as $cmd) {
    exec($cmd, $cmd_output, $return_code);
    $output[] = "$ $cmd (Code: $return_code)\n" . implode("\n", $cmd_output);
    if ($return_code !== 0) break;
}

// 5. Log results
$log_entry = date('[Y-m-d H:i:s]') . " Git output:\n" . implode("\n\n", $output) . "\n\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);



echo "Deployment completed successfully";
?>
