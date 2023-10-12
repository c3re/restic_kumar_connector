<?php

function checkAuth($user, $password, $credentials, $mode)
{
    if ($mode === "NO_AUTH") {
        return true;
    }
    if (!isset($credentials[$user])) {
        return false;
    }
    return $credentials[$user] === $password;
}

$credentials = [];
$mode = "NO_AUTH";
if (getenv("RKC_USER") !== false) {
    $mode = "SINGLE_AUTH";
    $credentials[getenv("RKC_USER")] = getenv("RKC_PASS");
}
foreach (getenv() as $k => $v) {
    if (preg_match("/^RKC_USER_(.*)$/", $k, $m)) {
        if ($mode === "SINGLE_AUTH") {
            header("HTTP/1.0 500 Internal Server Error");
            echo "single auth and multi auth is used at the same time";
            exit();
        }
        $mode = "MULTI_AUTH";
        $u = $m[1];
        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $k)) {
            header("HTTP/1.0 500 Internal Server Error");
            echo "invalid username, only a-zA-Z0-9 and _- are allowed";
            exit();
        }
        $credentials[strtolower($u)] = $v;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        checkAuth(
            strtolower($_SERVER["PHP_AUTH_USER"] ?? null),
            $_SERVER["PHP_AUTH_PW"] ?? null,
            $credentials,
            $mode
        ) === false
    ) {
        header('WWW-Authenticate: Basic realm="RKC"');
        header("HTTP/1.0 401 Unauthorized");
        echo "You are not authorized to access this page.";
        exit();
    }
    $fileName = $_SERVER["PHP_AUTH_USER"] . ".json";

    $resticData = file_get_contents("php://input");
    $resticData = explode("\n", $resticData);
    if (count($resticData) <= 1) {
        exit();
    }
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
        "/var/www/data/$fileName",
        json_encode($backups, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT)
    );
    exit();
}
if ($mode === "NO_AUTH") {
    $fileName = "backups.json";
} elseif (!isset($_GET["user"])) {
    header("HTTP/1.0 404 Not Found"); ?>
    <form method="get">
        no user specified
    <input type="text" name="user" placeholder="username">
    <input type="submit">
    </form>
    <?php exit();
} else {
    $fileName = strtolower($_GET["user"]);
    if (!isset($credentials[$fileName])) {
        header("HTTP/1.0 404 Not Found");
        echo "user not found";
        exit();
    }
    $fileName .= ".json";
}

header("Content-Type: text/plain");
$maxAge = isset($_GET["maxage"]) ? intval($_GET["maxage"]) : 28;
$maxAge = $maxAge * 60 * 60;
$backups = json_decode(file_get_contents("/var/www/data/$fileName"), true);
echo "BACKUP|HOST|PATH|STATUS\n";
foreach ($backups as $backupName => $backupTime) {
    echo "BACKUP|$backupName|";
    if ($backupTime + $maxAge < time()) {
        echo "TOO_OLD";
    } else {
        echo "OK";
    }
    echo "\n";
}

$byHost = [];
foreach ($backups as $backupName => $backupTime) {
    $backupName = explode("|", $backupName);
    $host = $backupName[0];
    $path = $backupName[1];
    if (!isset($byHost[$host])) {
        $byHost[$host] = PHP_INT_MAX;
    }
    $byHost[$host] = min($backupTime, $byHost[$host]);
}

echo "\nBACKUP|HOST|STATUS\n";
foreach ($byHost as $host => $latest) {
    echo "BACKUP|$host|";
    if ($latest + $maxAge < time()) {
        echo "TOO_OLD";
    } else {
        echo "OK";
    }
    echo "\n";
}
