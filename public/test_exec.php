<?php
header('Content-Type: text/plain');
echo "Current OS User: " . get_current_user() . "\n";
echo "PATH Env:\n" . getenv('PATH') . "\n\n";

$nodePath = shell_exec('where node 2>&1');
echo "where node:\n" . $nodePath . "\n";

$nodeVersion = shell_exec('node -v 2>&1');
echo "node -v:\n" . $nodeVersion . "\n";
