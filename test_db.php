<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get Heroku ClearDB connection information
$cleardb_url = parse_url(getenv('CLEARDB_DATABASE_URL'));
if (!$cleardb_url) {
    die("CLEARDB_DATABASE_URL environment variable not set or empty");
}

echo "Host: " . $cleardb_url['host'] . "<br>";
echo "User: " . $cleardb_url['user'] . "<br>";
echo "Pass: " . $cleardb_url['pass'] . "<br>";
echo "Path: " . substr($cleardb_url['path'], 1) . "<br>";
?>