<?php

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
