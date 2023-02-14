<?php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = getenv("RKC_USER");
    $pass = getenv("RKC_PASS");
    if (false !== $user or false !== $pass) {
        if (
            !isset($_SERVER["PHP_AUTH_USER"]) ||
            !isset($_SERVER["PHP_AUTH_PW"]) ||
            $_SERVER["PHP_AUTH_USER"] !== $user ||
            $_SERVER["PHP_AUTH_PW"] !== $pass
        ) {
            header('WWW-Authenticate: Basic realm="RKC"');
            header("HTTP/1.0 401 Unauthorized");
            echo "You are not authorized to access this page.";
            exit();
        }
    }

    $resticData = file_get_contents("php://input");
    $resticData = explode("\n", $resticData);
    $backups = [];
    foreach ($resticData as $snapshot) {
        if (
            !preg_match(
                "/^(?<id>[a-z0-9]{8}) +(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2}) +(?<hour>\d{2}):+(?<minute>\d{2}):+(?<second>\d{2}) +(?<host>[^ ]+) +(?<path>[^ ]+).*/",
                $snapshot,
                $m
            )
        ) {
            continue;
        }
        $backupName = $m["host"] . "|" . $m["path"];
        if (!isset($backups[$backupName])) {
            $backups[$backupName] = 0;
        }
        $backupTime = mktime(
            $m["hour"],
            $m["minute"],
            $m["second"],
            $m["month"],
            $m["day"],
            $m["year"]
        );
        if ($backupTime > $backups[$backupName]) {
            $backups[$backupName] = $backupTime;
        }
    }
    ksort($backups);
    file_put_contents(
        "/var/www/data/backups.json",
        json_encode($backups, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT)
    );
    exit();
}
header("Content-Type: text/plain");
$maxAge = isset($_GET["maxage"]) ? intval($_GET["maxage"]) : 28;
$maxAge = $maxAge * 60 * 60;
$backups = json_decode(file_get_contents("/var/www/data/backups.json"), true);
foreach ($backups as $backupName => $backupTime) {
    echo "BACKUP|$backupName|";
    if ($backupTime + $maxAge < time()) {
        echo "TOO_OLD";
    } else {
        echo "OK";
    }
    echo "\n";
}
