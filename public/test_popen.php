<?php
header('Content-Type: text/plain');
$botPath1 = "D:\\laragon\\www\\gas/rpa-bot";
$botPath2 = "D:\\laragon\\www\\gas\\rpa-bot";

$cmd1 = "start cmd /k \"cd /d {$botPath1} && node -v\"";
$cmd2 = "start cmd /k \"cd /d {$botPath2} && node -v\"";

echo "Running cmd1: " . $cmd1 . "\n";
$res1 = popen($cmd1, "r");
if (is_resource($res1)) {
    pclose($res1);
    echo "cmd1 started successfully.\n";
} else {
    echo "Failed to start cmd1.\n";
}

echo "Running cmd2: " . $cmd2 . "\n";
$res2 = popen($cmd2, "r");
if (is_resource($res2)) {
    pclose($res2);
    echo "cmd2 started successfully.\n";
} else {
    echo "Failed to start cmd2.\n";
}
