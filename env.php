<?php
$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die('.env file is missing');
}

$env = parse_ini_file($envPath);

foreach ($env as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}
?>