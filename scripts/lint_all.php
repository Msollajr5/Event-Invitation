<?php
$files = array_merge(
    glob(__DIR__ . '/../*.php'),
    glob(__DIR__ . '/../api/*.php'),
    glob(__DIR__ . '/../config/*.php'),
    glob(__DIR__ . '/../includes/*.php'),
    glob(__DIR__ . '/*.php')
);

$errors = 0;
foreach ($files as $file) {
    $out = [];
    $ret = 0;
    exec('C:\\xampp\\php\\php.exe -l ' . escapeshellarg($file), $out, $ret);
    if ($ret !== 0) {
        echo 'ERROR in ' . basename($file) . ': ' . implode(' ', $out) . "\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "✓ ALL " . count($files) . " PHP FILES LINTED CLEANLY WITH ZERO SYNTAX ERRORS!\n";
} else {
    echo "Found {$errors} lint error(s).\n";
}
