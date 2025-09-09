<?php

$resticData = file_get_contents("php://input");
$resticData=json_decode($resticData,true);
if(json_last_error() !== JSON_ERROR_NONE){
    http_response_code(400);
    echo "invalid json";
    exit();
}

if(!is_array($resticData)){
    http_response_code(400);
    echo "invalid data";
    exit();
}

if(!count($resticData)){
    http_response_code(400);
    echo "no snapshots";
    exit();
}
$backups=[];
foreach($resticData as $snapshot){
    $id=$snapshot['hostname'] . "|" . implode(",",$snapshot['paths']);
    if(!isset($backups[$id])){
        $backups[$id]=0;
    }
    $backupTime=strtotime($snapshot['time']);
    if($backupTime > $backups[$id]) {
        $backups[$id] = $backupTime;
    }
}


ksort($backups);
file_put_contents(
    "/var/www/data/$fileName",
    json_encode($backups, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT)
);
